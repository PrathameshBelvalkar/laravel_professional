<?php

namespace App\Http\Controllers\API\V1\Game;

use App\Models\Game\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Game\Player\AddPlayerRequest;
use App\Http\Requests\Game\Player\GetPlayerRequest;
use App\Http\Requests\Game\Player\UpdatePlayerRequest;

class PlayerController extends Controller
{
    public function getPlayer(GetPlayerRequest $request)
    {

        try {
            $id = $request->id;
            $player = Player::where('id', $id)->first();
            $player['player_image'] = getFileTemporaryURL($player['player_image']);


            if (!$player) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Player data with the provided player id not found', 'toast' => true]);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Player retrieved successfully', 'toast' => true, 'data' => $player->toArray()]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving player: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getPlayerList(Request $request)
    {
        try {
            $query = Player::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('player_name', 'LIKE', '%' . $searchTerm . '%');
            }

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $players = $query->offset($offset)->limit($limit)->get();

            foreach ($players as $player) {
                $player->player_image = getFileTemporaryURL($player->player_image);
            }

            $count = $query->count();

            if ($count <= 0) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No players found matching the search criteria', 'toast' => true]);
            }

            return generateResponse([
                'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Players retrieved successfully', 'toast' => true, 'data' => $players->toArray(), 'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Error while retrieving players: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing',]);
        }
    }

    public function addPlayer(AddPlayerRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $userFolder = "users/private/{$user->id}/game/team_{$request->team_id}/player";
            Storage::makeDirectory($userFolder);

            $existingPlayer = Player::where('player_name', $request->player_name)->where('team_id', $request->team_id)->first();

            if ($existingPlayer) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Player name already exists with this team ID ' . $request->team_id, 'toast' => true]);
            }


            $player = new Player();
            $player->organizer_id = $user->id;
            $player->team_id = $request->team_id;
            $player->player_name = $request->player_name;

            if ($request->hasFile('player_image') && $request->file('player_image')->isValid()) {
                $imageName = $request->player_image->getClientOriginalName();
                $imagePath = $request->player_image->storeAs($userFolder, $imageName);
                $player->player_image =  $imagePath;
            }

            $player->player_position = $request->player_position;
            $player->display_number = $request->display_number;
            $player->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Player added successfully', 'toast' => true], ['data' => $player]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding player: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }

    public function updatePlayer(UpdatePlayerRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $player_id = $request->player_id;

            $userFolder = "users/private/{$user->id}/game/team_{$request->team_id}/player";
            Storage::makeDirectory($userFolder);

            $player = Player::where('id', $player_id)->where('organizer_id', $user->id)->first();

            if (!$player) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Player id not found', 'toast' => true]);
            }

            // Check if a player with the same name already exists within the specified team
            $existingPlayer = Player::where('player_name', $request->player_name)->where('team_id', $request->team_id)->where('id', '!=', $player_id)->first();

            if ($existingPlayer) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Player name already exists with this team ID ' . $request->team_id, 'toast' => true]);
            }

            if ($request->hasFile('player_image') && $request->file('player_image')->isValid()) {
                $imageName = $request->player_image->getClientOriginalName();
                $imagePath = $request->player_image->storeAs($userFolder, $imageName);

                if ($player->player_image) {
                    Storage::delete($player->player_image);
                }
                $player->player_image = $imagePath;
            }

            if (isset($request->player_name)) {
                $player->player_name = $request->player_name;
            }
            if (isset($request->player_position)) {
                $player->player_position = $request->player_position;
            }
            if (isset($request->display_number)) {
                $player->display_number = $request->display_number;
            }

            $player->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Player updated successfully', 'toast' => true], ['data' => $player]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating player: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }


    public function deletePlayer(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $player_id = $request->player_id;

            $player = Player::where('id', $player_id)->where('organizer_id', $user->id)->first();

            if (!$player) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Player not found', 'toast' => true]);
            }

            if ($player->player_image) {
                Storage::delete($player->player_image);
            }
            $player->delete();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Player deleted successfully', 'toast' => true]);
        } catch (\Exception $e) {
            Log::error('Error deleting player: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while proceeding', 'toast' => true]);
        }
    }
}
