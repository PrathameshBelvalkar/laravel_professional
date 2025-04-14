<?php

namespace App\Http\Controllers\API\V1\Community;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CommunityUserProfile;
use App\Models\CommunityLive;
use App\Models\CommunityLiveComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CommunityLiveController extends Controller
{
  public function createCommunityLiveStream(Request $request)
  {
    DB::beginTransaction();
    $communityLiveStream = new CommunityLive();
    try {
      $existingLiveStream = CommunityLive::where('stream_title', $request->stream_title)->first();

      if ($existingLiveStream) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The live stream title has already been taken.', 'toast' => true, 'data' => []]);
      }

      $user = $request->attributes->get('user');
      $communityLiveStream->user_id = $user->id;

      $communityLiveStream->community_id = Str::uuid();

      if (!$request->filled('stream_title')) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Live stream title not provided', 'toast' => true, 'data' => []]);
      }
      $communityLiveStream->stream_title = $request->stream_title;

      if (!$request->filled('stream_key_id')) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Stream key id not provided', 'toast' => true, 'data' => []]);
      }

      $existingLiveStream = CommunityLive::where('stream_key_id', $request->stream_key_id)->first();
      if ($existingLiveStream) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'The stream key id has already been taken.', 'toast' => true, 'data' => []]);
      }
      $communityLiveStream->stream_key_id = $request->stream_key_id;

      $communityLiveStream->stream_key = hash('sha256', Str::random(32));
      $communityLiveStream->playback_url = hash('sha256', Str::random(32));

      if ($request->filled('stream_url_live')) {
        $communityLiveStream->stream_url_live = $request->stream_url_live;
      }

      $communityLiveStream->audience = $request->filled('audience') ? $request->audience : 0;
      $communityLiveStream->views = $request->filled('views') ? $request->views : 0;

      $communityLiveStream->save();

      DB::commit();
      $newLiveStream = CommunityLive::where('id', $communityLiveStream->id)->first();
      $liveStreamData = [
        'id' => $newLiveStream->id,
        'stream_title' => $newLiveStream->stream_title,
        'stream_key_id' => $newLiveStream->stream_key_id,
        'stream_key' => $newLiveStream->stream_key,
        'playback_url' => $newLiveStream->playback_url,
        'community_id' => $newLiveStream->community_id,
        'stream_url_live' => $newLiveStream->stream_url_live,
        'views' => $newLiveStream->views
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Community live stream created successfully.', 'toast' => true, 'data' => ['live_stream_data' => $liveStreamData]]);
    } catch (\Exception $e) {
      Log::info('Error while creating community live stream: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error creating live stream.', 'toast' => true]);
    }
  }

  public function getCommunityLiveById(Request $request)
  {
    try {
      $community_id = $request->query('community_id');

      if (empty($community_id)) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Community ID is required.', 'toast' => true, 'data' => []]);
      }

      $communityLiveStream = CommunityLive::where('community_id', $community_id)->first();

      if (!$communityLiveStream) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Community live stream not found.', 'toast' => true, 'data' => []]);
      }

      $responseData = [
        'id' => $communityLiveStream->id,
        'stream_title' => $communityLiveStream->stream_title,
        'stream_url_live' => $communityLiveStream->stream_url_live,
        'views' => $communityLiveStream->views,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All data retrieved successfully.', 'toast' => true, 'data' => ['livestreams' => $responseData]]);
    } catch (\Exception $e) {
      Log::info('Error while fetching community live stream data: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing the request.', 'toast' => true, 'data' => []]);
    }
  }

  public function deleteCommunityLiveStream(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $stream_id = $request->stream_id;
      $communityLiveStream = CommunityLive::where('user_id', $userId)->where('id', $stream_id)->first();

      if (!$communityLiveStream) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Id not found.', 'toast' => true]);
      }

      $communityLiveStream->delete();
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::info('Error while deleting live stream: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting live stream.', 'toast' => true]);
    }
  }
  public function addLiveComment(Request $request)
  {

    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $streamId = $request->stream_id;

      $communityLiveStream = CommunityLive::findOrFail($streamId);
      if ($communityLiveStream) {
        $CommunityLiveComments = new CommunityLiveComment();
        $CommunityLiveComments->comment = $request->comment;
        $CommunityLiveComments->stream_id = $streamId;
        $CommunityLiveComments->user_id = $user->id;
        $commentData = $CommunityLiveComments->save();

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comment added successfully. ', 'toast' => true], ['live_comments' => $commentData]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Live stream not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Comment add error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
    }
  }

  public function getLiveComments(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $stream_id = $request->stream_id;
      $CommunityLiveComments = CommunityLiveComment::where('stream_id', $stream_id)->orderBy('created_at', 'desc')->get();

      if ($CommunityLiveComments->isNotEmpty()) {
        $transformedComments = $CommunityLiveComments->transform(function ($comment) {
          $commentUser = User::find($comment->user_id);
          $userProfile = UserProfile::where('user_id', $comment->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $comment->user_id)->first();

          $profileImagePath = null;
          $tempUrl = null;
          if ($communityUserProfile && $communityUserProfile->profile_image_path) {
            $profileImagePath = $communityUserProfile->profile_image_path;
            $tempUrl = getFileTemporaryURL($profileImagePath);
          } elseif ($userProfile && $userProfile->profile_image_path) {
            $profileImagePath = $userProfile->profile_image_path;
            $tempUrl = getFileTemporaryURL($profileImagePath);
          }
          return [
            'id' => $comment->id,
            'stream_id' => $comment->stream_id,
            'user_id' => $comment->user_id,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'username' => $commentUser->username,
            'profile_image_path' => $profileImagePath,
            'media_url' => $tempUrl,
          ];
        });

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Comments retrieved successfully', 'toast' => true], ['livecomments' => $transformedComments]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Comments not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Error fetching comments: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error fetching comments.', 'toast' => true]);
    }
  }
}
