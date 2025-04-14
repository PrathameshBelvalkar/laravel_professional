<?php

namespace App\Http\Controllers\API\V1\Game;

use App\Models\Game\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Game\Team\AddTeamRequest;
use App\Http\Requests\Game\Team\GetTeamRequest;

class TeamController extends Controller
{
    public function getTeam(GetTeamRequest $request)
    {
        try {
            $id = $request->id;
            $sport_id = $request->sport_id;

            if ($id) {
                $team = Team::where('id', $id)->where('sport_id', $sport_id)->first();
                if (!$team) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team not found', 'toast' => true]);
                }

                if ($team->team_logo) {
                    $team->team_logo = getFileTemporaryURL($team->team_logo);
                }

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Team retrieved successfully', 'toast' => true, 'data' => $team->toArray()]);
            } else {
                $teams = Team::where('sport_id', $sport_id)->get()->toArray();
                foreach ($teams as &$team) {
                    if (isset($team['team_logo'])) {
                        $team['team_logo'] = getFileTemporaryURL($team['team_logo']);
                    }
                }

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All teams retrieved successfully', 'toast' => true, 'data' => $teams]);
            }
        } catch (\Exception $e) {
            Log::error('Error while retrieving team: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function getTeamList(Request $request)
    {
        try {
            $query = Team::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('team_name', 'LIKE', '%' . $searchTerm . '%');
            }

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $teams = $query->offset($offset)->limit($limit)->get();

            foreach ($teams as $team) {
                $team->team_logo = getFileTemporaryURL($team->team_logo);
            }

            $count = $query->count();

            if ($count <= 0) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No teams found matching the search criteria', 'toast' => true]);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Teams retrieved successfully', 'toast' => true, 'data' => $teams->toArray(), 'count' => $count,]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving teams: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function addTeam(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $userFolder = "users/private/{$user->id}/game/teams";

            Storage::makeDirectory($userFolder);

            $existingTeam = Team::where('team_name', $request->team_name)->where('sport_id', $request->sport_id)->first();

            if ($existingTeam) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team name already exists with this sport ID ' . $request->sport_id, 'toast' => true]);
            }

            $team = new Team();

            if ($request->hasFile('team_logo') && $request->file('team_logo')->isValid()) {
                $imageName = time() . '.' . $request->team_logo->extension();
                $imagePath = $request->team_logo->storeAs($userFolder, $imageName);
                $team->team_logo =  $imagePath;
            }

            $team->organizer_id = $user->id;
            $team->sport_id = $request->sport_id;
            $team->team_name = $request->team_name;

            $team->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Team added successfully', 'toast' => true], ["data" => $team]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding team: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function updateTeam(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $team_id = $request->id;

            $userFolder = "users/private/{$user->id}/game/teams";
            Storage::makeDirectory($userFolder);

            $team = Team::where('id', $team_id)->where('organizer_id', $user->id)->first();

            if (!$team) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Team id not found', 'toast' => true]);
            } else {

                // Check if a team with the same name and sport ID already exists
                $existingTeam = Team::where('team_name', $request->team_name)->where('sport_id', $request->sport_id)->where('id', '!=', $team_id)->first();

                if ($existingTeam) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team name already exists with this sport ID ' . $request->sport_id, 'toast' => true]);
                }

                if ($request->hasFile('team_logo') && $request->file('team_logo')->isValid()) {
                    $imageName = time() . '.' . $request->team_logo->extension();
                    $imagePath = $request->team_logo->storeAs($userFolder, $imageName);

                    if ($team->team_logo) {
                        Storage::delete(str_replace('storage/', '', $team->team_logo));
                    }

                    $team->team_logo =  $imagePath;
                }

                if (isset($request->team_name)) {
                    $team->team_name = $request->team_name;
                }
                if (isset($request->sport_id)) {
                    $team->sport_id = $request->sport_id;
                }

                $team->save();
                DB::commit();

                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Team updated successfully', 'toast' => true], ['data' => $team]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating team: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function deteteTeam(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $team_id = $request->team_id;

            $team = team::where('id', $team_id)->where('organizer_id', $user->id)->first();

            if (!$team) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Team id not found', 'toast' => true]);
            } else {
                $team->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Team deleted successfully', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting team: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
