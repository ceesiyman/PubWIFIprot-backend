<?php

namespace App\Services;

use App\Models\User;
use App\Models\VpnSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;

class VpnService
{
    private const ENCRYPTION_CIPHER = 'aes-256-gcm';
    private const KEY_SIZE = 32;
    private const WG_CONFIG_PATH = '/etc/wireguard/';
    private const WG_KEY_PATH = '/var/www/wireguard/';
    private const WG_CLIENT_CONFIG_PATH = 'wireguard/clients/';

    protected $maxConnections;
    protected $loggingEnabled;
    protected $loggingChannel;
    protected $serverPublicKey;
    protected $serverPrivateKey;

    public function __construct()
    {
        $this->maxConnections = (int) config('vpn.max_connections', 1);
        $this->loggingEnabled = (bool) config('vpn.logging_enabled', true);
        $this->loggingChannel = config('vpn.logging_channel', 'daily');
        
        // Load server keys from the new location
        $this->serverPrivateKey = trim(file_get_contents(self::WG_KEY_PATH . 'server_private.key'));
        $this->serverPublicKey = trim(file_get_contents(self::WG_KEY_PATH . 'server_public.key'));
    }

    /**
     * Create a new VPN session for a user
     *
     * @param User $user
     * @param string $clientIp
     * @return VpnSession
     * @throws \Exception
     */
    public function createSession(User $user, string $clientIp): VpnSession
    {
        // Check if user has reached max connections
        $activeSessions = VpnSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        if ($activeSessions >= $this->maxConnections) {
            throw new \Exception('Maximum number of VPN connections reached');
        }

        // Generate client keys
        $clientPrivateKey = trim(shell_exec('wg genkey'));
        $clientPublicKey = trim(shell_exec("echo '$clientPrivateKey' | wg pubkey"));

        // Generate client IP (increment from 10.0.0.2)
        $lastClientIp = VpnSession::where('status', 'active')
            ->orderBy('client_ip', 'desc')
            ->value('client_ip');
        
        $clientIp = $lastClientIp ? $this->incrementIp($lastClientIp) : '10.0.0.2';

        // Create client configuration
        $clientConfig = $this->generateClientConfig($clientPrivateKey, $clientPublicKey, $clientIp);
        
        // Save client configuration
        $configPath = self::WG_CLIENT_CONFIG_PATH . $user->id . '.conf';
        Storage::put($configPath, $clientConfig);

        // Add client to server configuration
        $this->addClientToServer($clientPublicKey, $clientIp);

        // Create new session
        $session = new VpnSession([
            'user_id' => $user->id,
            'status' => 'active',
            'client_ip' => $clientIp,
            'server_address' => config('vpn.server_address', 'vpn.pubwifi.com'),
            'server_port' => config('vpn.port', 51820),
            'client_public_key' => $clientPublicKey,
            'client_private_key' => encrypt($clientPrivateKey),
            'bytes_sent' => 0,
            'bytes_received' => 0,
        ]);

        $session->save();

        if ($this->loggingEnabled) {
            Log::channel($this->loggingChannel)->info('VPN session created', [
                'user_id' => $user->id,
                'client_ip' => $clientIp,
                'session_id' => $session->id
            ]);
        }

        return $session;
    }

    /**
     * Disconnect a VPN session
     *
     * @param VpnSession $session
     * @return void
     */
    public function disconnectSession(VpnSession $session): void
    {
        // Remove client from server configuration
        $this->removeClientFromServer($session->client_public_key);

        // Delete client configuration
        Storage::delete(self::WG_CLIENT_CONFIG_PATH . $session->user_id . '.conf');

        $session->update([
            'status' => 'disconnected',
            'disconnected_at' => now(),
        ]);

        if ($this->loggingEnabled) {
            Log::channel($this->loggingChannel)->info('VPN session disconnected', [
                'user_id' => $session->user_id,
                'session_id' => $session->id,
                'bytes_sent' => $session->bytes_sent,
                'bytes_received' => $session->bytes_received,
            ]);
        }
    }

    /**
     * Update session statistics
     *
     * @param VpnSession $session
     * @param int $bytesSent
     * @param int $bytesReceived
     * @return void
     */
    public function updateSessionStats(VpnSession $session, int $bytesSent, int $bytesReceived): void
    {
        $session->update([
            'bytes_sent' => $bytesSent,
            'bytes_received' => $bytesReceived,
        ]);

        if ($this->loggingEnabled) {
            Log::channel($this->loggingChannel)->info('VPN session stats updated', [
                'user_id' => $session->user_id,
                'session_id' => $session->id,
                'bytes_sent' => $bytesSent,
                'bytes_received' => $bytesReceived,
            ]);
        }
    }

