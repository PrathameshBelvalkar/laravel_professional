<?php

namespace App\Http\Controllers\API\V1\SiloTalk;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TalkController extends Controller
{
    public function uploadSiloTalkChatFiles(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userSiloTalkFolder = "users/private/{$user->id}/SiloTalk";
            $receiverUserId = $request->input("file_receiver_user_id");

            $receiverUser = User::find($receiverUserId);
            $receiverUserStorageLimitExceeded = checkUserStorageLimit($request->allFiles(), $receiverUser);
            if ($receiverUserStorageLimitExceeded) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Receiver user storage limit exceeded', 'toast' => true]);
            }

            if (!Storage::disk('local')->exists($userSiloTalkFolder)) {
                Storage::disk('local')->makeDirectory($userSiloTalkFolder);
            }

            $uploadedFiles = array();
            foreach ($request->file("files") as $file) {
                $originalFilename = $file->getClientOriginalName();
                $originalFilename = str_replace(['/', "\\", '?', '<', '>', '|', ':', '"', '\''], '', $originalFilename);
                $fileType = $file->getClientOriginalExtension();
                $fileNameWithoutExtension = pathinfo($originalFilename, PATHINFO_FILENAME);

                $newFilename = $originalFilename;

                $files = Storage::files($userSiloTalkFolder);
                $fileExists = collect($files)->contains(function ($value) use ($originalFilename) {
                    return strpos($value, $originalFilename) !== false;
                });

                $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);

                // Count the number of files with the same base filename and extension
                $counter = collect($files)->filter(function ($value) use ($baseFilename, $fileType) {
                    $fileName = pathinfo($value, PATHINFO_FILENAME);
                    return preg_replace('/\(\d+\)$/', '', $fileName) === $baseFilename && pathinfo($value, PATHINFO_EXTENSION) === $fileType;
                })->count();

                while ($fileExists) {
                    $fileNameWithoutExtension = pathinfo($originalFilename, PATHINFO_FILENAME);
                    $newFilename = "{$fileNameWithoutExtension}($counter).{$fileType}";
                    $fileExists = false;
                    $counter++;
                }

                $uploadedFile = $file->storeAs($userSiloTalkFolder, $newFilename);

                // store file on receiver user id folder 
                $this->storeSiloTalkFileOnReceiverUserIdFolder($receiverUserId, $file, $newFilename);

                $uploadedFileData = [];
                $uploadedFileData['file_name'] = $newFilename;
                $uploadedFileData['file_url'] = getFileTemporaryURL($uploadedFile);
                $uploadedFileData['full_path'] =  $uploadedFile;

                $uploadedFiles[] = $uploadedFileData;
            }
            $storageSize = formatFileSize(getFileSize("users/private/$user->id", "folder"), false);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Files Uploaded successfully.', 'toast' => true], ['uploadedFiles' => $uploadedFiles, "storageSize" => $storageSize]);
        } catch (\Exception $e) {
            Log::error('File add error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error uploading the files', 'toast' => true]);
        }
    }
    public function storeSiloTalkFileOnReceiverUserIdFolder($receiverUserId, $file, $fileName)
    {
        $sharedWithUserSiloTalkFolder = "users/private/{$receiverUserId}/SiloTalk";

        if (!Storage::disk('local')->exists($sharedWithUserSiloTalkFolder)) {
            Storage::disk('local')->makeDirectory($sharedWithUserSiloTalkFolder);
        }
        $file->storeAs($sharedWithUserSiloTalkFolder, $fileName);
    }
    public function addNotification(Request $request)
    {
        $user = $request->attributes->get("user");
        $receiverUserId = $request->receiver_user_id;

        addCallNotification($user, $receiverUserId, "11");
    }
}
