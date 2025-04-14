<?php

namespace App\Http\Controllers\API\V1\Community;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CommunityStory;
use App\Models\CommunityStorySeen;
use App\Models\CommunityStoryTag;
use App\Models\user;
use App\Models\UserProfile;
use App\Models\CommunityStoryHighlight;
use App\Models\CommunityUserProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Community\UploadStoryRequest;
use App\Http\Requests\Community\StorySeenStatusRequest;
use App\Http\Requests\Community\ReportStoryRequest;
use App\Http\Requests\Community\UpdateHighlightRequest;


class CommunityStoryController extends Controller
{

  public function uploadStory(UploadStoryRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $Story = new CommunityStory();

      $userFolder = "users/private/{$user->id}/community/stories";
      Storage::makeDirectory($userFolder);

      $mediaPaths = [];

      if ($request->hasFile('media')) {
        $media = $request->file('media');
        if ($media->isValid()) {
          $mediaName = time() . '_' . $media->getClientOriginalName();
          $mediaPath = $media->storeAs($userFolder, $mediaName);
          $mediaPaths[] = $mediaPath;
        }
      }

      $Story->user_id = $user->id;
      $Story->media_type = $request->media_type;
      $Story->expires_at = Carbon::now()->addHours(24);
      $Story->visibility = $request->visibility;
      $Story->is_archived = true;
      if ($request->has('location')) {
        $Story->location = $request->location;
      }
      if ($request->visibility == '2' && $request->has('shared_with')) {
        $Story->shared_with = json_encode($request->shared_with);
      } else {
        $Story->shared_with = json_encode([]);
      }

      $Story->media_path = json_encode($mediaPaths);

      if ($request->filled('tagged_users')) {
        $taggedUserIds = array_map('trim', explode(',', $request->tagged_users));
        $Story->tagged_users = json_encode($taggedUserIds);
      }
      $Story->is_expired = false;

      $Story->save();

      if (!empty($taggedUserIds)) {
        foreach ($taggedUserIds as $userId) {
          CommunityStoryTag::create([
            'story_id' => $Story->id,
            'user_id' => $userId,
          ]);
          $authToken = $request->header('authToken');
          addNotification($userId, $user->id, "{$user->username} Tagged you in a post.", "Tagged you in a post", null, "18", null, null, $authToken);
        }
      }
      $authToken = $request->header('authToken');
      addNotification($user->id, $user->id, "Story uploaded!", "Story uploaded successfully.", null,  "18", null, null, $authToken);

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story uploaded successfully', 'toast' => true], ['storyData' => $Story]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error uploading story: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error uploading story', 'toast' => true]);
    }
  }

  public function getTaggedStories(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = (string)$user->id;

      $taggedStories = CommunityStory::whereJsonContains('tagged_users', $userId)->where('is_expired', false)->get();

      $userDetails = [];

      foreach ($taggedStories as $story) {
        $userProfile = UserProfile::where('user_id', $story->user_id)->first();
        $communityUserProfile = CommunityUserProfile::where('user_id', $story->user_id)->first();
        $user = User::find($story->user_id);

        if (!isset($userDetails[$story->user_id])) {
          $profileImagePath = null;
          $profileImageTempUrl = null;
          if ($communityUserProfile) {
            $profileImagePath = $communityUserProfile->profile_image_path;
          } elseif ($userProfile) {
            $profileImagePath = $userProfile->profile_image_path;
          }
          if ($profileImagePath) {
            $profileImageTempUrl = getFileTemporaryURL($profileImagePath);
          }

          $userDetails[$story->user_id] = [
            'user_id' => $story->user_id,
            'username' => $user ? $user->username : null,
            'profile_image_path' => $profileImagePath,
            'profile_image_temp_url' => $profileImageTempUrl,
            'stories' => []
          ];
        }

        $taggedUserIds = json_decode($story->tagged_users, true);
        $taggedUsers = [];
        if (is_array($taggedUserIds)) {
          foreach ($taggedUserIds as $taggedUserId) {
            $taggedUser = User::find($taggedUserId);
            if ($taggedUser) {
              $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
              $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();

              $taggedProfileImagePath = null;
              if ($taggedUserCommunityProfile && $taggedUserCommunityProfile->profile_image_path) {
                $taggedProfileImagePath = $taggedUserCommunityProfile->profile_image_path;
              } elseif ($taggedUserProfile) {
                $taggedProfileImagePath = $taggedUserProfile->profile_image_path;
              }

              $taggedProfileImageTempUrl = $taggedProfileImagePath ? Storage::temporaryUrl($taggedProfileImagePath, now()->addMinutes(60)) : null;

              $taggedUsers[] = [
                'id' => $taggedUser->id,
                'username' => $taggedUser->username,
                'profile_image_path' => $taggedProfileImagePath,
                'profile_image_temp_url' => $taggedProfileImageTempUrl,
              ];
            }
          }
        }

        $mediaPaths = json_decode($story->media_path, true);
        $mediaUrls = [];
        if (is_array($mediaPaths) && !empty($mediaPaths)) {
          foreach ($mediaPaths as $mediaPath) {
            $mediaUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(60));
          }
        }

        $userDetails[$story->user_id]['stories'][] = [
          'id' => $story->id,
          'media_type' => $story->media_type,
          'media_path' => $story->media_path,
          'visibility' => $story->visibility,
          'shared_with' => $story->shared_with,
          'location' => $story->location,
          'tagged_users' => $story->tagged_users,
          'tagged_users_details' => $taggedUsers,
          'expires_at' => $story->expires_at,
          'is_expired' => $story->is_expired,
          'created_at' => $story->created_at,
          'updated_at' => $story->updated_at,
          'mediaUrls' => $mediaUrls
        ];
      }

      $responseData = array_values($userDetails);
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Tagged stories retrieved successfully.', 'data' => $responseData]);
    } catch (\Exception $e) {
      Log::error('Error retrieving tagged stories: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving tagged stories',]);
    }
  }

  public function deleteStory(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $story_id = $request->story_id;
      $Story = CommunityStory::where('user_id', $user->id)->where('id', $story_id)->first();

      if ($Story) {
        $mediaPaths = json_decode($Story->media_path, true);
        // if (!empty($mediaPaths)) {
        //   foreach ($mediaPaths as $mediaPath) {
        //     if (Storage::exists($mediaPath)) {
        //       Storage::delete($mediaPath);
        //     }
        //   }
        //}

        $Story->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story deleted successfully.', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Story not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error deleting story: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting story.', 'toast' => true]);
    }
  }

  public function toggleLikeStory(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $story_id = $request->story_id;
      $story = CommunityStory::find($story_id);

      if (!$story) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Story not found.', 'toast' => true]);
      }

      $likes = json_decode($story->like_dislike, true) ?? [];
      $user_id = $user->id;

      $userAlreadyLiked = false;
      $index = null;

      foreach ($likes as $key => $like) {
        if ($like['user_id'] == $user_id) {
          $userAlreadyLiked = true;
          $index = $key;
          break;
        }
      }

      if ($userAlreadyLiked) {
        unset($likes[$index]);
        $message = 'Story disliked successfully.';
      } else {
        $likes[] = ['user_id' => $user_id];
        $message = 'Story liked successfully.';

        $authToken = $request->header('authToken');
        addNotification($story->user_id, $user_id, "{$user->username} liked your story.", "liked your story", null, "18", null, null, $authToken);
      }

      $story->like_dislike = json_encode(array_values($likes));
      $story->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Story like/dislike error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error on story like/dislike.', 'toast' => true]);
    }
  }

  public function getStory(Request $request)
  {
    DB::beginTransaction();
    try {
      $stories = CommunityStory::where('is_deleted_from_archive', '0')->orderBy('created_at', 'desc')->get();
      // $stories = CommunityStory::whereNull('deleted_at')->where('is_expired', false)->orderBy('created_at', 'desc')->get();

      $userDetails = [];

      foreach ($stories as $story) {
        if (Carbon::now()->greaterThan($story->expires_at)) {
          $story->is_expired = true;
          $story->save();
        }

        if (!$story->is_expired) {
          $userProfile = UserProfile::where('user_id', $story->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $story->user_id)->first();

          $user = User::find($story->user_id);

          if (!isset($userDetails[$story->user_id])) {
            $profileImagePath = null;
            $profileImageTempUrl = null;
            if ($communityUserProfile) {
              $profileImagePath = $communityUserProfile->profile_image_path;
            } elseif ($userProfile) {
              $profileImagePath = $userProfile->profile_image_path;
            }
            if ($profileImagePath) {
              $profileImageTempUrl = getFileTemporaryURL($profileImagePath);
            }
            $userDetails[$story->user_id] = [
              'user_id' => $story->user_id,
              'username' => $user ? $user->username : null,
              'profile_image_path' => $profileImagePath,
              'profile_image_temp_url' => $profileImageTempUrl,
              'stories' => []
            ];
          }
          $taggedUserIds = json_decode($story->tagged_users, true);
          $taggedUsers = [];
          if (is_array($taggedUserIds)) {
            foreach ($taggedUserIds as $taggedUserId) {
              $taggedUser = User::find($taggedUserId);
              if ($taggedUser) {
                $taggedUserProfile = UserProfile::where('user_id', $taggedUser->id)->first();
                $taggedUserCommunityProfile = CommunityUserProfile::where('user_id', $taggedUser->id)->first();

                $taggedProfileImagePath = null;
                if ($taggedUserCommunityProfile && $taggedUserCommunityProfile->profile_image_path) {
                  $taggedProfileImagePath = $taggedUserCommunityProfile->profile_image_path;
                } elseif ($taggedUserProfile) {
                  $taggedProfileImagePath = $taggedUserProfile->profile_image_path;
                }

                $taggedProfileImageTempUrl = $taggedProfileImagePath ? Storage::temporaryUrl($taggedProfileImagePath, now()->addMinutes(60)) : null;

                $taggedUsers[] = [
                  'id' => $taggedUser->id,
                  'username' => $taggedUser->username,
                  'profile_image_path' => $taggedProfileImagePath,
                  'profile_image_temp_url' => $taggedProfileImageTempUrl,
                ];
              }
            }
          }
          $mediaPaths = json_decode($story->media_path, true);
          $mediaUrls = [];

          if (is_string($mediaPaths)) {
            $mediaPaths = [trim($mediaPaths, '"')];
          }

          if (is_array($mediaPaths) && !empty($mediaPaths)) {
            foreach ($mediaPaths as $mediaPath) {
              $mediaUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(60));
            }
          }

          $mediaUrlsString = implode(', ', $mediaUrls);

          $userDetails[$story->user_id]['stories'][] = [
            'id' => $story->id,
            'media_type' => $story->media_type,
            'media_path' => $story->media_path,
            'likes' => $story->like_dislike,
            'visibility' => $story->visibility,
            'shared_with' => $story->shared_with,
            'location' => $story->location,
            'tagged_users' => $story->tagged_users,
            'tagged_users_details' => $taggedUsers,
            'expires_at' => $story->expires_at,
            'deleted_at' => $story->deleted_at,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'mediaUrls' => $mediaUrlsString,
            'is_expired' => $story->is_expired
          ];
        }
      }

      DB::commit();
      $responseData = array_values($userDetails);
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Stories retrieved successfully.', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while retrieving stories: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function getAllUserStory(Request $request)
  {
    DB::beginTransaction();
    try {
      $loggedInUser = $request->attributes->get('user');
      $userStory = $request->query('user_story', false);
      $storiesQuery = CommunityStory::where('is_deleted_from_archive', '0');

      if ($userStory === 'true') {
        $storiesQuery->where('user_id', $loggedInUser->id);
      } else {
        $storiesQuery->where('user_id', '!=', $loggedInUser->id);
      }

      $stories = $storiesQuery->orderBy('created_at', 'desc')->get();

      $userDetails = [];
      foreach ($stories as $story) {
        if (Carbon::now()->greaterThan($story->expires_at)) {
          $story->is_expired = true;
          $story->save();
        }

        if (!$story->is_expired) {
          $userProfile = UserProfile::where('user_id', $story->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $story->user_id)->first();
          $user = User::find($story->user_id);

          if (!isset($userDetails[$story->user_id])) {
            $profileImagePath = null;
            $profileImageTempUrl = null;

            if ($communityUserProfile) {
              $profileImagePath = $communityUserProfile->profile_image_path;
            } elseif ($userProfile) {
              $profileImagePath = $userProfile->profile_image_path;
            }

            if ($profileImagePath) {
              $profileImageTempUrl = getFileTemporaryURL($profileImagePath);
            }

            $userDetails[$story->user_id] = [
              'user_id' => $story->user_id,
              'username' => $user ? $user->username : null,
              'profile_image_path' => $profileImagePath,
              'profile_image_temp_url' => $profileImageTempUrl,
              'stories' => []
            ];
          }

          $mediaPaths = json_decode($story->media_path, true);
          $mediaUrls = [];

          if (is_string($mediaPaths)) {
            $mediaPaths = [trim($mediaPaths, '"')];
          }

          if (is_array($mediaPaths) && !empty($mediaPaths)) {
            foreach ($mediaPaths as $mediaPath) {
              $mediaUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(60));
            }
          }

          $mediaUrlsString = implode(', ', $mediaUrls);

          $userDetails[$story->user_id]['stories'][] = [
            'id' => $story->id,
            'media_type' => $story->media_type,
            'media_path' => $story->media_path,
            'likes' => $story->like_dislike,
            'visibility' => $story->visibility,
            'location' => $story->location,
            'shared_with' => $story->shared_with,
            'expires_at' => $story->expires_at,
            'deleted_at' => $story->deleted_at,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'mediaUrls' => $mediaUrlsString,
            'is_expired' => $story->is_expired
          ];
        }
      }

      DB::commit();
      $responseData = array_values($userDetails);
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $userStory === 'true' ? 'Logged-in user\'s stories retrieved successfully.' : 'Other users\' stories retrieved successfully.', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while retrieving stories: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function getArchiveStories(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $archivedStories = CommunityStory::withTrashed()->where('user_id', $user->id)->where('is_archived', true)->where('is_deleted_from_archive', '0')->orderBy('created_at', 'desc')->get();

      $userDetails = [];

      foreach ($archivedStories as $story) {
        if (!$story->is_expired) {
          $userProfile = UserProfile::where('user_id', $story->user_id)->first();
          $communityUserProfile = CommunityUserProfile::where('user_id', $story->user_id)->first();

          $user = User::find($story->user_id);

          if (!isset($userDetails[$story->user_id])) {
            $profileImagePath = $communityUserProfile->profile_image_path ?? $userProfile->profile_image_path ?? null;
            $profileImageTempUrl = getFileTemporaryURL($profileImagePath);

            $userDetails[$story->user_id] = [
              'user_id' => $story->user_id,
              'username' => $user ? $user->username : null,
              'profile_image_path' => $profileImagePath,
              'profile_image_temp_url' => $profileImageTempUrl,
              'stories' => []
            ];
          }

          $mediaPaths = json_decode($story->media_path, true);
          $mediaUrls = array_map(function ($mediaPath) {
            return Storage::temporaryUrl($mediaPath, now()->addMinutes(60));
          }, $mediaPaths);

          $userDetails[$story->user_id]['stories'][] = [
            'id' => $story->id,
            'media_type' => $story->media_type,
            'media_path' => $story->media_path,
            'likes' => $story->like_dislike,
            'visibility' => $story->visibility,
            'shared_with' => $story->shared_with,
            'location' => $story->location,
            'is_expired' => $story->is_expired,
            'expires_at' => $story->expires_at,
            'is_archived' => $story->is_archived,
            'is_deleted_from_archive' => $story->is_deleted_from_archive,
            'deleted_at' => $story->deleted_at,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'mediaUrls' => implode(', ', $mediaUrls),
          ];
        }
      }

      DB::commit();
      $responseData = array_values($userDetails);
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Archived stories retrieved successfully.', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving archived stories: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function deleteArchiveStory(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $story_id = $request->story_id;

      $Story = CommunityStory::withTrashed()->where('user_id', $user->id)->where('id', $story_id)->where('is_archived', true)->where('is_deleted_from_archive', '0')->first();

      if ($Story) {
        $Story->is_deleted_from_archive = '1';
        $Story->save();

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Archived story marked as deleted from archive.', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Archived story not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error marking archived story as deleted: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error marking archived story as deleted.', 'toast' => true]);
    }
  }

  public function addHighlight(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $title = $request->input('title');
      $storyIdsString = $request->input('story_ids');

      $storyIds = json_decode($storyIdsString, true);

      if (!is_array($storyIds)) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => "Invalid format for story_ids.", 'toast' => true]);
      }

      foreach ($storyIds as $id) {
        if (!is_numeric($id)) {
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => "Invalid story ID: $id", 'toast' => true]);
        }
      }

      $stories = CommunityStory::withTrashed()->whereIn('id', $storyIds)->where('user_id', $user->id)->where('is_archived', true)->where('is_expired', false)->get();

      if ($stories->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'No valid archived stories found for the logged-in user.', 'toast' => true]);
      }
      $userFolder = "users/private/{$user->id}/community/highlights/cover_images";
      Storage::makeDirectory($userFolder);

      $coverImgPath = null;
      if ($request->hasFile('cover_img')) {
        $coverImg = $request->file('cover_img');
        if ($coverImg->isValid()) {
          $coverImgName = time() . '_' . $coverImg->getClientOriginalName();
          $coverImgPath = $coverImg->storeAs($userFolder, $coverImgName);
        }
      }
      $highlight = CommunityStoryHighlight::create([
        'user_id' => $user->id,
        'title' => $title,
        'story_ids' => json_encode($storyIds),
        'cover_img' => $coverImgPath
      ]);

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Stories added to highlight successfully', 'data' => $highlight, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding stories to highlight: ' . $e->getMessage(), 'toast' => true]);
    }
  }
  public function addStoriesToHighlight(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $highlightId = $request->id;
      $highlight = CommunityStoryHighlight::where('id', $highlightId)->where('user_id', $user->id)
        ->first();

      if (!$highlight) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Highlight not found or you do not have permission to edit it.', 'toast' => true]);
      }

      $currentStoryIds = json_decode($highlight->story_ids, true);
      if (!is_array($currentStoryIds)) {
        $currentStoryIds = [];
      }

      $newStoryIdsString = $request->input('story_ids');
      $newStoryIds = json_decode($newStoryIdsString, true);

      if (!is_array($newStoryIds)) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid format for story_ids.', 'toast' => true]);
      }

      foreach ($newStoryIds as $id) {
        if (!is_numeric($id)) {
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => "Invalid story ID: $id", 'toast' => true]);
        }
      }

      $stories = CommunityStory::withTrashed()->whereIn('id', $newStoryIds)->where('user_id', $user->id)->where('is_archived', true)->where('is_expired', false)->get();

      if ($stories->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'No valid archived stories found for the logged-in user.', 'toast' => true]);
      }
      $existingStories = array_intersect($newStoryIds, $currentStoryIds);
      if (!empty($existingStories)) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'The following stories are already in the highlight: ' . implode(', ', $existingStories),
          'toast' => true
        ]);
      }
      $mergedStoryIds = array_unique(array_merge($currentStoryIds, $newStoryIds));

      $highlight->story_ids = json_encode($mergedStoryIds);
      $highlight->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Stories added to highlight successfully.', 'data' => $highlight, 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding stories to highlight: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function getHighlights(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $targetUserId = $request->input('userId');

      if (is_null($targetUserId)) {
        $targetUserId = $user->id;
      }

      $highlights = CommunityStoryHighlight::where('user_id', $targetUserId)->orderBy('created_at', 'desc')->get();

      $highlightData = [];

      foreach ($highlights as $highlight) {
        $storyIds = json_decode($highlight->story_ids, true);

        $stories = CommunityStory::withTrashed()->whereIn('id', $storyIds)->where('is_deleted_from_archive', '0')->get();

        $storiesWithMediaUrls = $stories->map(function ($story) {
          $mediaPaths = json_decode($story->media_path, true);
          $mediaUrl = '';

          if (is_array($mediaPaths) && !empty($mediaPaths)) {
            $mediaUrl = getFileTemporaryURL(ltrim($mediaPaths[0], '/'));
          }

          return [
            'id' => $story->id,
            'user_id' => $story->user_id,
            'media_type' => $story->media_type,
            'media_path' => $story->media_path,
            'likes' => $story->like_dislike,
            'visibility' => $story->visibility,
            'shared_with' => $story->shared_with,
            'location' => $story->location,
            'expires_at' => $story->expires_at,
            'deleted_at' => $story->deleted_at,
            'created_at' => $story->created_at,
            'updated_at' => $story->updated_at,
            'mediaUrls' => $mediaUrl,
            'is_expired' => $story->is_expired,
            'is_deleted' => $story->trashed(),
            'is_archived' => $story->is_delete_from_archive,
          ];
        });

        if ($storiesWithMediaUrls->isEmpty()) {
          $highlight->delete();
          continue;
        }

        $coverImgPath = $highlight->cover_img;
        $coverImageTempUrl = $coverImgPath ? Storage::temporaryUrl($coverImgPath, now()->addMinutes(60)) : null;

        $highlightData[] = [
          'highlight_id' => $highlight->id,
          'title' => $highlight->title,
          'cover_img' => $coverImgPath,
          'cover_img_temp_url' => $coverImageTempUrl,
          'stories' => $storiesWithMediaUrls,
        ];
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Highlights retrieved successfully', 'data' => $highlightData, 'toast' => true]);
    } catch (\Exception $e) {
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving highlights: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function deleteHighlight(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $highlightId = $request->id;
      $storyId = $request->story_id;

      $highlight = CommunityStoryHighlight::where('id', $highlightId)->where('user_id', $user->id)->first();

      if (!$highlight) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Highlight not found or does not belong to the user.', 'toast' => true]);
      }

      $storyIds = json_decode($highlight->story_ids, true);

      if (($key = array_search($storyId, $storyIds)) !== false) {
        unset($storyIds[$key]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Story ID not found in highlight.', 'toast' => true]);
      }

      if (empty($storyIds)) {
        $highlight->delete();
      } else {
        $highlight->story_ids = json_encode(array_values($storyIds));
        $highlight->save();
      }

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story deleted from highlight successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting story from highlight: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function updateHighlight(UpdateHighlightRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $id = $request->id;
      $highlight = CommunityStoryHighlight::where('id', $id)->where('user_id', $user->id)->first();

      if (!$highlight) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Highlight not found.', 'toast' => true]);
      }

      if ($request->has('title')) {
        $highlight->title = $request->input('title');
      }

      if ($request->hasFile('cover_img')) {
        $userFolder = "users/private/{$user->id}/community/highlights/cover_images";
        Storage::makeDirectory($userFolder);
        $coverImg = $request->file('cover_img');
        if ($coverImg->isValid()) {
          $coverImgName = time() . '_' . $coverImg->getClientOriginalName();
          $coverImgPath = $coverImg->storeAs($userFolder, $coverImgName);
          $highlight->cover_img = $coverImgPath;
        }
      }

      $existing_story_ids = json_decode($highlight->story_ids, true) ?? [];
      if ($request->has('add_story_ids')) {
        $add_story_ids = json_decode($request->input('add_story_ids'), true);
        if (is_array($add_story_ids)) {
          $existing_story_ids = array_unique(array_merge($existing_story_ids, $add_story_ids));
        }
      }

      if ($request->has('remove_story_ids')) {
        $remove_story_ids = json_decode($request->input('remove_story_ids'), true);
        if (is_array($remove_story_ids)) {
          $existing_story_ids = array_values(array_diff($existing_story_ids, $remove_story_ids));
        }
      }

      $highlight->story_ids = json_encode($existing_story_ids);

      $highlight->save();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Highlight updated successfully.', 'data' => $highlight, 'toast' => true]);
    } catch (\Exception $e) {
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error updating highlight: ' . $e->getMessage(), 'toast' => true]);
    }
  }

  public function getLikesCount(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $story_id = $request->input('story_id');

      if (!$story_id) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Story ID is required.', 'toast' => true]);
      }
      $CommunityStory = CommunityStory::where('id', $story_id)->where('user_id', $user->id)->first();

      if (!$CommunityStory) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Story not found.', 'toast' => true]);
      } else {
        $likes = json_decode($CommunityStory->like_dislike, true) ?? [];
        $likesCount = count($likes);

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Count of likes on this story'], ['likes_count' => $likesCount]);
      }
    } catch (\Exception $e) {
      Log::error('Get likes count error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error getting likes count.', 'toast' => true]);
    }
  }

  public function StorySeenStatus(StorySeenStatusRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $story_id = $request->input('story_id');
      $seen_by = $request->input('seen_by') ? '1' : '0';

      $story = CommunityStory::find($story_id);

      if (!$story) {
        DB::rollback();
        return response()->json(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Story not found.', 'toast' => true,]);
      }

      $seenStatus = CommunityStorySeen::where('story_id', $story_id)
        ->where('user_id', $user->id)
        ->first();

      if ($seenStatus) {
        if ($seenStatus->seen_by == '1') {
          DB::commit();
          return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story seen status already marked as seen.', 'toast' => true,]);
        }
        $seenStatus->update(['seen_by' => $seen_by]);
      } else {
        CommunityStorySeen::create([
          'story_id' => $story_id,
          'user_id' => $user->id,
          'seen_by' => $seen_by,
        ]);
      }

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story seen status updated successfully', 'toast' => true,]);
    } catch (\Exception $e) {
      DB::rollback();
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error updating story seen status', 'toast' => true,]);
    }
  }

  public function getStorySeenStatus(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'User not authenticated.', 'toast' => true]);
      }

      // Fetch unseen stories first
      $unseenStories = CommunityStory::leftJoin('community_story_seens', function ($join) use ($user) {
        $join->on('community_stories.id', '=', 'community_story_seens.story_id')->where('community_story_seens.user_id', $user->id);
      })->whereNull('community_story_seens.id')->select('community_stories.*')->orderBy('created_at', 'desc')->get();

      // Fetch seen stories
      $seenStories = CommunityStory::join('community_story_seens', function ($join) use ($user) {
        $join->on('community_stories.id', '=', 'community_story_seens.story_id')->where('community_story_seens.user_id', $user->id);
      })->select('community_stories.*')->orderBy('created_at', 'desc')->get();

      $stories = $unseenStories->merge($seenStories);

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Stories fetched successfully', 'data' => ['unseen_stories' => $unseenStories, 'seen_stories' => $seenStories], 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Get stories by seen status error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error fetching stories.', 'toast' => true]);
    }
  }
  public function reportStory(ReportStoryRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $storyId = $request->story_id;

      $CommunityStory = CommunityStory::findOrFail($storyId);
      if ($CommunityStory->user_id == $user->id) {
        return generateResponse(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'You cannot report your own story.', 'toast' => true]);
      }

      $reports = json_decode($CommunityStory->report_reason, true) ?? [];

      $reports[] = [
        'user_id' => $user->id,
        'reason' => $request->report_reason,
        'reported_at' => now()->toDateTimeString()
      ];

      $CommunityStory->report_reason = json_encode($reports);
      $CommunityStory->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Story reported successfully.', 'toast' => true], ['reports' => $reports]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Story not found.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Report add error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error processing the request', 'toast' => true]);
    }
  }
}
