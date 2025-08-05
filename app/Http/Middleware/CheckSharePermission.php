<?php

namespace App\Http\Middleware;

use App\Models\SharedTodoList;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSharePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = 'can_view')
    {
        $shareToken = $request->route('shareToken');
        
        if (!$shareToken) {
            abort(404, 'Share token tidak ditemukan');
        }

        // Find shared access
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid atau sudah expired');
        }

        // If user is authenticated, check specific permissions
        if (Auth::check()) {
            $user = Auth::user();
            
            // Owner can do everything
            if ($sharedAccess->todoList->user_id === $user->id) {
                return $next($request);
            }

            // Check if user has specific permission
            if ($sharedAccess->shared_with_user_id === $user->id) {
                if ($permission === 'can_edit' && !$sharedAccess->canEdit()) {
                    abort(403, 'Anda tidak memiliki permission untuk edit');
                }
                
                if ($permission === 'can_view' && !$sharedAccess->canView()) {
                    abort(403, 'Anda tidak memiliki permission untuk view');
                }
            } else {
                // Public access (no specific user assigned)
                if ($sharedAccess->shared_with_user_id === null) {
                    if ($permission === 'can_edit' && !$sharedAccess->canEdit()) {
                        abort(403, 'Anda tidak memiliki permission untuk edit');
                    }
                    
                    if ($permission === 'can_view' && !$sharedAccess->canView()) {
                        abort(403, 'Anda tidak memiliki permission untuk view');
                    }
                } else {
                    abort(403, 'Anda tidak memiliki akses ke todo list ini');
                }
            }
        } else {
            // For unauthenticated users, only allow view if it's a public share
            if ($permission === 'can_edit') {
                abort(401, 'Silakan login untuk mengedit');
            }
            
            if ($sharedAccess->shared_with_user_id !== null) {
                abort(401, 'Silakan login untuk mengakses todo list ini');
            }
        }

        // Add shared access to request for use in controllers
        $request->attributes->set('shared_access', $sharedAccess);
        
        return $next($request);
    }
} 