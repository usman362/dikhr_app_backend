<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a push notification to a specific user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send a push notification to multiple users.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::query()
            ->whereIn('user_id', $userIds)
            ->pluck('token')
            ->toArray();

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send a push notification to all campaign participants.
     */
    public function sendToCampaignParticipants(int $campaignId, string $title, string $body, array $data = []): void
    {
        $userIds = \App\Models\DhikrContribution::query()
            ->where('campaign_id', $campaignId)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        $this->sendToUsers($userIds, $title, $body, $data);
    }

    /**
     * Send via Firebase Cloud Messaging HTTP v1 API.
     */
    private function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey) || empty($tokens)) {
            return;
        }

        foreach (array_chunk($tokens, 500) as $chunk) {
            foreach ($chunk as $token) {
                try {
                    Http::withHeaders([
                        'Authorization' => 'key=' . $serverKey,
                        'Content-Type'  => 'application/json',
                    ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to' => $token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                            'sound' => 'default',
                        ],
                        'data' => $data,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("FCM send failed for token {$token}: {$e->getMessage()}");
                }
            }
        }
    }
}
