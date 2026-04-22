<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SeedTestData extends Command
{
    protected $signature = 'app:seed-test-data {--fresh : Remove existing test data before seeding}';

    protected $description = 'Seed realistic test data: users, campaigns, groups, and dhikr contributions';

    private array $names = [
        'Ahmad Hassan',
        'Fatima Zahra',
        'Omar Farooq',
        'Aisha Rahman',
        'Yusuf Ali',
        'Khadijah Noor',
        'Ibrahim Malik',
        'Maryam Siddiqui',
        'Bilal Khan',
        'Hafsa Begum',
        'Zaid Ahmed',
        'Sumaya Patel',
        'Hamza Sheikh',
        'Safiya Qureshi',
        'Ismail Hussain',
        'Ruqayyah Javed',
        'Abdullah Mirza',
        'Amina Chowdhury',
        'Tariq Aziz',
        'Layla Mahmoud',
    ];

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->cleanup();
        }

        $users     = $this->createUsers();
        $campaigns = $this->createCampaigns($users);
        $groups    = $this->createGroups($users);

        $contributionCount = $this->createContributions($users, $campaigns, $groups);

        Cache::forget('global_stats');

        $this->info('');
        $this->info("Created {$users->count()} test users");
        $this->info('Created ' . count($campaigns) . ' global campaigns');
        $this->info('Created ' . count($groups) . ' groups with members');
        $this->info("Generated {$contributionCount} dhikr contributions over the past 7 days");
        $this->info('Cache cleared. Test data is ready!');

        return self::SUCCESS;
    }

    private function cleanup(): void
    {
        $this->info('Cleaning up existing test data...');

        $testUserIds = User::query()
            ->where('email', 'like', '%@testdhikr.com')
            ->pluck('id');

        if ($testUserIds->isEmpty()) {
            $this->info('No existing test data found.');
            return;
        }

        DhikrContribution::query()->whereIn('user_id', $testUserIds)->delete();
        Campaign::query()->whereIn('created_by', $testUserIds)->delete();

        $groupIds = Group::query()->whereIn('created_by', $testUserIds)->pluck('id');
        if ($groupIds->isNotEmpty()) {
            \DB::table('group_user')->whereIn('group_id', $groupIds)->delete();
            Group::query()->whereIn('id', $groupIds)->delete();
        }

        User::query()->whereIn('id', $testUserIds)->delete();

        $this->info("Removed {$testUserIds->count()} test users and related data.");
    }

    private function createUsers(): \Illuminate\Support\Collection
    {
        $users = collect();

        foreach ($this->names as $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@testdhikr.com';

            $users->push(
                User::query()->firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $name,
                        'password' => 'password',
                        'is_admin' => false,
                    ]
                )
            );
        }

        return $users;
    }

    private function createCampaigns(\Illuminate\Support\Collection $users): array
    {
        $creator = $users->first();

        $campaignData = [
            [
                'title'        => 'Ramadan 2026 — 1 Million SubhanAllah',
                'description'  => 'Let us come together as an Ummah to complete 1 million SubhanAllah during this blessed month.',
                'target_count' => 1_000_000,
            ],
            [
                'title'        => "Jumu'ah Dhikr Challenge",
                'description'  => 'A weekly challenge to increase our collective remembrance of Allah on the best day of the week.',
                'target_count' => 100_000,
            ],
            [
                'title'        => '100K Astaghfirullah',
                'description'  => 'Seek forgiveness together — 100,000 Astaghfirullah as a community.',
                'target_count' => 100_000,
            ],
            [
                'title'        => 'Global La ilaha illallah',
                'description'  => 'Unite the Ummah in the declaration of Tawheed — half a million La ilaha illallah.',
                'target_count' => 500_000,
            ],
        ];

        $campaigns = [];

        foreach ($campaignData as $data) {
            $campaigns[] = Campaign::query()->firstOrCreate(
                ['title' => $data['title']],
                [
                    'group_id'     => null,
                    'description'  => $data['description'],
                    'target_count' => $data['target_count'],
                    'status'       => 'active',
                    'created_by'   => $creator->id,
                    'starts_at'    => Carbon::now()->subDays(7),
                    'ends_at'      => Carbon::now()->addDays(30),
                ]
            );
        }

        return $campaigns;
    }

    private function createGroups(\Illuminate\Support\Collection $users): array
    {
        $groupData = [
            [
                'name'        => 'Masjid Al-Noor Community',
                'description' => 'Daily dhikr circle for the Al-Noor congregation.',
                'creator'     => $users[0],
                'members'     => $users->slice(1, 9)->values(),
            ],
            [
                'name'        => 'Sisters Circle of Dhikr',
                'description' => 'A supportive space for sisters to track and encourage dhikr together.',
                'creator'     => $users[1],
                'members'     => $users->slice(3, 7)->values(),
            ],
        ];

        $groups = [];

        foreach ($groupData as $data) {
            $group = Group::query()->firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'created_by'  => $data['creator']->id,
                ]
            );

            // Attach creator as admin
            if (! $group->hasMember($data['creator'])) {
                $group->users()->attach($data['creator']->id, ['role' => 'admin']);
            }

            // Attach members
            foreach ($data['members'] as $member) {
                if (! $group->hasMember($member)) {
                    $group->users()->attach($member->id, ['role' => 'member']);
                }
            }

            // Create a group campaign
            $groupCampaign = Campaign::query()->firstOrCreate(
                ['title' => "{$data['name']} Daily Dhikr", 'group_id' => $group->id],
                [
                    'description'  => "Daily dhikr goal for {$data['name']}",
                    'target_count' => 10_000,
                    'status'       => 'active',
                    'created_by'   => $data['creator']->id,
                    'starts_at'    => Carbon::now()->subDays(7),
                    'ends_at'      => Carbon::now()->addDays(30),
                ]
            );

            $groups[] = ['group' => $group, 'campaign' => $groupCampaign, 'members' => $data['members']->push($data['creator'])];
        }

        return $groups;
    }

    private function createContributions(\Illuminate\Support\Collection $users, array $campaigns, array $groups): int
    {
        $counts    = [33, 33, 33, 33, 99, 100, 500, 1000];
        $total     = 0;
        $batchSize = 200;
        $rows      = [];

        // Global campaign contributions
        foreach ($campaigns as $campaign) {
            $numContributions = rand(50, 100);

            for ($i = 0; $i < $numContributions; $i++) {
                $rows[] = [
                    'user_id'     => $users->random()->id,
                    'campaign_id' => $campaign->id,
                    'count'       => $counts[array_rand($counts)],
                    'created_at'  => Carbon::now()->subMinutes(rand(1, 10080)),
                    'updated_at'  => Carbon::now(),
                ];

                if (count($rows) >= $batchSize) {
                    DhikrContribution::query()->insert($rows);
                    $total += count($rows);
                    $rows = [];
                }
            }
        }

        // Group campaign contributions
        foreach ($groups as $groupData) {
            $campaign = $groupData['campaign'];
            $members  = $groupData['members'];
            $numContributions = rand(20, 40);

            for ($i = 0; $i < $numContributions; $i++) {
                $rows[] = [
                    'user_id'     => $members->random()->id,
                    'campaign_id' => $campaign->id,
                    'count'       => $counts[array_rand($counts)],
                    'created_at'  => Carbon::now()->subMinutes(rand(1, 10080)),
                    'updated_at'  => Carbon::now(),
                ];

                if (count($rows) >= $batchSize) {
                    DhikrContribution::query()->insert($rows);
                    $total += count($rows);
                    $rows = [];
                }
            }
        }

        // Flush remaining
        if (! empty($rows)) {
            DhikrContribution::query()->insert($rows);
            $total += count($rows);
        }

        return $total;
    }
}
