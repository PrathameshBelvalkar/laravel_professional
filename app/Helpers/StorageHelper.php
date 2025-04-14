<?php

if (!function_exists('userStorageFilledAlert')) {
    function userStorageFilledAlert($user)
    {
        $userStorageLimitSize = getUserStoragePlan($user);
        $userFilledStorageSize = convertBytesToMB(getFileSize("users/private/$user->id", "folder"));
        $ninetyPercentOfStorageLimit = $userStorageLimitSize * 0.9;

        // Check if the user's filled storage size is 90% or greater than their storage limit
        if ($userFilledStorageSize >= $ninetyPercentOfStorageLimit) return true;
        return false;
    }
}
if (!function_exists('checkUserStorageLimit')) {
    function checkUserStorageLimit($files, $user)
    {
        if (!empty($files)) {
            $files = is_array($files) ? $files : [$files];

            $totalFilesSizeInMb = 0;

            foreach ($files as $fileGroup) {
                $fileGroup = is_array($fileGroup) ? $fileGroup : [$fileGroup];

                foreach ($fileGroup as $file) {
                    $fileSize = $file->getSize();
                    $totalFilesSizeInMb += convertBytesToMB($fileSize);
                }
            }

            $storageUsed = convertBytesToMB(getFileSize("users/private/$user->id", "folder"));
            $storageLimit = getUserStoragePlan($user);
            $availableStorage = $storageLimit - $storageUsed;

            if ($totalFilesSizeInMb > $availableStorage) {
                return true;
            } else {
                return false;
            }
        }
    }
}
