<?php

namespace App\Http\Controllers\API\V1\Game;

use Illuminate\Http\Request;
use App\Models\Game\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Game\Tournament\AddTournamentRequest;
use App\Http\Requests\Game\Tournament\GetTournamentRequest;
use App\Http\Requests\Game\Tournament\UpdateTournamentRequest;

class TournamentController extends Controller
{

    public function getTournament(GetTournamentRequest $request)
    {
        try {
            $id = $request->id;
            $tournament = Tournament::where('id', $id)->first();
            $tournament['tournament_logo'] = getFileTemporaryURL($tournament['tournament_logo']);

            if (!$tournament) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Tournament data with the provided tournament id not found', 'toast' => true]);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tournament retrieved successfully', 'toast' => true, 'data' => $tournament->toArray()]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving tournaments: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getTournamentList(Request $request)
    {
        try {
            $query = Tournament::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('tournament_name', 'LIKE', '%' . $searchTerm . '%');
            }

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $tournaments = $query->offset($offset)->limit($limit)->get();


            foreach ($tournaments as $tournament) {
                $tournament->tournament_logo = getFileTemporaryURL($tournament->tournament_logo);
            }

            $count = $query->count();

            if ($count <= 0) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No tournament found matching the search criteria', 'toast' => true]);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tournaments retrieved successfully', 'toast' => true, 'data' => $tournaments->toArray(), 'count' => $count,]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving tournaments: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function addTournament(AddTournamentRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $userFolder = "users/private/{$user->id}/game/tournament";

            Storage::makeDirectory($userFolder);

            $existingTournament = Tournament::where('tournament_name', $request->tournament_name)->where('sport_id', $request->sport_id)->first();

            if ($existingTournament) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Tournament name already exists with this sport ID ' . $request->sport_id, 'toast' => true]);
            }

            $tournament = new Tournament();

            if ($request->hasFile('tournament_logo') && $request->file('tournament_logo')->isValid()) {
                $imageName = time() . '.' . $request->tournament_logo->extension();
                $imagePath = $request->tournament_logo->storeAs($userFolder, $imageName);
                $tournament->tournament_logo =  $imagePath;
            }

            $tournament->organizer_id = $user->id;
            $tournament->sport_id = $request->sport_id;
            $tournament->tournament_name = $request->tournament_name;
            $tournament->location = $request->location;
            $tournament->start_date = $request->start_date;
            $tournament->end_date = $request->end_date;
            $tournament->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tournament added successfully', 'toast' => true], ["data" => $tournament]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding tournament: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function updateTournament(UpdateTournamentRequest $request)
    {
        DB::beginTransaction();
        try {

            $user = $request->attributes->get('user');
            $tournament_id = $request->id;

            $userFolder = "users/private/{$user->id}/game/tournament";
            Storage::makeDirectory($userFolder);

            $tournament = Tournament::where('id', $tournament_id)->where('organizer_id', $user->id)->first();

            if (!$tournament) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Tournament id not found', 'toast' => true]);
            } else {

                // Check if a tournament with the same name and sport ID already exists
                $existingTournament = Tournament::where('tournament_name', $request->tournament_name)->where('sport_id', $request->sport_id)->where('id', '!=', $tournament_id)->first();

                if ($existingTournament) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Tournament name already exists with this sport ID ' . $request->sport_id, 'toast' => true]);
                }

                if ($request->hasFile('tournament_logo') && $request->file('tournament_logo')->isValid()) {
                    $imageName = time() . '.' . $request->tournament_logo->extension();
                    $imagePath = $request->tournament_logo->storeAs($userFolder, $imageName);

                    if ($tournament->tournament_logo) {
                        Storage::delete(str_replace('storage/', '', $tournament->tournament_logo));
                    }

                    $tournament->tournament_logo =  $imagePath;
                }
                if (isset($request->sport_id)) {
                    $tournament->sport_id = $request->sport_id;
                }
                if (isset($request->tournament_name)) {
                    $tournament->tournament_name = $request->tournament_name;
                }
                if (isset($request->location)) {
                    $tournament->location = $request->location;
                }
                if (isset($request->start_date)) {
                    $tournament->start_date = $request->start_date;
                }
                if (isset($request->end_date)) {
                    $tournament->end_date = $request->end_date;
                }
                $tournament->save();
                DB::commit();

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tournament updated successfully', 'toast' => true], ['data' => $tournament]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating tournament: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function deteteTournament(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $tournament_id = $request->tournament_id;

            $tournament = Tournament::where('id', $tournament_id)->where('organizer_id', $user->id)->first();

            if (!$tournament) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Tournament id not found', 'toast' => true]);
            } else {
                $tournament->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tournament deleted successfully', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting tournament: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
