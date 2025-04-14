<?php

namespace App\Http\Controllers\API\V1\FileManager;

use App\Models\FileManager\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagerController extends Controller
{
    public function add(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userFolder = "users/private/{$user->id}/FileManager/default";

            if ($request->has("parent_folder_path")) {
                $userFolder = $request->parent_folder_path;
            }

            if (!Storage::disk('local')->exists($userFolder)) {
                Storage::disk('local')->makeDirectory($userFolder);
            }

            $uploadedFiles = array();
            foreach ($request->file('files') as $file) {
                $uniqueIdentifier = Str::uuid()->toString();
                $originalFilename = $file->getClientOriginalName();
                $originalFilename = str_replace(['/', "\\", '?', '<', '>', '|', ':', '"', '\''], '', $originalFilename);
                $fileSize = $file->getSize();
                $fileType = $file->getClientOriginalExtension();
                $fileNameWithoutExtension = pathinfo($originalFilename, PATHINFO_FILENAME);

                $newFilename = "{$uniqueIdentifier}_{$originalFilename}";

                $files = Storage::files($userFolder);
                $fileExists = collect($files)->contains(function ($value, $key) use ($originalFilename) {
                    return strpos($value, $originalFilename) !== false;
                });

                $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);

                // Count the number of files with the same base filename and extension
                $sliceOffset = 1;
                $counter = collect($files)->filter(function ($value, $key) use ($baseFilename, $fileType, $sliceOffset) {
                    $fileNameWithoutExtension = explode("_", pathinfo($value, PATHINFO_FILENAME));
                    $fileNameArray = array_slice($fileNameWithoutExtension, $sliceOffset);
                    $fileName = implode("_", $fileNameArray);
                    return preg_replace('/\(\d+\)$/', '', $fileName) === $baseFilename && pathinfo($value, PATHINFO_EXTENSION) === $fileType;
                })->count();

                while ($fileExists) {
                    $fileNameWithoutExtension = pathinfo($originalFilename, PATHINFO_FILENAME);
                    $newFilename = "{$uniqueIdentifier}_{$fileNameWithoutExtension}($counter).{$fileType}";
                    $fileExists = false;
                    $originalFilename = "{$baseFilename}($counter).{$fileType}";
                    $counter++;
                }

                $uploadedFile = $file->storeAs($userFolder, $newFilename);

                $createdAtTimestamp = $file->getCTime();
                $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;

                $uploadedFileData = [];
                $uploadedFileData['id'] = $uniqueIdentifier;
                $uploadedFileData['name'] =  $originalFilename;
                $uploadedFileData['name_without_ext'] =  $fileNameWithoutExtension;
                $uploadedFileData['path'] =  makePath($uploadedFile, 5, "file-manager");
                $uploadedFileData['full_path'] =  $uploadedFile;
                $uploadedFileData['size'] =  formatFileSize($fileSize);
                $uploadedFileData['ext'] = $fileType;
                $uploadedFileData['icon'] =  $fileType;
                $uploadedFileData['type'] =  $fileType;
                $uploadedFileData['is_starred'] =  "0";
                $uploadedFileData['is_shared'] =  "0";
                $uploadedFileData['is_deleted'] =  false;
                $uploadedFileData['owner'] =  "Me";
                $uploadedFileData['date'] =  $createdAt;
                $uploadedFileData['file_url'] = getFileTemporaryURL($uploadedFile);
                $uploadedFileData['is_image'] = checkFileIsImageOrNot($uploadedFile);
                $uploadedFileData['is_media'] = checkFileIsMediaOrNot($uploadedFile);
                $uploadedFileData['is_document'] = checkFileIsDocumentOrNot($uploadedFile);
                $uploadedFileData['is_text'] = checkFileIsTextOrNot($uploadedFile);

                $uploadedFiles[] = $uploadedFileData;
            }
            $storageSize = formatFileSize(getFileSize("users/private/$user->id", "folder"), false);
            $storageFilledAlert = userStorageFilledAlert($user);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Files Uploaded successfully.', 'toast' => true], ['uploadedFiles' => $uploadedFiles, "storageSize" => $storageSize, "storageFilledAlert" => $storageFilledAlert]);
        } catch (\Exception $e) {
            Log::error('File add error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error uploading the files', 'toast' => true]);
        }
    }
    public function fetchFiles(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $userId = $user->id;
            $folderPath = "users/private/$userId/FileManager/default";
            if ($request->has('folder_path')) {
                $folderPath = $request->folder_path;
            }

            $files = Storage::files($folderPath);
            $directories = Storage::directories($folderPath);

            $fileDetails = fileDetails($files, $user);
            $folderDetails = fileDetails($directories, $user, "folder");

            $formattedFilesLength = $fileDetails->filter()->count() + $folderDetails->filter()->count();
            $isMoreFilesAvailable = false;
            if (($request->offset + 12) < $formattedFilesLength) {
                $isMoreFilesAvailable = true;
            }

            $limitedFormattedFiles = array_slice(array_merge($fileDetails->filter()->toArray(), $folderDetails->filter()->toArray()), $request->offset ? $request->offset : 0, 12);


            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Files fetched successfully.', 'toast' => true], ['filesData' => $limitedFormattedFiles, "folderList" => $folderDetails->filter()->toArray(), 'isMoreFilesAvailable' => $isMoreFilesAvailable]);
        } catch (\Exception $e) {
            Log::info('File add error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error Fetching the files', 'toast' => true]);
        }
    }
    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $fileDeleteType = $request->input("file_delete_type");
            $user = $request->attributes->get('user');
            $updatedStorageSize = false;

            $filesToDelete = $request->input("files");
            foreach ($filesToDelete as $file) {
                $fileType = $file["file_type"];
                $filePath = $file["file_path"];
                $fileName = $file["file_name"];
                $fileId = $file["file_id"];
                $fileSize = $file["file_size"];
                $fileCreationDate = $file["file_creation_date"];

                if (!Storage::disk('local')->exists($filePath)) {
                    DB::rollBack();
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'selected items not found', 'toast' => true]);
                } else {
                    $storageFilledAlert = false;
                    $fileEntry = FileManager::where('user_id', $user->id)->where("file_id", $fileId)->first();
                    if ($fileDeleteType == "temporary") {
                        if ($fileEntry) {
                            $fileEntry->is_deleted = "1";
                            $fileEntry->save();
                        } else {
                            $newFile = new FileManager();
                            $newFile->user_id = $user->id;
                            $newFile->file_id = $fileId;
                            $newFile->file_path = $filePath;
                            $newFile->file_name = $fileName;
                            $newFile->file_size = $fileSize;
                            $newFile->file_type = $fileType;
                            $newFile->is_deleted = "1";
                            $newFile->file_creation_date = $fileCreationDate;
                            $newFile->save();
                        }
                    } else {
                        if ($fileType == "folder") {
                            $allFiles = Storage::allFiles($filePath);
                            $allDirectories = Storage::allDirectories($filePath);
                            $allFoldersAndDirectories = array_merge($allDirectories, $allFiles);
                            foreach ($allFoldersAndDirectories as $nestedFile) {
                                $nestedFileId = explode("_", basename($nestedFile))[0];
                                $nestedFileDbEntry = FileManager::where("file_id", $nestedFileId)->first();
                                if ($nestedFileDbEntry) $nestedFileDbEntry->delete();
                            }
                            Storage::disk('local')->deleteDirectory($filePath);
                        } else {
                            Storage::delete($filePath);
                        }
                        $fileEntry->delete();
                    }
                    $storageFilledAlert = userStorageFilledAlert($user);
                    DB::commit();
                }
            };
            $updatedStorageSize = formatFileSize(getFileSize("users/private/$user->id", "folder"), false);
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Selected items have been successfully deleted", 'toast' => true], ["updatedStorageSize" => $updatedStorageSize, "storageFilledAlert" => $storageFilledAlert]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('File delete error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting selected items', 'toast' => true]);
        }
    }
    public function restore(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $filesToDelete = $request->input("files");
            foreach ($filesToDelete as $file) {
                $fileId = $file['file_id'];
                $fileFullPath = $file['file_path'];

                if (!Storage::disk('local')->exists($fileFullPath)) {
                    DB::rollBack();
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Selected items not found to restore', 'toast' => true]);
                } else {
                    $dbFileEntry = FileManager::where('user_id', $user->id)->where("file_id", $fileId)->where("is_deleted", "1")->first();
                    $dbFileEntry->is_deleted = "0";
                    $dbFileEntry->save();
                    DB::commit();
                }
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Items restored successfully", 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('File delete error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error restoring items', 'toast' => true]);
        }
    }
    public function fetchDelete(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $offset = $request->offset ?? 0;
            $isMoreFilesAvailable = false;

            $files = FileManager::where("is_deleted", "1")
                ->where("user_id", $user->id)
                ->offset($offset)
                ->limit(12)
                ->get();
            DB::commit();
            if ($files->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No deleted items found', 'toast' => true]);
            }

            $deletedFiles = $files->map(function ($file) {
                $createdAtTimestamp = Storage::disk('local')->lastModified($file->file_path);
                $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;

                $fileDetails = [
                    'id' => $file->file_id,
                    'name' => $file->file_name,
                    'path' => makePath($file->file_path, 5, "file-manager"),
                    'full_path' => $file->file_path,
                    'size' => $file->file_size,
                    'ext' => $file->file_type,
                    'icon' => $file->file_type,
                    'type' => $file->file_type,
                    'is_deleted' => $file->is_deleted == "1" ? true : false,
                    'is_starred' => $file->is_star == "1" ? true : false,
                    'date' => $file->file_creation_date,
                    'owner' => "Me",
                    'deleted_at' => $createdAt,
                ];

                if ($file->file_type != "folder") {
                    $fileDetails['file_url'] = getFileTemporaryURL($file->file_path);
                    $fileDetails['is_image'] = checkFileIsImageOrNot($file->file_path);
                    $fileDetails['is_media'] = checkFileIsMediaOrNot($file->file_path);
                    $fileDetails['is_document'] = checkFileIsDocumentOrNot($file->file_path);
                    $fileDetails['is_text'] = checkFileIsTextOrNot($file->file_path);
                }
                return $fileDetails;
            });

            if (count($deletedFiles) >= 12) {
                $isMoreFilesAvailable = true;
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Deleted items fetched successfully', 'toast' => true], ['deletedFiles' => $deletedFiles, "isMoreFilesAvailable" => $isMoreFilesAvailable]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Get deleted files error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting the deleted the items', 'toast' => true]);
        }
    }
    public function createFolder(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $folderName = $request->input('folder_name');
            $folderName = str_replace(['/', "\\", '?', '<', '>', '|', ':', '"', '\'', '.'], '', $folderName);

            $uniqueIdentifier = Str::uuid()->toString();

            $folderPath = "users/private/{$user->id}/FileManager/default";

            $userFolderPath = "{$folderPath}/{$uniqueIdentifier}_{$folderName}";
            if ($request->has("parent_folder_path")) {
                $folderPath = $request->parent_folder_path;
                $userFolderPath = "{$folderPath}/{$uniqueIdentifier}_{$folderName}";
            }

            $directories = Storage::directories($folderPath);

            $folderExists = collect($directories)->filter(function ($value) use ($folderName) {
                $traversedFolderNameExploded = explode("_", pathinfo($value, PATHINFO_FILENAME));
                $folderId = array_shift($traversedFolderNameExploded);
                $traversedFolderName = implode("_", $traversedFolderNameExploded);

                if (preg_replace('/\(\d+\)$/', '', $traversedFolderName) === $folderName) {
                    $folderEntryInDb = FileManager::where("file_id", $folderId)->where("is_deleted", "1")->first();
                    if ($folderEntryInDb && $folderEntryInDb != NULL) return false;
                    return true;
                }
            })->count();

            if (!$folderExists) {
                Storage::disk('local')->makeDirectory($userFolderPath);

                $createdAt = Carbon::now()->toDateString();

                $folderPath = makePath($userFolderPath, 5, "file-manager");

                $createdFolderInfo = [];
                $createdFolderInfo['id'] = $uniqueIdentifier;
                $createdFolderInfo['name'] = $folderName;
                $createdFolderInfo['path'] = $folderPath;
                $createdFolderInfo['full_path'] = $userFolderPath;
                $createdFolderInfo['size'] = 0;
                $createdFolderInfo['ext'] = 'folder';
                $createdFolderInfo['icon'] = 'folder';
                $createdFolderInfo['type'] = 'folder';
                $createdFolderInfo['is_starred'] =  "0";
                $createdFolderInfo['is_deleted'] =  false;
                $createdFolderInfo['owner'] =  "Me";
                $createdFolderInfo['date'] = $createdAt;

                DB::commit();

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Folder created successfully.', 'toast' => true], ['folderData' => $createdFolderInfo]);
            } else {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Folder already exists.', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Folder creation error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error creating the folder', 'toast' => true]);
        }
    }
    public function move(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            if ($request->has('destination_folder_data') && isset($request->destination_folder_data['path']) && isset($request->destination_folder_data['id'])) {
                $destinationPath = $request->input('destination_folder_data')['path'];
                $destinationFolderId = $request->input('destination_folder_data')['id'];
            } else {
                $destinationPath = "users/private/{$user->id}/FileManager/default/";
                $destinationFolderId = NULL;
            }
            $operationType = $request->input('operation_type');
            $files = $request->input('files');

            $index = 0;
            $operationFileData = [];
            $message = "moved";
            $totalCopyFileSizeInBytes = 0;
            $userOccupiedStorageSizeInBytes = getFileSize("users/private/$user->id", "folder");
            $userStoragePlanInMB = getUserStoragePlan($user);
            $updatedStorageSize = false;

            foreach ($files as $file) {
                $fileId = $file['file_id'];
                $fileName = $file['file_name'];
                $fileType = $file['file_type'];
                $sourcePath = $file['source_path'];
                $fileFullName = "{$fileId}_{$fileName}";

                if ($operationType == "move") {
                    $dbDestinationPath = $destinationPath;
                    $fileDbEntry = FileManager::where("file_id", $fileId)->first();

                    if ($fileDbEntry) {
                        $operationFileData[$index]['is_starred'] = $fileDbEntry->is_star;
                        $operationFileData[$index]['is_deleted'] = $fileDbEntry->is_deleted == "1" ? true : false;
                        $operationFileData[$index]['is_shared'] = $fileDbEntry->is_shared;
                    } else {
                        $operationFileData[$index]['is_starred'] = "0";
                        $operationFileData[$index]['is_deleted'] = false;
                        $operationFileData[$index]['is_shared'] = "0";
                    }

                    if ($fileType == "folder") {
                        // Skip the if the selected folder for move and the destination folder is same 
                        if ($fileId == $destinationFolderId) continue;

                        $sourcePathExploded = explode("/", $sourcePath);
                        $destinationFolderName = array_pop($sourcePathExploded);
                        $destinationFolderNameExploded = explode("_", $destinationFolderName);
                        $destinationFolderId = array_shift($destinationFolderNameExploded);
                        $destinationFolderName = implode("_", $destinationFolderNameExploded);
                        $destinationFolderNameWithId = "{$destinationFolderId}_{$destinationFolderName}";
                        $sliceOffset = 1;

                        $destinationPathDirectories = Storage::directories($destinationPath);
                        // Delete the destination path folder's if the source path folder's is present in destination folder
                        collect($destinationPathDirectories)->map(function ($value) use ($destinationFolderNameWithId, $sliceOffset) {
                            if (basename($value) == $destinationFolderNameWithId) return;
                            $folderNameWithoutExtension = explode("_", pathinfo($value, PATHINFO_FILENAME));
                            $folderNameArray = array_slice($folderNameWithoutExtension, $sliceOffset);
                            $folder = implode("_", $folderNameArray);

                            $destinationFolderExploded = explode("_", $destinationFolderNameWithId);
                            $destinationFolderArray = array_slice($destinationFolderExploded, 1);
                            $destinationFolder = implode("_", $destinationFolderArray);

                            if ($folder === $destinationFolder) {
                                File::deleteDirectory(Storage::path($value));
                            }
                        });

                        // 1. Copy the directory recursively
                        File::copyDirectory(Storage::path($sourcePath), Storage::path("$destinationPath/$destinationFolderNameWithId"));

                        // 2. Delete the original directory
                        if ($sourcePath != "$destinationPath/$destinationFolderNameWithId") {
                            File::deleteDirectory(Storage::path($sourcePath));
                        }

                        $movedFolderDate = Carbon::now()->toDateString();
                        $totalSize = formatFileSize(getFileSize($sourcePath, "folder"));

                        $operationFileData[$index]['id'] = $fileId;
                        $operationFileData[$index]['name'] = $destinationFolderName;
                        $operationFileData[$index]['path'] = makePath("$destinationPath/$destinationFolderNameWithId", 5, "file-manager");
                        $operationFileData[$index]['full_path'] = "$destinationPath/$destinationFolderNameWithId";
                        $operationFileData[$index]['size'] = $totalSize;
                        $operationFileData[$index]['ext'] = "folder";
                        $operationFileData[$index]['type'] = "folder";
                        $operationFileData[$index]['owner'] = "Me";
                        $operationFileData[$index]['date'] = $movedFolderDate;
                    } else {
                        $destinationPathFiles = Storage::files($destinationPath);
                        $sliceOffset = 1;

                        collect($destinationPathFiles)->map(function ($value) use ($fileName, $sliceOffset, $fileFullName) {
                            if (basename($value) == $fileFullName) return;
                            $fileNameWithoutExtension = explode("_", basename($value));
                            $fileNameArray = array_slice($fileNameWithoutExtension, $sliceOffset);
                            $file = implode("_", $fileNameArray);

                            if ($file === $fileName) {
                                File::delete(Storage::path($value));
                            }
                        });

                        $dbDestinationPath = "$destinationPath/$fileFullName";
                        Storage::move($sourcePath, $dbDestinationPath);

                        $movedFileDate = Carbon::now()->toDateString();

                        $operationFileData[$index]['id'] = $fileId;
                        $operationFileData[$index]['name'] = $fileName;
                        $operationFileData[$index]['full_path'] = "$destinationPath/$fileFullName";
                        $operationFileData[$index]['path'] = makePath("$destinationPath/$fileFullName", 5, "file-manager");
                        $operationFileData[$index]['size'] = formatFileSize(Storage::disk('local')->size("$destinationPath/$fileFullName"));
                        $operationFileData[$index]['ext'] = $fileType;
                        $operationFileData[$index]['type'] = $fileType;
                        $operationFileData[$index]['owner'] = "Me";
                        $operationFileData[$index]['date'] = $movedFileDate;
                        $operationFileData[$index]['file_url'] = getFileTemporaryURL("$destinationPath/$fileFullName");
                        $operationFileData[$index]['is_image'] = checkFileIsImageOrNot("$destinationPath/$fileFullName");
                        $operationFileData[$index]['is_media'] = checkFileIsMediaOrNot("$destinationPath/$fileFullName");
                        $operationFileData[$index]['is_document'] = checkFileIsDocumentOrNot("$destinationPath/$fileFullName");
                        $operationFileData[$index]['is_text'] = checkFileIsTextOrNot("$destinationPath/$fileFullName");
                    }

                    // Get the moved directory files and folders
                    $destinationPathFiles = Storage::allFiles($destinationPath);
                    $destinationPathDirectories = Storage::allDirectories($destinationPath);

                    $destinationPathFilesAndFolders = array_merge($destinationPathFiles, $destinationPathDirectories);

                    // Update nested files and folders path in database in the newely moved directory 
                    foreach ($destinationPathFilesAndFolders as $nestedFile) {
                        $nestedFileId = explode("_", basename($nestedFile))[0];
                        updateNestedPaths($nestedFileId, $nestedFile);
                    }

                    $file = FileManager::where("file_id", $fileId)->where("user_id", $user->id)->first();
                    if ($file) {
                        $file->file_path = $dbDestinationPath;
                        $file->save();
                    }
                } else if ($operationType == "copy") {
                    $totalCopyFileSizeInBytes += getFileSize($sourcePath, $fileType);
                    $tempUserOccupiedStorageSizeInMB = convertBytesToMB($totalCopyFileSizeInBytes + $userOccupiedStorageSizeInBytes);
                    if ($tempUserOccupiedStorageSizeInMB > $userStoragePlanInMB) {
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Storage limit exceeded, please upgrade your storage plan", 'toast' => true]);
                    }

                    $message = "pasted";
                    if ($fileType == "folder") {
                        $sourcePathExploded = explode("/", $sourcePath);
                        $destinationFolderName = array_pop($sourcePathExploded);
                        $destinationFolderExploded = explode("_", $destinationFolderName);
                        array_shift($destinationFolderExploded);
                        $destinationFolderImploded = implode("_", $destinationFolderExploded);
                        $uniqueIdentifier = Str::uuid()->toString();
                        $destinationFolderName = "{$uniqueIdentifier}_{$destinationFolderImploded}";
                        $destinationFolderPath = "$destinationPath/$destinationFolderName";

                        $files = Storage::allFiles($sourcePath);
                        $directories = Storage::allDirectories($sourcePath);

                        // Create destination directory if it doesn't exist

                        $folders = Storage::directories($destinationPath);
                        $folderExists = collect($folders)->contains(function ($value) use ($destinationFolderImploded) {
                            $iteratedFolderNameExploded = explode("_", basename($value));
                            array_shift($iteratedFolderNameExploded);
                            $iteratedFolderName = implode("_", $iteratedFolderNameExploded);

                            return preg_replace('/\(copy\)\d*$/', '', $iteratedFolderName) == $destinationFolderImploded;
                        });

                        // Count the number of files with the same base filename and extension
                        $sliceOffset = 1;
                        $counter = collect($folders)->filter(function ($value) use ($destinationFolderImploded, $sliceOffset) {
                            $folderNameExploded = explode("_", basename($value));
                            $folderNameArray = array_slice($folderNameExploded, $sliceOffset);
                            $folderName = implode("_", $folderNameArray);

                            return preg_replace('/\(copy\)\d*$/', '', $folderName) === $destinationFolderImploded;
                        })->count();


                        if ($folderExists) {
                            $destinationFolderPath = "$destinationPath/$destinationFolderName(copy)$counter";
                            $destinationFolderName = "$destinationFolderName(copy)$counter";
                        }

                        Storage::disk('local')->makeDirectory($destinationFolderPath);

                        $copiedFolderNameExploded = explode("_", $destinationFolderName);
                        array_shift($copiedFolderNameExploded);
                        $copiedFolderName = implode("_", $copiedFolderNameExploded);

                        // Copy directories recursively
                        foreach ($directories as $directory) {
                            $uniqueIdentifier = Str::uuid()->toString();
                            $folderNameExploded = explode("_", basename($directory));
                            array_shift($folderNameExploded);
                            $originalFolderName = implode("_", $folderNameExploded);
                            $newDestinationDirectory = "$destinationFolderPath/{$uniqueIdentifier}_{$originalFolderName}";

                            File::copyDirectory(
                                Storage::path($directory),
                                Storage::path($newDestinationDirectory)
                            );
                        }

                        // Copy each file  
                        foreach ($files as $file) {
                            $uniqueIdentifier = Str::uuid()->toString();
                            $fileNameExploded = explode("_", basename($file));
                            array_shift($fileNameExploded);
                            $originalFileName = implode("_", $fileNameExploded);
                            $fileNewPath = "$destinationFolderPath/{$uniqueIdentifier}_{$originalFileName}";
                            Storage::copy($file, $fileNewPath);
                        }


                        $copiedFolderDate = Carbon::now()->toDateString();
                        $totalSize = formatFileSize(getFileSize($sourcePath, "folder"));

                        $operationFileData[$index]['id'] = $uniqueIdentifier;
                        $operationFileData[$index]['name'] = $copiedFolderName;
                        $operationFileData[$index]['path'] = makePath($destinationFolderPath, 5, "file-manager");
                        $operationFileData[$index]['full_path'] = $destinationFolderPath;
                        $operationFileData[$index]['size'] = $totalSize;
                        $operationFileData[$index]['ext'] = "folder";
                        $operationFileData[$index]['type'] = "folder";
                        $operationFileData[$index]['owner'] = "Me";
                        $operationFileData[$index]['is_starred'] = "0";
                        $operationFileData[$index]['is_shared'] = "0";
                        $operationFileData[$index]['is_deleted'] = false;
                        $operationFileData[$index]['date'] = $copiedFolderDate;
                    } else {
                        $uniqueIdentifier = Str::uuid()->toString();

                        $files = Storage::files($destinationPath);
                        $fileExists = collect($files)->contains(function ($value, $key) use ($fileName) {
                            return strpos($value, $fileName) !== false;
                        });

                        $baseFilename = pathinfo($fileName, PATHINFO_FILENAME);
                        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

                        // Count the number of files with the same base filename and extension
                        $sliceOffset = 1;
                        $counter = collect($files)->filter(function ($value) use ($baseFilename, $fileType, $sliceOffset) {
                            $fileNameWithoutExtension = explode("_", pathinfo($value, PATHINFO_FILENAME));
                            $fileNameArray = array_slice($fileNameWithoutExtension, $sliceOffset);
                            $fileName = implode("_", $fileNameArray);
                            return preg_replace('/\(copy\)\d*$/', '', $fileName) === $baseFilename && pathinfo($value, PATHINFO_EXTENSION) === $fileType;
                        })->count();

                        $newFilenameWithId = "{$uniqueIdentifier}_{$fileName}";
                        $newFilename = $fileName;

                        if ($fileExists) {
                            $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                            $newFilenameWithId = "{$uniqueIdentifier}_{$fileNameWithoutExtension}(copy)$counter.{$fileType}";
                            $newFilename = "{$fileNameWithoutExtension}(copy)$counter.{$fileType}";
                        }
                        Storage::copy($sourcePath, "$destinationPath/$newFilenameWithId");
                        $copiedFileDate = Carbon::now()->toDateString();

                        $operationFileData[$index]['id'] = $uniqueIdentifier;
                        $operationFileData[$index]['name'] = $newFilename;
                        $operationFileData[$index]['full_path'] = "$destinationPath/$newFilenameWithId";
                        $operationFileData[$index]['path'] = makePath("$destinationPath/$newFilenameWithId", 5, "file-manager");
                        $operationFileData[$index]['size'] = formatFileSize(Storage::disk('local')->size("$destinationPath/$newFilenameWithId"));
                        $operationFileData[$index]['ext'] = $fileType;
                        $operationFileData[$index]['type'] = $fileType;
                        $operationFileData[$index]['owner'] = "Me";
                        $operationFileData[$index]['is_starred'] = "0";
                        $operationFileData[$index]['is_shared'] = "0";
                        $operationFileData[$index]['is_deleted'] = false;
                        $operationFileData[$index]['date'] = $copiedFileDate;
                        $operationFileData[$index]['file_url'] = getFileTemporaryURL("$destinationPath/$newFilenameWithId");
                        $operationFileData[$index]['is_image'] = checkFileIsImageOrNot("$destinationPath/$newFilenameWithId");
                        $operationFileData[$index]['is_media'] = checkFileIsMediaOrNot("$destinationPath/$newFilenameWithId");
                        $operationFileData[$index]['is_document'] = checkFileIsDocumentOrNot("$destinationPath/$newFilenameWithId");
                        $operationFileData[$index]['is_text'] = checkFileIsTextOrNot("$destinationPath/$newFilenameWithId");
                    }
                    $updatedStorageSize = formatFileSize(getFileSize("users/private/$user->id", "folder"), false);
                }
                $index++;
            }

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Items $message successfully", 'toast' => true], ["updatedStorageSize" => $updatedStorageSize, "operationFileData" => $operationFileData]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error on processing : " . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error on processing", 'toast' => true]);
        }
    }
    public function toggleStar(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $fileId = $request->input('file_id');
            $fileName = $request->input('file_name');
            $filePath = $request->input('file_path');
            $isStar = $request->input('is_star');
            $fileType = $request->input('file_type');
            $fileSize = $request->input('file_size');
            $fileCreationDate = $request->input('file_creation_date');
            $fileType == "folder" ? $message = "Folder" : $message = "File";

            $file = FileManager::where("file_id", $fileId)->first();
            if ($file) {
                $file->is_star = $isStar;
                $file->save();
            } else {
                $file = new FileManager();
                $file->user_id = $user->id;
                $file->file_id = $fileId;
                $file->file_path = $filePath;
                $file->file_name = $fileName;
                $file->file_size = $fileSize;
                $file->file_type = $fileType;
                $file->is_star = $isStar;
                $file->file_creation_date = $fileCreationDate;
                $file->save();
            }
            DB::commit();

            if ($isStar == "1") {
                $message .= " starred";
            } else {
                $message .= " unstarred";
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "$message successfully", 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("$message star error: " . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error on $message", 'toast' => true]);
        }
    }
    public function fetchStar(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $isMoreFilesAvailable = false;
            $offset = $request->offset ?? 0;
            if ($request->has("parent_folder_path")) {
                $starredFiles = [];
                $parentFolderPath = $request->parent_folder_path;

                $starredFiles = Storage::files($parentFolderPath);
                $starredFolders = Storage::directories($parentFolderPath);

                $additionalKeys = [
                    "is_starred" => "1"
                ];

                $fileDetails = fileDetails($starredFiles, $user, "file", $additionalKeys);
                $folderDetails = fileDetails($starredFolders, $user, "folder", $additionalKeys);

                $formattedFilesLength = ($fileDetails->filter()->count() + $folderDetails->filter()->count());
                if (($offset + 12) < $formattedFilesLength) {
                    $isMoreFilesAvailable = true;
                }

                $starredFiles = array_slice(array_merge($fileDetails->filter()->toArray(), $folderDetails->filter()->toArray()), $offset, 12);
            } else {
                $starredFiles = FileManager::where("is_star", "1")
                    ->where("user_id", $user->id)
                    ->limit(12)
                    ->offset($offset)
                    ->get();
                if ($starredFiles->isEmpty()) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No starred files/folders found', 'toast' => true]);
                }

                $starredFiles = $starredFiles->map(function ($file) {
                    $createdAtTimestamp = Storage::disk('local')->lastModified($file->file_path);
                    $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;

                    $fileDetails = [
                        'id' => $file->file_id,
                        'name' => $file->file_name,
                        'path' => makePath($file->file_path, 5, "file-manager"),
                        'full_path' => $file->file_path,
                        'size' => $file->file_type == "folder" ? formatFileSize(getFileSize($file->file_path, "folder")) : $file->file_size,
                        'ext' => $file->file_type,
                        'icon' => $file->file_type,
                        'type' => $file->file_type,
                        'owner' => "Me",
                        'is_starred' => $file->is_star,
                        'is_deleted' => $file->is_deleted == "0" ? false : true,
                        'date' => $createdAt,
                    ];
                    if ($file->file_type != "folder") {
                        $fileDetails['file_url'] = getFileTemporaryURL($file->file_path);
                        $fileDetails['is_image'] = checkFileIsImageOrNot($file->file_path);
                        $fileDetails['is_media'] = checkFileIsMediaOrNot($file->file_path);
                        $fileDetails['is_document'] = checkFileIsDocumentOrNot($file->file_path);
                        $fileDetails['is_text'] = checkFileIsTextOrNot($file->file_path);
                    }
                    return $fileDetails;
                });
                if (count($starredFiles) >= 12) {
                    $isMoreFilesAvailable = true;
                }
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Starred files fetched successfully', 'toast' => true], ['starredFiles' => $starredFiles, "isMoreFilesAvailable" => $isMoreFilesAvailable]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Get starred files error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting the starred the files/folders', 'toast' => true]);
        }
    }
    public function share(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $fileId = $request->input('file_id');
            $file = FileManager::where('file_id', $fileId)->first();
            $shareUsersIds = $request->input('share_users');
            $sharePermission = $request->input('share_permission');
            $storageService = Service::where("key", "storage")->first();

            if ($file) {
                $shareWithData = json_decode($file->shared_with, true) ?? [];
                $shareUsersIds = array_diff($shareUsersIds, $shareWithData);

                $shareWithData = array_unique(array_merge($shareWithData, $shareUsersIds));

                $file->shared_with = $shareWithData;
                if ($request->has("share_message")) $file->share_message = $request->share_message;
                $file->is_shared = "1";
                $file->shared_permission = $sharePermission;
                $file->save();
            } else {
                $fileName = $request->input('file_name');
                $fileType = $request->input('file_type');
                $filePath = $request->input('file_path');
                $fileSize = $request->input('file_size');
                $fileCreationDate = $request->input('file_creation_date');

                $file = new FileManager();
                $file->user_id = $user->id;
                $file->file_id = $fileId;
                $file->file_path = $filePath;
                $file->file_name = $fileName;
                $file->file_size = $fileSize;
                $file->file_type = $fileType;
                $file->is_shared = "1";
                $file->shared_with = json_encode($shareUsersIds);
                $file->shared_permission = $sharePermission;
                $file->file_creation_date = $fileCreationDate;
                if ($request->has("share_message")) $file->share_message = $request->share_message;
                $file->save();
            }
            sendAllNotificationsToShareFileUsers($user, $shareUsersIds, $storageService, $file->id);

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Items shared successfully', 'toast' => true], ["users" => $shareUsersIds]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info('Item share error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error sharing the item', 'toast' => true]);
        }
    }
    public function fetchShare(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $offset = $request->offset ?? 0;
            $parentFolderOwner = $request->parent_folder_owner;
            $isMoreFilesAvailable = false;
            $fetchSection = $request->fetch_section;
            if ($request->has("parent_folder_path")) {
                $sharedFiles = [];
                $parentFolderPath = $request->parent_folder_path;
                $sharedFiles = Storage::files($parentFolderPath);
                $sharedFolders = Storage::directories($parentFolderPath);

                $additionalKeys = [
                    "is_shared" => "1",
                    "owner" => $parentFolderOwner,
                ];

                $fileDetails = fileDetails($sharedFiles, $user, "file", $additionalKeys);
                $folderDetails = fileDetails($sharedFolders, $user, "folder", $additionalKeys);

                $formattedFilesLength = ($fileDetails->filter()->count() + $folderDetails->filter()->count());
                if (($offset + 12) < $formattedFilesLength) {
                    $isMoreFilesAvailable = true;
                }

                $sharedFiles = array_slice(array_merge($fileDetails->filter()->toArray(), $folderDetails->filter()->toArray()), $offset, 12);
            } else {
                $sharedFiles = collect([]);
                if ($fetchSection == "Incoming") {
                    $sharedFiles = FileManager::where(function ($query) use ($user) {
                        $query->whereJsonContains("shared_with", "{$user->id}");
                    })
                        ->where("is_shared", "1")
                        ->offset($offset)
                        ->limit(12)
                        ->get();
                } else if ($fetchSection == "Outgoing") {
                    $sharedFiles = FileManager::where(function ($query) use ($user) {
                        $query->where("user_id", $user->id);
                    })
                        ->where("is_shared", "1")
                        ->offset($offset)
                        ->limit(12)
                        ->get();
                }

                if ($sharedFiles->isEmpty()) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No shared files/folders found', 'toast' => true]);
                }

                $sharedFiles = $sharedFiles->map(function ($file) use ($user) {
                    if ($file->user_id == $user->id) {
                        $owner = "Me";
                    } else {
                        $owner = User::find($file->user_id);
                        $owner = $owner->username;
                    }

                    $createdAtTimestamp = Storage::disk('local')->lastModified($file->file_path);
                    $createdAt = Carbon::createFromTimestamp($createdAtTimestamp)->timestamp;

                    $fileDetails = [
                        'id' => $file->file_id,
                        'name' => $file->file_name,
                        'path' => makePath($file->file_path, 5, "file-manager"),
                        'full_path' => $file->file_path,
                        'size' => $file->file_type == "folder" ? formatFileSize(getFileSize($file->file_path, "folder")): $file->file_size,
                        'ext' => $file->file_type,
                        'icon' => $file->file_type,
                        'type' => $file->file_type,
                        'owner' => $owner,
                        'is_shared' => $file->is_shared,
                        'is_deleted' => $file->is_deleted == "0" ? false : true,
                        'date' => $createdAt,
                    ];
                    if ($file->file_type != "folder") {
                        $fileDetails['file_url'] = getFileTemporaryURL($file->file_path);
                        $fileDetails['is_image'] = checkFileIsImageOrNot($file->file_path);
                        $fileDetails['is_media'] = checkFileIsMediaOrNot($file->file_path);
                        $fileDetails['is_document'] = checkFileIsDocumentOrNot($file->file_path);
                        $fileDetails['is_text'] = checkFileIsTextOrNot($file->file_path);
                    }
                    return $fileDetails;
                });

                if (count($sharedFiles) >= 12) {
                    $isMoreFilesAvailable = true;
                }
            }


            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Shared files fetched successfully', 'toast' => true], ['sharedFiles' => $sharedFiles, "isMoreFilesAvailable" => $isMoreFilesAvailable]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Get shared files error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting the shared the files/folders', 'toast' => true]);
        }
    }
    public function getFolders(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $folderFullPath = "users/private/{$user->id}/FileManager/default/";
            if ($request->has('folder_path')) {
                $folderFullPath = $request->input('folder_path');
            }
            $allFolders = fetchDirectoryFolders($folderFullPath, $user);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Successfully fetch all folders list', 'toast' => true], ["folderList" => $allFolders]);
        } catch (\Exception $e) {
            Log::info('Get all files/folders error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting the the files/folders list', 'toast' => true]);
        }
    }
    public function fetchUsers(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $searchValue = $request->input('search');
            $users = User::where(function ($query) use ($searchValue) {
                $query->where('first_name', 'like', "%$searchValue%")
                    ->orWhere('last_name', 'like', "%$searchValue%")
                    ->orWhere('username', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%");
            })
                ->where('id', '!=', $user->id)
                ->limit(10)
                ->get(["id", "username", "email"]);


            $userList = generateUsersListForSearch($users);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Userlist fetched successfully', 'toast' => true], ["userList" => $userList]);
        } catch (\Exception $e) {
            Log::error('User search error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on user search', 'toast' => true]);
        }
    }
    public function renameFile(Request $request)
    {
        DB::beginTransaction();
        try {
            $renamedFileData = [];
            $fileId = $request->file_id;
            $fileType = $request->file_type;
            $filePath = $request->file_path;
            $fileExt = $request->file_ext;
            $newFileName = str_replace(['/', "\\", '?', '<', '>', '|', ':', '"', '\''], '', $request->new_name);
            if ($fileType != "folder") {
                $renamedFileData['name_without_ext'] = $newFileName;
                $newFileName = "{$newFileName}.{$fileExt}";
            }

            $filePathExploded = explode('/', $filePath);
            array_pop($filePathExploded);
            $parentFolderPath = implode('/', $filePathExploded);

            if ($fileType == "folder") {
                $directories = Storage::directories($parentFolderPath);
                $folderExist = false;
                foreach ($directories as $directory) {
                    $directoryNameExploded = explode("_", basename($directory));
                    array_shift($directoryNameExploded);
                    $fileName = implode("_", $directoryNameExploded);
                    if ($fileName == $newFileName) $folderExist = true;
                }
                if ($folderExist) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Folder with that name already exist', 'toast' => true]);
                }
            } else {
                $files = Storage::files($parentFolderPath);
                $fileExist = false;
                foreach ($files as $file) {
                    $fileNameExploded = explode("_", basename($file));
                    array_shift($fileNameExploded);
                    $fileName = implode("_", $fileNameExploded);
                    if ($fileName == $newFileName) $fileExist = true;
                }
                if ($fileExist) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'File with that name already exist', 'toast' => true]);
                }
            }

            $uniqueIdentifier = Str::uuid()->toString();
            $newFilePath = "$parentFolderPath/{$uniqueIdentifier}_{$newFileName}";

            Storage::move($filePath, $newFilePath);
            $fileDbEntry = FileManager::where('file_id', $fileId)->first();
            if ($fileDbEntry) {
                $fileDbEntry->file_path = $newFilePath;
                $fileDbEntry->file_name = $newFileName;
                $fileDbEntry->save();
            }

            if ($fileType == "folder") {
                // Update the nested file/folder information from that folder in database if the file/folder entry exist in database 
                $allDirectories = Storage::allDirectories($parentFolderPath);
                $allFiles = Storage::allFiles($parentFolderPath);
                $allFilesAndFolders = array_merge($allDirectories, $allFiles);

                collect($allFilesAndFolders)->each(function ($directory) {
                    $fileNameExploded = explode("_", basename($directory));
                    $nestedFileId = array_shift($fileNameExploded);
                    $nestedFileDbEntry = FileManager::where('file_id', $nestedFileId)->first();
                    if ($nestedFileDbEntry) {
                        $nestedFileDbEntry->file_path = $directory;
                        $nestedFileDbEntry->save();
                    }
                });
            } else $renamedFileData['file_url'] = getFileTemporaryURL($newFilePath);

            DB::commit();

            $renamedFileData['id'] = $uniqueIdentifier;
            $renamedFileData['name'] = $newFileName;
            $renamedFileData['path'] = makePath($newFilePath, 5, "file-manager");
            $renamedFileData['full_path'] = $newFilePath;

            $message = ($fileType == "folder" ? $fileType : "file") . " " . "renamed successfully";

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true], ["renamedFileData" => $renamedFileData]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("$fileType rename error: " . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on processing', 'toast' => true]);
        }
    }
    public function getAllVideos(Request $request)
    {
        try {
            $user = $request->attributes->get('user');

            $basePath = "users/private/{$user->id}/FileManager/default";

            $videos = collect(Storage::disk('local')->allFiles($basePath))
                ->filter(function ($file) {
                    return in_array(Storage::disk('local')->mimeType($file), ['video/mp4', 'video/webm']);
                })
                ->map(function ($file) use ($basePath) {
                    $fileNameExploded = explode("_", basename($file));
                    $fileId = array_shift($fileNameExploded);
                    $fileName = implode("_", $fileNameExploded);
                    return [
                        'id' => $fileId,
                        'name' => $fileName,
                        'path' => $file, // Generate public URL
                        'size' => formatFileSize(Storage::disk('local')->size($file)),
                    ];
                });
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Videos fetched successfully', 'toast' => true], ['videos' => $videos]);
        } catch (\Exception $e) {
            Log::error('Get all videos error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting all videos', 'toast' => true]);
        }
    }
    public function getVideo(Request $request)
    {
        try {
            $filePath = $request->file_path;

            if (!Storage::disk('local')->exists($filePath)) {
                abort(404);
            }

            $mimeType = Storage::disk('local')->mimeType($filePath);

            return response()->file(
                storage_path('app/' . $filePath),
                ['Content-Type' => $mimeType]
            );
        } catch (\Exception $e) {
            Log::error('Get video error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting video', 'toast' => true]);
        }
    }
    public function getAppsStorageInfo(Request $request)
    {
        try {
            $user = $request->attributes->get("user");
            $folderToFetch = "users/private/$user->id";
            $offset = $request->input('offset') ?? 0;
            $isMoreFilesAvailable = false;

            $folders = Storage::directories($folderToFetch);
            
            $allFilesData = fileDetailsForApps($folders, "folder", config('app.folders_to_avoid_in_apps_storage'))->filter()->toArray();
            $allFilesDataLength = count($allFilesData);
            $allFilesData = array_slice($allFilesData, $offset, 12);

            if (($offset + 12) < $allFilesDataLength) {
                $isMoreFilesAvailable = true;
            }

            if ($request->input("parent_folder_path")) {
                $folderToFetch = $request->input('parent_folder_path');

                $folders = Storage::directories($folderToFetch);
                $files = Storage::files($folderToFetch);

                $allFolderDetails = fileDetailsForApps($folders, "folder")->filter()->toArray();
                $allFileDetails = fileDetailsForApps($files)->filter()->toArray();

                $allFilesData = array_merge($allFolderDetails, $allFileDetails);
                $allFilesDataLength = count($allFilesData);

                if (($offset + 12) < $allFilesDataLength) {
                    $isMoreFilesAvailable = true;
                }

                $allFilesData = array_slice(array_merge($allFilesData), $offset, 12);
            }


            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Apps list fetched successfully', 'toast' => true], ["allFilesData" => $allFilesData, "isMoreFilesAvailable" => $isMoreFilesAvailable]);
        } catch (\Exception $e) {
            Log::error('Error on getting apps : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error getting apps storage', 'toast' => true]);
        }
    }
}
