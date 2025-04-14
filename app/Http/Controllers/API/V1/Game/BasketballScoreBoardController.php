<?php

namespace App\Http\Controllers\API\V1\Game;

use Pusher\Pusher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Game\BasketballScoreboard;

class BasketballScoreBoardController extends Controller
{
    public function addScore(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');

            $requestData = $request->all();

            $scoreboard = BasketballScoreboard::where('tournament_id', $requestData['tournament_id'])
                ->where('team_one_id', $requestData['team_one_id'])
                ->where('team_two_id', $requestData['team_two_id'])
                ->first();

            if ($scoreboard) {
                $scoreboard->update([
                    'team_one_score' => $requestData['team_one_score'],
                    'team_two_score' => $requestData['team_two_score'],
                    'team_one_log' => $requestData['team_one_log'],
                    'team_two_log' => $requestData['team_two_log']
                ]);

                $message = 'Scores updated successfully';
            } else {

                $scoreboard = new BasketballScoreboard();
                $scoreboard->organizer_id = $user->id;
                $scoreboard->tournament_id = $requestData['tournament_id'];
                $scoreboard->team_one_id = $requestData['team_one_id'];
                $scoreboard->team_two_id = $requestData['team_two_id'];
                $scoreboard->team_one_score = $requestData['team_one_score'];
                $scoreboard->team_two_score = $requestData['team_two_score'];
                $scoreboard->team_one_log = $requestData['team_one_log'];
                $scoreboard->team_two_log = $requestData['team_two_log'];
                $scoreboard->save();

                $message = 'Scores added successfully';
            }

            DB::commit();


            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => 'ap2',
                    'useTLS' => true,
                ]
            );

            $pusher->trigger('basketball-scores', 'new-score', $scoreboard->toArray());

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true, 'data' => $scoreboard->toArray()]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding/updating scores: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
