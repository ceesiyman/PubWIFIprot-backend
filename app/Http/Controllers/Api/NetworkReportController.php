<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NetworkTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Network Reports",
 *     description="API Endpoints for analyzing suspicious networks"
 * )
 */
class NetworkReportController extends Controller
{
    protected $networkTrustService;

    public function __construct(NetworkTrustService $networkTrustService)
    {
        $this->networkTrustService = $networkTrustService;
    }

    /**
     * @OA\Post(
     *     path="/api/networks/report",
     *     summary="Analyze a suspicious network",
     *     tags={"Network Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ssid", "bssid", "reason"},
     *             @OA\Property(property="ssid", type="string", example="Suspicious_WiFi"),
     *             @OA\Property(property="bssid", type="string", example="00:11:22:33:44:55"),
     *             @OA\Property(property="encryption_type", type="string", example="WPA2"),
     *             @OA\Property(property="signal_strength", type="integer", example=-65),
     *             @OA\Property(property="reason", type="string", example="Network appears to be spoofing a legitimate business network"),
     *             @OA\Property(property="additional_info", type="string", example="Network appeared suddenly and has very strong signal"),
     *             @OA\Property(property="device_id", type="string", example="device123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Network analysis completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="analysis", type="object",
     *                 @OA\Property(property="is_trusted", type="boolean"),
     *                 @OA\Property(property="is_suspicious", type="boolean"),
     *                 @OA\Property(property="trust_score", type="integer"),
     *                 @OA\Property(property="warnings", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="recommendation", type="string", example="Avoid connecting to this network due to suspicious characteristics")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too many requests. Please try again later.")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'ssid' => 'required|string|max:255',
            'bssid' => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'encryption_type' => 'nullable|string|in:WPA2,WPA,WEP,Open,Unknown',
            'signal_strength' => 'nullable|integer',
            'reason' => 'required|string|max:1000',
            'additional_info' => 'nullable|string|max:1000',
            'device_id' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check rate limit
        $identifier = $request->device_id ?? $request->ip();
        $key = "network_analysis:{$identifier}";
        
        if (RateLimiter::tooManyAttempts($key, 10)) { // 10 requests per hour
            return response()->json([
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        // Increment rate limit
        RateLimiter::hit($key, 3600); // 1 hour window

        // Analyze network trust
        $networkData = $request->only(['ssid', 'bssid', 'encryption_type', 'signal_strength']);
        $analysis = $this->networkTrustService->analyzeNetworks([$networkData])[0];

        // Add recommendation based on analysis
        $analysis['recommendation'] = $this->getRecommendation($analysis);

        return response()->json([
            'analysis' => $analysis
        ]);
    }

    /**
     * Get a recommendation based on network analysis
     */
    private function getRecommendation(array $analysis): string
    {
        if ($analysis['is_trusted']) {
            return 'This network appears to be safe based on its characteristics.';
        }

        if ($analysis['is_suspicious']) {
            return 'Avoid connecting to this network due to suspicious characteristics.';
        }

        if ($analysis['trust_score'] < 50) {
            return 'Exercise caution when connecting to this network.';
        }

        return 'This network appears to be moderately safe, but always verify its legitimacy.';
    }
} 