<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\DomainReputationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="DNS & Domain Reputation",
 *     description="API Endpoints for domain reputation checks and malicious domain management"
 * )
 */
class DnsController extends Controller
{
    protected $domainReputationService;

    public function __construct(DomainReputationService $domainReputationService)
    {
        $this->domainReputationService = $domainReputationService;
    }

    /**
     * @OA\Post(
     *     path="/api/dns/check",
     *     summary="Check if a domain is safe",
     *     tags={"DNS & Domain Reputation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"domain"},
     *             @OA\Property(property="domain", type="string", example="example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Domain safety check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_safe", type="boolean", example=true),
     *             @OA\Property(property="reputation_score", type="integer", example=85),
     *             @OA\Property(property="threat_level", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="last_checked", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function checkDomain(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/'
        ]);

        $result = $this->domainReputationService->checkDomain($request->domain);
        
        // Log the check for analytics
        auth()->user()->domainChecks()->create([
            'domain' => $request->domain,
            'result' => $result
        ]);

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/dns/history",
     *     summary="Get domain check history for the authenticated user",
     *     tags={"DNS & Domain Reputation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of domain checks",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="domain", type="string"),
     *                 @OA\Property(property="is_safe", type="boolean"),
     *                 @OA\Property(property="reputation_score", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function history(): JsonResponse
    {
        $history = auth()->user()->domainChecks()
            ->latest()
            ->paginate(20);

        return response()->json($history);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/domains/malicious",
     *     summary="Get list of malicious domains (Admin only)",
     *     tags={"DNS & Domain Reputation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of malicious domains",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="domain", type="string"),
     *                 @OA\Property(property="threat_level", type="string"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="added_by", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function maliciousDomains(): JsonResponse
    {
        $domains = Domain::where('is_malicious', true)
            ->with('addedBy')
            ->latest()
            ->get();

        return response()->json($domains);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/domains/malicious",
     *     summary="Add a malicious domain (Admin only)",
     *     tags={"DNS & Domain Reputation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"domain", "threat_level", "categories"},
     *             @OA\Property(property="domain", type="string", example="malicious-site.com"),
     *             @OA\Property(property="threat_level", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Malicious domain added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Malicious domain added successfully"),
     *             @OA\Property(property="domain", type="object")
     *         )
     *     )
     * )
     */
    public function addMaliciousDomain(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|unique:domains,domain',
            'threat_level' => 'required|string|in:low,medium,high',
            'categories' => 'required|array',
            'categories.*' => 'string',
            'notes' => 'nullable|string'
        ]);

        $domain = Domain::create([
            'domain' => $request->domain,
            'is_malicious' => true,
            'threat_level' => $request->threat_level,
            'categories' => $request->categories,
            'notes' => $request->notes,
            'added_by' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Malicious domain added successfully',
            'domain' => $domain
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/domains/malicious/{domain}",
     *     summary="Remove a malicious domain (Admin only)",
     *     tags={"DNS & Domain Reputation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="domain",
     *         in="path",
     *         required=true,
     *         description="Domain ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Malicious domain removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Malicious domain removed successfully")
     *         )
     *     )
     * )
     */
    public function removeMaliciousDomain(Domain $domain): JsonResponse
    {
        $domain->update(['is_malicious' => false]);
        return response()->json(['message' => 'Malicious domain removed successfully']);
    }
} 