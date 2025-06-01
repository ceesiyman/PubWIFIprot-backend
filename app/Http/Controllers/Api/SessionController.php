<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Http\Requests\Session\StartSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Sessions",
 *     description="API Endpoints for managing user session activity"
 * )
 */
class SessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/sessions/start",
     *     summary="Start a new session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id", "network_ssid", "network_bssid"},
     *             @OA\Property(property="device_id", type="integer", example=1),
     *             @OA\Property(property="network_ssid", type="string", example="CoffeeShop_WiFi"),
     *             @OA\Property(property="network_bssid", type="string", example="00:11:22:33:44:55"),
     *             @OA\Property(property="location", type="object",
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.0060)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Session started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Session started successfully"),
     *             @OA\Property(property="session", type="object")
     *         )
     *     )
     * )
     */
    public function start(StartSessionRequest $request): JsonResponse
    {
        $session = auth()->user()->sessions()->create([
            'device_id' => $request->device_id,
            'network_ssid' => $request->network_ssid,
            'network_bssid' => $request->network_bssid,
            'location' => $request->location,
            'started_at' => now()
        ]);

        return response()->json([
            'message' => 'Session started successfully',
            'session' => $session
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/sessions/end",
     *     summary="End an active session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id"},
     *             @OA\Property(property="session_id", type="integer", example=1),
     *             @OA\Property(property="summary", type="object",
     *                 @OA\Property(property="total_dns_requests", type="integer", example=150),
     *                 @OA\Property(property="blocked_domains", type="integer", example=3),
     *                 @OA\Property(property="network_changes", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session ended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Session ended successfully"),
     *             @OA\Property(property="session", type="object")
     *         )
     *     )
     * )
     */
    public function end(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|integer|exists:sessions,id',
            'summary' => 'required|array',
            'summary.total_dns_requests' => 'required|integer|min:0',
            'summary.blocked_domains' => 'required|integer|min:0',
            'summary.network_changes' => 'required|integer|min:0'
        ]);

        $session = Session::where('user_id', auth()->id())
            ->where('id', $request->session_id)
            ->whereNull('ended_at')
            ->firstOrFail();

        $session->update([
            'ended_at' => now(),
            'summary' => $request->summary
        ]);

        return response()->json([
            'message' => 'Session ended successfully',
            'session' => $session
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/sessions/update",
     *     summary="Update an active session with new activity",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "activity"},
     *             @OA\Property(property="session_id", type="integer", example=1),
     *             @OA\Property(property="activity", type="object",
     *                 @OA\Property(property="type", type="string", enum={"dns_request", "network_change", "blocked_domain"}),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session activity updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Session activity updated successfully")
     *         )
     *     )
     * )
     */
    public function update(UpdateSessionRequest $request): JsonResponse
    {
        $session = Session::where('user_id', auth()->id())
            ->where('id', $request->session_id)
            ->whereNull('ended_at')
            ->firstOrFail();

        $session->activities()->create([
            'type' => $request->activity['type'],
            'details' => $request->activity['details']
        ]);

        return response()->json([
            'message' => 'Session activity updated successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/sessions/history",
     *     summary="Get session history for the authenticated user",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user sessions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="device_id", type="integer"),
     *                     @OA\Property(property="network_ssid", type="string"),
     *                     @OA\Property(property="network_bssid", type="string"),
     *                     @OA\Property(property="location", type="object"),
     *                     @OA\Property(property="started_at", type="string", format="date-time"),
     *                     @OA\Property(property="ended_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="summary", type="object", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $sessions = auth()->user()->sessions()
            ->with(['device', 'activities'])
            ->latest('started_at')
            ->paginate($perPage);

        return response()->json($sessions);
    }
} 