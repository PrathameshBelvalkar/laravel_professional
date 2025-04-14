<?php

use App\Models\User;

if (!function_exists('addCallNotification')) {
    function addCallNotification($user, $toUserId, $referenceId)
    {
        addNotification($toUserId, $user->id, "Video Call", "user started video call with you", $referenceId, "11", "https://talk.silocloud.io");

    }
}
