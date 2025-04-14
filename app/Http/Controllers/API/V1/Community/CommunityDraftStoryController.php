<?php

namespace App\Http\Controllers\API\V1\Community;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CommunityStory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Community\SaveDraftRequest;
use App\Http\Requests\Community\UpdateDraftRequest;
use App\Models\CommunityDraftStory;

class CommunityDraftStoryController extends Controller
{
  public function saveDraft(SaveDraftRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $userFolder = "users/private/{$user->id}/community/stories/drafts";
      Storage::makeDirectory($userFolder);

      $mediaPaths = [];
      $temporaryUrls = [];

      if ($request->hasFile('media') && $request->file('media')->isValid()) {
        $media = $request->file('media');
        $mediaName = time() . '_' . $media->getClientOriginalName();
        $mediaPath = $media->storeAs($userFolder, $mediaName);
        $mediaPaths[] = $mediaPath;
        $temporaryUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(60));
      }

      $draft = new CommunityDraftStory();
      $draft->user_id = $user->id;
      $draft->media_type = $request->input('media_type');
      $draft->media_path = json_encode($mediaPaths);
      $draft->save();

      DB::commit();
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Draft saved successfully', 'toast' => true, 'draft' => $draft, 'mediaUrls' => $temporaryUrls]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error saving draft: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error saving draft', 'toast' => true,]);
    }
  }

  public function getSavedDrafts(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $drafts = CommunityDraftStory::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
      DB::commit();

      if ($drafts->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No drafts yet.', 'toast' => true]);
      } else {
        $draftsData = [];
        foreach ($drafts as $draft) {
          $draftData = $draft->toArray();
          $mediaPaths = json_decode($draftData['media_path'], true);

          $mediaUrls = [];
          if (is_array($mediaPaths)) {
            foreach ($mediaPaths as $mediaPath) {
              $mediaUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(5));
            }
          }

          $draftData['mediaUrls'] = $mediaUrls;
          $draftsData[] = $draftData;
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Saved drafts retrieved successfully.', 'toast' => true], ['draftData' => $draftsData]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error retrieving drafts: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error occurred while retrieving draft data', 'toast' => true]);
    }
  }
  public function updateDraft(UpdateDraftRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $id = $request->input('id');

      $draft = CommunityDraftStory::findOrFail($id);

      $userFolder = "users/private/{$user->id}/stories/drafts";
      Storage::makeDirectory($userFolder);

      $mediaPaths = [];
      $temporaryUrls = [];

      if ($request->hasFile('media') && $request->file('media')->isValid()) {
        $media = $request->file('media');
        $mediaName = time() . '_' . $media->getClientOriginalName();
        $mediaPath = $media->storeAs($userFolder, $mediaName);
        $mediaPaths[] = $mediaPath;
        $temporaryUrls[] = Storage::temporaryUrl($mediaPath, now()->addMinutes(60));

        $draft->media_type = $request->input('media_type');
        $draft->media_path = json_encode($mediaPaths);
      } else {
        $draft->media_path = json_encode($draft->media_path ? json_decode($draft->media_path) : []);
      }

      $draft->save();

      DB::commit();
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Draft updated successfully', 'toast' => true, 'draft' => $draft, 'mediaUrls' => $temporaryUrls]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error updating draft: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error updating draft', 'toast' => true,]);
    }
  }

  public function deleteDraft(Request $request)
  {
    $request->validate([
      'id' => 'required|exists:community_draft_stories,id',
    ]);
    DB::beginTransaction();
    try {
      $id = $request->input('id');
      $draft = CommunityDraftStory::findOrFail($id);
      $mediaPaths = json_decode($draft->media_path, true);

      $draft->delete();

      foreach ($mediaPaths as $path) {
        if (Storage::exists($path)) {
          Storage::delete($path);
        }
      }
      DB::commit();
      return response()->json(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Draft story deleted successfully', 'toast' => true,]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error discarding draft: ' . $e->getMessage());
      return response()->json(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error discarding draft', 'toast' => true,]);
    }
  }
}
