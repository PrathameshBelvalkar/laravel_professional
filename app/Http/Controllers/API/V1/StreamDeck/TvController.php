<?php

namespace App\Http\Controllers\API\V1\StreamDeck;

use getID3;
use Exception;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use App\Models\StreamDeck\Genre;
use App\Models\StreamDeck\Channel;
use Illuminate\Support\Facades\DB;
use App\Models\StreamDeck\TvSeries;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\StreamDeck\SeriesReview;
use Illuminate\Support\Facades\Storage;
use App\Models\StreamDeck\TvSeriesSeason;
use App\Models\StreamDeck\FavoritesSeries;
use App\Models\StreamDeck\TvSeasonEpisode;
use App\Models\StreamDeck\UserWatchHistory;
use App\Models\StreamDeck\UserEpisodeWatchlist;
use App\Http\Requests\StreamDeck\CreateSeasonsRequest;
use App\Http\Requests\StreamDeck\UpadateSeasonRequest;
use App\Http\Requests\StreamDeck\CreateTvSeriesRequest;
use App\Http\Requests\StreamDeck\UpdateTvSeriesRequest;
use App\Http\Requests\StreamDeck\CreateWatchlistRequest;
use App\Http\Requests\StreamDeck\UpdateWatchlistRequest;
use App\Http\Requests\StreamDeck\CreateSeriesReviewRequest;
use App\Http\Requests\StreamDeck\UpdateSeriesReviewRequest;
use App\Http\Requests\StreamDeck\CreateSeasonEpisodeRequest;
use App\Http\Requests\StreamDeck\UpdateSeasonEpisodeRequest;
use App\Http\Requests\StreamDeck\CreateFavoritesSeriesRequest;
use App\Http\Requests\StreamDeck\UpdateFavoritesSeriesRequest;
use App\Http\Requests\StreamDeck\CreateUserWatchHistoryRequest;
use App\Http\Requests\StreamDeck\UpdateUserWatchHistoryRequest;

