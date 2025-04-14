<?php

namespace App\Http\Controllers\API\V1\Game;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Game\VersionControl;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Game\versions\AddVersionControlRequest;
use App\Http\Requests\Game\versions\GetVersionControlRequest;
use App\Http\Requests\Game\versions\UpdateVersionControlRequest;

class VersionController extends Controller
{
    public function getVersionControl(GetVersionControlRequest $request)
    {
        try {
            $id = $request->id;

            $version = VersionControl::where('id', $id)->first();

            if (!$version) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Version data with the provided version ID not found', 'toast' => true]);
            }

            // Modify the file URL if the version is found
            $version->file_url = getFileTemporaryURL($version->file);

            return generateResponse([
                'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Version retrieved successfully', 'toast' => true, 'data' => $version->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving versions: ' . $e->getMessage());
            return generateResponse([
                'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
            ]);
        }
    }


    public function getVersionControlList(Request $request)
    {
        try {
            $query = VersionControl::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('title', 'LIKE', '%' . $searchTerm . '%');
            }

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $versions = $query->offset($offset)->limit($limit)->get();

            foreach ($versions as $version) {
                $version->file_url = getFileTemporaryURL($version->file);
            }

            $count = $query->count();

            if ($count <= 0) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No versions found matching the search criteria', 'toast' => true,]);
            }

            return generateResponse([
                'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Versions retrieved successfully', 'toast' => true, 'data' => $versions->toArray(), 'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving versions: ' . $e->getMessage());
            return generateResponse([
                'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true,
            ]);
        }
    }



    public function addVersionControl(AddVersionControlRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $userFolder = "users/private/{$user->id}/game/versions/{$request->title}";
            $filePrefix = 'file_';
            Storage::makeDirectory($userFolder);

            $versionControl = new VersionControl();

            $file = $request->file('file');
            $filePath = $file->storeAs($userFolder, $filePrefix . $file->getClientOriginalName());

            $versionControl->user_id = $user->id;
            $versionControl->group_key = $request->group_key;
            $versionControl->title = $request->title;
            $versionControl->file = $filePath;
            $versionControl->version = $request->version;
            $versionControl->description = $request->description;

            $versionControl->save();

            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Version added successfully', 'toast' => true], ["data" => $versionControl]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding version control: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function updateVersionControl(UpdateVersionControlRequest $request)
    {
        DB::beginTransaction();
        try {

            $user = $request->attributes->get('user');
            $id = $request->id;

            $userFolder = "users/private/{$user->id}/game/versions/{$request->title}";
            $filePrefix = 'file_';
            Storage::makeDirectory($userFolder);

            $versionControl = VersionControl::where('id', $id)->where('user_id', $user->id)->first();

            if (!$versionControl) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Version id not found', 'toast' => true]);
            }

            if (isset($request->title)) {
                $versionControl->title = $request->title;
            }
            if (isset($request->group_key)) {
                $versionControl->group_key = $request->group_key;
            }
            if (isset($request->version)) {
                $versionControl->version = $request->version;
            }
            if (isset($request->description)) {
                $versionControl->description = $request->description;
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->storeAs($userFolder, $filePrefix . $file->getClientOriginalName());

                Storage::delete($versionControl->file);

                $versionControl->file = $filePath;
            }
            $versionControl->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Version updated successfully', 'toast' => true, 'data' => $versionControl]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating version control: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function deteteVersionControl(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $versionControlId = $request->version_control_id;

            $versionControl = VersionControl::where('id', $versionControlId)->where('user_id', $user->id)->first();

            if (!$versionControl) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Version id not found', 'toast' => true]);
            }

            Storage::delete($versionControl->file);
            $versionControl->delete();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Version deleted successfully', 'toast' => true]);
        } catch (\Exception $e) {
            Log::error('Error adding tournament: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
