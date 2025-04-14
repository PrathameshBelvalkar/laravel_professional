<?php

namespace App\Http\Controllers\API\V1\Game;

use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Game\AddSportRequest;
use App\Http\Requests\Game\UpdateSportRequest;

class SportController extends Controller
{


    public function getSports(Request $request)
    {
        try {
            if ($request->has('sport_id')) {
                $sport_id = $request->sport_id;
                $sport = Sport::where('id', $sport_id)->first();

                if (!$sport) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sport not found']);
                }

                $sport->sport_image = getFileTemporaryURL($sport->sport_image);

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Sport found', 'toast' => true], ['data' => $sport]);
            } else {
                $sports = Sport::all();

                foreach ($sports as $sport) {
                    $sport->sport_image = getFileTemporaryURL($sport->sport_image);
                }

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All sports retrieved', 'toast' => true], ['data' => $sports]);
            }
        } catch (\Exception $e) {
            Log::error('Error getting sports: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }

    public function addSport(AddSportRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $existingSport = Sport::where('sport_name', $request->sport_name)->first();
            if ($existingSport) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sport name already exists', 'toast' => true]);
            }

            $sport = new Sport();
            $userFolder = "users/private/{$user->id}/game/sport";
            Storage::makeDirectory($userFolder);

            if ($request->hasFile('sport_image') && $request->file('sport_image')->isValid()) {
                $imageName = time() . '.' . $request->sport_image->extension();
                $imagePath = $request->sport_image->storeAs($userFolder, $imageName);
                $sport->sport_image =  $imagePath;
            }

            $sport->user_id = $user->id;
            $sport->sport_name = $request->sport_name;
            $sport->sport_description = $request->sport_description;
            $sport->save();

            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Sport added successfully', 'toast' => true], ['data' => $sport]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding sport: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }

    public function updateSport(UpdateSportRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $sport_id = $request->sport_id;

            $userFolder = "users/private/{$user->id}/game/sport";
            Storage::makeDirectory($userFolder);

            $sport = Sport::where('user_id', $user->id)->where('id', $sport_id)->first();

            if (!$sport) {
                DB::rollback();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sport id not found', 'toast' => true]);
            }

            if (isset($request->sport_name)) {
                $sport->sport_name = $request->sport_name;
            }

            if (isset($request->sport_description)) {
                $sport->sport_description = $request->sport_description;
            }

            if ($request->hasFile('sport_image') && $request->file('sport_image')->isValid()) {
                $imageName = time() . '.' . $request->sport_image->extension();
                $imagePath = $request->sport_image->storeAs($userFolder, $imageName);

                if ($imagePath) {
                    if ($sport->sport_image) {
                        Storage::delete(str_replace('storage/', '', $sport->sport_image));
                    }
                    $sport->sport_image = $imagePath;
                }
            }

            $sport->save();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Sport updated successfully', 'toast' => true], ['data' => $sport]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating sport: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }

    public function deleteSport(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $sport_id = $request->sport_id;

            $sport = Sport::where('user_id', $user->id)->where('id', $sport_id)->first();

            if (!$sport) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sport not found', 'toast' => true]);
            }

            if ($sport->sport_image) {
                Storage::delete(str_replace('storage/', '', $sport->sport_image));
            }

            $sport->delete();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Sport deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting sport: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => ' Error while proceeding', 'toast' => true]);
        }
    }
}
