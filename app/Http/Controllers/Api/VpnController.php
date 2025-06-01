<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpnSession;
use App\Services\VpnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="VPN",
 *     description="VPN connection management endpoints"
 * )
 */
class VpnController extends Controller
{
    protected $vpnService;

    public function __construct(VpnService $vpnService)
    {
        $this->vpnService = $vpnService;
    }

    /**
     * @OA\Post(
     *     path="/api/vpn/connect",
     *     summary="Connect to VPN",
     *     description="Establishes a VPN connection for the authenticated user",
     *     tags={"VPN"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="VPN connection established successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="server_address", type="string", example="vpn.pubwifi.com"),
     *                 @OA\Property(property="server_port", type="integer", example=1194),
     *                 @OA\Property(property="encryption_key", type="string", example="base64-encoded-key")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to establish VPN connection"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function connect(Request $request)
    {
        try {
            // Create VPN session for the authenticated user
            $session = $this->vpnService->createSession(
                $request->user(),
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'server_address' => $session->server_address,
                    'server_port' => $session->server_port,
                    'encryption_key' => decrypt($session->encryption_key),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to establish VPN connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/vpn/disconnect",
     *     summary="Disconnect from VPN",
     *     description="Terminates the active VPN connection for the authenticated user",
     *     tags={"VPN"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="VPN disconnected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="VPN disconnected successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to disconnect VPN"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function disconnect(Request $request)
    {
        try {
            $session = VpnSession::where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->first();

            if ($session) {
                $this->vpnService->disconnectSession($session);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'VPN disconnected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to disconnect VPN',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/vpn/stats",
     *     summary="Update VPN statistics",
     *     description="Updates the statistics for the active VPN session of the authenticated user",
     *     tags={"VPN"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bytes_sent", "bytes_received"},
     *             @OA\Property(property="bytes_sent", type="integer", example=1024),
     *             @OA\Property(property="bytes_received", type="integer", example=2048)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stats updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Stats updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="bytes_sent", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="bytes_received", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to update stats"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateStats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bytes_sent' => 'required|integer|min:0',
            'bytes_received' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $session = VpnSession::where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->first();

            if ($session) {
                $this->vpnService->updateSessionStats(
                    $session,
                    $request->bytes_sent,
                    $request->bytes_received
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Stats updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/vpn/status",
     *     summary="Get VPN session status",
     *     description="Retrieves the current status of the VPN session for the authenticated user",
     *     tags={"VPN"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="VPN session status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="connected_at", type="string", format="date-time"),
     *                 @OA\Property(property="disconnected_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="bytes_sent", type="integer", example=1024),
     *                 @OA\Property(property="bytes_received", type="integer", example=2048)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to get VPN status"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function status(Request $request)
    {
        try {
            $session = VpnSession::where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'status' => 'disconnected',
                        'connected_at' => null,
                        'disconnected_at' => null,
                        'bytes_sent' => 0,
                        'bytes_received' => 0,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => $session->status,
                    'connected_at' => $session->connected_at,
                    'disconnected_at' => $session->disconnected_at,
                    'bytes_sent' => $session->bytes_sent,
                    'bytes_received' => $session->bytes_received,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get VPN status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 