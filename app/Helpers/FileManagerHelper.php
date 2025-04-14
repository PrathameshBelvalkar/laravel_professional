<?php

use App\Mail\SendMail;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Subscription\Service;
use App\Models\FileManager\FileManager;
use Illuminate\Support\Facades\Storage;
use App\Models\Subscription\UserServiceSubscription;
use App\Models\UserProfile;

if (!function_exists('fileDetails')) {
    function fileDetails($files, $user, $type = null, $additionalKeys = array())
    {
        $fileDetails = collect($files)->map(function ($file) use ($type, $additionalKeys, $user) {
            $fileId = explode("_", basename($file))[0];

            if (count($additionalKeys) <= 0) {
                $dbEntry = FileManager::where('file_id', $fileId)->first();
                if ($dbEntry) {
                    $additionalKeys['is_starred'] = $dbEntry->is_star;
                    if ($dbEntry->is_deleted == "1")
                        return;
                    $additionalKeys['is_shared'] = $dbEntry->is_shared;

                    $sharedUserIds = json_decode($dbEntry->shared_with);
                    if (!empty($sharedUserIds)) {
                        $additionalKeys['shared_users'] = getUsersInfoForSharedFile($sharedUserIds);
                    }
                    if ($dbEntry->user_id == $user->id) {
                        $additionalKeys['owner'] = "Me";
                    } else {
                        $owner = User::find($dbEntry->user_id);
                        $additionalKeys['owner'] = $owner->username;
                    }
                } else {
                    $additionalKeys['is_starred'] = "0";
                    $additionalKeys['is_shared'] = "0";
                }
                $additionalKeys['is_deleted'] = false;
            }

            $fileNameExploded = explode("_", basename($file));

            $sliceOffset = 1;

            $createdAtTimestamp = Storage::disk('local')->lastModified($file);
            $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;


            $fileNameArray = array_slice($fileNameExploded, $sliceOffset);
            $fileName = implode("_", $fileNameArray);

            if ($type == "folder") {
                $totalSize = formatFileSize(getFileSize($file, "folder"));
            }

            $filePath = makePath($file, 5, "file-manager");
            $fileType = pathinfo($file, PATHINFO_EXTENSION);

            $fileDetails = [
                'id' => $fileId,
                'name' => $fileName,
                'path' => $filePath,
                'full_path' => $file,
                'size' => $type == "folder" ? $totalSize : formatFileSize(Storage::disk('local')->size($file)),
                'ext' => $type == "folder" ? $type : $fileType,
                'icon' => $type == "folder" ? $type : $fileType,
                'type' => $type == "folder" ? $type : $fileType,
                'owner' => "Me",
                'date' => $createdAt,
            ];
            if (count($additionalKeys) > 0) {
                foreach ($additionalKeys as $key => $value) {
                    $fileDetails[$key] = $value;
                }
            }
            if ($type != "folder") {
                $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

                $fileDetails['file_url'] = getFileTemporaryURL($file);
                $fileDetails['is_image'] = checkFileIsImageOrNot($file);
                $fileDetails['name_without_ext'] = $fileNameWithoutExtension;
                $fileDetails['is_media'] = checkFileIsMediaOrNot($file);
                $fileDetails['is_document'] = checkFileIsDocumentOrNot($file);
                $fileDetails['is_text'] = checkFileIsTextOrNot($file);
            }
            return $fileDetails;
        });

        return $fileDetails;
    }
}
if (!function_exists('formatFileSize')) {
    function formatFileSize($sizeInBytes, $singleText = true)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $sizeInBytes > 1024; $i++) {
            $sizeInBytes /= 1024;
        }
        $totalSize = floor($sizeInBytes * 100) / 100;
        $totalSizeWithText = $totalSize . ' ' . $units[$i];

        if ($singleText)
            return $totalSizeWithText;
        else
            return ["text" => $totalSizeWithText, "value" => $totalSize, "unit" => $units[$i]];
    }
}
if (!function_exists('makePath')) {
    function makePath($path, $sliceOffset, $type = null)
    {
        $path = explode('/', $path);
        $pathArray = array_slice($path, $sliceOffset);
        $path = array_slice($pathArray, 0, -1);

        if (count($pathArray) > 1) {
            if ($type == "file-manager") {
                foreach ($path as $key => $item) {
                    $itemExploded = explode("_", $item);
                    $path[$key] = implode("_", array_slice($itemExploded, 1));
                }
            }
            $path = implode("/", $path);
        } else {
            $path = null;
        }
        return $path;
    }
}
if (!function_exists('updateNestedPaths')) {
    function updateNestedPaths($fileId, $newPath)
    {
        // Get all files and folders within the current folder
        $item = FileManager::where("file_id", $fileId)->first();

        if ($item) {
            // Update the path of the item
            $item->file_path = $newPath;
            $item->save();
        }
    }
}
if (!function_exists('fetchDirectoryFolders')) {
    function fetchDirectoryFolders($folderFullPath, $user)
    {
        $folders = Storage::disk('local')->directories($folderFullPath);

        $allFolders = collect($folders)->map(function ($folder) {
            $folderNameExploded = explode("_", basename($folder));
            $dbEntry = FileManager::where('file_id', $folderNameExploded[0])->first();
            if ($dbEntry && $dbEntry->is_deleted == "1") {
                return;
            }

            $folderId = explode("_", basename($folder))[0];

            $folderNameArray = array_slice($folderNameExploded, 1);
            $folderName = implode("_", $folderNameArray);

            return [
                'id' => $folderId,
                'name' => $folderName,
                'full_path' => $folder,
                'type' => 'folder',
                'icon' => 'folder',
            ];
        })->filter();
        return $allFolders;
    }
}
if (!function_exists('getFileSize')) {
    function getFileSize($path, $type = NULL, $disk = 'local')
    {
        $totalFileSizeBytes = 0;
        if ($type == "folder") {
            $files = Storage::disk($disk)->allFiles($path);
            $excludedFolders = config('app.folders_to_avoid_in_storage');

            foreach ($files as $file) {
                $shouldExclude = false;
                if (count($excludedFolders) > 0) {
                    $relativePath = str_replace($path . '/', '', $file);
                    $folderToExclude = explode('/', $relativePath)[0];

                    if (in_array($folderToExclude, $excludedFolders)) {
                        $shouldExclude = true;
                    }
                }
                if (!$shouldExclude) {
                    $totalFileSizeBytes += Storage::disk($disk)->size($file);
                }
            }
            return $totalFileSizeBytes;
        }
        return $totalFileSizeBytes = Storage::disk($disk)->size($path);
    }
}
if (!function_exists('getUserStoragePlan')) {
    function getUserStoragePlan($user)
    {
        $storageService = Service::where("key", "storage")->first();
        $userStorageSubscription = UserServiceSubscription::where("user_id", $user->id)->where("service_id", $storageService->id)->first();

        $storageLimit = $user->storage;
        if ($userStorageSubscription && $userStorageSubscription != NULL) {
            $userStorageData = json_decode(json_decode($userStorageSubscription['service_plan_data'])->features)->storage;
            $storageLimit = $userStorageData->value;
            if ($userStorageData->unit == "GB") {
                $storageLimit = convertGBToMB($userStorageData->value);
            }
        }
        return $storageLimit;
    }
}
if (!function_exists('convertGBToMB')) {
    function convertGBToMB($sizeInGB)
    {
        return round($sizeInGB * 1024, 2);
    }
}
if (!function_exists('convertBytesToMB')) {
    function convertBytesToMB($sizeInBytes)
    {
        return round($sizeInBytes / (1024 * 1024), 2);
    }
}
if (!function_exists('getUsersInfoForSharedFile')) {
    function getUsersInfoForSharedFile($userIds)
    {
        $userList = [];
        foreach ($userIds as $userId) {
            $user = User::where('id', $userId)->first();
            $userList[] = ['value' => $user->id, 'label' => $user->username];
        }
        return $userList;
    }
}
if (!function_exists('fileDetailsForApps')) {
    function fileDetailsForApps($files, $type = null, $foldersToFilter = [])
    {
        $appsFileDetails = collect($files)->map(function ($file) use ($type, $foldersToFilter) {
            if (count($foldersToFilter) > 0 && in_array(basename($file), $foldersToFilter))
                return;

            $createdAtTimestamp = Storage::disk('local')->lastModified($file);
            $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;

            if ($type == "folder") {
                $totalSize = formatFileSize(getFileSize($file, "folder"));
            } else {
                $fileType = pathinfo($file, PATHINFO_EXTENSION);
            }
            $filePath = makePath($file, 3);

            $fileDetails = [
                'id' => Str::uuid(),
                'name' => basename($file),
                'path' => $filePath,
                'full_path' => $file,
                'size' => $type == "folder" ? $totalSize : formatFileSize(Storage::disk('local')->size($file)),
                'ext' => $type == "folder" ? $type : $fileType,
                'icon' => $type == "folder" ? $type : $fileType,
                'type' => $type == "folder" ? $type : $fileType,
                'owner' => "Me",
                'date' => $createdAt,

            ];

            if ($type != "folder") {
                $fileDetails['file_url'] = getFileTemporaryURL($file);
                $fileDetails['is_image'] = checkFileIsImageOrNot($file);
                $fileDetails['is_media'] = checkFileIsMediaOrNot($file);
                $fileDetails['is_document'] = checkFileIsDocumentOrNot($file);
                $fileDetails['is_text'] = checkFileIsTextOrNot($file);
            }

            return $fileDetails;
        });

        return $appsFileDetails;
    }
}
if (!function_exists('checkFileIsMediaOrNot')) {
    function checkFileIsMediaOrNot($file)
    {
        $mimeType = Storage::disk('local')->mimeType($file);

        $videoMimeTypes = [
            'video/avi',
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-flv',
            'video/x-matroska',
            'video/webm',
            'video/x-m4v',
            'video/3gpp',
            'video/3gpp2',
            'application/vnd.apple.mpegurl',
            'application/x-mpegURL',
        ];

        $audioMimeTypes = [
            'audio/mpeg',
            'audio/mp3',
            'audio/ogg',
            'audio/wav',
            'audio/x-wav',
            'audio/x-m4a',
            'audio/flac',
            'audio/aac',
            'audio/x-ms-wma',
        ];

        if (strpos($mimeType, 'audio/') === 0 || strpos($mimeType, 'video/') === 0 || in_array($mimeType, $videoMimeTypes) || in_array($mimeType, $audioMimeTypes)) {
            return true;
        }

        return false;
    }
}
if (!function_exists('checkFileIsDocumentOrNot')) {
    function checkFileIsDocumentOrNot($file)
    {
        if ((strpos(Storage::disk('local')->mimeType($file), 'application/pdf') === 0)) {
            return true;
        }
        return false;
    }
}
if (!function_exists('checkFileIsTextOrNot')) {
    function checkFileIsTextOrNot($file)
    {
        if ((strpos(Storage::disk('local')->mimeType($file), 'text/plain') === 0)) {
            return true;
        }
        return false;
    }
}
if (!function_exists('checkFileIsImageOrNot')) {
    function checkFileIsImageOrNot($file)
    {
        if (strpos(Storage::disk('local')->mimeType($file), 'image/') === 0) {
            return true;
        }
        return false;
    }
}
if (!function_exists('sendFileSharedEmail')) {
    function sendFileSharedEmail($fromUser, $toUser, $storageService)
    {
        $data['projectName'] = config("app.app_name");
        $data['supportMail'] = config("app.support_mail");
        $data['subject'] = $data['title'] = "Storage File Share";
        $data['view'] = "mail-templates.file-share";
        $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
        $data['username'] = $toUser->username;
        $data['message'] = "$fromUser->username shared a file with you, you can see here: ";
        $data['storageShareLink'] = $storageService->link . "shared";
        $data['linkTitle'] = config('app.app_name') . " Storage";

        Mail::to($toUser->email)->send(new SendMail($data, $data['view']));
    }
}
if (!function_exists('sendFileSharedSMS')) {
    function sendFileSharedSMS($fromUser, $toUser, $storageService)
    {
        $supportMail = config("app.support_mail");
        $message = "Hi $toUser->username, \n$fromUser->username shared a file with you, you can see here " . $storageService->link . "shared" . "\nHope you'll enjoy the experience, we're here if you have any questions, drop us a line at $supportMail";

        $toUserProfile = UserProfile::where('user_id', $toUser->id)->first();
        if (!empty($toUserProfile)) {
            $toUserCountry = Country::where("id", $toUserProfile->country)->first();
            if (isset($toUserCountry->phonecode)) {
                $phone_number = "+" . $toUserCountry->phonecode . $toUserProfile->phone_number;
                send_sms($phone_number, $message);
            }
        }
    }
}
if (!function_exists('sendAllNotificationsToShareFileUsers')) {
    function sendAllNotificationsToShareFileUsers($user, $userIds, $storageService, $referenceId)
    {
        foreach ($userIds as $userId) {
            addNotification($userId, $user->id, "Storage File Share", "Storage file has been shared with you", $referenceId, "7", "https://storage." . strtolower(config('app.app_name')) . ".io/shared");

            $fileSharedUser = User::find($userId);
            sendFileSharedEmail($user, $fileSharedUser, $storageService);
            // sendFileSharedSMS($user, $fileSharedUser, $storageService);

        }
    }
}
