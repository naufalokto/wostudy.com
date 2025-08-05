<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\SharedTodoList;
use App\Models\CollaborativeParticipant;
use App\Models\UserPresence;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CollaborativePresenceController extends Controller
{
    /**
     * Join collaborative session
     */
    public function joinSession(Request $request, $shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        $user = Auth::user();
        $sessionId = Str::random(32);

        // Create or update participant record
        $participant = CollaborativeParticipant::updateOrCreate(
            [
                'todo_list_id' => $sharedAccess->todo_list_id,
                'user_id' => $user->id,
            ],
            [
                'shared_todo_list_id' => $sharedAccess->id,
                'permission_type' => $sharedAccess->permission_type,
                'status' => 'online',
                'joined_at' => now(),
                'session_id' => $sessionId,
                'user_agent' => [
                    'browser' => $request->header('User-Agent'),
                    'platform' => $request->header('Sec-Ch-Ua-Platform'),
                ],
                'ip_address' => $request->ip(),
                'is_active' => true,
            ]
        );

        // Create presence record
        UserPresence::updateOrCreate(
            [
                'user_id' => $user->id,
                'todo_list_id' => $sharedAccess->todo_list_id,
                'session_id' => $sessionId,
            ],
            [
                'status' => 'online',
                'last_activity_at' => now(),
                'current_activity' => ['action' => 'joined_session'],
            ]
        );

        // Log activity
        $this->logActivity($sharedAccess->todoList, 'user_joined', [
            'user_name' => $user->name,
            'permission_type' => $sharedAccess->permission_type
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'participant' => $participant->load('user'),
            'message' => 'Berhasil bergabung dengan session'
        ]);
    }

    /**
     * Leave collaborative session
     */
    public function leaveSession(Request $request, $shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        $user = Auth::user();
        $sessionId = $request->header('X-Session-ID');

        // Update participant status
        $participant = CollaborativeParticipant::where([
            'todo_list_id' => $sharedAccess->todo_list_id,
            'user_id' => $user->id,
            'session_id' => $sessionId,
        ])->first();

        if ($participant) {
            $participant->markAsOffline();
        }

        // Update presence status
        $presence = UserPresence::where([
            'user_id' => $user->id,
            'todo_list_id' => $sharedAccess->todo_list_id,
            'session_id' => $sessionId,
        ])->first();

        if ($presence) {
            $presence->markAsOffline();
        }

        // Log activity
        $this->logActivity($sharedAccess->todoList, 'user_left', [
            'user_name' => $user->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dari session'
        ]);
    }

    /**
     * Update user presence and activity
     */
    public function updatePresence(Request $request, $shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        $user = Auth::user();
        $sessionId = $request->header('X-Session-ID');

        $request->validate([
            'activity' => 'nullable|string',
            'cursor_position' => 'nullable|string',
        ]);

        // Update participant last seen
        $participant = CollaborativeParticipant::where([
            'todo_list_id' => $sharedAccess->todo_list_id,
            'user_id' => $user->id,
            'session_id' => $sessionId,
        ])->first();

        if ($participant) {
            $participant->updateLastSeen();
        }

        // Update presence
        $presence = UserPresence::where([
            'user_id' => $user->id,
            'todo_list_id' => $sharedAccess->todo_list_id,
            'session_id' => $sessionId,
        ])->first();

        if ($presence) {
            $presence->updateActivity(
                $request->activity,
                $request->cursor_position
            );
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get all participants in collaborative session
     */
    public function getParticipants($shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        // Get all participants
        $participants = CollaborativeParticipant::where('todo_list_id', $sharedAccess->todo_list_id)
            ->with(['user', 'userPresence'])
            ->active()
            ->get()
            ->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'user' => [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                        'email' => $participant->user->email,
                        'avatar' => $this->generateAvatar($participant->user->name),
                    ],
                    'permission_type' => $participant->permission_type,
                    'status' => $participant->status,
                    'status_color' => $participant->getStatusColor(),
                    'status_icon' => $participant->getStatusIcon(),
                    'last_seen' => $participant->getTimeSinceLastSeen(),
                    'joined_at' => $participant->joined_at->diffForHumans(),
                    'current_activity' => $participant->userPresence?->getActivityDescription() ?? 'Idle',
                    'can_edit' => $participant->canEdit(),
                ];
            });

        // Get online count
        $onlineCount = $participants->where('status', 'online')->count();
        $awayCount = $participants->where('status', 'away')->count();
        $offlineCount = $participants->where('status', 'offline')->count();

        return response()->json([
            'participants' => $participants,
            'stats' => [
                'total' => $participants->count(),
                'online' => $onlineCount,
                'away' => $awayCount,
                'offline' => $offlineCount,
            ],
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Get real-time presence updates
     */
    public function getPresenceUpdates($shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        // Get recent presence changes
        $presenceUpdates = UserPresence::where('todo_list_id', $sharedAccess->todo_list_id)
            ->with('user')
            ->where('last_activity_at', '>', now()->subMinutes(10))
            ->get()
            ->map(function ($presence) {
                return [
                    'user_id' => $presence->user_id,
                    'user_name' => $presence->user->name,
                    'status' => $presence->status,
                    'status_color' => $presence->getStatusColor(),
                    'status_icon' => $presence->getStatusIcon(),
                    'current_activity' => $presence->getActivityDescription(),
                    'last_activity' => $presence->last_activity_at->diffForHumans(),
                    'cursor_position' => $presence->cursor_position,
                ];
            });

        // Get recent activities
        $recentActivities = ActivityLog::where('todo_list_id', $sharedAccess->todo_list_id)
            ->with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user_name' => $activity->user->name,
                    'action' => $activity->getActionDescription(),
                    'data' => $activity->getFormattedData(),
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'presence_updates' => $presenceUpdates,
            'recent_activities' => $recentActivities,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Mark user as away (when user is inactive)
     */
    public function markAsAway(Request $request, $shareToken)
    {
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid');
        }

        $user = Auth::user();
        $sessionId = $request->header('X-Session-ID');

        // Update participant status
        $participant = CollaborativeParticipant::where([
            'todo_list_id' => $sharedAccess->todo_list_id,
            'user_id' => $user->id,
            'session_id' => $sessionId,
        ])->first();

        if ($participant) {
            $participant->markAsAway();
        }

        // Update presence status
        $presence = UserPresence::where([
            'user_id' => $user->id,
            'todo_list_id' => $sharedAccess->todo_list_id,
            'session_id' => $sessionId,
        ])->first();

        if ($presence) {
            $presence->markAsAway();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status diubah menjadi away'
        ]);
    }

    /**
     * Generate avatar from user name
     */
    private function generateAvatar(string $name): string
    {
        $initials = strtoupper(substr($name, 0, 2));
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
        $color = $colors[array_rand($colors)];
        
        return "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=fff&size=40";
    }

    /**
     * Log activity
     */
    private function logActivity($todoList, $action, $data = [])
    {
        ActivityLog::create([
            'todo_list_id' => $todoList->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
        ]);
    }
} 