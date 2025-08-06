<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SharedTodoList;
use App\Models\CollaborativeParticipant;

class CollaborativeAccessLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shareToken = $request->route('shareToken');
        
        if (!$shareToken) {
            return $next($request);
        }

        // Get shared access info
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            return response()->json([
                'error' => 'Share link tidak valid atau sudah expired'
            ], 404);
        }

        // 1. Rate Limiting per IP
        $ipKey = 'collaborative_ip_' . $request->ip() . '_' . $shareToken;
        $maxRequestsPerMinute = 30; // Maksimal 30 request per menit per IP
        
        if (RateLimiter::tooManyAttempts($ipKey, $maxRequestsPerMinute)) {
            return response()->json([
                'error' => 'Terlalu banyak request. Silakan coba lagi dalam 1 menit.'
            ], 429);
        }
        
        RateLimiter::hit($ipKey, 60); // 60 detik

        // 2. Concurrent User Limit
        $maxConcurrentUsers = 10; // Maksimal 10 user bersamaan
        $currentUsers = CollaborativeParticipant::where('todo_list_id', $sharedAccess->todo_list_id)
            ->where('is_active', true)
            ->count();

        if ($currentUsers >= $maxConcurrentUsers) {
            return response()->json([
                'error' => 'Sesi collaborative sedang penuh. Maksimal ' . $maxConcurrentUsers . ' user bersamaan.'
            ], 503);
        }

        // 3. Total Daily Access Limit
        $dailyKey = 'collaborative_daily_' . $shareToken . '_' . date('Y-m-d');
        $maxDailyAccess = 100; // Maksimal 100 akses per hari
        
        $dailyAccessCount = Cache::get($dailyKey, 0);
        if ($dailyAccessCount >= $maxDailyAccess) {
            return response()->json([
                'error' => 'Batas akses harian telah tercapai. Silakan coba lagi besok.'
            ], 429);
        }
        
        Cache::put($dailyKey, $dailyAccessCount + 1, 86400); // 24 jam

        // 4. Session Duration Limit
        $sessionKey = 'collaborative_session_' . $request->ip() . '_' . $shareToken;
        $maxSessionDuration = 3600; // 1 jam maksimal per session
        
        if (Cache::has($sessionKey)) {
            $sessionStart = Cache::get($sessionKey);
            if (time() - $sessionStart > $maxSessionDuration) {
                Cache::forget($sessionKey);
            }
        } else {
            Cache::put($sessionKey, time(), $maxSessionDuration);
        }

        // 5. Geographic Restriction (optional)
        $allowedCountries = ['ID', 'MY', 'SG']; // Indonesia, Malaysia, Singapore
        $userCountry = $this->getUserCountry($request->ip());
        
        if (!in_array($userCountry, $allowedCountries)) {
            return response()->json([
                'error' => 'Akses dibatasi untuk wilayah tertentu.'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get user country from IP (simplified version)
     */
    private function getUserCountry($ip)
    {
        // In production, use a proper IP geolocation service
        // For now, return 'ID' as default
        return 'ID';
    }
}
