<?php

namespace App\Http\Controllers;

use App\Models\VpnSession;
use App\Services\VpnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VpnController extends Controller
{
    protected $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get VPN status for the authenticated user
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $session = VpnSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'disconnected',
                    'bytes_sent' => 0,
                    'bytes_received' => 0,
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'status' => $session->status,
                'client_ip' => $session->client_ip,
                'server_address' => $session->server_address,
                'server_port' => $session->server_port,
                'bytes_sent' => $session->bytes_sent,
                'bytes_received' => $session->bytes_received,
                'connected_at' => $session->created_at,
            ]
        ]);
    }

    /**
     * Connect to VPN
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function connect(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientIp = $request->ip();

            // Check if user already has an active session
            $existingSession = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existingSession) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'status' => 'active',
                        'client_ip' => $existingSession->client_ip,
                        'server_address' => $existingSession->server_address,
                        'server_port' => $existingSession->server_port,
                        'bytes_sent' => $existingSession->bytes_sent,
                        'bytes_received' => $existingSession->bytes_received,
                        'connected_at' => $existingSession->created_at,
                    ]
                ]);
            }

            // Create new session
            $session = $this->vpnService->createSession($user, $clientIp);

            // Get client configuration
            $configPath = 'wireguard/clients/' . $user->id . '.conf';
            $config = Storage::get($configPath);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'active',
                    'client_ip' => $session->client_ip,
                    'server_address' => $session->server_address,
                    'server_port' => $session->server_port,
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                    'connected_at' => $session->created_at,
                    'config' => base64_encode($config),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN connection failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to VPN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect from VPN
     *
     * @return JsonResponse
     */
    public function disconnect(): JsonResponse
    {
        try {
            $user = auth()->user();
            $session = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$session) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'status' => 'disconnected',
                        'bytes_sent' => 0,
                        'bytes_received' => 0,
                    ]
                ]);
            }

            $this->vpnService->disconnectSession($session);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'disconnected',
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN disconnection failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to disconnect from VPN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update VPN session statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $session = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active VPN session found'
                ], 404);
            }

            $this->vpnService->updateSessionStats(
                $session,
                (int) $request->input('bytes_sent', 0),
                (int) $request->input('bytes_received', 0)
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN stats update failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update VPN statistics: ' . $e->getMessage()
            ], 500);
        }
    }
} 

namespace App\Http\Controllers;

use App\Models\VpnSession;
use App\Services\VpnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VpnController extends Controller
{
    protected $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get VPN status for the authenticated user
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $session = VpnSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'disconnected',
                    'bytes_sent' => 0,
                    'bytes_received' => 0,
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'status' => $session->status,
                'client_ip' => $session->client_ip,
                'server_address' => $session->server_address,
                'server_port' => $session->server_port,
                'bytes_sent' => $session->bytes_sent,
                'bytes_received' => $session->bytes_received,
                'connected_at' => $session->created_at,
            ]
        ]);
    }

    /**
     * Connect to VPN
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function connect(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientIp = $request->ip();

            // Check if user already has an active session
            $existingSession = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existingSession) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'status' => 'active',
                        'client_ip' => $existingSession->client_ip,
                        'server_address' => $existingSession->server_address,
                        'server_port' => $existingSession->server_port,
                        'bytes_sent' => $existingSession->bytes_sent,
                        'bytes_received' => $existingSession->bytes_received,
                        'connected_at' => $existingSession->created_at,
                    ]
                ]);
            }

            // Create new session
            $session = $this->vpnService->createSession($user, $clientIp);

            // Get client configuration
            $configPath = 'wireguard/clients/' . $user->id . '.conf';
            $config = Storage::get($configPath);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'active',
                    'client_ip' => $session->client_ip,
                    'server_address' => $session->server_address,
                    'server_port' => $session->server_port,
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                    'connected_at' => $session->created_at,
                    'config' => base64_encode($config),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN connection failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to VPN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect from VPN
     *
     * @return JsonResponse
     */
    public function disconnect(): JsonResponse
    {
        try {
            $user = auth()->user();
            $session = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$session) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'status' => 'disconnected',
                        'bytes_sent' => 0,
                        'bytes_received' => 0,
                    ]
                ]);
            }

            $this->vpnService->disconnectSession($session);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => 'disconnected',
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN disconnection failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to disconnect from VPN: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update VPN session statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStats(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $session = VpnSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active VPN session found'
                ], 404);
            }

            $this->vpnService->updateSessionStats(
                $session,
                (int) $request->input('bytes_sent', 0),
                (int) $request->input('bytes_received', 0)
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VPN stats update failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update VPN statistics: ' . $e->getMessage()
            ], 500);
        }
    }
} 