class TvController extends Controller
{
    public function addTvSeries(CreateTvSeriesRequest $request)
    {

        try {
            $imagepath = null;
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $tvSeries = TvSeries::create([
                'user_id' => $user_id,
                'title' => $request->title,
                'description' => $request->description,
                'genre' => $request->genre,
                'release_date' => $request->release_date,
                'cover_image' => null,
                'content_rating' => $request->content_rating,
                'status' => $request->status,
                'cast' => $request->cast,
                'directors' => $request->directors,
                'channel_id' => $request->channel_id
            ]);

            if ($request->hasFile('cover_image')) {
                $imageFile = $request->file('cover_image');
                $fileName = $imageFile->getClientOriginalName();
                $imagepath = "users/private/{$user_id}/streamdeck/on_demand/series/{$tvSeries->id}/cover_image/{$fileName}";
                Storage::put($imagepath, file_get_contents($imageFile));
                $tvSeries->update([
                    'cover_image' => $imagepath,
                ]);
            }
            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Series added successfully',
                'toast' => true
            ], ['seriesdata' => $tvSeries]);
        } catch (\Exception $e) {
            Log::info('error to add series'. $e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to add series',
                'toast' => true
            ]);
        }
    }

    public function updateTvSeries(UpdateTvSeriesRequest $request)
    {

        try {
            $tvSeriesId = $request->input('tvSeriesId');
            $tvSeries = TvSeries::findOrFail($tvSeriesId);
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $imagepath = $tvSeries->cover_image;

            if ($request->hasFile('cover_image')) {
                Storage::delete($imagepath);
                $imageFile = $request->file('cover_image');
                $fileName = $imageFile->getClientOriginalName();
                $imagepath = "users/private/{$user_id}/streamdeck/on_demand/series/{$tvSeries->id}/cover_image/{$fileName}";
                Storage::put($imagepath, \file_get_contents($imageFile));
            }

            $DataToUpdate = [
                'title' => $request->filled('title') ? $request->title : $tvSeries->title,
                'description' => $request->filled('description') ? $request->description : $tvSeries->description,
                'genre' => $request->filled('genre') ? $request->genre : $tvSeries->genre,
                'release_date' => $request->filled('release_date') ? $request->release_date : $tvSeries->release_date,
                'cover_image' => $request->hasFile('cover_image') ? $imagepath : $tvSeries->cover_image,
                'content_rating' => $request->filled('content_rating') ? $request->content_rating : $tvSeries->content_rating,
                'status' => $request->filled('status') ? $request->status : $tvSeries->status,
                'cast' => $request->filled('cast') ? $request->cast : $tvSeries->cast,
                'directors' => $request->filled('directors') ? $request->directors : $tvSeries->directors,
                'channel_id' => $request->filled('channel_id') ? $request->channel_id : $tvSeries->channel_id

            ];

            $tvSeries->update($DataToUpdate);

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Series updated successfully',
                'toast' => true
            ], ['seriesdata' => $tvSeries]);
        } catch (\Exception $e) {
            Log::info('error to update series' . $e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to update series',
                'toast' => true
            ]);
        }
    }

    public function deleteTvSeries(Request $request)
    {
        $validateData = $request->validate([
            'tvSeries_id' => 'required|exists:tv_series,id'
        ], [
            'tvSeries_id.required' => 'The series ID is required.',
            'tvSeries_id.exists' => 'The selected series ID does not exist.'
        ]);
        try {
            $tvSeriesId = $validateData['tvSeries_id'];

            $tvSeries = TvSeries::findOrFail($tvSeriesId);

            if ($tvSeries->cover_image  && Storage::exists($tvSeries->cover_image)) {
                Storage::delete($tvSeries->cover_image);
            }

            $tvSeries->delete();
            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Series deleted successfully',
                'toast' => true
            ]);
        } catch (\Exception $e) {
            Log::info('error to deleting series'. $e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to deleting series',
                'toast' => true
            ]);
        }
    }

    public function addSeriesSeasons(CreateSeasonsRequest $request)
    {


        $request->validated();

        try {
            $imagepath = null;
            $videoPath = null;
            $user = $request->attributes->get('user');
            $user_id = $user->id;
            $season_number = $request->input('season_number');
            $series_id = $request->input('series_id');

            if (TvSeriesSeason::where('season_number', $season_number)->where('series_id', $series_id)->exists()) {
                return response()->json([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'errors' => ['The season number already exists.'],
                    'toast' => true
                ]);
            }
            $seasons = TvSeriesSeason::create([
                'user_id' => $user_id,
                'series_id' => $request->series_id,
                'season_number' => $request->season_number,
                'title' => $request->title,
                'description' => $request->description,
                'release_date' => $request->release_date,
                'episode_count' => $request->episode_count,
                'cover_image' => $imagepath,
                'video_url' => $videoPath
            ]);

            $seasonNumber = $request->input('season_number');

            if ($request->hasFile('cover_image')) {
                $imageFile = $request->file('cover_image');
                $fileName = $imageFile->getClientOriginalName();
                $imagepath = "users/private/{$user_id}/streamdeck/on_demand/series/{$seasons->series_id}/season_{$seasonNumber}/cover_image/{$fileName}";
                Storage::put($imagepath, file_get_contents($imageFile));
                $seasons->update([
                    'cover_image' => $imagepath,
                ]);
            }

            if ($request->hasFile('video_url')) {
                $videoFile = $request->file('video_url');

                $extension = $videoFile->getClientOriginalExtension();

                $videoFileName = "trailer_{$seasonNumber}.{$extension}";

                $videoPath = "users/private/{$user_id}/streamdeck/on_demand/series/{$seasons->series_id}/season_{$seasonNumber}/trailers/videos/{$videoFileName}";
                Storage::put($videoPath, file_get_contents($videoFile));
                $seasons->update([
                    'video_url' => $videoPath
                ]);
            }


            return generateResponse(
                [
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Season added successfully',
                    'toast' => true
                ],
                ['seasondata' => $seasons]
            );
        } catch (Exception $e) {
            Log::info('error to add seasons' . $e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to add seasons',
                'toast' => true
            ]);
        }
    }

    public function updateSeriesSeason(UpadateSeasonRequest $request)
    {
        $request->validated();

        try {
            $season_id = $request->input('season_id');
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $season = TvSeriesSeason::findOrFail($season_id);

            $videoPath = $season->video_url;

            $imagepath = $season->cover_image;
            $season_number = $request->input('season_number');
            $series_id = $request->input('series_id');

            if (TvSeriesSeason::where('season_number', $season_number)->where('series_id', $series_id)->exists()) {
                return response()->json([
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'errors' => ['The season number already exists.'],
                    'toast' => true
                ]);
            }

            $DataToUpdate = [
                'series_id' => $request->filled('series_id') ? $request->series_id : $season->series_id,
                'season_number' => $request->filled('season_number') ? $request->season_number : $season->season_number,
                'title' => $request->filled('title') ? $request->title : $season->title,
                'description' => $request->filled('description') ? $request->description : $season->description,
                'release_date' => $request->filled('release_date') ? $request->release_date : $season->release_date,
                'episode_count' => $request->filled('episode_count') ? $request->episode_count : $season->episode_count,
            ];

            $seasonNumber = $request->input('season_number');

            if ($request->hasFile('cover_image')) {

                if (Storage::exists($imagepath)) {
                    Storage::delete($imagepath);
                }

                $imageFile = $request->file('cover_image');
                $fileName = $imageFile->getClientOriginalName();
                $imagepath = "users/private/{$user_id}/streamdeck/on_demand/series/{$season->series_id}/season_{$seasonNumber}/cover_image/{$fileName}";
                Storage::put($imagepath, \file_get_contents($imageFile));
                $DataToUpdate['cover_image'] = $imagepath;
            }


            if ($request->hasFile('video_url')) {

                if ($videoPath) {
                    Storage::delete($videoPath);
                }


                $videoFile = $request->file('video_url');
                $seasonNumber = $request->input('season_number');
                $extension = $videoFile->getClientOriginalExtension();
                $videoFileName = "trailer_{$seasonNumber}.{$extension}";
                $videoPath = "users/private/{$user_id}/streamdeck/on_demand/series/{$season->series_id}/season_{$seasonNumber}/trailers/videos/{$videoFileName}";

                Storage::put($videoPath, file_get_contents($videoFile));
                $DataToUpdate['video_url'] = $videoPath;
            }

            $season->update($DataToUpdate);


            $DataToUpdate['id'] = $season_id;

            return \generateResponse(
                [
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Season updated successfully',
                    'toast' => true
                ],
                ['seasondata' => $DataToUpdate]
            );
        } catch (Exception $e) {
            Log::info('error to update season' . $e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to update season.',
                'toast' => true
            ]);
        }
    }

    public function deleteSeason(Request $request)
    {
        $validateData = $request->validate([
            'season_id' => 'required|exists:tv_seasons,id',
        ], [
            'season_id.required' => 'selected ID is required',
            'season_id.exists' => 'selected ID is not exists'
        ]);

        try {
            $season_id = $validateData['season_id'];

            $season = TvSeriesSeason::findOrFail($season_id);

            if ($season->cover_image) {
                Storage::delete($season->cover_image);
            }

            if ($season->video_url) {
                Storage::delete($season->video_url);
            }

            $season->episodes()->each(function ($episode) {
                $episode->delete();
            });

            $season->delete();

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Season deleted successfully',
                'toast' => true
            ]);
        } catch (Exception $e) {
            Log::info('error to delete season' .$e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to delete season',
                'toast' => true
            ]);
        }
    }

    public function addSeasonEpisode(CreateSeasonEpisodeRequest $request)
    {
        try {
            $imagePath = null;
            $videoPath = null;
            $subtitlePath = null;
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $episode = TvSeasonEpisode::create([
                'user_id' => $user_id,
                'series_id' => $request->series_id,
                'season_id' => $request->season_id,
                'episode_number' => null,
                'title' => $request->title,
                'description' => $request->description,
                'video_url' => $videoPath,
                'release_date' => $request->release_date,
                'thumbnail' => $imagePath,
                'views' => $request->views,
                'rating' => $request->rating,
                'subtitles' => $subtitlePath,
                'duration' => null
            ]);


            if ($request->hasFile('subtitles')) {
                $subtitleFile = $request->file('subtitles');
                $episodeNumber = $request->input('episode_number');
                $extension = $subtitleFile->getClientOriginalExtension();
                $subtitleFileName = "episode_{$episode->id}.{$extension}";

                $subtitlePath = "users/private/{$user_id}/streamdeck/on_demand/series/{$episode->series_id}/seasons/{$episode->season_id}/episodes/{$episode->id}/subtitles/{$subtitleFileName}";
                Storage::put($subtitlePath, file_get_contents($subtitleFile));
                $episode->update(['subtitles' => $subtitlePath]);
            }


            if ($request->hasFile('video_url')) {
                $file = $request->file('video_url');

                $filePath = $file->storeAs(
                    "users/private/{$user_id}/streamdeck/on_demand/series/{$request->series_id}/seasons/{$episode->season_id}/episodes/{$episode->id}",
                    $file->getClientOriginalName()
                );

                $getID3 = new getID3();
                $fileInfo = $getID3->analyze(storage_path("app/{$filePath}"));


                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = (int)$fileInfo['playtime_seconds'];
                    $episode->update(['duration' => $duration]);
                }

                $videoPath = $this->convertToHLS(storage_path("app/{$filePath}"), $user_id, $request->series_id, $request->season_id, $episode->id);
                $episode->update(['video_url' => $videoPath]);

                if (file_exists(storage_path("app/{$filePath}"))) {
                    unlink(storage_path("app/{$filePath}"));
                }
            }


            if ($request->hasFile('thumbnail')) {
                $imageFile = $request->file('thumbnail');

                $extension = $imageFile->getClientOriginalExtension();
                $fileName = "episode_{$episode->id}.{$extension}";

                $imagePath = "users/private/{$user_id}/streamdeck/on_demand/series/{$episode->series_id}/seasons/{$episode->season_id}/episodes/{$episode->id}/thumbnails/{$fileName}";
                Storage::put($imagePath, file_get_contents($imageFile));
                $episode->update(['thumbnail' => $imagePath]);
            }

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episode added successfully',
                'toast' => true
            ], ['episodedata' => $episode]);
        } catch (Exception $e) {
            Log::info('Error adding episode:'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error adding episode',
                'toast' => true
            ]);
        }
    }

    public function convertToHLS($outputFilePath, $user_id, $series_id, $season_id, $episode_id)
    {

        $folderPath = "users/private/{$user_id}/streamdeck/on_demand/series/{$series_id}/seasons/{$season_id}/episodes/{$episode_id}";


        $ffmpegPath = config('app.ffmpeg_binaries');


        if (!Storage::exists($folderPath)) {
            Storage::makeDirectory($folderPath);
        }


        $playlistPath480p = storage_path("app/{$folderPath}/playlist_480p.m3u8");
        $command480p = "{$ffmpegPath} -i " . escapeshellarg($outputFilePath) . " -vf scale=w=854:h=480 -c:a copy -start_number 0 -hls_time 10 -hls_list_size 0 -f hls " . escapeshellarg($playlistPath480p);


        exec($command480p);


        $returnedPath = "{$folderPath}/playlist_480p.m3u8";

        return  $returnedPath;
    }

    public function UpdateSeasonEpisode(UpdateSeasonEpisodeRequest $request)
    {
        try {

            $episode_id = $request->input('episode_id');
            $user = $request->attributes->get('user');
            $user_id = $user->id;
            $episode = TvSeasonEpisode::findOrFail($episode_id);


            $videoPath = $episode->video_url;
            $imagePath = $episode->thumbnail;
            $subtitlePath = $episode->subtitles;




            $DataToUpdate = [
                'series_id' => $request->filled('series_id') ? $request->series_id : $episode->series_id,
                'season_id' => $request->filled('season_id') ? $request->season_id : $episode->season_id,
                'episode_number' => $request->filled('episode_number') ? $request->episode_number : $episode->episode_number,
                'title' => $request->filled('title') ? $request->title : $episode->title,
                'description' => $request->filled('description') ? $request->description : $episode->description,
                'release_date' => $request->filled('release_date') ? $request->release_date : $episode->release_date,
                'rating' => $request->filled('rating') ? $request->rating : $episode->rating,
                'views' => $request->filled('views') ? $request->views : $episode->views,
            ];


            if ($request->hasFile('subtitles')) {
                if ($subtitlePath) {
                    Storage::delete($subtitlePath);
                }
                $subtitleFile = $request->file('subtitles');

                $episodeNumber = $request->input('episode_number');

                $extension = $subtitleFile->getClientOriginalExtension();

                $subtitleFileName = "episode_{$episode->id}.{$extension}";

                $subtitlePath = "users/private/{$user_id}/streamdeck/on_demand/series/{$episode->series_id}/seasons/{$episode->season_id}/episodes/{$episode->id}/subtitles/{$subtitleFileName}";
                Storage::put($subtitlePath, file_get_contents($subtitleFile));
                $DataToUpdate['subtitles'] = $subtitlePath;
            }



            if ($request->hasFile('video_url')) {

                if (Storage::exists($videoPath)) {
                    Storage::delete($videoPath);
                }


                $file = $request->file('video_url');
                $episodeNumber = $request->input('episode_number');
                $fileName = "episode_{$episode->id}";

                $filePath = $file->storeAs("users/private/{$user_id}/streamdeck/on_demand/series/{$request->series_id}/seasons/{$request->season_id}/episodes/{$episode_id}/$fileName", $file->getClientOriginalName());

                $getID3 = new getID3();
                $fileInfo = $getID3->analyze(storage_path("app/{$filePath}"));


                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = (int)$fileInfo['playtime_seconds'];
                    $episode->update(['duration' => $duration]);
                }


                $videoPath = $this->convertToHLS(storage_path("app/{$filePath}"), $user_id, $fileName, $request->series_id, $request->season_id, $episode_id);
                $episode->update(['video_url' => $videoPath]);

                if (file_exists(storage_path("app/{$filePath}"))) {
                    unlink(storage_path("app/{$filePath}"));
                }
            }



            if ($request->hasFile('thumbnail')) {

                if (Storage::exists($imagePath)) {
                    Storage::delete($imagePath);
                }

                $imageFile = $request->file('thumbnail');
                $episodeNumber = $request->input('episode_number');
                $extension = $imageFile->getClientOriginalExtension();
                $fileName = "episode_{$episode->id}.{$extension}";
                $imagePath = "users/private/{$user_id}/streamdeck/on_demand/series/{$episode->series_id}/seasons/{$episode->season_id}/episodes/{$episode_id}/thumbnails/{$fileName}";

                Storage::put($imagePath, file_get_contents($imageFile));
                $DataToUpdate['thumbnail'] = $imagePath;
            }


            $episode->update($DataToUpdate);


            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episode updated successfully',
                'toast' => true
            ], ['episodedata' => $episode]);
        } catch (Exception $e) {
            Log::info('Error updating episode'.$e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error updating episode',
                'toast' => true
            ]);
        }
    }

    public function deleteSeasonEpisode(Request $request)
    {
        $validateData = $request->validate(
            [
                'episode_id' => 'required|exists:season_episodes,id',
            ],
            [
                'episode_id.required' => 'The series ID is required',
                'episode_id.exists' => 'The selected series ID does not exist'
            ]
        );

        try {
            $episode_id = $validateData['episode_id'];
            $episode = TvSeasonEpisode::findOrFail($episode_id);

            if ($episode->subtitles) {
                Storage::delete($episode->subtitles);
            }

            if ($episode->video_url) {
                Storage::delete($episode->video_url);
            }


            if ($episode->thumbnail) {
                Storage::delete($episode->thumbnail);
            }


            $episode->delete();

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episode deleted successfully.',
                'toast' => true
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch reviewed series'.$e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to delete episode',
                'toast' => true
            ]);
        }
    }

    public function addEpisodeWatchlist(CreateWatchlistRequest $request)
    {

        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $watchlist = UserEpisodeWatchlist::create([
                'user_id' => $user_id,
                'episode_id' => $request->episode_id,

            ]);

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episode added in watchlist successfully',
                'toast' => true
            ], ['watchlistdata' => $watchlist]);
        } catch (Exception $e) {
            Log::info('Error to added episode in watchlist'.$e->getMessage().'Line no'.$e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to added episode in watchlist ',
                'toast' => true
            ]);
        }
    }


    public function DeleteWatchlistEpisodes(Request $request)
    {
        $validateData = $request->validate([
            'watchlist_id' => 'required|exists:user_watchlists,id'
        ], [
            'watchlist_id.required' => 'The watchlist ID is required',
            'watchlist_id.exists' => 'The watchlist ID not found'
        ]);

        try {
            $watchlist_id = $validateData['watchlist_id'];
            $watchlist = UserEpisodeWatchlist::findOrFail($watchlist_id);

            $watchlist->delete();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episode deleted successfully from watchlist.',
                'toast' => true
            ]);
        } catch (Exception $e) {
            Log::info(' error to  delete episode from watchlist.'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => ' Error to  delete episode from watchlist.',
                'toast' => true
            ]);
        }
    }

    public function AddUserWatchHistory(CreateUserWatchHistoryRequest $request)
    {
    try {
        $user = $request->attributes->get('user');
        $user_id = $user->id;
        $watched_duration = $request->input('watched_duration');
        $episode_id = $request->input('episode_id');

        $episode = TvSeasonEpisode::select('duration')
            ->where('user_id', $user_id)
            ->where('id', $episode_id)
            ->first();

        $user_Watch_history=UserWatchHistory::where('episode_id',$episode_id)->where('user_id',$user_id)->exists();

        if($user_Watch_history){

            $userHistory = UserWatchHistory::findOrFail($user_Watch_history);

            $ProgressCalculate = ($watched_duration / $episode->duration) * 100;

            $DataToUpdate = [
                'episode_id' => $request->filled('episode_id') ? $request->episode_id : $userHistory->episode_id,
                'progress_percent' => $ProgressCalculate
            ];

            $userHistory->update($DataToUpdate);

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'User watch history updated successfully.',
                'toast' => true
            ], ['watchHistoryData' =>  $userHistory]);

        }

        else{

        if ($episode && $episode->duration) {
            $ProgressCalculate = ($watched_duration / $episode->duration) * 100;

            $user_history = UserWatchHistory::create([
                'user_id' => $user_id,
                'episode_id' => $request->episode_id,
                'watched_at' => $request->watched_at,
                'progress_percent' => round($ProgressCalculate, 2)
            ]);

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'User watch history added successfully.',
                'toast' => true
            ], ['watchHistoryData' => $user_history]);
        } else {
            return generateResponse([
                'type' => 'error',
                'code' => 404,
                'status' => false,
                'message' => 'Episode not found or duration not available.',
                'toast' => true
            ]);
        }
        }
    } catch (Exception $e) {
        Log::info('Error adding user history'.$e->getMessage().'Line no'.$e->getLine());
        return generateResponse([
            'type' => 'error',
            'code' => 500,
            'status' => false,
            'message' => 'Error adding user history',
            'toast' => true
        ]);
    }
}

    public function deleteUserHistory(Request $request)
    {
        $validateData = $request->validate([
            'episode_id' => 'required|exists:season_episodes,id',
        ], [
            'episode_id.required' => 'episode  ID is required',
            'episode_id.exists' => 'episode ID is not exists'
        ]);

        try {

            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $episode_id = $request->input('episode_id');

            $user_Watch_history=UserWatchHistory::where('episode_id',$episode_id)->where('user_id',$user_id)->exists();

            if($user_Watch_history){
            $userHistory = UserWatchHistory::findOrFail( $user_Watch_history);

            $userHistory->delete();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'watch history deleted successfully.',
                'toast' => true
            ]);
            }
            else{
                return generateResponse([
                    'type' => 'error',
                    'code' => 404,
                    'status' => false,
                    'message' => 'watch history not found.',
                    'toast' => true
                ]);
            }
        } catch (Exception $e) {
            Log::info('Error to delete watch history: ' . $e->getMessage() . 'line on ' . $e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to delete  watch history.',
                'toast' => true
            ]);
        }
    }


    public function addFavoritesSeries(CreateFavoritesSeriesRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $favorite = FavoritesSeries::create([
                'user_id' => $user_id,
                'series_id' => $request->series_id,
                'added_at' => $request->added_at
            ]);

            return \generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Favorite series added successfully.',
                'toast' => true
            ], ['favoritesData' => $favorite]);
        } catch (Exception $e) {
            Log::info('Error to add Favorite series:' . $e->getMessage() . 'line no' . $e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to add Favorite series.',
                'toast' => true
            ]);
        }
    }

    public function UpdateFavoriteSeries(UpdateFavoritesSeriesRequest $request)
    {
        try {
            $favoriteSeries_id = $request->input('favoriteSeries_id');

            $favoriteSeries = FavoritesSeries::findOrFail($favoriteSeries_id);

            $DataToUpdate = [
                'series_id' => $request->filled('series_id') ? $request->series_id :  $favoriteSeries->series_id,
                'added_at' => $request->filled('added_at') ? $request->added_at :  $favoriteSeries->added_at,
            ];

            $favoriteSeries->update($DataToUpdate);

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Favorite series updated successfully.',
                'toast' => true
            ], ['favoritesData' => $favoriteSeries]);
        } catch (Exception $e) {
            Log::info('Error to updated Favorite series:' . $e->getMessage() . 'line no' . $e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to updated Favorite series.',
                'toast' => true
            ]);
        }
    }

    public function deleteFavoriteSeries(Request $request)
    {
        $validateData = $request->validate([
            'favoriteSeries_id' => 'required|exists:tv_favorites,id'
        ], [
            'favoriteSeries_id.required' => 'Favorite series ID required.',
            'favoriteSeries_id.exists' => 'Favorite series ID not exists.'
        ]);

        try {

            $favoriteSeries_id = $request->input('favoriteSeries_id');

            $favoriteSeries = FavoritesSeries::findOrFail($favoriteSeries_id);

            $favoriteSeries->delete();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Favorite series deleted successfully.',
                'toast' => true
            ]);
        } catch (Exception $e) {

            Log::info('Error to delete Favorite series:' . $e->getMessage() . 'line no' . $e->getLine());

            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to delete Favorite series.',
                'toast' => true
            ]);
        }
    }

    public function addSeriesReview(CreateSeriesReviewRequest $request)
    {

        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $review = SeriesReview::create([
                'user_id' => $user_id,
                'series_id' => $request->series_id,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Review added successfully.',
                'toast' => true
            ], ['ReviewData' => $review]);
        } catch (Exception $e) {
            Log::info('Error to add review:' . $e->getMessage() . 'line no' . $e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to add review.',
                'toast' => true
            ]);
        }
    }

    public function UpdateSeriesReview(UpdateSeriesReviewRequest $request)
    {
        try {

            $review_id = $request->input('review_id');

            $review = SeriesReview::findOrFail($review_id);

            $DataToUpdate = [
                'series_id' => $request->filled('series_id') ? $request->series_id : $review->series_id,
                'rating' => $request->filled('rating') ? $request->rating : $review->rating,
                'comment' => $request->filled('comment') ? $request->comment : $review->comment,
            ];

            $review->update($DataToUpdate);

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Review updated successfully.',
                'toast' => true
            ], ['ReviewData' => $review]);
        } catch (Exception $e) {
            Log::info('Error to updated review:' . $e->getMessage() . 'line no' . $e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to updated review.',
                'toast' => true
            ]);
        }
    }

    public function deleteSeriesReview(Request $request)
    {
        $validateData = $request->validate([
            'review_id' => 'required|exists:tv_reviews,id'
        ], [
            'review_id.required' => 'Review ID is required.',
            'review_id.exists' => 'Review ID is not exists.'
        ]);

        try {

            $review_id = $request->input('review_id');

            $review = SeriesReview::findOrFail($review_id);

            $review->delete();


            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Review deleted successfully.',
                'toast' => true
            ]);
        } catch (Exception $e) {

            Log::info('Error to delete Review :' . $e->getMessage() . 'line no' . $e->getLine());

            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to delete Review .',
                'toast' => true
            ]);
        }
    }

    public function getOnDemandChannels(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $onDemands = Channel::select('channels.id', 'channels.logo', 'channels.channel_name', DB::raw('count(tv_series.id) as totalSeries'))
                ->leftJoin('tv_series', function ($join) {
                    $join->on('channels.id', '=', 'tv_series.channel_id')
                        ->whereNull('tv_series.deleted_at');
                })
                ->where('channels.channel_type', "2")
                ->where('channels.user_id', $user_id)
                ->groupBy('channels.id', 'channels.logo', 'channels.channel_name')
                ->orderBy('channels.id', 'DESC')
                ->get();

            $onDemands->transform(function ($onDemand) {
                $onDemand->logo = getFileTemporaryURL($onDemand->logo);
                return $onDemand;
            });


            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'All On-Demand channels displayed successfully.',
                'toast' => true
            ], [
                'ondemandData' => $onDemands,
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch onDemand channels:' . $e->getMessage() . 'line no' . $e->getLine());
            return \generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch onDemand channels.',
                'toast' => true
            ]);
        }
    }

    public function getSeries(Request $request)
    {
        $request->validate([
            'channel_id' => 'nullable|exists:tv_series,channel_id',
        ], [
            'channel_id.required' => 'the channel ID is required',
            'channel_id.exists' => 'the channel ID does not exist.',
        ]);

        try {
            $channel_id = $request->input('channel_id');
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $query = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.title',
                'tv_series.status',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('MAX(tv_seasons.season_number) as latestSeasonNumber'),
                DB::raw('AVG(tv_reviews.rating) as avgRating'),
                DB::raw('COUNT(DISTINCT tv_seasons.id) as seasons'),
                DB::raw('COUNT(DISTINCT season_episodes.id) as episodes')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function ($join) {
                    $join->on('tv_series.id', '=', 'tv_seasons.series_id')
                        ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', function ($join) {
                    $join->on('tv_series.id', '=', 'season_episodes.series_id')
                        ->whereNull('season_episodes.deleted_at');
                })
                ->where('tv_series.user_id', $user_id)
                ->whereNull('tv_series.deleted_at');

            if ($channel_id) {
                $query->where('tv_series.channel_id', $channel_id);
            }

            $TvSeries = $query->groupBy(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date',
                'tv_series.cover_image',
                'tv_series.content_rating',
                'tv_series.cast',
                'tv_series.directors',
                'tv_series.channel_id'
            )
                ->orderBy('tv_series.id', 'DESC')
                ->get();

            $TvSeries->transform(function ($series) {

                $series->poster = getFileTemporaryURL($series->poster);
                $series->casts = explode(',', $series->casts);
                $series->directors = explode(',', $series->directors);

                $SeriesGenre = explode(',', $series->genre);

                $genres = DB::table('genres')
                    ->whereIn('id', $SeriesGenre)
                    ->get(['id', 'name', 'slug']);


                $series->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });

                unset($series->genre);

                return $series;
            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Series displayed successfully.',
                'toast' => true
            ], [
                'SeriesData' => $TvSeries,
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch Series: ' . $e->getMessage() . ' line no ' . $e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch Series.',
                'toast' => true
            ]);
        }
    }


    public function getSeasons(Request $request)
    {
        $request->validate([
            'series_id' => 'required|exists:tv_series,id'
        ], [
            'series_id.required' => 'the selected ID is required.',
            'series_id.exists' => 'the selected ID not exists'
        ]);

        try {
            $series_id = $request->input('series_id');
            $user = $request->attributes->get('user');
            $user_id = $user->id;

            $exists = TvSeries::where('user_id', $user_id)->where('id', $series_id)->exists();

            if (!$exists) {
                return response()->json(['message' => 'Series not found'], 404);
            }

            $seasons = DB::table('tv_seasons')
                ->select(
                    'tv_seasons.id',
                    'tv_seasons.title',
                    'tv_seasons.season_number as seasonNumber',
                    'tv_seasons.cover_image as poster',
                    'tv_seasons.video_url as trailer',
                    'tv_seasons.description',
                    'tv_seasons.release_date as releaseDate',
                    DB::raw('COUNT(season_episodes.id) as episodes'),
                    'tv_seasons.series_id'
                )
                ->leftJoin('tv_series', 'tv_seasons.series_id', '=', 'tv_series.id')
                ->leftJoin('season_episodes', 'tv_seasons.id', '=', 'season_episodes.season_id')
                ->where('tv_seasons.series_id', $series_id)
                ->where('tv_seasons.user_id',$user_id)
                ->whereNull('tv_seasons.deleted_at')
                ->groupBy(
                    'tv_seasons.id',
                    'tv_seasons.title',
                    'tv_seasons.season_number',
                    'tv_seasons.cover_image',
                    'tv_seasons.video_url',
                    'tv_seasons.description',
                    'tv_seasons.release_date',
                    'tv_seasons.series_id'
                )
                ->orderBy('tv_seasons.season_number', 'DESC')
                ->get();

            $seasons->transform(function ($season) {

                $season->poster = getFileTemporaryURL($season->poster);
                if ($season->trailer) {
                    $season->trailer = getFileTemporaryURL($season->trailer);
                }

                return $season;
            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Season displayed successfully.',
                'toast' => true
            ], [
                'SeasonsData' => $seasons
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch Seasons: ' . $e->getMessage() . ' line no ' . $e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch Seasons.',
                'toast' => true
            ]);
        }
    }

    public function getGenres()
    {
        try {

            $genres =  DB::table('genres')->select('id', 'name', 'slug')->get();

            $genresData = $genres->map(function ($genre) {
                return [
                    'value' => $genre->slug,
                    'label' => $genre->name,
                    'id' => $genre->id,
                ];
            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Genres fetched successfully',
                'toast' => true
            ], [
                'genresData' => $genresData
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch genres'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch genres',
                'toast' => true
            ]);
        }
    }

    public function FindSeriesByGenre($genreId) {
        try {

            $Series = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT IF(season_episodes.id IS NOT NULL, tv_seasons.id, NULL)) as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes'),
                DB::raw('avg(season_episodes.views) as avgViews')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function($join) {
                    $join->on('tv_series.id', '=', 'tv_seasons.series_id')
                         ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', function($join) {
                    $join->on('tv_seasons.id', '=', 'season_episodes.season_id')
                         ->whereNull('season_episodes.deleted_at');
                })
                ->whereRaw("FIND_IN_SET(?, tv_series.genre)", [$genreId])
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having('episodes', '>', 0)
                ->orderBy('avgViews', 'DESC')
                ->limit(15)
                ->get();


            $Series->transform(function ($series) {
                $series->poster = getFileTemporaryURL($series->poster);
                $seriesGenreIds = explode(',', $series->genre);
                $series->casts = explode(',', $series->casts);
                $series->directors = explode(',', $series->directors);


                $genres = DB::table('genres')
                    ->whereIn('id', $seriesGenreIds)
                    ->get(['id', 'name', 'slug']);


                $series->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });

                unset($series->genre);

                return $series;
            });

            return $Series;

        } catch(Exception $e) {
            Log::error('Error fetching series by genre: ' . $e->getMessage());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error fetching series by genre.',
                'toast' => true
            ]);
        }
    }



    public function getAllPublicCategories()
    {
        try {
            $TopFiveSeries = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT IF(season_episodes.id IS NOT NULL, tv_seasons.id, NULL)) as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes')
            )
            ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
            ->leftJoin('tv_seasons', function($seasonJoin){
                $seasonJoin->on('tv_series.id', '=', 'tv_seasons.series_id')
                           ->whereNull('tv_seasons.deleted_at');
            })
            ->leftJoin('season_episodes', function($episodeJoin) {
                $episodeJoin->on('tv_seasons.id', '=', 'season_episodes.season_id')
                            ->whereNull('season_episodes.deleted_at');
            })
            ->groupBy(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date',
                'tv_series.cover_image',
                'tv_series.content_rating',
                'tv_series.cast',
                'tv_series.directors',
                'tv_series.channel_id'
            )
            ->having(DB::raw('count(tv_seasons.id)'), '>', 0)
            ->having(DB::raw('count(season_episodes.id)'), '>', 0)
            ->orderBy('tv_series.id', 'DESC')
            ->limit(5)
            ->get();

        $TopFiveSeries->transform(function ($series) {

            $series->poster = getFileTemporaryURL($series->poster);

            $SeriesGenre = explode(',', $series->genre);
            $series->casts = explode(',', $series->casts);
            $series->directors = explode(',', $series->directors);

            $genres = DB::table('genres')
                ->whereIn('id', $SeriesGenre)
                ->get(['id', 'name', 'slug']);

            $series->genres = $genres->map(function ($genre) {
                return [
                    'value' => $genre->slug,
                    'label' => $genre->name,
                    'id' => $genre->id,
                ];
            });

            unset($series->genre);

            return $series;
        });


            $TopTenSeries = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT  IF(season_episodes.id IS NOT NULL, tv_seasons.id, NULL))  as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes'),

            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function($topJoin){
                $topJoin->on('tv_series.id', '=', 'tv_seasons.series_id')
                ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', 'tv_seasons.id', '=', 'season_episodes.season_id')
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having(DB::raw('count(season_episodes.id)'), '>', 0)
                ->having(DB::raw('avg(season_episodes.views)'), '>', 0)
                ->orderBy(DB::raw('avg(season_episodes.views)'), 'DESC')
                ->limit(10)
                ->get();

            $TopTenSeries->transform(function ($TopSeries) {

                $TopSeries->poster = getFileTemporaryURL($TopSeries->poster);

                $SeriesGenre = explode(',', $TopSeries->genre);
                $TopSeries->casts = explode(',', $TopSeries->casts);
                $TopSeries->directors = explode(',', $TopSeries->directors);

                $genres = DB::table('genres')
                    ->whereIn('id', $SeriesGenre)
                    ->get(['id', 'name', 'slug']);

                $TopSeries->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });

                unset($TopSeries->genre);


                return $TopSeries;
            });

            $HiddenGems = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT  IF(season_episodes.id IS NOT NULL, tv_seasons.id, NULL))  as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes'),

            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function($join){
                    $join->on('tv_series.id', '=', 'tv_seasons.series_id')
                    ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', 'tv_seasons.id', '=', 'season_episodes.season_id')
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having(DB::raw('count(season_episodes.id)'), '>', 0)
                ->having(DB::raw('avg(season_episodes.views)'), '>', 0)
                ->orderBy(DB::raw('avg(season_episodes.views)'))
                ->limit(5)
                ->get();


            $HiddenGems->transform(function ($Gems) {

                $Gems->poster = getFileTemporaryURL($Gems->poster);

                $SeriesGenre = explode(',', $Gems->genre);
                $Gems->casts = explode(',', $Gems->casts);
                $Gems->directors = explode(',', $Gems->directors);


                $genres = DB::table('genres')
                    ->whereIn('id', $SeriesGenre)
                    ->get(['id', 'name', 'slug']);


                $Gems->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });

                unset($Gems->genre);

                return $Gems;
            });


            return generateResponse(
                [
                    'type' => 'success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Public categories fetched successfully',
                    'toast' => true
                ],
                [
                    [
                        'title' => "Featured",
                        'slug' => "featured",
                        'showsList' => $TopFiveSeries,
                    ],
                    [
                        'title' => "Top 10",
                        'slug' => "top-10",
                        'showsList' => $TopTenSeries,
                    ],

                    [

                        'title' => "Popular in Comedy",
                        'slug' => "comedy-series",
                        'showsList' => $this->FindSeriesByGenre(3),
                    ],

                    [

                        'title' => "Popular in Action ",
                        'slug' => "popular-series",
                        'showsList' => $this->FindSeriesByGenre(1),
                    ],

                    [

                        'title' => "Popular in Thriller",
                        'slug' => "thriller-series",
                        'showsList' => $this->FindSeriesByGenre(10),
                    ],

                    [

                        'title' => "Hidden Gems",
                        'slug' => "hidden-gems",
                        'showsList' => $HiddenGems,
                    ],

                ]
            );
        } catch (Exception $e) {
            Log::info('Error to fetch public categories'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch public categories',
                'toast' => true
            ]);
        }
    }

    public function setGenre(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'genre_id' => 'required'
        ]);

        try {
            $genre_ids = explode(',', $request->input('genre_id'));
            $user_id = $request->input('user_id');


            $existing_genres = Genre::whereRaw("FIND_IN_SET(?, user_id)", [$user_id])->get();


            foreach ($genre_ids as $genre_id) {
                $Pgenre = Genre::findOrFail($genre_id);

                $current_users = explode(',', $Pgenre->user_id);


                if (!in_array($user_id, $current_users)) {
                    $current_users[] = $user_id;
                    $Pgenre->user_id = implode(',', $current_users);
                    $Pgenre->save();
                }
            }


            foreach ($existing_genres as $existing_genre) {
                if (!in_array($existing_genre->id, $genre_ids)) {
                    $current_users = explode(',', $existing_genre->user_id);


                    $updated_users = array_filter($current_users, function ($uid) use ($user_id) {
                        return $uid != $user_id;
                    });


                    $existing_genre->user_id = !empty($updated_users) ? implode(',', $updated_users) : null;
                    $existing_genre->save();
                }
            }

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Genre preference updated successfully.',
                'toast' => true
            ]);
        } catch (Exception $e) {
            Log::info('Error updating genre preference.'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error updating genre preference.',
                'toast' => true
            ]);
        }
    }

    public function getRecommendedSeries(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;


            $genreUserIds = Genre::whereRaw("FIND_IN_SET(?, user_id)", [$user_id])
                ->pluck('id')
                ->toArray();


            $recommended = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(tv_seasons.id) as seasons'),
                DB::raw('count(season_episodes.id) as episodes')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', 'tv_series.id', '=', 'tv_seasons.series_id')
                ->leftJoin('season_episodes', 'tv_series.id', '=', 'season_episodes.series_id')
                ->where(function ($query) use ($genreUserIds) {

                    foreach ($genreUserIds as $genreId) {
                        $query->orWhereRaw("FIND_IN_SET(?, tv_series.genre)", [$genreId]);
                    }
                })
                ->whereNull('tv_series.deleted_at')
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having(DB::raw('count(season_episodes.id)'), '>', 0)
                ->get();


            $recommended->transform(function ($series) {
                $series->poster = getFileTemporaryURL($series->poster);


                $series->casts = explode(',', $series->casts);
                $series->directors = explode(',', $series->directors);
                $seriesGenres = explode(',', $series->genre);


                $genres = DB::table('genres')
                    ->whereIn('id', $seriesGenres)
                    ->get(['id', 'name', 'slug']);


                $series->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });


                unset($series->genre);

                return $series;
            });


            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Recommended Series fetched successfully',
                'toast' => true
            ], [
                'recommendData' => $recommended
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch Recommended Series'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch Recommended Series',
                'toast' => true
            ]);
        }
    }

    public function updateEpisodeSequence(Request $request)
    {
        try {
            $items = $request->json()->all();

            foreach ($items as $item) {

                if (isset($item['episode_id']) && isset($item['episode_number'])) {
                    $episode_id = $item['episode_id'];


                    $episodeData = TvSeasonEpisode::findOrFail($episode_id);


                    $episodeData->update([
                        'episode_number' => $item['episode_number']
                    ]);

                    $episodeData->save();
                } else {

                    return generateResponse([
                        'type' => 'error',
                        'code' => 422,
                        'status' => false,
                        'message' => 'Invalid input data: "episode_id" and "episode_number" are required for each item.',
                        'toast' => true
                    ]);
                }
            }

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Episodes updated successfully',
                'toast' => true
            ]);
        } catch (Exception $e) {
            Log::info('Error updating episodes:'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error updating episodes.',
                'toast' => true
            ]);
        }
    }

    public function getEpiosdes(Request $request){
        $request->validate([
            'season_id'=>'required|exists:tv_seasons,id',
        ],[
            'season_id.required'=>'season ID is required. ',
            'season_id.exists'=>'season ID is not exists.'
        ]);

        try{
           $user=$request->attributes->get('user');
           $user_id=$user->id;
            $season_id=$request->input('season_id');


            $episodes=TvSeasonEpisode::select(
                'season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
               'season_episodes.title',
               'season_episodes.description',
               'season_episodes.video_url',
               'season_episodes.release_date',
               'season_episodes.thumbnail',
               'season_episodes.rating',
               'season_episodes.subtitles',
               'season_episodes.duration',
               'season_episodes.views',
               'user_watch_history.progress_percent'
                )
            ->leftJoin('tv_seasons',function($join){
                $join->on('season_episodes.season_id','=','tv_seasons.id')
                ->whereNull('season_episodes.deleted_at');
            })
            ->leftJoin('user_watch_history','season_episodes.id','=','user_watch_history.episode_id')
            ->where('season_episodes.season_id',$season_id)
            ->where('tv_seasons.user_id',$user_id)
            ->groupBy('season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
                'season_episodes.title',
                'season_episodes.description',
                'season_episodes.video_url',
                'season_episodes.release_date',
                'season_episodes.thumbnail',
                'season_episodes.rating',
                'season_episodes.subtitles',
                'season_episodes.duration',
                'season_episodes.views',
                'user_watch_history.progress_percent'
               )
            ->orderBy('season_episodes.episode_number')
            ->get();


            $episodes->transform(function($episode){

                $episode->thumbnail= getFileTemporaryURL($episode->thumbnail);

                $episode->video_url= getFileTemporaryURL($episode->video_url);

                if($episode->subtitles){
                    $episode->subtitles =  getFileTemporaryURL($episode->subtitles );
                }
                return  $episode;

            });

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'staus'=>true,
                'message'=>'Episode fetched successfully.',
                'toast'=>true
            ],[
                'episodeData'=>$episodes
            ]);
        }
        catch(Exception $e){
            Log::info('Error to fetch epiosdes :'.$e->getMessage().' Line no'.$e->getLine());
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'staus'=>false,
                'message'=>'Error to fetch episode.',
                'toast'=>true
            ]);
        }
    }

    public function getPublicSeasons(Request $request)
    {
        $request->validate([
            'series_id' => 'required|exists:tv_series,id'
        ], [
            'series_id.required' => 'the selected ID is required.',
            'series_id.exists' => 'the selected ID not exists'
        ]);

        try {
            $series_id = $request->input('series_id');

            $seasons = DB::table('tv_seasons')
                ->select(
                    'tv_seasons.id',
                    'tv_seasons.title',
                    'tv_seasons.season_number as seasonNumber',
                    'tv_seasons.cover_image as poster',
                    'tv_seasons.video_url as trailer',
                    'tv_seasons.description',
                    'tv_seasons.release_date as releaseDate',
                    DB::raw('COUNT(season_episodes.id) as episodes'),
                    'tv_seasons.series_id'
                )
                ->leftJoin('tv_series', 'tv_seasons.series_id', '=', 'tv_series.id')
                ->leftJoin('season_episodes', function($join){
                    $join->on('tv_seasons.id', '=', 'season_episodes.season_id')
                    ->whereNull('season_episodes.deleted_at');
                })
                ->where('tv_seasons.series_id', $series_id)
                ->whereNull('tv_seasons.deleted_at')
                ->groupBy(
                    'tv_seasons.id',
                    'tv_seasons.title',
                    'tv_seasons.season_number',
                    'tv_seasons.cover_image',
                    'tv_seasons.video_url',
                    'tv_seasons.description',
                    'tv_seasons.release_date',
                    'tv_seasons.series_id'
                )
                ->having(DB::raw('count(season_episodes.id)'),'>',0)
                ->orderBy('tv_seasons.season_number')
                ->get();

            $seasons->transform(function ($season) {

                $season->poster = getFileTemporaryURL($season->poster);
                if ($season->trailer) {
                    $season->trailer = getFileTemporaryURL($season->trailer);
                }

                return $season;
            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Season displayed successfully.',
                'toast' => true
            ], [
                'SeasonsData' => $seasons
            ]);
        } catch (Exception $e) {
            Log::info('Error to fetch Seasons: ' . $e->getMessage() . ' line no ' . $e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch Seasons.',
                'toast' => true
            ]);
        }
    }

    public function getPublicEpiosdes(Request $request){
        $request->validate([
            'season_id'=>'required|exists:tv_seasons,id',
        ],[
            'season_id.required'=>'season ID is required. ',
            'season_id.exists'=>'season ID is not exists.'
        ]);

        try{
            $season_id=$request->input('season_id');

            $episodes=TvSeasonEpisode::select(
                'season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
               'season_episodes.title',
               'season_episodes.description',
               'season_episodes.release_date',
               'season_episodes.thumbnail',
               'season_episodes.rating',
               'season_episodes.subtitles',
               'season_episodes.duration',
               'season_episodes.views',
                'user_watch_history.progress_percent'
                )
            ->leftJoin('tv_seasons',function($join){
                $join->on('season_episodes.season_id','=','tv_seasons.id')
                ->whereNull('season_episodes.deleted_at');
            })
            ->leftJoin('user_watch_history','season_episodes.id','=','user_watch_history.episode_id')
            ->where('season_episodes.season_id',$season_id)
            ->groupBy('season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
                'season_episodes.title',
                'season_episodes.description',
                'season_episodes.release_date',
                'season_episodes.thumbnail',
                'season_episodes.rating',
                'season_episodes.subtitles',
                'season_episodes.duration',
                'season_episodes.views',
                 'user_watch_history.progress_percent'
               )
            ->orderBy('season_episodes.episode_number')
            ->get();

            $episodes->transform(function($episode){

                $episode->thumbnail= getFileTemporaryURL($episode->thumbnail);

                if($episode->subtitles)
                    $episode->subtitles =  getFileTemporaryURL($episode->subtitles );

                return  $episode;

            });

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'staus'=>true,
                'message'=>'Episode fetched successfully.',
                'toast'=>true
            ],[
                'episodeData'=>$episodes
            ]);
        }
        catch(Exception $e){
            Log::info('Error to fetch epiosdes :'.$e->getMessage().' Line no'.$e->getLine());
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'staus'=>false,
                'message'=>'Error to fetch episode.',
                'toast'=>true
            ]);
        }
    }

    public function getEpisodeId(Request $request){
        $request->validate([
            'episode_id'=>'required|exists:season_episodes,id'
        ],[
            'episode_id.required'=>'the episode ID is required.',
            'episode_id0.exists'=>'the episode ID is not exists.'
        ]);
        try{

            $user=$request->attributes->get('user');
            $user_id=$user->id;
            $episode_id=$request->input('episode_id');

            if($episode_id && $user_id){
            $episodes=TvSeasonEpisode::select(
                'season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
               'season_episodes.title',
               'season_episodes.description',
               'season_episodes.release_date',
               'season_episodes.video_url',
               'season_episodes.thumbnail',
               'season_episodes.duration',
               'season_episodes.rating',
               'season_episodes.subtitles',
               'season_episodes.views',
                'user_watch_history.progress_percent'
                )
            ->leftJoin('tv_seasons',function($join){
                $join->on('season_episodes.season_id','=','tv_seasons.id')
                ->whereNull('season_episodes.deleted_at');
            })
            ->leftJoin('user_watch_history','season_episodes.id','=','user_watch_history.episode_id')
            ->where('season_episodes.id',$episode_id)
            ->groupBy('season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
                'season_episodes.title',
                'season_episodes.description',
                'season_episodes.release_date',
                'season_episodes.video_url',
                'season_episodes.thumbnail',
                'season_episodes.duration',
                'season_episodes.rating',
                'season_episodes.subtitles',
                'season_episodes.views',
                 'user_watch_history.progress_percent'
               )
            ->orderBy('season_episodes.episode_number')
            ->get();

            $episodes->transform(function($episode){

                $episode->thumbnail= getFileTemporaryURL($episode->thumbnail);
                $episode->video_url= getFileTemporaryURL($episode->video_url);

                if($episode->subtitles)
                    $episode->subtitles =  getFileTemporaryURL($episode->subtitles );

                return  $episode;

            });
        }
            else{
                return generateResponse([
                    'type'=>'Not Found',
                    'code'=>404,
                    'status'=>false,
                    'message'=>'Episode is not found.',
                    'toast'=>true
                ]);
            }

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'status'=>true,
                'message'=>'Episode is displayed.',
                'toast'=>true
            ],[
                'episodeData'=>$episodes
            ]);

        }
        catch(Exception $e){
            Log::info("Error to display episode :".$e->getMessage(). "line no" .$e->getLine());
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'status'=>false,
                'message'=>'Error to display episode.',
                'toast'=>true
            ]);
        }

    }

    public function getRecentlyAdded(){
        try{

            $query = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(tv_seasons.id) as seasons'),
                DB::raw('count(season_episodes.id) as episodes')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', 'tv_series.id', '=', 'tv_seasons.series_id')
                ->leftJoin('season_episodes', 'tv_series.id', '=', 'season_episodes.series_id')
                ->whereNull('tv_series.deleted_at')
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having(DB::raw('count(season_episodes.id)'), '>', 0)
                ->orderBy('tv_series.id','DESC')
                ->get();

                $query->transform(function($series){
                $series->poster=getFileTemporaryURL( $series->poster);
                $series->casts=explode(',',$series->casts);
                $series->genre=explode(',',$series->genre);
                $series->directors=explode(',',  $series->directors);

                $genres=DB::table('genres')
                ->select('id','name','slug')
                ->whereIn('id',$series->genre)
                ->get();

                $series->genres=$genres->map(function($genre){
                    return [
                    'id'=>$genre->id,
                    'label'=>$genre->name,
                    'value'=>$genre->slug
                    ];
                });

                unset($series->genre);

                return $series;
            });

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'status'=>true,
                'message'=>'Recently added series fetched successfully.',
                'toast'=>true
            ],[
                'seriesData'=>$query
            ]);

        }
        catch(Exception $e){
           Log::info('Error to fetch Recently added series.'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'status'=>false,
                'message'=>'Error to fetch Recently added series.',
                'toast'=>true
            ]);
        }
    }

    public function getContinueWatching(Request $request){
        try{
            $user=$request->attributes->get('user');
            $user_id=$user->id;

            $episodes = TvSeasonEpisode::select(
                'season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
                'season_episodes.title',
                'season_episodes.description',
                'season_episodes.video_url',
                'season_episodes.release_date',
                'season_episodes.thumbnail',
                'season_episodes.duration',
                'season_episodes.rating',
                'season_episodes.subtitles',
                'season_episodes.views',
                'user_watch_history.progress_percent'
            )
            ->rightJoin('user_watch_history', function ($join) use ($user_id) {
                $join->on('season_episodes.id', '=', 'user_watch_history.episode_id')
                     ->where('user_watch_history.user_id', '=',$user_id);
            })
            ->whereNull('season_episodes.deleted_at')
            ->orderBy('user_watch_history.updated_at', 'DESC')
            ->get();

            $episodes->transform(function($episode){

                $episode->thumbnail= getFileTemporaryURL($episode->thumbnail);

                $episode->video_url= getFileTemporaryURL($episode->video_url);

                $episode->subtitles =  getFileTemporaryURL($episode->subtitles );

                return  $episode;

            });


            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Continues watching episodes fetched successfully.',
                'toast' => true
            ],[
                'episodeData'=>$episodes
            ]);
        }
        catch(Exception $e){
            Log::info('Error to fetch continues watching episodes.'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch continues watching episodes.',
                'toast' => true
            ]);
        }
    }

    public function SearchSeries(Request $request)
    {
        $request->validate([
            'SeriesName' => 'required|string',
            'limit' => 'nullable|integer',
            'page' => 'nullable|integer'
        ], [
            'SeriesName.required' => 'The series is required.',
            'SeriesName.string' => 'The series name should be a string.'
        ]);

        try {
            $SeriesName = $request->input('SeriesName');
            $limit = $request->input('limit', 25);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $limit;


            $total_items = DB::table('tv_series')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($SeriesName) {
                    $query->where('tv_series.title', 'like','%'. $SeriesName . '%')
                    ->orWhere('tv_series.description', 'like', '%'.$SeriesName . '%')
                    ->orWhere('tv_series.directors', 'like', '%'.$SeriesName . '%')
                    ->orWhere('tv_series.cast', 'like', '%'.$SeriesName . '%');
                })
                ->count();


            $search = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT tv_seasons.id) as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function ($join) {
                    $join->on('tv_series.id', '=', 'tv_seasons.series_id')
                         ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', 'tv_series.id', '=', 'season_episodes.series_id')
                ->whereNull('tv_series.deleted_at')
                ->where(function ($query) use ($SeriesName) {
                    $query->where('tv_series.title', 'like','%'. $SeriesName . '%')
                          ->orWhere('tv_series.description', 'like', '%'.$SeriesName . '%')
                          ->orWhere('tv_series.directors', 'like', '%'.$SeriesName . '%')
                          ->orWhere('tv_series.cast', 'like', '%'.$SeriesName . '%');
                })
                ->groupBy(
                    'tv_series.id',
                    'tv_series.genre',
                    'tv_series.status',
                    'tv_series.title',
                    'tv_series.description',
                    'tv_series.release_date',
                    'tv_series.cover_image',
                    'tv_series.content_rating',
                    'tv_series.cast',
                    'tv_series.directors',
                    'tv_series.channel_id'
                )
                ->having(DB::raw('count(tv_seasons.id)'), '>', 0)
                ->having(DB::raw('count(season_episodes.id)'), '>', 0)
                ->limit($limit)
                ->offset($offset)
                ->get();


            $search->transform(function ($series) {
                $series->poster = getFileTemporaryURL($series->poster);
                $series->casts = explode(',', $series->casts);
                $series->genre = explode(',', $series->genre);
                $series->directors = explode(',', $series->directors);

                $genres = DB::table('genres')
                    ->select('id', 'name', 'slug')
                    ->whereIn('id', $series->genre)
                    ->get();

                $series->genres = $genres->map(function ($genre) {
                    return [
                        'id' => $genre->id,
                        'label' => $genre->name,
                        'value' => $genre->slug
                    ];
                });

                unset($series->genre);

                return $series;
            });


            $total_pages = ceil($total_items / $limit);

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Your searched series displayed successfully.'
            ], [
                'searchedData' => $search,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $limit,
                    'total_pages' => $total_pages,
                    'total_items' => $total_items,
                ]
            ]);
        } catch (Exception $e) {
            Log::info('Error displaying the series. ' . $e->getMessage() . ' Line no ' . $e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error displaying the series.',
                'toast' => true
            ]);
        }
    }


    public function getWatchList(Request $request){
        try{
            $user=$request->attributes->get('user');
            $user_id=$user->id;

            $episodes = TvSeasonEpisode::select(
                'season_episodes.id',
                'season_episodes.series_id',
                'season_episodes.season_id',
                'season_episodes.episode_number',
                'season_episodes.title',
                'season_episodes.description',
                'season_episodes.video_url',
                'season_episodes.release_date',
                'season_episodes.thumbnail',
                'season_episodes.rating',
                'season_episodes.subtitles',
                'season_episodes.views',
            )
            ->rightJoin('user_watchlists', function ($join) use ($user_id) {
                $join->on('season_episodes.id', '=', 'user_watchlists.episode_id')
                     ->where('user_watchlists.user_id', '=',$user_id);
            })
            ->whereNull('season_episodes.deleted_at')
            ->orderBy('user_watchlists.updated_at', 'DESC')
            ->get();

            $episodes->transform(function($episode){

                $episode->thumbnail= getFileTemporaryURL($episode->thumbnail);

                $episode->video_url= getFileTemporaryURL($episode->video_url);

                $episode->subtitles =  getFileTemporaryURL($episode->subtitles);

                return  $episode;

            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Watchlist displayed successfully.',
                'toast' => true
            ],[
                'episodeData'=>$episodes
            ]);
        }
        catch(Exception $e){
            Log::info('Error to fetch watchlist'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch watchlist',
                'toast' => true
            ]);
        }
    }

    public function getFavroitesSeries(Request $request){
        try{
           $user=$request->attributes->get('user');
           $user_id=$user->id;
           $query = TvSeries::select(
            'tv_series.id',
            'tv_series.genre',
            'tv_series.status',
            'tv_series.title',
            'tv_series.description',
            'tv_series.release_date as releaseDate',
            'tv_series.cover_image as poster',
            'tv_series.content_rating as contentRating',
            'tv_series.cast as casts',
            'tv_series.directors as directors',
            'tv_series.channel_id as channelId',
            DB::raw('avg(tv_reviews.rating) as avgRating'),
            DB::raw('count(DISTINCT tv_seasons.id) as seasons'),
            DB::raw('count(DISTINCT season_episodes.id) as episodes')
        )
        ->rightJoin('tv_favorites', function ($join) use ($user_id) {
            $join->on('tv_series.id', '=', 'tv_favorites.series_id')
                 ->where('tv_favorites.user_id', '=', $user_id);
        })
        ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
        ->leftJoin('tv_seasons', function($join){
            $join->on('tv_series.id', '=', 'tv_seasons.series_id')
            ->whereNull('tv_seasons.deleted_at');
        })
        ->leftJoin('season_episodes',function($join){
            $join->on('tv_series.id', '=', 'season_episodes.series_id')
            ->whereNull('season_episodes.deleted_at');
        })
        ->whereNull('tv_series.deleted_at')
        ->groupBy(
            'tv_series.id',
            'tv_series.genre',
            'tv_series.status',
            'tv_series.title',
            'tv_series.description',
            'tv_series.release_date',
            'tv_series.cover_image',
            'tv_series.content_rating',
            'tv_series.cast',
            'tv_series.directors',
            'tv_series.channel_id'
        )
        ->orderBy('tv_favorites.updated_at', 'DESC')
        ->get();

            $query->transform(function($series){
            $series->poster=getFileTemporaryURL( $series->poster);
            $series->casts=explode(',',$series->casts);
            $series->genre=explode(',',$series->genre);
            $series->directors=explode(',',  $series->directors);

            $genres=DB::table('genres')
            ->select('id','name','slug')
            ->whereIn('id',$series->genre)
            ->get();

            $series->genres=$genres->map(function($genre){
                return [
                'id'=>$genre->id,
                'label'=>$genre->name,
                'value'=>$genre->slug
                ];
            });

            unset($series->genre);

            return $series;
        });

        return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => 'Favorite series displayed successfully.',
            'toast' => true
        ],[
            'SeriesData'=> $query
        ]);

     }
    catch(Exception $e){
        Log::info('Error to fetch favorite series'.$e->getMessage().'Line no'.$e->getLine());
        return generateResponse([
            'type' => 'error',
            'code' => 500,
            'status' => false,
            'message' => 'Error to fetch favorite series',
            'toast' => true
        ]);
    }

    }

    public function getSeriesReviews(Request $request){
        $request->validate([
            'series_id'=>'required|exists:tv_series,id'
        ],[
            'series_id.required'=>'the series ID is required.',
            'series_id.exists'=>'the series ID is not exists'
        ]);
        try{
            $user=$request->attributes->get('user');
            $user_id=$user->id;
            $series_id=$request->input('series_id');

            $query = TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('avg(tv_reviews.rating) as avgRating'),
                DB::raw('count(DISTINCT tv_seasons.id) as seasons'),
                DB::raw('count(DISTINCT season_episodes.id) as episodes')
            )
            ->leftJoin('tv_reviews', function ($join) use ($user_id) {
                $join->on('tv_series.id', '=', 'tv_reviews.series_id')
                     ->where('tv_reviews.user_id', '=', $user_id);
            })
            ->leftJoin('tv_seasons', function($join){
            $join->on('tv_series.id', '=', 'tv_seasons.series_id')
            ->whereNull('tv_seasons.deleted_at');
            })
            ->leftJoin('season_episodes', function($joinEpisode){
                $joinEpisode->on('tv_series.id', '=', 'season_episodes.series_id')
                ->whereNull('season_episodes.deleted_at');
            })
            ->whereNull('tv_series.deleted_at')
            ->when(isset($series_id), function ($query  ) use ($series_id) {
                return $query->where('tv_series.id', $series_id);
            })
            ->groupBy(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date',
                'tv_series.cover_image',
                'tv_series.content_rating',
                'tv_series.cast',
                'tv_series.directors',
                'tv_series.channel_id'
            )
            ->orderBy('tv_reviews.updated_at', 'DESC')
            ->get();

                $query->transform(function($series){
                $series->poster=getFileTemporaryURL( $series->poster);
                $series->casts=explode(',',$series->casts);
                $series->genre=explode(',',$series->genre);
                $series->directors=explode(',',  $series->directors);

                $genres=DB::table('genres')
                ->select('id','name','slug')
                ->whereIn('id',$series->genre)
                ->get();

                $series->genres=$genres->map(function($genre){
                    return [
                    'id'=>$genre->id,
                    'label'=>$genre->name,
                    'value'=>$genre->slug
                    ];
                });

                unset($series->genre);

                return $series;
            });

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Series displayed successfully.',
                'toast' => true
            ],[
                'SeriesData'=> $query
            ]);
        }
        catch(Exception $e){
            Log::info('Error to fetch reviewed series'.$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error to fetch reviewed series',
                'toast' => true
            ]);
        }
    }


    public function increaseView(Request $request){
        $request->validate([
            'episode_id'=>'required|exists:season_episodes,id'
        ],[
            'episode_id.required'=>'the episode ID is required.',
            'episode_id.exists'=>'the episode ID is not exists.'
        ]);
        try{
            $episode_id=$request->input('episode_id');

            if($episode_id)
                $episode=TvSeasonEpisode::where('id',$episode_id)->increment('views');
            else{
                return generateResponse([
                    'type'=>'error',
                    'code'=>404,
                    'status'=>false,
                    'message'=>"Epiosde id not found",
                    'toast'=>true
                ]);
            }

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'status'=>true,
                'message'=>"Episode view increased.",
                'toast'=>true
            ]);
        }
        catch(Exception $e){
            Log::info("Error to increment the view:".$e->getMessage().'Line no'.$e->getLine());
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'status'=>false,
                'message'=>"Error to increment the view.",
                'toast'=>true
            ]);
        }
    }

    public function getSeriesById(Request $request){
        $request->validate([
            'series_id'=>'required|exists:tv_series,id'
        ],[
            'series_id.required'=>'the series ID is required.',
            'series_id.exists'=>'the series ID is not exists.'
        ]);
        try{
            $series_id=$request->input('series_id');

            $Series=TvSeries::select(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.title',
                'tv_series.status',
                'tv_series.description',
                'tv_series.release_date as releaseDate',
                'tv_series.cover_image as poster',
                'tv_series.content_rating as contentRating',
                'tv_series.cast as casts',
                'tv_series.directors as directors',
                'tv_series.channel_id as channelId',
                DB::raw('MAX(tv_seasons.season_number) as latestSeasonNumber'),
                DB::raw('AVG(tv_reviews.rating) as avgRating'),
                DB::raw('COUNT(DISTINCT tv_seasons.id) as seasons'),
                DB::raw('COUNT(DISTINCT season_episodes.id) as episodes')
            )
                ->leftJoin('tv_reviews', 'tv_series.id', '=', 'tv_reviews.series_id')
                ->leftJoin('tv_seasons', function ($join) {
                    $join->on('tv_series.id', '=', 'tv_seasons.series_id')
                        ->whereNull('tv_seasons.deleted_at');
                })
                ->leftJoin('season_episodes', function ($join) {
                    $join->on('tv_series.id', '=', 'season_episodes.series_id')
                        ->whereNull('season_episodes.deleted_at');
                })
                ->where('tv_series.id',$series_id)
                ->whereNull('tv_series.deleted_at')
                ->groupBy(
                'tv_series.id',
                'tv_series.genre',
                'tv_series.status',
                'tv_series.title',
                'tv_series.description',
                'tv_series.release_date',
                'tv_series.cover_image',
                'tv_series.content_rating',
                'tv_series.cast',
                'tv_series.directors',
                'tv_series.channel_id'
            )
            ->get();

            $Series->transform(function ($series) {

                $series->poster = getFileTemporaryURL($series->poster);
                $series->casts = explode(',', $series->casts);
                $series->directors = explode(',', $series->directors);

                $SeriesGenre = explode(',', $series->genre);

                $genres = DB::table('genres')
                    ->whereIn('id', $SeriesGenre)
                    ->get(['id', 'name', 'slug']);


                $series->genres = $genres->map(function ($genre) {
                    return [
                        'value' => $genre->slug,
                        'label' => $genre->name,
                        'id' => $genre->id,
                    ];
                });

                unset($series->genre);

                return $series;
            });

            return generateResponse([
                'type'=>'success',
                'code'=>200,
                'status'=>true,
                'message'=>"Series displayed successfully.",
                'toast'=>true
            ],[
                'seriesData'=>$Series
            ]);
        }
        catch(Exception $e){
            Log::info("Error to display the series:".$e->getMessage().'Line no'.$e);
            return generateResponse([
                'type'=>'error',
                'code'=>500,
                'status'=>false,
                'message'=>"Error to display the series.",
                'toast'=>true
            ]);
        }
    }
}
