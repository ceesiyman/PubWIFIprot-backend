<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NetworkTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Networks",
 *     description="API Endpoints for managing and checking WiFi networks"
 * )
 */
class NetworkController extends Controller
{
    protected $networkTrustService;

    public function __construct(NetworkTrustService $networkTrustService)
    {
        $this->networkTrustService = $networkTrustService;
    }

    /**
     * @OA\Post(
     *     path="/api/networks/analyze",
     *     summary="Analyze list of networks from mobile device",
     *     tags={"Networks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="ssid", type="string", example="CoffeeShop_WiFi"),
     *                 @OA\Property(property="bssid", type="string", example="00:11:22:33:44:55"),
     *                 @OA\Property(property="signal_strength", type="integer", example=-65),
     *                 @OA\Property(property="encryption_type", type="string", example="WPA2"),
     *                 @OA\Property(property="channel", type="integer", example=6)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analysis of networks",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="ssid", type="string", example="CoffeeShop_WiFi"),
     *                 @OA\Property(property="bssid", type="string", example="00:11:22:33:44:55"),
     *                 @OA\Property(property="signal_strength", type="integer", example=-65),
     *                 @OA\Property(property="encryption_type", type="string", example="WPA2"),
     *                 @OA\Property(property="channel", type="integer", example=6),
     *                 @OA\Property(property="is_trusted", type="boolean", example=true),
     *                 @OA\Property(property="is_suspicious", type="boolean", example=false),
     *                 @OA\Property(property="trust_score", type="integer", example=85),
     *                 @OA\Property(property="warnings", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            '*.ssid' => 'required|string',
            '*.bssid' => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            '*.signal_strength' => 'required|integer',
            '*.encryption_type' => 'required|string|in:WPA2,WPA,WEP,Open,Unknown',
            '*.channel' => 'required|integer|min:1|max:165'
        ]);

        $networks = $this->networkTrustService->analyzeNetworks($request->all());
        return response()->json($networks);
    }

    /**
     * @OA\Post(
     *     path="/api/networks/check",
     *     summary="Check if a specific network is safe",
     *     tags={"Networks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="ssid", type="string", example="CoffeeShop_WiFi"),
     *             @OA\Property(property="bssid", type="string", example="00:11:22:33:44:55"),
     *             @OA\Property(property="signal_strength", type="integer", example=-65),
     *             @OA\Property(property="encryption_type", type="string", example="WPA2"),
     *             @OA\Property(property="channel", type="integer", example=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Network safety check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_safe", type="boolean", example=true),
     *             @OA\Property(property="trust_score", type="integer", example=85),
     *             @OA\Property(property="warnings", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_trusted", type="boolean", example=true),
     *             @OA\Property(property="is_suspicious", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'ssid' => 'required|string',
            'bssid' => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'signal_strength' => 'required|integer',
            'encryption_type' => 'required|string|in:WPA2,WPA,WEP,Open,Unknown',
            'channel' => 'required|integer|min:1|max:165'
        ]);

        $result = $this->networkTrustService->checkNetworkSafety($request->all());
        return response()->json($result);
    }
} 