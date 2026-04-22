<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all event broadcasting channels that your application supports.
| Campaign channels are public so all authenticated users can listen.
| The global dhikr channel is also public for real-time counter updates.
|
*/

// Campaign-specific channel — public (any authenticated user can listen)
Broadcast::channel('campaign.{campaignId}', function ($user, $campaignId) {
    return $user !== null;
});

// Global dhikr counter channel — public
Broadcast::channel('dhikr.global', function ($user) {
    return $user !== null;
});