    /**
     * Generate client configuration
     *
     * @param string $clientPrivateKey
     * @param string $clientPublicKey
     * @param string $clientIp
     * @return string
     */
    private function generateClientConfig(string $clientPrivateKey, string $clientPublicKey, string $clientIp): string
    {
        $config = "[Interface]\n";
        $config .= "PrivateKey = $clientPrivateKey\n";
        $config .= "Address = $clientIp/24\n";
        $config .= "DNS = 1.1.1.1, 1.0.0.1\n\n";
        $config .= "[Peer]\n";
        $config .= "PublicKey = {$this->serverPublicKey}\n";
        $config .= "Endpoint = " . config('vpn.server_address', 'vpn.pubwifi.com') . ':' . config('vpn.port', 51820) . "\n";
        $config .= "AllowedIPs = 0.0.0.0/0\n";
        $config .= "PersistentKeepalive = 25\n";

        return $config;
    }

    /**
     * Add client to server configuration
     *
     * @param string $clientPublicKey
     * @param string $clientIp
     * @return void
     */
    private function addClientToServer(string $clientPublicKey, string $clientIp): void
    {
        try {
            // Create the peer configuration
            $config = [
                'action' => 'add',
                'public_key' => $clientPublicKey,
                'client_ip' => $clientIp
            ];

            // Validate the configuration
            if (empty($clientPublicKey) || empty($clientIp)) {
                throw new \Exception('Invalid configuration: public_key and client_ip are required');
            }

            // Log the configuration for debugging
            Log::debug('WireGuard configuration', [
                'config' => $config,
                'json' => json_encode($config)
            ]);

            // Use the WireGuard management script directly
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];

            $process = proc_open('/usr/local/bin/wg-manage', $descriptorspec, $pipes);
            
            if (!is_resource($process)) {
                throw new \Exception('Failed to execute WireGuard management script');
            }

            // Write the configuration to stdin
            $jsonConfig = json_encode($config);
            if ($jsonConfig === false) {
                throw new \Exception('Failed to encode configuration: ' . json_last_error_msg());
            }

            fwrite($pipes[0], $jsonConfig);
            fclose($pipes[0]);

            // Read output
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            // Log the script output for debugging
            Log::debug('WireGuard script output', [
                'returnCode' => $returnCode,
                'output' => $output,
                'errors' => $errors
            ]);

            if ($returnCode !== 0) {
                throw new \Exception('WireGuard script failed: ' . ($errors ?: $output));
            }
        } catch (\Exception $e) {
            Log::error('WireGuard configuration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Remove client from server configuration
     *
     * @param string $clientPublicKey
     * @return void
     */
    private function removeClientFromServer(string $clientPublicKey): void
    {
        try {
            // Create the operation configuration
            $config = [
                'action' => 'remove',
                'public_key' => $clientPublicKey
            ];

            // Validate the configuration
            if (empty($clientPublicKey)) {
                throw new \Exception('Invalid configuration: public_key is required');
            }

            // Log the configuration for debugging
            Log::debug('WireGuard configuration', [
                'config' => $config,
                'json' => json_encode($config)
            ]);

            // Use the WireGuard management script directly
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ];

            $process = proc_open('/usr/local/bin/wg-manage', $descriptorspec, $pipes);
            
            if (!is_resource($process)) {
                throw new \Exception('Failed to execute WireGuard management script');
            }

            // Write the configuration to stdin
            $jsonConfig = json_encode($config);
            if ($jsonConfig === false) {
                throw new \Exception('Failed to encode configuration: ' . json_last_error_msg());
            }

            fwrite($pipes[0], $jsonConfig);
            fclose($pipes[0]);

            // Read output
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            // Log the script output for debugging
            Log::debug('WireGuard script output', [
                'returnCode' => $returnCode,
                'output' => $output,
                'errors' => $errors
            ]);

            if ($returnCode !== 0) {
                throw new \Exception('WireGuard script failed: ' . ($errors ?: $output));
            }
        } catch (\Exception $e) {
            Log::error('WireGuard configuration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Increment IP address
     *
     * @param string $ip
     * @return string
     */
    private function incrementIp(string $ip): string
    {
        $parts = explode('.', $ip);
        $parts[3]++;
        if ($parts[3] > 254) {
            $parts[3] = 2;
            $parts[2]++;
            if ($parts[2] > 254) {
                throw new \Exception('VPN subnet is full');
            }
        }
        return implode('.', $parts);
    }

    public function encryptData(string $data, string $key): array
    {
        $cipher = new AES('gcm');
        $cipher->setKey($key);
        $iv = Random::string(16);
        $cipher->setIV($iv);
        
        return [
            'data' => base64_encode($cipher->encrypt($data)),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($cipher->getTag())
        ];
    }

    public function decryptData(array $encrypted, string $key): string
    {
        $cipher = new AES('gcm');
        $cipher->setKey($key);
        $cipher->setIV(base64_decode($encrypted['iv']));
        $cipher->setTag(base64_decode($encrypted['tag']));
        
        return $cipher->decrypt(base64_decode($encrypted['data']));
    }

    private function generateEncryptionKey(): string
    {
        return Random::string(self::KEY_SIZE);
    }
} 
