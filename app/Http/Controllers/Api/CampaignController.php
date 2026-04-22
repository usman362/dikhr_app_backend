<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * List campaigns the user can see (paginated).
     *
     * Query params:
     *   - group_id : filter by group
     *   - status   : filter by status (draft|active|ended)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::query()
            ->withSum('contributions as current_total', 'count')
            ->with('group:id,name', 'creator:id,name')
            ->orderByDesc('created_at');

        // Filter by group
        $groupId = $request->query('group_id');
        if ($groupId !== null && $groupId !== '') {
            $query->where('group_id', $groupId);
        }

        // Filter by status
        $status = $request->query('status');
        if (in_array($status, ['draft', 'active', 'ended'], true)) {
            $query->where('status', $status);
        }

        // Scope to campaigns the user can see (global + their groups)
        $userId = $request->user()->id;
        $query->where(function ($q) use ($userId) {
            $q->whereNull('group_id')
              ->orWhereHas('group.users', fn ($sub) => $sub->where('users.id', $userId));
        });

        $campaigns = $query->paginate($request->integer('per_page', 15));

        return response()->json($campaigns);
    }

    /**
     * Create a new campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_id'     => ['nullable', 'exists:groups,id'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'dhikr'        => ['nullable', 'string', 'max:50'],
            'target_count' => ['required', 'integer', 'min:1'],
            'starts_at'    => ['nullable', 'date'],
            'ends_at'      => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'       => ['required', 'string', 'in:draft,active,ended'],
        ]);

        if (! empty($data['group_id'])) {
            // Group campaign — only group admins can create.
            $group = Group::query()->findOrFail($data['group_id']);
            if (! $group->isAdmin($request->user())) {
                abort(403, 'Only group admins can create campaigns for this group.');
            }
        } else {
            // Global (Ummah) campaign — only app admins can create.
            if (! $request->user()->is_admin) {
                abort(403, 'Only administrators can create global campaigns.');
            }
        }

        $campaign = Campaign::query()->create([
            'group_id'     => $data['group_id'] ?? null,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'dhikr'        => $data['dhikr'] ?? null,
            'target_count' => $data['target_count'],
            'starts_at'    => $data['starts_at'] ?? null,
            'ends_at'      => $data['ends_at'] ?? null,
            'status'       => $data['status'],
            'created_by'   => $request->user()->id,
        ]);

        $campaign->loadSum('contributions as current_total', 'count');
        $campaign->load('group:id,name', 'creator:id,name');

        return response()->json($campaign, 201);
    }

    /**
     * Update a campaign — ONLY the creator can change dhikr/target/title.
     *
     * Client feedback: members who join a group or campaign should only be
     * able to contribute. Only the original creator may set, change, or
     * lock the dhikr and target.
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->created_by !== $request->user()->id) {
            abort(403, 'Only the creator can edit this campaign.');
        }

        $data = $request->validate([
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'dhikr'        => ['nullable', 'string', 'max:50'],
            'target_count' => ['sometimes', 'required', 'integer', 'min:1'],
            'starts_at'    => ['nullable', 'date'],
            'ends_at'      => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'       => ['sometimes', 'required', 'string', 'in:draft,active,ended'],
        ]);

        $campaign->update($data);

        $campaign->loadSum('contributions as current_total', 'count');
        $campaign->load('group:id,name', 'creator:id,name');

        return response()->json($campaign);
    }

    /**
     * Delete a campaign — creator only.
     */
    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        if ($campaign->created_by !== $request->user()->id) {
            abort(403, 'Only the creator can delete this campaign.');
        }

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted']);
    }

    /**
     * Show a single campaign.
     */
    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        if (! $this->canViewCampaign($request, $campaign)) {
            abort(403);
        }

        $campaign->loadSum('contributions as current_total', 'count');
        $campaign->load('group:id,name', 'creator:id,name');

        return response()->json($campaign);
    }

    /**
     * Leaderboard: top contributors for a campaign (paginated).
     */
    public function leaderboard(Request $request, Campaign $campaign): JsonResponse
    {
        if (! $this->canViewCampaign($request, $campaign)) {
            abort(403);
        }

        $leaders = $campaign->contributions()
            ->selectRaw('user_id, SUM(count) as total_count')
            ->groupBy('user_id')
            ->orderByDesc('total_count')
            ->with('user:id,name')
            ->paginate($request->integer('per_page', 20));

        return response()->json($leaders);
    }

    private function canViewCampaign(Request $request, Campaign $campaign): bool
    {
        if ($campaign->group_id === null) {
            return true;
        }

        $group = $campaign->group ?? Group::query()->find($campaign->group_id);

        return $group && $group->hasMember($request->user());
    }
}
