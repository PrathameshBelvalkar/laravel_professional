<?php

namespace App\Http\Controllers\API\V1\Game;

use App\Models\Game\Team;
use Illuminate\Http\Request;
use App\Models\Game\TeamMatch;
use App\Models\Sport;
use App\Models\Game\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\Match\AddMatchRequest;
use App\Http\Requests\Game\Match\UpdateMatchRequest;

class MatchController extends Controller
{

    public function getMatch(Request $request)
    {
        try {
            $id = $request->id;
            $match = TeamMatch::where('id', $id)->first();

            if (!$match) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Match data with the provided match id not found', 'toast' => true]);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Match retrieved successfully', 'toast' => true, 'data' => $match->toArray()]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving player: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getMatchList(Request $request)
    {
        try {
            $query = TeamMatch::query();

            $query->join('sports', 'team_matches.sport_id', '=', 'sports.id');

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('sports.sport_name', 'LIKE', '%' . $searchTerm . '%');
            }

            $perPage = max(1, $request->input('limit', 10));
            $page = max(1, $request->input('page', 1));

            $matches = $query->paginate($perPage, ['*'], 'page', $page);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Matches retrieved successfully', 'toast' => true, 'data' => $matches->toArray(),]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving matches: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing',]);
        }
    }

    public function addMatch(AddMatchRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $tournament = Tournament::where('id', $request->tournament_id)->where('sport_id', $request->sport_id)->first();

            if (!$tournament) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Tournament not found', 'toast' => true]);
            }

            $team_one = Team::where('id', $request->team_one_id)->first();
            $team_two = Team::where('id', $request->team_two_id)->first();

            if ($team_one == null || $team_two == null) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'One of the team IDs is incorrect', 'toast' => true]);
            } else if ($team_one->sport_id !== $team_two->sport_id) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Both teams do not belong to the same sport', 'toast' => true]);
            }

            $match = new TeamMatch();

            $match->organizer_id = $user->id;
            $match->sport_id = $tournament->sport_id;
            $match->tournament_id = $tournament->id;
            $match->team_one_id = $request->team_one_id;
            $match->team_two_id = $request->team_two_id;
            $match->location = $request->location;
            $match->date = $request->date;
            $match->time = $request->time;
            $match->save();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Match added successfully', 'toast' => true], ['data' => $match]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding Match: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }


    public function updateMatch(UpdateMatchRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $match_id = $request->match_id;

            $match = TeamMatch::where('id', $match_id)->where('organizer_id', $user->id)->first();

            if (!$match) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Match not found', 'toast' => true]);
            }

            if ($request->filled('team_one_id')) {
                $team_one = Team::where('id', $request->team_one_id)->first();
                if (!$team_one) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team one ID incorrect', 'toast' => true]);
                }

                $sport_id = Tournament::where('id', $match->tournament_id)->where('sport_id',  $team_one->sport_id)->first();

                if (!$sport_id || $sport_id == null) {

                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team one does not belong to the same sport', 'toast' => true]);
                }

                $match->team_one_id = $request->team_one_id;
            }

            if ($request->filled('team_two_id')) {
                $team_two = Team::where('id', $request->team_two_id)->first();
                if (!$team_two) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team two ID incorrect', 'toast' => true]);
                }
                $tournament = Tournament::where('id', $match->tournament_id)->where('sport_id',  $team_two->sport_id)->first();

                if (!$tournament || $tournament == null) {

                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Team two does not belong to the same sport', 'toast' => true]);
                }
                $match->team_two_id = $request->team_two_id;
            }

            if ($request->filled('location')) {
                $match->location = $request->location;
            }
            if ($request->filled('date')) {
                $match->date = $request->date;
            }
            if ($request->filled('time')) {
                $match->time = $request->time;
            }

            $match->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Match updated successfully', 'toast' => true], ['data' => $match]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating Match: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }

    public function deleteMatch(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $match_id = $request->match_id;

            $match = TeamMatch::where('id', $match_id)->where('organizer_id', $user->id)->first();

            if (!$match) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Match not found', 'toast' => true]);
            }
            $match->delete();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Match deleted successfully', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating Match: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }
}
