<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_global_campaigns(): void
    {
        Campaign::factory()->count(3)->create([
            'group_id' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/campaigns');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page']);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_filter_campaigns_by_status(): void
    {
        Campaign::factory()->create(['group_id' => null, 'status' => 'active']);
        Campaign::factory()->create(['group_id' => null, 'status' => 'draft']);
        Campaign::factory()->create(['group_id' => null, 'status' => 'ended']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/campaigns?status=active');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_create_campaign(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/campaigns', [
            'title' => 'SubhanAllah 100K',
            'description' => 'Global dhikr campaign',
            'target_count' => 100000,
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'SubhanAllah 100K']);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'SubhanAllah 100K',
            'target_count' => 100000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_user_can_view_campaign_detail(): void
    {
        $campaign = Campaign::factory()->create(['group_id' => null, 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJsonFragment(['title' => $campaign->title]);
    }

    public function test_user_can_view_leaderboard(): void
    {
        $campaign = Campaign::factory()->create(['group_id' => null, 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/campaigns/{$campaign->id}/leaderboard");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_group_campaign_requires_membership(): void
    {
        $group = Group::factory()->create();
        $campaign = Campaign::factory()->create([
            'group_id' => $group->id,
            'status' => 'active',
        ]);

        // User is NOT a member of the group
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }
}
