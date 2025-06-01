<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NetworkTrustService
{
    /**
     * Analyze networks sent from the Flutter app
     *
     * @param array $networks Array of networks from Flutter app
     * @return array
     */
    public function analyzeNetworks(array $networks): array
    {
        try {
            // Add trust and suspicious status to networks
            foreach ($networks as &$network) {
                $network['is_trusted'] = $this->isNetworkTrusted($network);
                $network['is_suspicious'] = $this->isNetworkSuspicious($network);
                $network['trust_score'] = $this->calculateTrustScore($network);
                $network['warnings'] = $this->getNetworkWarnings($network);
            }

            return $networks;

        } catch (\Exception $e) {
            Log::error('Network analysis failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to analyze networks: ' . $e->getMessage());
        }
    }

    /**
     * Check if a network is trusted based on its characteristics
     *
     * @param array $network
     * @return bool
     */
    private function isNetworkTrusted(array $network): bool
    {
        // Networks with WPA2 encryption and strong signal are considered more trustworthy
        return $network['encryption_type'] === 'WPA2' && $network['signal_strength'] > -70;
    }

    /**
     * Check if a network is suspicious based on various factors
     *
     * @param array $network
     * @return bool
     */
    private function isNetworkSuspicious(array $network): bool
    {
        // Check for common suspicious patterns
        $suspiciousPatterns = [
            'free_wifi',
            'public_wifi',
            'guest_wifi',
            'open_wifi',
            'airport_wifi',
            'hotel_wifi'
        ];

        $ssid = strtolower($network['ssid']);
        
        // Check for suspicious SSID patterns
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($ssid, $pattern) !== false) {
                return true;
            }
        }

        // Check for open networks
        if ($network['encryption_type'] === 'Open') {
            return true;
        }

        // Check for very weak encryption
        if ($network['encryption_type'] === 'WEP') {
            return true;
        }

        // Check for very weak signal (below -80 dBm)
        if ($network['signal_strength'] < -80) {
            return true;
        }

        return false;
    }

    /**
     * Check if a specific network is safe
     *
     * @param array $network Network data from Flutter app
     * @return array
     */
    public function checkNetworkSafety(array $network): array
    {
        try {
            // Calculate trust score
            $trustScore = $this->calculateTrustScore($network);
            
            // Get warnings
            $warnings = $this->getNetworkWarnings($network);

            return [
                'is_safe' => $trustScore >= 70 && empty($warnings),
                'trust_score' => $trustScore,
                'warnings' => $warnings,
                'is_trusted' => $this->isNetworkTrusted($network),
                'is_suspicious' => $this->isNetworkSuspicious($network)
            ];

        } catch (\Exception $e) {
            Log::error('Network safety check failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to check network safety: ' . $e->getMessage());
        }
    }

    /**
     * Calculate trust score for a network
     *
     * @param array $network
     * @return int
     */
    private function calculateTrustScore(array $network): int
    {
        $score = 50; // Base score

        // Encryption type scoring
        switch ($network['encryption_type']) {
            case 'WPA2':
                $score += 30;
                break;
            case 'WPA':
                $score += 20;
                break;
            case 'WEP':
                $score -= 20;
                break;
            case 'Open':
                $score -= 40;
                break;
        }

        // Signal strength scoring
        if ($network['signal_strength'] > -50) {
            $score += 10;
        } elseif ($network['signal_strength'] > -70) {
            $score += 5;
        } elseif ($network['signal_strength'] < -80) {
            $score -= 10;
        }

        // Check SSID for suspicious patterns
        $suspiciousPatterns = [
            'free_wifi' => -20,
            'public_wifi' => -15,
            'guest_wifi' => -10,
            'open_wifi' => -25,
            'airport_wifi' => -5,
            'hotel_wifi' => -5
        ];

        foreach ($suspiciousPatterns as $pattern => $penalty) {
            if (stripos($network['ssid'], $pattern) !== false) {
                $score += $penalty;
            }
        }

        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Get warnings for a network
     *
     * @param array $network
     * @return array
     */
    private function getNetworkWarnings(array $network): array
    {
        $warnings = [];

        // Check for suspicious SSID patterns
        $suspiciousPatterns = [
            'free_wifi' => 'Network name contains "free_wifi" which is commonly used in spoofing attacks',
            'public_wifi' => 'Network name contains "public_wifi" which is commonly used in spoofing attacks',
            'guest_wifi' => 'Network name contains "guest_wifi" which is commonly used in spoofing attacks',
            'open_wifi' => 'Network name contains "open_wifi" which is commonly used in spoofing attacks'
        ];

        foreach ($suspiciousPatterns as $pattern => $warning) {
            if (stripos($network['ssid'], $pattern) !== false) {
                $warnings[] = $warning;
            }
        }

        // Check encryption
        if ($network['encryption_type'] === 'Open') {
            $warnings[] = 'Network is open (no encryption)';
        } elseif ($network['encryption_type'] === 'WEP') {
            $warnings[] = 'Network uses weak WEP encryption';
        }

        // Check signal strength
        if ($network['signal_strength'] < -80) {
            $warnings[] = 'Network has very weak signal strength';
        }

        return $warnings;
    }
} 