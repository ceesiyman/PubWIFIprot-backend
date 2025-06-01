<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkReport;
use App\Models\Domain;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="API Endpoints for admin dashboard functionality"
 * )
 */
class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get admin dashboard statistics",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="object",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="active_today", type="integer", example=25),
     *                 @OA\Property(property="new_this_week", type="integer", example=10)
     *             ),
     *             @OA\Property(property="networks", type="object",
     *                 @OA\Property(property="total_reported", type="integer", example=50),
     *                 @OA\Property(property="pending_review", type="integer", example=5),
     *                 @OA\Property(property="confirmed_malicious", type="integer", example=15)
     *             ),
     *             @OA\Property(property="domains", type="object",
     *                 @OA\Property(property="total_malicious", type="integer", example=200),
     *                 @OA\Property(property="blocked_today", type="integer", example=25),
     *                 @OA\Property(property="new_this_week", type="integer", example=10)
     *             ),
     *             @OA\Property(property="sessions", type="object",
     *                 @OA\Property(property="active", type="integer", example=30),
     *                 @OA\Property(property="total_today", type="integer", example=150),
     *                 @OA\Property(property="average_duration", type="integer", example=45)
     *             ),
     *             @OA\Property(property="recent_activity", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="type", type="string", enum={"new_user", "network_report", "malicious_domain", "session_start"}),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function dashboard(): JsonResponse
    {
        // Get user statistics
        $userStats = [
            'total' => User::count(),
            'active_today' => User::whereHas('sessions', function ($query) {
                $query->whereDate('started_at', today());
            })->count(),
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count()
        ];

        // Get network statistics
        $networkStats = [
            'total_reported' => NetworkReport::count(),
            'pending_review' => NetworkReport::where('status', 'pending')->count(),
            'confirmed_malicious' => NetworkReport::where('status', 'approved')->count()
        ];

        // Get domain statistics
        $domainStats = [
            'total_malicious' => Domain::where('is_malicious', true)->count(),
            'blocked_today' => DB::table('domain_checks')
                ->whereDate('created_at', today())
                ->where('is_safe', false)
                ->count(),
            'new_this_week' => Domain::where('is_malicious', true)
                ->where('created_at', '>=', now()->subWeek())
                ->count()
        ];

        // Get session statistics
        $sessionStats = [
            'active' => Session::whereNull('ended_at')->count(),
            'total_today' => Session::whereDate('started_at', today())->count(),
            'average_duration' => Session::whereNotNull('ended_at')
                ->whereDate('started_at', today())
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, started_at, ended_at)'))
        ];

        // Get recent activity
        $recentActivity = $this->getRecentActivity();

        return response()->json([
            'users' => $userStats,
            'networks' => $networkStats,
            'domains' => $domainStats,
            'sessions' => $sessionStats,
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * Get recent activity across different models
     */
    private function getRecentActivity(): array
    {
        $activities = [];

        // Get recent users
        $recentUsers = User::latest()->take(5)->get();
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'new_user',
                'description' => "New user registered: {$user->email}",
                'created_at' => $user->created_at
            ];
        }

        // Get recent network reports
        $recentReports = NetworkReport::with('user')->latest()->take(5)->get();
        foreach ($recentReports as $report) {
            $activities[] = [
                'type' => 'network_report',
                'description' => "Network reported: {$report->ssid} by {$report->user->email}",
                'created_at' => $report->created_at
            ];
        }

        // Get recent malicious domains
        $recentDomains = Domain::where('is_malicious', true)
            ->with('addedBy')
            ->latest()
            ->take(5)
            ->get();
        foreach ($recentDomains as $domain) {
            $activities[] = [
                'type' => 'malicious_domain',
                'description' => "Malicious domain added: {$domain->domain} by {$domain->addedBy->email}",
                'created_at' => $domain->created_at
            ];
        }

        // Sort all activities by created_at
        usort($activities, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        // Return only the 10 most recent activities
        return array_slice($activities, 0, 10);
    }
} 