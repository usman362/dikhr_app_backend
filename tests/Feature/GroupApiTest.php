<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_group(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/groups', [
            'name' => 'Dhikr Circle',
            'description' => 'Daily dhikr group',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Dhikr Circle']);

        $this->assertDatabaseHas('groups', ['name' => 'Dhikr Circle']);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $response->json('id'),
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }

    public function test_free_user_limited_to_3_groups(): void
    {
        // Create 3 groups
        for ($i = 1; $i <= 3; $i++) {
            Group::factory()->create(['created_by' => $this->user->id]);
        }

        $response = $this->actingAs($this->user)->postJson('/api/v1/groups', [
            'name' => 'Fourth Group',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['upgrade_required' => true]);
    }

    public function test_premium_user_can_create_unlimited_groups(): void
    {
        $this->user->update(['is_premium' => true]);

        for ($i = 1; $i <= 3; $i++) {
            Group::factory()->create(['created_by' => $this->user->id]);
        }

        $response = $this->actingAs($this->user)->postJson('/api/v1/groups', [
            'name' => 'Fourth Group',
        ]);

        $response->assertStatus(201);
    }

    public function test_user_can_list_their_groups(): void
    {
        $group = Group::factory()->create();
        $group->users()->attach($this->user->id, ['role' => 'member']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/groups');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_user_can_join_group(): void
    {
        $group = Group::factory()->create();

        $response = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertStatus(201);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_leave_group(): void
    {
        $group = Group::factory()->create();
        $group->users()->attach($this->user->id, ['role' => 'member']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/leave");

        $response->assertOk();
        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_creator_cannot_leave_group(): void
    {
        $group = Group::factory()->create(['created_by' => $this->user->id]);
        $group->users()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/groups/{$group->id}/leave");

        $response->assertStatus(422);
    }

    public function test_user_can_search_groups(): void
    {
        Group::factory()->create(['name' => 'SubhanAllah Circle']);
        Group::factory()->create(['name' => 'Alhamdulillah Group']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/groups/search?q=SubhanAllah');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_update_group(): void
    {
        $group = Group::factory()->create(['created_by' => $this->user->id]);
        $group->users()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_non_admin_cannot_update_group(): void
    {
        $group = Group::factory()->create();
        $group->users()->attach($this->user->id, ['role' => 'member']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }
}
