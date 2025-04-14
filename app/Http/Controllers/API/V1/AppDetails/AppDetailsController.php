<?php

namespace App\Http\Controllers\API\V1\AppDetails;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppDetails\AppRatingReview;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\AppDetails\SiloApp;
use App\Models\AppDetails\AppDetail;
use App\Models\AppDetails\AppSection;
use App\Models\AppDetails\AppCategories;
use App\Models\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AppDetails\AddAppDetailsRequest;
use App\Http\Requests\AppDetails\UpdateAppDetailsRequest;
use App\Http\Requests\AppDetails\GetAppDetailsRequest;
use App\Http\Requests\AppDetails\AddRatingReviewRequest;
use App\Http\Requests\AppDetails\ReviewLikeDislikeRequest;


class AppDetailsController extends Controller
{

    public function addDetails(AddAppDetailsRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $user_id = $user ? $user->id : null;
            $role_id = $user->role_id;

            if ($role_id != '2') {
                return response()->json([
                    'type' => 'error',
                    'status' => false,
                    'code' => 403,
                    'message' => 'Only admin can add app details',
                ]);
            }

            $app_id = $request->app_id;

            $appDetailsExist = AppDetail::where('app_id', $app_id)->first();
            if ($appDetailsExist) {
                return generateResponse(['type' => 'error', 'code' => 409, 'status' => false, 'message' => 'App details are already exist for given app_id']);
            }

            $app = SiloApp::find($app_id);

            if (!$app) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => true, 'message' => 'App does not exist']);
            }

            $app_detail = new AppDetail;

            $app_detail->app_id = $app_id;
            $appName = strip_tags($app->name);
            $app_detail->app_name = $appName;

            $app_section_id = $app->section_id;
            $app_section = AppSection::where('id', $app_section_id)->first();
            $app_detail->app_section = $app_section->name;
            $app_detail->about_app = $request->about_app;

    $app_detail->app_features = $request->app_features;


            if ($request->hasFile('app_logo')) {
                $appLogoFolder = "app_detail/app_logo/{$appName}";
                $app_logo = $request->file('app_logo');
                $fileExtension = $app_logo->getClientOriginalExtension();
                $filename = uniqid() . '.' . $fileExtension;
                $file_path = "{$appLogoFolder}/{$filename}";

                $app_logo->move($appLogoFolder, $filename);

                $app_detail->app_logo = $file_path;
            }

            $appFolder = "app_detail/screenshot/{$appName}";
            $index = 1;
            $screenshotData = [];

            if ($request->hasFile('app_screenshots')) {
                $app_screenshots = $request->file('app_screenshots');

                foreach ($app_screenshots as $app_screenshot) {
                    $fileExtension = $app_screenshot->getClientOriginalExtension();
                    $filename = uniqid() . '.' . $fileExtension;

                    $app_screenshot->move($appFolder, $filename);

                    $screenshotData[] = [
                        'id' => $index++,
                        'path' => "app_detail/screenshot/{$appName}/{$filename}",
                    ];
                }
                $app_detail->app_screenshots = json_encode($screenshotData);
            }

            $app_detail->save();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'App details added successfully', 'data' => $app_detail]);
        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding App Details: ' . $e->getMessage(),]);
        }
    }

    public function addRatingReview(AddRatingReviewRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $user_id = $user->id;
            $user_full_name = trim($user->first_name . ' ' . ($user->last_name ?? ''));
            $user_name=$user->username;
            $appId = $request->app_id;

            $userProfile=UserProfile::where('user_id',$user_id)->first();

            $defaultProfilePath = 'assets/default/images/user_avatar.png';

            if ($userProfile && $userProfile->profile_image_path) {
                $profilePath = getFileTemporaryUrl($userProfile->profile_image_path);
            } else {
                $profilePath = url($defaultProfilePath);
            }

            $app_details = AppDetail::where('app_id', $appId)->first();
            $app = SiloApp::findOrFail($appId);

            if (!$app_details) {
                return  generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'App not found',
                ]);
            }

            $appRatingReview = new AppRatingReview;
            $appRatingReview->app_id = $appId;
            $appRatingReview->user_id = $user_id;
            $appRatingReview->app_name = $app->name;
            $appRatingReview->reviews = $request->reviews;
            $appRatingReview->ratings = $request->ratings;

            DB::commit();

            if (empty($request->reviews) && empty($request->ratings)) {
                return generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'You have not added review or rating',
                ], 400);
            }

            if ($appRatingReview->save()) {
                return response()->json([
                    'type' => 'Success',
                    'code' => 200,
                    'status' => true,
                    'message' => 'Rating-Review added successfully',
                    'data' => ['RatingReview'=>$appRatingReview,'user_name'=>!empty($user_full_name)?$user_full_name:$user_name,'user_profile'=>$profilePath],
                ]);
            }

            return generateResponse([
                'type' => 'Error',
                'code' => 500,
                'status' => false,
                'message' => 'Failed to save'
            ], 500);
        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding Ratings and Reviews: ' . $e->getMessage(),]);
        }
    }

    public function likeDislikeReview(ReviewLikeDislikeRequest $request)
    {
        DB::beginTransaction();
        try {
            $reviewId = $request->reviewId;
            $user = $request->attributes->get('user');
            $user_id = $user->id;
            $reviewRatings = AppRatingReview::where('id', $reviewId)->first();

            if (!$reviewRatings) {
                return  generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'Review not found',
                ]);
            }
            if (!$reviewRatings->reviews) {
                return  generateResponse([
                    'type' => 'error',
                    'code' => 400,
                    'status' => false,
                    'message' => 'Review not found',
                ]);
            }

            $likes = json_decode($reviewRatings->likes, true) ?? [];
            $userAlreadyLiked = false;
            $likeIndex = null;

            foreach ($likes as $key => $like) {
                if ($like['user_id'] == $user_id) {
                    $userAlreadyLiked = true;
                    $likeIndex = $key;
                    break;
                }
            }

            $dislikes = json_decode($reviewRatings->dislikes, true) ?? [];
            $userAlreadyDisliked = false;
            $dislikeIndex = null;

            foreach ($dislikes as $key => $dislike) {
                if ($dislike['user_id'] == $user_id) {
                    $userAlreadyDisliked = true;
                    $dislikeIndex = $key;
                    break;
                }
            }

            if ($request->has('like_dislike')) {
                if ($request->like_dislike == 1) {
                    if ($userAlreadyLiked) {
                        unset($likes[$likeIndex]);
                        $message = 'Like removed successfully.';
                    } else {
                        if ($userAlreadyDisliked) {
                            unset($dislikes[$dislikeIndex]);
                        }
                        $likes[] = ['user_id' => $user_id];
                        $message = 'Liked successfully.';
                    }
                } elseif ($request->like_dislike == 0) {
                    if ($userAlreadyDisliked) {
                        unset($dislikes[$dislikeIndex]);
                        $message = 'Dislike removed successfully.';
                    } else {
                        if ($userAlreadyLiked) {
                            unset($likes[$likeIndex]);
                        }
                        $dislikes[] = ['user_id' => $user_id];
                        $message = 'Disliked successfully.';
                    }
                }
            } else {
                return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid action.']);
            }

            $reviewRatings->likes = json_encode($likes);
            $reviewRatings->dislikes = json_encode(array_values($dislikes));
            $reviewRatings->save();
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding Like and Dislike: ' . $e->getMessage(),]);
        }
    }

    public function updateAppDetails(UpdateAppDetailsRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $user_id = $user ? $user->id : null;
            $role_id = $user->role_id;

            if ($role_id != '2') {
                return generateResponse([
                    'type' => 'error',
                    'status' => false,
                    'code' => 403,
                    'message' => 'Only admin can update app details',
                ]);
            }

            $app_id = $request->app_id;
            $app = AppDetail::where('app_id', $app_id)->first();

            if (!$app) {
                return generateResponse(['type' => 'error', 'code' => 404, 'message' => 'App details not found.']);
            }

            if ($request->filled('about_app')) {
                $app->about_app = $request->about_app;
            }
            if ($request->filled('app_features')) {
                $app_features = $request->app_features;
                $app->app_features = json_encode($app_features);
            }

            //Update App Logo
            if($request->filled('app_logo')){
                $existingAppLogo=$app->app_logo;
                $appName=$app->app_name;
                if($request->hasFile('app_logo')){
                    if($existingAppLogo){
                        unlink(public_path($existingAppLogo));
                    }
                    $appLogoFolder = "app_detail/app_logo/{$appName}";
                    $app_logo = $request->file('app_logo');
                    $fileExtension = $app_logo->getClientOriginalExtension();
                    $filename = uniqid() . '.' . $fileExtension;
                    $file_path = "{$appLogoFolder}/{$filename}";

                    $app_logo->move($appLogoFolder, $filename);

                    $app->app_logo = $file_path;
                }
            }

            //Update App Screenshots
            if($request->filled('app_screenshots')){
                $screenshotData = [];
                $index = 1;

                $appFolder = public_path("app_detail/screenshot/{$app->app_name}");

                if ($request->hasFile('app_screenshots')) {

                    $existingScreenshots = json_decode($app->app_screenshots, true) ?? [];
                    if ($existingScreenshots) {
                        foreach ($existingScreenshots as $file) {
                            if (file_exists(public_path($file['path']))) {
                                unlink(public_path($file['path']));
                            }
                        }
                    }

                    $app_screenshots = $request->file('app_screenshots');

                    foreach ($app_screenshots as $app_screenshot) {
                        $fileExtension = $app_screenshot->getClientOriginalExtension();
                        $filename = uniqid() . '.' . $fileExtension;
                        $app_screenshot->move("app_detail/screenshot/{$app->app_name}", $filename);

                        $screenshotData[] = [
                            'id' => $index++,
                            'path' => "app_detail/screenshot/{$app->app_name}/{$filename}",
                        ];
                    }
                    $app->app_screenshots = json_encode($screenshotData);
                }
            }

            $app->save();

            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'App details Updated successfully', 'data' => $app]);
        } catch (\Exception $e) {
            DB::rollBack();

            return generateResponse([
                'type' => 'error',
                'status' => false,
                'code' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function deleteAppDetails(Request $request)
    {
        DB::beginTransaction();
        try {
            $app_id = $request->app_id;
            $app_details = AppDetail::where('app_id', $app_id)->first();
            $appFolder = public_path("app_detail/screenshot/{$app_details->app_name}");
            $appLogoFolder = public_path("app_detail/app_logo/{$app_details->app_name}");

            if (!$app_details) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'App not found']);
            }

            if ($app_details) {

                $appLogo = $app_details->app_logo;

                if (!empty($appLogo)) {
                    unlink($appLogo);
                }
                if (is_dir($appLogoFolder)) {
                    rmdir($appLogoFolder);
                }

                $app_screenshots = json_decode($app_details->app_screenshots, true);
                foreach ($app_screenshots as $app_screenshot) {
                    if (!empty($app_screenshot['path'])) {
                        unlink($app_screenshot['path']);
                    }
                }

                if (is_dir($appFolder)) {
                    rmdir($appFolder);
                }

                $app_details->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'App Details Deleted Successfully']);
            } else {
                DB::rollback();
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'App not found']);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting details: ' . $e->getMessage(),]);
        }
    }

    public function deleteRatingReview(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $login_user_id = $user ? $user->id : null;
            $role_id = $user->role_id;
            $reviewRating_id = $request->reviewRating_id;
            $reviewRatings = AppRatingReview::where('id', $reviewRating_id)->first();


            if (!$reviewRatings) {
                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Ratings Reviews not found']);
            }

            $user_id=$reviewRatings->user_id;
            if($login_user_id == $user_id || $role_id=='2'){
                $reviewRatings->delete();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Ratings Reviews Deleted Successfully']);
            }else{
                return generateResponse(['type' => 'error', 'code' => 401, 'status' => true, 'message' => 'You can not delete review']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting Ratings Reviews: ' . $e->getMessage(),]);
        }
    }

    public function getAppDetails(GetAppDetailsRequest $request)
    {
        try {
            DB::beginTransaction();
            $app_id=$request->app_id;
            $app_details= AppDetail::where('app_id',$app_id)->first();
            $app=SiloApp::where('id',$app_id)->first();
            $app_rating_review=AppRatingReview::where('app_id',$app_id)->select('id','user_id','reviews','ratings','likes','dislikes','created_at')->orderBy('created_at','desc')->get();
            $ratingReview=[];

            foreach($app_rating_review as $ratingreview){
                $user = User::where('id', $ratingreview->user_id)->select('username','first_name','last_name')->first();
                $userProfile=UserProfile::where('user_id',$ratingreview->user_id)->first();

                $defaultProfilePath = 'assets/default/images/user_avatar.png';

            if ($userProfile && $userProfile->profile_image_path) {
                $profilePath = getFileTemporaryUrl($userProfile->profile_image_path);
            } else {
                $profilePath = url($defaultProfilePath);
            }

                $user_full_name = trim($user->first_name . ' ' . ($user->last_name ?? ''));
                $user_name=$user->username;
                $likes = json_decode($ratingreview->likes, true) ?? [];
                $totalLikes = count($likes);

                $dislikes = json_decode($ratingreview->dislikes, true) ?? [];
                $totalDislikes = count($dislikes);

                $ratingReview[]=[
                    "user_name"=>!empty($user_full_name)?$user_full_name:$user_name,
                    "username"=>$user_name,
                    "user_profile"=>$profilePath,
                    "reviewId"=>$ratingreview->id,
                    "reviews"=>$ratingreview->reviews,
                    "ratings"=>$ratingreview->ratings,
                    'review_likes'=>$totalLikes,
                    'review_disLikes'=>$totalDislikes,
                    "created_at"=>$ratingreview->created_at->format('Y-m-d'),
                ];
            }


            $project_link = $app->project_link;

            $more_apps = [];
            $siloApps = SiloApp::where('id', '!=', $request->app_id)->where('name','!=','Merchant')->where('name','!=','Influencers')->inRandomOrder()->limit(6)->get();
            foreach ($siloApps as $siloApp) {
                $app_id = $siloApp->id;

                $appCategoryName=$siloApp->category;

                $averageRating = AppRatingReview::where('app_id', $app_id)->avg('ratings');
                $ratings = number_format($averageRating, 2);

                $more_apps[] = [
                    'app_id' => $siloApp->id,
                    'App_Name' => strip_tags($siloApp->name),
                    'Section_Name' => $appCategoryName,
                    'App_Logo' => $siloApp->image_link,
                    'Ratings' => $ratings,
                ];
            }

            if (!$app_details) {
                $appDetails = ['app-details' => 'App details not found'];

                return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => $appDetails]);
            }


            $screenshots = json_decode($app_details->app_screenshots, true);
            $screenshoturl = [];
            if ($screenshots) {
                foreach ($screenshots as $screenshot) {
                    $screenshoturl[] = url($screenshot['path']);
                }
            }

            $appLogo = $app_details->app_logo;

            if ($appLogo) {
                $logourl = url($appLogo);
            }

            $totalReviews = AppRatingReview::where('app_id', $app_id)->count('reviews');
            $totalReviews = AppRatingReview::where('app_id', $app_details->app_id)->whereNotNull('reviews')->where('reviews', '!=', '')->count();
            $averageRating = AppRatingReview::where('app_id', $app_details->app_id)->avg('ratings');
            $ratings = number_format($averageRating, 2);

            $app_features = json_decode($app_details->app_features, true);
            $app_details = [
                'app_id' => $app_details->app_id,
                'app_name' => $app_details->app_name,
                'app_logo' => $logourl,
                'app_section' => $app->category,
                'about_app' => $app_details->about_app,
                'app_features' => $app_features,
                'app_screenshots' => $screenshoturl,
                'project_link' => $project_link,
                'totalReviews' => $totalReviews,
                'Ratings' => $ratings,
                'updated_at' => $app_details->updated_at->format('F j,Y'),
            ];


            $appData = ['app-details' => $app_details, 'app-ratings-reviews' => $ratingReview, 'More Apps' => $more_apps];
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'App Details featched successfully', 'data' =>$appData]);
        } catch (\Exception $e) {
            DB::rollback();
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error in getting app details: ' . $e->getMessage(),]);
        }
    }
}
