<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->campaign = Campaign::factory()->create([
            'group_id' => null,
            'status' => 'active',
            'target_count' => 10000,
        ]);
    }

    public function test_user_can_submit_contribution(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $this->campaign->id,
            'count' => 33,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['campaign_id' => $this->campaign->id])
            ->assertJsonStructure(['contribution', 'current_total', 'target_count', 'global_total']);

        $this->assertDatabaseHas('dhikr_contributions', [
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'count' => 33,
        ]);
    }

    public function test_contribution_requires_valid_campaign(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => 99999,
            'count' => 33,
        ]);

        $response->assertStatus(422);
    }

    public function test_contribution_fails_on_inactive_campaign(): void
    {
        $ended = Campaign::factory()->create(['group_id' => null, 'status' => 'ended']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $ended->id,
            'count' => 33,
        ]);

        $response->assertStatus(422);
    }

    public function test_group_campaign_requires_membership(): void
    {
        $group = Group::factory()->create();
        $campaign = Campaign::factory()->create([
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $campaign->id,
            'count' => 33,
        ]);

        $response->assertStatus(403);
    }

    public function test_contribution_updates_current_total(): void
    {
        // First contribution
        $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $this->campaign->id,
            'count' => 100,
        ]);

        // Second contribution
        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $this->campaign->id,
            'count' => 50,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(150, $response->json('current_total'));
    }

    public function test_count_must_be_positive(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dhikr-contributions', [
            'campaign_id' => $this->campaign->id,
            'count' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_stats_endpoint(): void
    {
        DhikrContribution::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'count' => 33,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'total_count', 'today_count', 'this_week_count',
                'this_month_count', 'campaigns_participated', 'current_streak',
            ]);
    }

    public function test_user_contributions_paginated(): void
    {
        DhikrContribution::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/contributions');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }
}
