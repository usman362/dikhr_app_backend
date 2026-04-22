<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GroupController extends Controller
{
    /**
     * List groups the authenticated user belongs to (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $request->user()->id))
            ->withCount('users as members_count')
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        // Enrich each group with activity signals for the mobile UI.
        $groups->getCollection()->transform(
            fn (Group $g) => $this->enrichWithActivity($g)
        );

        return response()->json($groups);
    }

    /**
     * Search public groups by name (paginated).
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        $groups = Group::query()
            ->where('name', 'like', '%' . $request->query('q') . '%')
            ->withCount('users as members_count')
            ->with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        $groups->getCollection()->transform(
            fn (Group $g) => $this->enrichWithActivity($g)
        );

        return response()->json($groups);
    }

    /**
     * Attach activity signals to a group for the list/search views:
     *   - today_count:           sum of all dhikr contributed to the group's campaigns today
     *   - week_count:            same, this calendar week
     *   - active_campaigns_count: campaigns currently with status=active
     *   - last_activity_at:      timestamp of the most recent contribution
     *   - recent_contributors:   last 4 unique users who contributed (id + name)
     *   - daily_counts:          7-day sparkline array (oldest → newest)
     */
    protected function enrichWithActivity(Group $group): Group
    {
        $campaignIds = Campaign::query()
            ->where('group_id', $group->id)
            ->pluck('id');

        if ($campaignIds->isEmpty()) {
            $group->setAttribute('today_count', 0);
            $group->setAttribute('week_count', 0);
            $group->setAttribute('active_campaigns_count', 0);
            $group->setAttribute('last_activity_at', null);
            $group->setAttribute('recent_contributors', []);
            $group->setAttribute('daily_counts', array_fill(0, 7, 0));
            return $group;
        }

        $baseQuery = DhikrContribution::query()->whereIn('campaign_id', $campaignIds);

        $todayCount = (int) (clone $baseQuery)
            ->whereDate('created_at', Carbon::today())
            ->sum('count');

        $weekCount = (int) (clone $baseQuery)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->sum('count');

        $activeCampaigns = (int) Campaign::query()
            ->where('group_id', $group->id)
            ->where('status', 'active')
            ->count();

        $lastActivity = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->value('created_at');

        // Last 4 unique contributors (most recent first)
        $recentContributors = (clone $baseQuery)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->take(4)
            ->values()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->all();

        // 7-day sparkline — counts per day, oldest to newest
        $dailyCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dailyCounts[] = (int) (clone $baseQuery)
                ->whereDate('created_at', $day)
                ->sum('count');
        }

        $group->setAttribute('today_count', $todayCount);
        $group->setAttribute('week_count', $weekCount);
        $group->setAttribute('active_campaigns_count', $activeCampaigns);
        $group->setAttribute(
            'last_activity_at',
            $lastActivity ? Carbon::parse($lastActivity)->toIso8601String() : null
        );
        $group->setAttribute('recent_contributors', $recentContributors);
        $group->setAttribute('daily_counts', $dailyCounts);

        return $group;
    }

    /**
     * Create a new group.
     *
     * Free users: max 3 groups. Premium: unlimited.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // Enforce group creation limit for free users
        $user = $request->user();
        if (! $user->isPremium()) {
            $ownedGroups = Group::query()->where('created_by', $user->id)->count();
            if ($ownedGroups >= 3) {
                return response()->json([
                    'message' => 'Free users can create up to 3 groups. Upgrade to Premium for unlimited groups.',
                    'upgrade_required' => true,
                ], 403);
            }
        }

        $group = Group::query()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by'  => $request->user()->id,
        ]);

        $group->users()->attach($request->user()->id, ['role' => 'admin']);

        $group->load('creator:id,name');
        $group->loadCount('users as members_count');

        return response()->json($group, 201);
    }

    /**
     * Show a single group with members, campaigns, and activity signals.
     */
    public function show(Request $request, Group $group): JsonResponse
    {
        if (! $group->hasMember($request->user())) {
            abort(403, 'You are not a member of this group.');
        }

        $group->load([
            'creator:id,name',
            'users:id,name,email',
            'campaigns' => fn ($q) => $q->withSum('contributions as current_total', 'count'),
        ]);
        $group->loadCount('users as members_count');

        $this->enrichWithActivity($group);

        return response()->json($group);
    }

    /**
     * Update group details (admin only).
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        if (! $group->isAdmin($request->user())) {
            abort(403, 'Only group admins can update this group.');
        }

        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $group->update($data);
        $group->load('creator:id,name');

        return response()->json($group);
    }

    /**
     * Delete a group (admin only).
     */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        if (! $group->isAdmin($request->user())) {
            abort(403, 'Only group admins can delete this group.');
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted']);
    }

    /**
     * Join an existing group.
     */
    public function join(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if ($group->hasMember($user)) {
            return response()->json(['message' => 'Already a member', 'group' => $group]);
        }

        $group->users()->attach($user->id, ['role' => 'member']);

        return response()->json([
            'message' => 'Joined',
            'group'   => $group->fresh('creator:id,name'),
        ], 201);
    }

    /**
     * Join a group using an invite code.
     */
    public function joinByCode(Request $request): JsonResponse
    {
        // Min 4 characters so users can accept short manually-issued codes
        // (client feedback). Existing auto-generated codes are 6-8 chars.
        $data = $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $group = Group::query()
            ->where('invite_code', strtoupper($data['code']))
            ->first();

        if (! $group) {
            return response()->json(['message' => 'Invalid invite code.'], 404);
        }

        $user = $request->user();

        if ($group->hasMember($user)) {
            $group->load('creator:id,name');
            $group->loadCount('users as members_count');

            return response()->json(['message' => 'Already a member', 'group' => $group]);
        }

        $group->users()->attach($user->id, ['role' => 'member']);
        $group->load('creator:id,name');
        $group->loadCount('users as members_count');

        return response()->json([
            'message' => 'Joined',
            'group'   => $group,
        ], 201);
    }

    /**
     * Regenerate the invite code for a group (admin only).
     */
    public function regenerateInviteCode(Request $request, Group $group): JsonResponse
    {
        if (! $group->isAdmin($request->user())) {
            abort(403, 'Only group admins can regenerate the invite code.');
        }

        $group->update(['invite_code' => Group::generateInviteCode()]);

        return response()->json([
            'message'     => 'Invite code regenerated',
            'invite_code' => $group->invite_code,
        ]);
    }

    /**
     * Leave a group (non-creators only).
     */
    public function leave(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (! $group->hasMember($user)) {
            abort(404, 'You are not a member of this group.');
        }

        if ($group->created_by === $user->id) {
            abort(422, 'Group creator cannot leave. Transfer ownership or delete the group.');
        }

        $group->users()->detach($user->id);

        return response()->json(['message' => 'Left the group']);
    }
}
