<?php

namespace App\Http\Controllers\API\V1\Podcast;

use App\Http\Controllers\Controller;
use App\Http\Requests\Podcast\CreateEpisodeRequest;
use App\Http\Requests\Podcast\CreatePodcastRequest;
use App\Http\Requests\Podcast\UpdateEpisodeRequest;
use App\Http\Requests\Podcast\UpdatePodcastRequest;
use App\Models\Podcast\Artist;
use App\Models\Podcast\Episode;
use App\Models\Podcast\Podcasts;
use App\Models\Podcast\Podcategory;
use App\Models\Podcast\Podtag;
use App\Models\User;
use App\Models\UserProfile;
use getID3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Podcast\Langauge;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PodcastController extends Controller
{
  //Podcast Controllers
  public function addPodcast(CreatePodcastRequest $request)
  {
    try {
      $imagepath = null;
      $user = $request->attributes->get('user');
      $user_id = $user->id;
      $user_name = $user->username;

      $artist = Artist::where('user_id', $user_id)->first();

      if (!$artist) {
        $artist = Artist::create([
          'user_id' => $user_id,
          'artist_name' => $user_name,
          'artist_image' => null,
          'artist_cover_image' => null,
          'artist_bio' => null,
          'total_podcasts' => 0,
          'followers_count' => 0,
          'total_plays' => 0,
        ]);
      }

      // if ($request->hasFile('image_url')) {
      //   $imageFile = $request->file('image_url');
      //   $fileName = $imageFile->getClientOriginalName();
      //   $imagepath = "users/private/{$user_id}/podcast/{$fileName}";
      //   Storage::put($imagepath, file_get_contents($imageFile));
      // }

      if ($request->hasFile('image_url')) {
        $imageFile = $request->file('image_url');
        $fileName = $imageFile->getClientOriginalName();
        $imagepath = "podcast/{$user_id}/{$fileName}";
        $imageFile->move("podcast/{$user_id}", $fileName);
      }

      $podcast = Podcasts::create([
        'user_id' => $user_id,
        'title' => $request->title,
        'description' => $request->description,
        'image_url' => $imagepath,
        'publisher' => $request->publisher ? $request->publisher : $user_name,
        'language' => $request->language,
        'explicit' => $request->explicit,
        'category_id' => $request->category_id,
        'tags_id' => $request->tags_id,
        'release_date' => $request->release_date ? $request->release_date : date('Y-m-d H:i:s'),
        'favourite' => $request->favourite,
        'website' => $request->website,
        'number_of_episodes' => $request->number_of_episodes,
      ]);

      $artist->increment('total_podcasts');

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Podcast added successfully',
        'toast' => true,
      ], ['podcastdata' => $podcast]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error adding podcast: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function updatePodcast(UpdatePodcastRequest $request)
  {
    try {
      $podcastId = $request->input('podcast_id');
      $podcast = Podcasts::findOrFail($podcastId);
      $user = $request->attributes->get('user');
      $user_id = $user->id;
      $imagepath = $podcast->image_url;

      // if ($request->hasFile('image_url')) {
      //   $imageFile = $request->file('image_url');
      //   $fileName = $imageFile->getClientOriginalName();
      //   $imagepath = "users/private/{$user_id}/podcast/{$fileName}";
      //   Storage::put($imagepath, file_get_contents($imageFile));
      // }

      if ($request->hasFile('image_url')) {
        $imageFile = $request->file('image_url');
        $fileName = $imageFile->getClientOriginalName();
        $imagepath = "podcast/{$user_id}/{$fileName}";
        $imageFile->move("podcast/{$user_id}", $fileName);
      }
      $dataToUpdate = [
        'title' => $request->filled('title') ? $request->input('title') : $podcast->title,
        'description' => $request->filled('description') ? $request->input('description') : $podcast->description,
        'publisher' => $request->filled('publisher') ? $request->input('publisher') : $podcast->publisher,
        'language' => $request->filled('language') ? $request->input('language') : $podcast->language,
        'explicit' => $request->filled('explicit') ? $request->input('explicit') : $podcast->explicit,
        'category_id' => $request->filled('category_id') ? $request->input('category_id') : $podcast->category_id,
        'tags_id' => $request->filled('tags_id') ? $request->input('tags_id') : $podcast->tags_id,
        'release_date' => $request->filled('release_date') ? $request->input('release_date') : $podcast->release_date,
        'favourite' => $request->filled('favourite') ? $request->input('favourite') : $podcast->favourite,
        'website' => $request->filled('website') ? $request->input('website') : $podcast->website,
        'number_of_episodes' => $request->filled('number_of_episodes') ? $request->input('number_of_episodes') : $podcast->number_of_episodes,
        'image_url' => $request->hasFile('image_url') ? $imagepath : $podcast->image_url,
      ];

      $podcast->update($dataToUpdate);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Podcast updated successfully',
        'toast' => true,
      ], ['podcastdata' => $podcast]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error updating podcast: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function deletePodcast(Request $request)
  {
    $validatedData = $request->validate([
      'podcast_id' => 'required|exists:podcasts,id',
    ], [
      'podcast_id.required' => 'The podcast ID is required.',
      'podcast_id.exists' => 'The selected podcast ID does not exist.',
    ]);

    try {
      $podcastId = $validatedData['podcast_id'];
      $podcast = Podcasts::findOrFail($podcastId);

      if ($podcast->image_url) {
        Storage::delete($podcast->image_url);
      }

      $podcast->delete();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Podcast deleted successfully',
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error deleting podcast: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getPodcastById(Request $request, $id)
  {
    try {
      $user = $request->attributes->get('user');
      $user_id = $user->id;
      $podcast = Podcasts::where('id', $id)
        ->where('user_id', $user_id)
        ->first();
      if (!$podcast) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Podcast not found or you do not have permission to access it',
          'toast' => true,
        ]);
      }
      // $podcast->image_link = getFileTemporaryURL($podcast->image_url);
      $podcast->image_link = url($podcast->image_url);
      $podcast->image_url = $podcast->image_url;
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Podcast retrieved successfully',
        'toast' => true,
      ], ['podcastdata' => $podcast]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving podcast: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  //Episodes Functions
  public function addEpisode(CreateEpisodeRequest $request)
  {
    try {
      $podcastID = $request->podcast_id;
      $imagepath = null;
      $audiopath = null;
      $duration = 0;
      $user = $request->attributes->get('user');
      $user_id = $user->id;

      $podcast = Podcasts::find($podcastID);
      if (!$podcast) {
        throw new \Exception('Podcast not found');
      }

      // if ($request->hasFile('audio_url')) {
      //   $audioFile = $request->file('audio_url');

      //   $fileName = $audioFile->getClientOriginalName();

      //   $sanitizedFileName = preg_replace('/[^A-Za-z0-9\-\.]/', '_', $fileName);

      //   $audiopath = "users/private/{$user_id}/{$podcastID}/episode/audio/{$sanitizedFileName}";

      //   Storage::put($audiopath, file_get_contents($audioFile));

      if ($request->hasFile('audio_url')) {
        $audioFile = $request->file('audio_url');
        $fileName = preg_replace('/[^A-Za-z0-9\-\.]/', '_', $audioFile->getClientOriginalName());
        $audiopath = "podcast/{$user_id}/{$podcastID}/episode/audio/{$fileName}";
        $audioFile->move("podcast/{$user_id}/{$podcastID}/episode/audio", $fileName);

        $getID3 = new getID3();
        $fileInfo = $getID3->analyze($audioFile->getPathname());

        if (isset($fileInfo['playtime_seconds'])) {
          $duration = (int) $fileInfo['playtime_seconds'];
        }
      }

      // if ($request->hasFile('image_url')) {
      //   $imageFile = $request->file('image_url');
      //   $fileName = $imageFile->getClientOriginalName();
      //   $imagepath = "users/private/{$user_id}/episode/image/{$fileName}";
      //   Storage::put($imagepath, file_get_contents($imageFile));
      // } else {
      //   $imagepath = $podcast->image_url;
      // }
      if ($request->hasFile('image_url')) {
        $imageFile = $request->file('image_url');
        $fileName = $imageFile->getClientOriginalName();
        $imagepath = "podcast/{$user_id}/episode/image/{$fileName}";
        $imageFile->move("podcast/{$user_id}/episode/image", $fileName);
      } else {
        $imagepath = $podcast->image_url;
      }
      $guestNames = explode(',', $request->guest_speakers); // Assuming names are comma-separated
      $guestIds = User::whereIn('username', $guestNames)->pluck('id')->toArray();

      $episode = Episode::create([
        'podcast_id' => $request->podcast_id,
        'title' => $request->title,
        'description' => $request->description,
        'audio_url' => $audiopath,
        'duration' => $duration,
        'published_at' => $request->published_at,
        'explicit' => $request->explicit,
        'image_url' => $imagepath,
        'transcriptions' => $request->transcriptions,
        //'guest_speakers' => $request->guest_speakers,
        'guest_speakers' => json_encode($guestIds),
        'season_number' => $request->season_number,
        'episode_number' => $request->episode_number,
        'listened' => $request->listened,
      ]);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Episode added successfully',
        'toast' => true
      ], ['episode' => $episode]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error adding episode: ' . $e->getMessage(),
        'toast' => true
      ]);
    }
  }
  public function updateEpisode(UpdateEpisodeRequest $request)
  {
    try {
      $episodeId = $request->input('episode_id');
      $episode = Episode::findOrFail($episodeId);
      $user = $request->attributes->get('user');
      $user_id = $user->id;

      $imagepath = $episode->image_url;
      $audiopath = $episode->audio_url;
      $duration = $episode->duration;

      // if ($request->hasFile('audio_url')) {
      //   $audioFile = $request->file('audio_url');
      //   $fileName = $audioFile->getClientOriginalName();
      //   $audiopath = "users/private/{$user_id}/{$episode->podcast_id}/episode/audio/{$fileName}";
      //   Storage::put($audiopath, file_get_contents($audioFile));

      if ($request->hasFile('audio_url')) {
        $audioFile = $request->file('audio_url');
        $fileName =  $audioFile->getClientOriginalName();
        $audiopath = "podcast/{$user_id}/{$episode->podcast_id}/episode/audio/{$fileName}";
        $audioFile->move("podcast/{$user_id}/{$episode->podcast_id}/episode/audio", $fileName);

        $getID3 = new getID3();
        $fileInfo = $getID3->analyze($audioFile->getPathname());
        if (isset($fileInfo['playtime_seconds'])) {
          $duration = (int) $fileInfo['playtime_seconds'];
        }
      }

      if ($request->hasFile('image_url')) {
        $imageFile = $request->file('image_url');
        $fileName = $imageFile->getClientOriginalName();
        $imagepath = "podcast/{$user_id}/episode/image/{$fileName}";
        $imageFile->move("podcast/{$user_id}/episode/image", $fileName);
      }

      $dataToUpdate = [
        'title' => $request->filled('title') ? $request->input('title') : $episode->title,
        'description' => $request->filled('description') ? $request->input('description') : $episode->description,
        'audio_url' => $request->hasFile('audio_url') ? $audiopath : $episode->audio_url,
        'duration' => $duration, // Duration updated if audio is uploaded
        'published_at' => $request->filled('published_at') ? $request->input('published_at') : $episode->published_at,
        'explicit' => $request->filled('explicit') ? $request->input('explicit') : $episode->explicit,
        'image_url' => $request->hasFile('image_url') ? $imagepath : $episode->image_url,
        'transcriptions' => $request->filled('transcriptions') ? $request->input('transcriptions') : $episode->transcriptions,
        'guest_speakers' => $request->filled('guest_speakers') ? $request->input('guest_speakers') : $episode->guest_speakers,
        'season_number' => $request->filled('season_number') ? $request->input('season_number') : $episode->season_number,
        'episode_number' => $request->filled('episode_number') ? $request->input('episode_number') : $episode->episode_number,
      ];

      $episode->update($dataToUpdate);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Episode updated successfully',
        'toast' => true
      ], ['episode' => $episode]);
    } catch (\Exception $e) {
      Log::info('Error updating episode: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error updating episode: ' . $e->getMessage(),
        'toast' => true
      ]);
    }
  }

  public function deleteEpisode(Request $request)
  {
    $validatedData = $request->validate([
      'episode_id' => 'required|exists:podcast_episodes,id',
    ], [
      'episode_id.required' => 'The episode ID is required.',
      'episode_id.exists' => 'The selected episode ID does not exist.',
    ]);

    try {
      $podcastId = $validatedData['episode_id'];
      $podcast = Episode::findOrFail($podcastId);

      // if ($podcast->image_url) {
      //     Storage::delete($podcast->image_url);
      // }

      $podcast->delete();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Podcast deleted successfully',
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error deleting podcast: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function getEpisodeById(Request $request, $id)
  {
    try {
      $user = $request->attributes->get('user');
      $user_id = $user->id;

      $episode = Episode::where('id', $id)
        ->whereHas('podcast', function ($query) use ($user_id) {
          $query->where('user_id', $user_id);
        })
        ->first();

      if (!$episode) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Episode not found or you do not have permission to access it',
          'toast' => true,
        ]);
      }
      $guestSpeakersIds = is_string($episode->guest_speakers)
        ? json_decode($episode->guest_speakers, true)
        : $episode->guest_speakers;

      $guestSpeakersIds = (array) $guestSpeakersIds;

      $guestSpeakers = User::whereIn('users.id', $guestSpeakersIds)
        ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
        ->get(['users.id', 'users.username', 'user_profiles.profile_image_path', 'user_profiles.country'])
        ->map(function ($guest) {
          $guest->profile_image_path = $guest->profile_image_path ? getFileTemporaryURL($guest->profile_image_path)  : null;
          $guest->country = $guest->country ?? null;
          return $guest;
        });
      $episode->guest_speakers = $guestSpeakers;

      $audioFileName = basename($episode->audio_url);
      $episode->audio_file_name = $audioFileName;
      $episode->image_url = url($episode->image_url);
      $episode->audio_url = url($episode->audio_url);
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Episode retrieved successfully',
        'toast' => true,
      ], ['episode' => $episode]);
    } catch (\Exception $e) {
      Log::info('Error retrieving episode: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving episode: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getEpisodeByCategoryId(Request $request)
  {
    try {
      $categoryId = $request->category_id;

      $episodes = Episode::whereHas('podcast', function ($query) use ($categoryId) {
        $query->where('category_id', $categoryId);
      })->get();

      if ($episodes->isEmpty()) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No episodes found for the specified category',
          'toast' => true,
        ]);
      }

      $episodeData = $episodes->map(function ($episode) {
        $audioFileName = basename($episode->audio_url);
        $episode->audio_file_name = $audioFileName;
        $episode->image_url = url($episode->image_url);
        $episode->audio_url = url($episode->audio_url);

        $guestSpeakersIds = is_string($episode->guest_speakers)
          ? json_decode($episode->guest_speakers, true)
          : $episode->guest_speakers;

        $guestSpeakersIds = (array) $guestSpeakersIds;

        $guestSpeakers = User::whereIn('users.id', $guestSpeakersIds)
          ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
          ->get(['users.id', 'users.username', 'user_profiles.profile_image_path', 'user_profiles.country'])
          ->map(function ($guest) {
            $guest->profile_image_path = $guest->profile_image_path ? getFileTemporaryURL($guest->profile_image_path) : null;
            $guest->country = $guest->country ?? null;
            return $guest;
          });
        $episode->guest_speakers = $guestSpeakers;

        return $episode;
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Episodes retrieved successfully',
        'toast' => true,
      ], ['episodes' => $episodeData]);
    } catch (\Exception $e) {
      Log::error('Error retrieving episode: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving episode: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getPodcasts(Request $request)
  {
    try {
      $fetch = $request->type;
      if ($fetch == 0) {
        $newlyAdded = Podcasts::select('podcasts.id', 'podcasts.title', 'podcasts.image_url', 'podcasts.description', 'podcasts.created_at', 'podcast_categories.name as category_name', 'podcast_categories.icon', 'users.username as author_name')
          ->join('podcast_categories', 'podcasts.category_id', '=', 'podcast_categories.id')
          ->join('users', 'podcasts.user_id', '=', 'users.id')
          ->orderBy('podcasts.created_at', 'desc')
          ->limit(10)
          ->get();

        $popular = Podcasts::select('podcasts.id', 'podcasts.title', 'podcasts.image_url', 'podcasts.description', 'podcasts.created_at', 'podcast_categories.name as category_name', 'users.username as author_name')
          ->join('podcast_episodes', 'podcasts.id', '=', 'podcast_episodes.podcast_id')
          ->join('podcast_categories', 'podcasts.category_id', '=', 'podcast_categories.id')
          ->join('users', 'podcasts.user_id', '=', 'users.id')
          ->selectRaw('podcasts.id, podcasts.title, podcasts.image_url, podcasts.description, SUM(podcast_episodes.listened) as total_listened')
          ->groupBy('podcasts.id', 'podcasts.title', 'podcasts.image_url', 'podcasts.description', 'podcasts.created_at', 'podcast_categories.name', 'users.username')
          ->orderByDesc('total_listened')
          ->limit(9)
          ->get();

        $newlyAdded = $newlyAdded->map(function ($podcast) {
          $podcast->image_url = url($podcast->image_url);
          return $podcast;
        });

        $popular = $popular->map(function ($podcast) {
          $podcast->image_url = url($podcast->image_url);
          return $podcast;
        });

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Podcasts retrieved successfully',
          'toast' => true,
        ], [
          'newly_added' => $newlyAdded,
          'popular' => $popular,
        ]);
      } elseif ($fetch == 1) {
        $podcastID = $request->podcast_id;

        $podcastData = Podcasts::select('podcasts.id', 'podcasts.title', 'podcasts.description', 'podcasts.image_url', 'podcasts.tags_id', 'podcast_categories.name as category_name', 'languages.label as language_name')
          ->leftJoin('podcast_categories', 'podcasts.category_id', '=', 'podcast_categories.id')
          ->leftJoin('languages', 'podcasts.language', '=', 'languages.id')
          ->where('podcasts.id', $podcastID)
          ->first();

        if (!$podcastData) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Podcast not found',
            'toast' => true,
          ]);
        }

        $tagsArray = explode(',', $podcastData->tags_id);
        $tags = Podtag::whereIn('id', $tagsArray)->pluck('name');

        $episodes = Episode::where('podcast_id', $podcastID)
          ->select(
            'id',
            'podcast_id',
            'title',
            'description',
            'audio_url',
            'duration',
            'published_at',
            'explicit',
            'image_url',
            'transcriptions',
            'guest_speakers',
            'season_number',
            'episode_number',
            'listened'
          )
          ->get();

        $episodes = $episodes->map(function ($episode) {
          $episode->image_url = url($episode->image_url);
          $episode->audio_url = url($episode->audio_url);
          return $episode;
        });

        $episodeCount = $episodes->count();

        $podcastData->image_url = url($podcastData->image_url);
        $podcastData->tags = $tags;
        $podcastData->episode_count = $episodeCount;

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Podcast retrieved successfully',
          'toast' => true,
        ], [
          'episodes' => $episodes->isEmpty() ? [] : $episodes,
          'podcast_data' => $podcastData
        ]);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Invalid fetch parameter',
          'toast' => true,
        ]);
      }
    } catch (\Exception $e) {
      Log::info($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error fetching data: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getPodcastCategoriesAndTags(Request $request)
  {
    try {
      $podcastCategories = Podcategory::select('id', 'name', 'icon', 'gradient', 'category_image', 'slug')
        ->get()
        ->map(function ($category) {
          $category->category_image = asset($category->category_image);
          return $category;
        });

      $category_id = $request->query('category_id');
      $onlyCategory = $request->query('only_category');

      if ($onlyCategory === 'true') {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Categories retrieved successfully',
          'toast' => true,
        ], [
          'categories' => $podcastCategories
        ]);
      }

      if ($category_id) {
        $podcastTags = Podtag::select('id', 'name')->where('category_id', $category_id)->get();
      } else {
        $podcastTags = Podtag::select('id', 'name')->get();
      }
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Categories retrieved successfully',
        'toast' => true,
      ], [
        'categories' => $podcastCategories,
        'tags' => $podcastTags
      ]);
    } catch (\Exception $e) {
      Log::info($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error fetching Categories: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function addPodcastCategoriesAndTags(Request $request)
  {
    try {
      $validatedData = $request->validate([
        'category_name' => 'nullable|string|max:255',
        'category_id' => 'nullable|exists:podcast_categories,id|required_without:category_name',
        'tag_name' => 'nullable|string|max:255|required_with:category_id',
      ]);

      if (!empty($validatedData['category_name'])) {
        $addpodcast = Podcategory::create([
          'name' => $validatedData['category_name']
        ]);

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Category added successfully',
          'toast' => true,
        ], ['category_id' => $addpodcast->id]);
      }

      if (!empty($validatedData['category_id']) && !empty($validatedData['tag_name'])) {
        $slug = Str::slug($validatedData['tag_name'], '-');
        $addtag = Podtag::create([
          'name' => $validatedData['tag_name'],
          'slug' => $slug,
          'category_id' => $validatedData['category_id']
        ]);

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Tag added successfully',
          'toast' => true,
        ], ['tag_id' => $addtag->id]);
      }

      return generateResponse([
        'type' => 'error',
        'code' => 400,
        'status' => false,
        'message' => 'Invalid request: Either category_name or category_id with tag_name must be provided',
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing request: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }


  public function getLanguages(Request $request)
  {
    $limit = $request->query('limit');
    $page = $request->query('page');
    $search = $request->query('search', '');

    if (is_null($limit) || is_null($page)) {
      $query = Langauge::select('id', 'label', 'value');

      if (!empty($search)) {
        $query->where(function ($query) use ($search) {
          $query->where('value', 'like', '%' . $search . '%')
            ->orWhere('label', 'like', '%' . $search . '%');
        });
      }

      $languages = $query->get();
      $total = $languages->count();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Languages retrieved successfully',
        'toast' => true,
      ], [
        'languages' => $languages,
        'total' => $total,
        'limit' => $total,
        'page' => 1,
        'total_pages' => 1,
      ]);
    }

    $limit = (int)$limit;
    $page = (int)$page;
    $offset = ($page - 1) * $limit;

    try {
      $query = Langauge::select('id', 'label', 'value');

      if (!empty($search)) {
        $query->where(function ($query) use ($search) {
          $query->where('value', 'like', '%' . $search . '%')
            ->orWhere('label', 'like', '%' . $search . '%');
        });
      }

      $languages = $query->skip($offset)
        ->take($limit)
        ->get();

      $total = $query->count();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Languages retrieved successfully',
        'toast' => true,
      ], [
        'languages' => $languages,
        'total' => $total,
        'limit' => $limit,
        'page' => $page,
        'total_pages' => (int)ceil($total / $limit),
      ]);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching languages: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function getUserPodcasts(Request $request, $podcastID = null)
  {
    try {
      $user = $request->attributes->get('user');
      $userID = $user->id;

      if (!$userID) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'User ID is required',
          'toast' => true,
        ]);
      }

      if ($podcastID) {
        $podcastID = $request->podcast_id;

        $podcastData = Podcasts::select('id', 'title', 'description', 'image_url', 'tags_id')
          ->where('id', $podcastID)
          ->where('user_id', $userID)
          ->first();

        if (!$podcastData) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Podcast not found',
            'toast' => true,
          ]);
        }

        $tagsArray = explode(',', $podcastData->tags_id);
        $tags = Podtag::whereIn('id', $tagsArray)->pluck('name');

        $episodes = Episode::where('podcast_id', $podcastID)
          ->select(
            'id',
            'podcast_id',
            'title',
            'description',
            'audio_url',
            'duration',
            'published_at',
            'explicit',
            'image_url',
            'transcriptions',
            'guest_speakers',
            'season_number',
            'episode_number',
            'listened'
          )
          ->get();

        $episodes = $episodes->map(function ($episode) {
          $episode->image_url = url($episode->image_url);
          $episode->audio_url = url($episode->audio_url);
          return $episode;
        });

        $episodeCount = $episodes->count();

        $podcastData->image_url = url($podcastData->image_url);

        $podcastData->tags = $tags;
        $podcastData->episode_count = $episodeCount;

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Podcast retrieved successfully',
          'toast' => true,
        ], [
          'episodes' => $episodes->isEmpty() ? [] : $episodes,
          'podcast_data' => $podcastData
        ]);
      } else {
        $userPodcasts = Podcasts::select('id', 'title', 'image_url', 'description')
          ->where('user_id', $userID)
          ->orderBy('created_at', 'desc')
          ->get();

        if ($userPodcasts->isEmpty()) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'No podcasts found for the specified user',
            'toast' => true,
          ]);
        }

        $userPodcasts = $userPodcasts->map(function ($podcast) {
          $podcast->image_url = url($podcast->image_url);
          return $podcast;
        });

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'User podcasts retrieved successfully',
          'toast' => true,
        ], [
          'user_podcasts' => $userPodcasts,
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error fetching user podcasts: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching data: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  // Function To increment episodes listening count
  public function incrementEpisodeListenCount($episodeID)
  {
    try {
      Episode::where('id', $episodeID)->increment('listened');
      $episode = Episode::find($episodeID);
      $podcast_id = $episode->podcast_id;
      $podcast = Podcasts::find($podcast_id);
      $user_id = $podcast->user_id;
      Artist::where('user_id', $user_id)->increment('total_plays');
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Incrementing episodes listening',
        'toast' => true,
      ], ['user_id' => $user_id]);
    } catch (\Exception $e) {
      Log::error('Error incrementing episode listen count: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching data: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
  public function likeEpisode(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $user_id = $user->id;
      $episode_id = $request->episodes_id;

      $episode = Episode::find($episode_id);
      if (!$episode) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Podcast episode not found.',
          'toast' => true,
        ]);
      }

      $likedUsers = $episode->liked_user_id ? explode(',', $episode->liked_user_id) : [];

      if (in_array($user_id, $likedUsers)) {
        $likedUsers = array_diff($likedUsers, [$user_id]);
        $episode->liked_user_id = implode(',', $likedUsers);
        $episode->save();

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'You unliked the episode.',
          'toast' => true,
        ]);
      } else {
        $likedUsers[] = $user_id;
        $episode->liked_user_id = implode(',', $likedUsers);
        $episode->save();

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'You liked the episode.',
          'toast' => true,
        ]);
      }
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error processing request: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getLikedEpisodes(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $user_id = $user->id;

      $likedEpisodes = Episode::whereRaw('FIND_IN_SET(?, liked_user_id)', [$user_id])
        ->select('title', 'image_url', 'created_at', 'duration')
        ->get();

      if ($likedEpisodes->isEmpty()) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No liked episodes found for this user.',
          'toast' => true,
        ]);
      }

      $transformedEpisodes = $likedEpisodes->transform(function ($episode, $index) {
        $episode->image_url = url($episode->image_url);
        return [
          'sr_no' => $index + 1,
          'title'      => $episode->title,
          'image_url'  => $episode->image_url,
          'created_at' => $episode->created_at->format('Y-m-d H:i:s'),
          'duration'   => $episode->duration,
        ];
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Liked episodes retrieved successfully.',
        'data' => $transformedEpisodes,
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching liked episodes: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }

  public function getAudioUrlByPodcastId(Request $request)
  {
    try {
      $podcastId = $request->podcast_id;
      if (!$podcastId) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'Podcast ID is required.',
          'toast' => true,
        ]);
      }

      $podcast = Podcasts::select('podcasts.*', 'podcast_categories.name as category_name')
        ->leftJoin('podcast_categories', 'podcasts.category_id', '=', 'podcast_categories.id')
        ->where('podcasts.id', $podcastId)
        ->first();

      if (!$podcast) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Podcast not found.',
          'toast' => true,
        ]);
      }
      $podcastEpisodes = Episode::where('podcast_id', $podcastId)->get();


      if ($podcastEpisodes->isEmpty()) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No podcast episodes found.',
          'toast' => true,
        ]);
      }

      $audioUrls = $podcastEpisodes->map(function ($episode, $index) use ($podcast) {
        return [
          'sr_no' => $index + 1,
          'episode_url' => url($episode->audio_url),
          'episode_name' => $episode->title,
          'category' => $podcast->category_name
        ];
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Audio URLs retrieved successfully',
        'toast' => true,
      ], [
        'audio_urls' => $audioUrls,
      ]);
    } catch (\Exception $e) {
      Log::error('Error retrieving audio URLs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error retrieving audio URLs: ' . $e->getMessage(),
        'toast' => true,
      ]);
    }
  }
}
