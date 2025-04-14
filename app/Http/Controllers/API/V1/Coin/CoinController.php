<?php

namespace App\Http\Controllers\API\V1\Coin;

use App\Models\coin\CoinInvestment;
use App\Models\coin\CoinModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Coin\CoinUpdateRequest;
use App\Http\Requests\Coin\CoinRegisterRequest;
use App\Http\Requests\Coin\GetReviewRequest;
use App\Http\Requests\Coin\MakeInvestmentRequest;
use App\Http\Requests\Coin\AddFeedbackRequest;
use App\Models\coin\CoinCalendarYear;
use App\Models\coin\FeedbackModel;
use App\Models\User;
use App\Models\TokenTransactionLog;

class CoinController extends Controller
{
  public function getCoin(Request $request)
  {
    try {
      $page = null;
      if ($request->filled('coin_id')) {
        $coin_id = $request->input('coin_id');
        $coinArr = CoinModel::where('id', $coin_id)->first();
        if (!empty($coinArr)) {
          $coin[] = $coinArr->toArray();
        } else {
          $coin = [];
        }
        $getTotalCount = count($coin);
      } else {
        $coinquery = CoinModel::query();
        $getTotalCount = $coinquery->count();

        if ($request->filled('search_keyword')) {
          $searchKeyword = $request->search_keyword;
          $keywords = explode(' ', $searchKeyword);
          $coinquery->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->where(function ($query) use ($keyword) {
                $query->where("coin_name", "like", "%{$keyword}%")
                  ->orWhere("coin_symbol", "like", "%{$keyword}%")
                  ->orWhere("description", "like", "%{$keyword}%")
                  ->orWhere("price", "like", "%{$keyword}%");
              });
            }
          });
        }
        if ($request->filled('page')) {
          $start = $request->page * $request->limit;

          $coinquery->skip($start);
          $page = $request->page;
        }
        if ($request->filled('limit')) {
          $coinquery->take($request->limit);
        }
        $coinquery->orderBy("id", "desc");
        $coin = $coinquery->get()->toArray();
      }

      $result = array();
      if (!empty($coin)) {
        foreach ($coin as $key => $value) {
            if (!empty($value["coin_logo"])) {
                 $value["coin_logo"] = getFileTemporaryURL("public/" . $value["coin_logo"]);
            }else{
                $value["coin_logo"] = asset('assets/default/images/coin-logo.png');
            }

          if (!empty($value["video_url"])) {
            $value["video_url"] = getFileTemporaryURL("public/" . $value["video_url"]);
          } else {
            $value["video_url"] = asset('assets/default/videos/channel_video.mp4');
          }
          $totalInvestMent = CoinInvestment::where("coin_id", $value["id"])->sum('investment_amount');
          $value["total_investment"] = $totalInvestMent;
          $result[] = $value;
        }
      }

      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'coin data retrieved successfully', 'toast' => false, 'data' => ["coin" => $result, "page" => $page, "count" => $getTotalCount]]);
      } else {

        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'coin data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('Getcoin API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }


  public function getAdminCoin(Request $request)
  {

    try {
      $page = null;
      if ($request->filled('coin_id')) {
        $coin_id = $request->input('coin_id');
        $coinArr = CoinModel::where('id', $coin_id)->first();
        if (!empty($coinArr)) {
          $coin[] = $coinArr->toArray();
        } else {
          $coin = [];
        }
        $getTotalCount = count($coin);
      } else {
        $coinquery = CoinModel::query();
        $getTotalCount = $coinquery->count();

        if ($request->filled('search_keyword')) {
          $searchKeyword = $request->search_keyword;
          $keywords = explode(' ', $searchKeyword);
          $coinquery->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->where(function ($query) use ($keyword) {
                $query->where("coin_name", "like", "%{$keyword}%")
                  ->orWhere("coin_symbol", "like", "%{$keyword}%")
                  ->orWhere("description", "like", "%{$keyword}%")
                  ->orWhere("price", "like", "%{$keyword}%");
              });
            }
          });
        }
        if ($request->filled('page')) {
          // $start = $request->page * $request->limit;
          $start = ($request->page - 1) * $request->limit;
          // $start = ($request->page - 1) * $request->limit;
          $coinquery->skip($start);
          $page = $request->page;
        }
        if ($request->filled('limit')) {
          $coinquery->take($request->limit);
        }
        $coinquery->orderBy("id", "desc");
        $coin = $coinquery->get()->toArray();
      }

      $result = array();
      if (!empty($coin)) {
        foreach ($coin as $key => $value) {
            if (!empty($value["coin_logo"])) {
                $value["coin_logo"] = getFileTemporaryURL("public/" . $value["coin_logo"]);
           }else{
               $value["coin_logo"] = asset('assets/default/images/coin-logo.png');
           }

          $totalInvestMent = CoinInvestment::where("coin_id", $value["id"])->sum('investment_amount');
          $value["total_investment"] = $totalInvestMent;
          $result[] = $value;
        }
      }
      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'coin data retrieved successfully', 'toast' => false, 'data' => ["coin" => $result, "page" => $page, "count" => $getTotalCount]]);
      } else {

        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'coin data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('Getcoin API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function registerCoin(CoinRegisterRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $getCoinData = CoinModel::where("coin_name", $request->coin_name)->first();
        if (!empty($getCoinData)) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Coin name already exists', 'toast' => true]);
        }

        $coin = new CoinModel();
        $coin->user_id = $user->id;
        $coin->coin_name = $request->coin_name;
        $coin->coin_symbol = $request->coin_symbol;
        $coin->description = $request->description;
        $coin->price = $request->price;
        $coin->one_h = $request->one_h;
        $coin->twenty_four_h = $request->twenty_four_h;
        $coin->seven_d = $request->seven_d;
        $coin->market_cap = $request->market_cap;
        $coin->volume = $request->volume;
        $coin->circulation_supply = $request->circulation_supply;
        if ($request->hasFile('coin_logo')) {
          $file = $request->file('coin_logo');
          $fileName = $file->getClientOriginalName();
          $coin_logo_Path = "public/assets/coin/{$user->id}/coin_logo/{$fileName}";
          Storage::put($coin_logo_Path, file_get_contents($file));
          $coin_logo_Path = substr($coin_logo_Path, strlen('public/'));
          $coin->coin_logo = $coin_logo_Path;
        }
        if ($request->hasFile('video_url')) {
          $file = $request->file('video_url');
          $fileName = $file->getClientOriginalName();
          $video_url_Path = "public/assets/coin/{$user->id}/video_url/{$fileName}";
          Storage::put($video_url_Path, file_get_contents($file));
          $video_url_Path = substr($video_url_Path, strlen('public/'));
          $coin->video_url = $video_url_Path;
        }
        $coin->save();
        DB::commit();
        $coin_silo = CoinModel::where('id', $coin->id)->first();
        $coin_data = [
          'id' => $coin_silo->id,
          'user_id' => $coin_silo->user_id,
          'coin_name' => $coin_silo->coin_name,
          'coin_symbol' => $coin_silo->coin_symbol,
          'description' => $coin_silo->description,
          'coin_logo' => getFileTemporaryURL("public/" . $coin_silo->coin_logo),
          'video_url' => getFileTemporaryURL("public/" . $coin_silo->video_url),
          'price' => $coin_silo->price,
          'one_h' => $coin_silo->one_h,
          'twenty_four_h' => $coin_silo->twenty_four_h,
          'seven_d' => $coin_silo->seven_d,
          'market_cap' => $coin_silo->market_cap,
          'volume' => $coin_silo->volume,
          'circulation_supply' => $coin_silo->circulation_supply,
        ];
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Coin data added successfully.', 'toast' => true], ['coin' => $coin_data]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Coin register API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function updateCoin(CoinUpdateRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $coin_id = $request->input('coin_id');
        $coin = CoinModel::where('id', $coin_id)->first();
        if ($request->filled('coin_name')) {
          $coin->coin_name = $request->coin_name;
        }
        if ($request->filled('coin_symbol')) {
          $coin->coin_symbol = $request->coin_symbol;
        }
        if ($request->filled('description')) {
          $coin->description = $request->description;
        }
        if ($request->filled('price')) {
          $coin->price = $request->price;
        }
        if ($request->filled('one_h')) {
          $coin->one_h = $request->one_h;
        }
        if ($request->filled('twenty_four_h')) {
          $coin->twenty_four_h = $request->twenty_four_h;
        }
        if ($request->filled('seven_d')) {
          $coin->seven_d = $request->seven_d;
        }
        if ($request->filled('market_cap')) {
          $coin->market_cap = $request->market_cap;
        }
        if ($request->filled('volume')) {
          $coin->volume = $request->volume;
        }
        if ($request->filled('circulation_supply')) {
          $coin->circulation_supply = $request->circulation_supply;
        }

        if ($request->hasFile('coin_logo')) {
          if ($coin->coin_logo) {
            Storage::delete($coin->coin_logo);
          }
          $file = $request->file('coin_logo');
          $fileName = $file->getClientOriginalName();
          $coin_logo_Path = "public/assets/coin/{$user->id}/coin_logo/{$fileName}";
          Storage::put($coin_logo_Path, file_get_contents($file));
          $coin_logo_Path = substr($coin_logo_Path, strlen('public/'));
          $coin->coin_logo = $coin_logo_Path;
        }

        if ($request->hasFile('video_url')) {
          if ($coin->video_url) {
            Storage::delete($coin->video_url);
          }
          $file = $request->file('video_url');
          $fileName = $file->getClientOriginalName();
          $video_url_Path = "public/assets/coin/{$user->id}/video_url/{$fileName}";
          Storage::put($video_url_Path, file_get_contents($file));
          $video_url_Path = substr($video_url_Path, strlen('public/'));
          $coin->video_url = $video_url_Path;
        } elseif ($request->input('video_url') === null) {
          if ($coin->video_url) {
            Storage::delete($coin->video_url);
          }
          $coin->video_url = null;
        }

        $coin->save();
        DB::commit();

        $coin_silo = CoinModel::where('id', $coin->id)->first();
        $coin_data = [
          'id' => $coin_silo->id,
          'user_id' => $coin_silo->user_id,
          'coin_name' => $coin_silo->coin_name,
          'coin_symbol' => $coin_silo->coin_symbol,
          'description' => $coin_silo->description,
          'coin_logo' => getFileTemporaryURL("public/" . $coin_silo->coin_logo),
          'video_url' => getFileTemporaryURL("public/" . $coin_silo->video_url),
          'price' => $coin_silo->price,
          'one_h' => $coin_silo->one_h,
          'twenty_four_h' => $coin_silo->twenty_four_h,
          'seven_d' => $coin_silo->seven_d,
          'market_cap' => $coin_silo->market_cap,
          'volume' => $coin_silo->volume,
          'circulation_supply' => $coin_silo->circulation_supply,
        ];
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Coin updated successfully.', 'toast' => true], ['coin' => $coin_data]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Coin update API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function deleteCoin(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $coin_id = $request->input('coin_id');
        $coin = CoinModel::where('id', $coin_id)->first();

        if (!$coin) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Coin not found', 'toast' => true]);
        }
        if(!empty($coin->coin_logo)){
            storage::delete($coin->coin_logo);
        }
      
        $coin->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Coin deleted successfully.', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Coin delete API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getCoinCalendar(Request $request)
  {
    try {
      $coinCalendar = CoinCalendarYear::where("start_year", ">=", date("y"))->get();
      if ($coinCalendar) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'coin calendar data retrieved successfully', 'toast' => false, 'data' => ["list" => $coinCalendar]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'coin calendar data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('Getcoin API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getTokenValue(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 401, 'status' => false, 'message' => 'User not authenticated', 'toast' => true]);
      }

      // $investmentAmount = $request->query('investment_amount');
      $investmentAmount = $request->investment_amount;

      if (!$investmentAmount) {
        return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Investment amount is required', 'toast' => true]);
      }

      $tokenMetricsValue = getTokenMetricsValues();
      $packageTokenPrice = $investmentAmount / $tokenMetricsValue;
      $augerTokens = $packageTokenPrice * (config('app.auger_fee') / 100);

      $responseData = [
        "required_tokens" => $packageTokenPrice + $augerTokens,
        "available_tokens" => $user->account_tokens,
        "token_value" => $tokenMetricsValue,
        "price" => $investmentAmount,
        "auger_fee" => config('app.auger_fee'),
        "augerTokens" => $augerTokens
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully.', 'toast' => true, 'data' => $responseData]);
    } catch (\Exception $e) {
      Log::info('Coin make investment API error: ' . $e->getMessage());

      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }


  public function addFeedback(AddFeedbackRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      if ($request->coin_id || $user->id) {
        $reviewquery = FeedbackModel::where('user_id', $user->id)
          ->where('coin_id', $request->coin_id)->get();
        $review_count = $reviewquery->count();
      }

      $userreview = new FeedbackModel();
      $userreview->user_id = $user->id;
      $userreview->coin_id = $request->coin_id;
      $userreview->rating = $request->rating;
      $userreview->review = $request->review;
      $userreview->save();
      $admin_id = getadmindetails();
      $coin_nm = getTabledata('coin', 'id', $request->coin_id);
      $coin_name = $coin_nm->coin_name;
      $userdata = getTabledata('users', 'id', $user->id);
      $username = $userdata->username;
      if ($username && $coin_name) {
        addNotification($admin_id, $admin_id, "New review added by user " . $username . " for coin " . $coin_name, null, null, '14', '/admin-manage-coinexchange/reviewsList','1');
      }
      DB::commit();
      if ($userreview) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Review added successfully.', 'toast' => true, 'data' => ["userreview" => $userreview, "count" => $review_count]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Submit Review API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getReview(GetReviewRequest $request)
  {
    try {
      $user = $request->attributes->get('user');

      if (isset($request->coin_id)) {
        $coin_id = $request->input('coin_id');
        // $getreview = DB::table('coin_feedback')
        // ->join('users', 'coin_feedback.user_id', '=', 'users.id')
        // ->join('user_profiles', 'coin_feedback.user_id', '=', 'user_profiles.user_id')
        // ->select('coin_feedback.*', 'users.username', 'user_profiles.profile_image_path')
        // ->where('coin_feedback.coin_id',$coin_id)
        // ->where('coin_feedback.is_approve', '1')
        // ->orderBy('coin_feedback.created_at', 'desc')
        // ->take(4)
        // ->get();
        $getreview = FeedbackModel::where('coin_id', $coin_id)->where('is_approve', '1')->with([
          'user' => function ($query) {
            $query->select('id', 'username', 'email');
          },
          'userProfile' => function ($query) {
            $query->select('user_id', 'profile_image_path');
          }
        ])
          ->orderBy('id', 'desc')
          ->limit(4)
          ->get();

        $result = $getreview->map(function ($f) {
          return [
            'id' => $f->id,
            'user_id' => $f->user_id,
            'coin_id' => $f->coin_id,
            'rating' => $f->rating,
            'review' => $f->review,
            'is_approve' => $f->is_approve,
            'created_at' => $f->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $f->updated_at->format('Y-m-d H:i:s'),
            'username' => $f->user->username,
            'profile_image_path' => $f->userProfile->profile_image_path ?? 'default/path/to/image.jpg'
          ];
        });

        foreach ($result as $key => $value) {
            $totalusers = $value['user_id'];
        }
        $totalreviewcount = $result->count();
        // $usercount = $result->pluck('user_id')->unique();
        $ratingsSum = $result->sum('rating');
        $starcount = $ratingsSum / $totalreviewcount;

        if ($totalreviewcount != 0) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Reviews retrieved successfully', 'toast' => true, 'data' => ['totalreviewcount' => $totalreviewcount, 'starcount' => ceil($starcount), 'userreview' => $result]]);
        } else {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'coin data not found', 'toast' => true,]);
        }
      }
    } catch (\Exception $e) {
      Log::error('Error while retrieving coin data: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
      ]);
    }
  }

  public function getDashboard(Request $request)
  {
    try {
      // $getinvestment = CoinInvestment::sum('investment_amount');
      $getinvestment = CoinInvestment::distinct('user_id')->count('user_id');
      $value = [];
      if ($getinvestment) {
        $value["total_investment"] = $getinvestment;
      }
      $value["total_return"] = 0;

      $coinquery = CoinModel::query();
      $getcompanyCount = $coinquery->count();
      $value["total_companies"] = $getcompanyCount;

      $value["average_return"] = 0;
      $result[] = $value;
      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'data retrieved successfully', 'toast' => true, 'data' => ['section_first' => $result]]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::error('Error while retrieving coin data: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
      ]);
    }
  }

  public function incrementView(Request $request)
  {
    DB::beginTransaction();
    try {
      if ($request->has('coin_id')) {
        $coin_id = $request->input('coin_id');
        $coin_data = CoinModel::find($coin_id);

        if ($coin_data) {
          $coin_data->coin_view = $coin_data->coin_view + 1;
          $coin_data->save();

          DB::commit();

          return generateResponse([
            'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Coin view count incremented successfully', 'toast' => true
          ]);
        } else {
          return generateResponse([
            'type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Coin not found', 'toast' => true
          ], 404);
        }
      } else {
        return generateResponse([
          'type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Coin ID is missing in the request', 'toast' => true
        ], 400);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error while incrementing coin view: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing: ' . $e->getMessage(), 'toast' => true
      ], 500);
    }
  }

  public function recentlyAdded(Request $request)
  {
    try {
      $id = $request->input('id');
      $searchKeyword = $request->input('search_keyword');
      $page = $request->input('page', 1);
      $limit = $request->input('limit', 10); // Default to limit 10 if not provided

      // Common search and pagination function
      $applySearchAndPagination = function ($query) use ($searchKeyword, $page, $limit) {
        if ($searchKeyword) {
          $keywords = explode(' ', $searchKeyword);
          $query->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->where(function ($query) use ($keyword) {
                $query->where("coin_name", "like", "%{$keyword}%")
                  ->orWhere("coin_symbol", "like", "%{$keyword}%")
                  ->orWhere("description", "like", "%{$keyword}%")
                  ->orWhere("price", "like", "%{$keyword}%");
              });
            }
          });
        }
        $start = ($page - 1) * $limit;
        $query->skip($start)->take($limit);

        return $query;
      };

      $response_data = [];

      switch ($id) {
        case 0:
          $coinquery = CoinInvestment::join('coin', 'coin_investment.coin_id', '=', 'coin.id')
            ->select('coin_investment.*', 'coin.*', 'coin.coin_symbol', 'coin.coin_logo')
            // ->groupBy('coin_investment.coin_id')
            ->orderBy('coin_investment.investment_amount', 'desc');

          $coinquery = $applySearchAndPagination($coinquery);
          $response_data['trending_coins'] = $coinquery->get();
          break;

        case 1:
          $coinquery = CoinModel::orderBy('created_at', 'desc');
          $coinquery = $applySearchAndPagination($coinquery);
          $response_data['recently_added_coins'] = $coinquery->get();
          break;

        case 2:
          $coinquery = CoinModel::orderBy('coin_view', 'desc');
          $coinquery = $applySearchAndPagination($coinquery);
          $response_data['most_viewed_coins'] = $coinquery->get();
          break;

        default:
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid id parameter', 'toast' => true], 400);
      }

      return generateResponse([
        'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => true, 'data' => $response_data
      ]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving coin data: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing: ' . $e->getMessage(), 'toast' => true], 500);
    }
  }

  public function trendingcoin(Request $request)
  {
    try {
      $response_data = [];
      $subquery = DB::table('coin_investment')
        ->selectRaw('MAX(coin_investment.investment_amount) as max_investment_amount, coin_investment.coin_id')
        ->groupBy('coin_investment.coin_id');

      $trendingquery = CoinInvestment::join('coin', 'coin_investment.coin_id', '=', 'coin.id')
        ->joinSub($subquery, 'subquery', function ($join) {
          $join->on('coin_investment.investment_amount', '=', 'subquery.max_investment_amount')
            ->on('coin_investment.coin_id', '=', 'subquery.coin_id');
        })
        ->select('coin_investment.*', 'coin.coin_name', 'coin.coin_symbol', 'coin.coin_logo')
        ->orderBy('coin_investment.investment_amount', 'desc')
        ->take(5);

      $trendingquery = $this->applySearchAndPagination($trendingquery);
      $response_data['trending_coins'] = $trendingquery->get();

      $recentlyquery = CoinModel::orderBy('created_at', 'desc')->take(5);
      $recentlyquery = $this->applySearchAndPagination($recentlyquery);
      $response_data['recently_added_coins'] = $recentlyquery->get();

      $coinquery = CoinModel::orderBy('coin_view', 'desc')->take(5);
      $coinquery = $this->applySearchAndPagination($coinquery);
      $response_data['most_viewed_coins'] = $coinquery->get();

      return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Data retrieved successfully','toast' => true,'data' => $response_data]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving coin data: ' . $e->getMessage());
      return generateResponse(['type' => 'error','code' => 500,'status' => false,'message' => 'Error while processing: ' . $e->getMessage(),'toast' => true], 500);
    }
  }

  protected function applySearchAndPagination($query)
  {
    return $query;
  }


  public function getadminreviews(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $data = [];
      // $reviewdata = $user_profile->coinFeedbacks ? $user_profile->coinFeedbacks : null;
      if (isset($request->id)) {
        $reviewsDetails = DB::table('coin_feedback')
          ->join('users', 'coin_feedback.user_id', '=', 'users.id')
          ->join('user_profiles', 'coin_feedback.user_id', '=', 'user_profiles.user_id')
          ->join('coin', 'coin_feedback.coin_id', '=', 'coin.id')
          ->select('coin_feedback.*', 'users.username', 'user_profiles.profile_image_path', 'coin.coin_name')
          ->where('coin_feedback.id', $request->id)
          ->orderBy('coin_feedback.created_at', 'desc')
          ->get();

        $getTotalCount = count($reviewsDetails);
      } else {
        $newsQuery = DB::table('coin_feedback')
          ->leftJoin('users', 'coin_feedback.user_id', '=', 'users.id')
          ->join('user_profiles', 'coin_feedback.user_id', '=', 'user_profiles.user_id')
          ->join('coin', 'coin_feedback.coin_id', '=', 'coin.id')
          ->select('coin_feedback.*', 'users.username', 'user_profiles.profile_image_path', 'coin.coin_name');

        if (isset($request->search_keyword)) {
          $searchKeyword = $request->search_keyword;
          $keywords = explode(' ', $searchKeyword);
          $newsQuery->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->orWhere('coin_feedback.review', 'like', "%{$keyword}%");
              $query->orWhere('users.username', 'like', "%{$keyword}%");
              $query->orWhere('coin.coin_name', 'like', "%{$keyword}%");
            }
          });
        }

        $getTotalCount = $newsQuery->count();

        if (isset($request->page) && isset($request->limit)) {
          $start = ($request->page - 1) * $request->limit;
          $newsQuery->skip($start)->take($request->limit);
          $data['page'] = $request->page;
        }

        $reviewsDetails = $newsQuery->orderBy('coin_feedback.created_at', 'desc')->get();
      }

      if (!empty($reviewsDetails)) {
        $data['Reviews'] = $reviewsDetails;
        $data['totalRecords'] = $getTotalCount;
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Feedback data retrieved successfully', 'toast' => true, 'data' => $data]);
      } else {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => ['packages' => []]]);
      }
    } catch (\Exception $e) {
      Log::info('Get Feedback API error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function reviewApproval(Request $request)
  {
    DB::beginTransaction();
    try {
      if ($request->has('review_id')) {
        $review_id = $request->input('review_id');
        $is_approve = $request->input('is_approve');
        $coin_data = FeedbackModel::find($review_id);

        if ($coin_data) {
          $coin_data->is_approve = $is_approve;
          $coin_data->save();

          DB::commit();

          return generateResponse([
            'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Review Approved successfully', 'toast' => true
          ]);
        } else {
          return generateResponse([
            'type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Review not found', 'toast' => true
          ], 404);
        }
      } else {
        return generateResponse([
          'type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Review ID is missing in the request', 'toast' => true
        ], 400);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error while review approval: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing: ' . $e->getMessage(), 'toast' => true
      ], 500);
    }
  }

  public function deleteReview(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        // if ($request->has('review_id')) {
        $review_id = $request->input('review_id');
        $news = FeedbackModel::where('id', $review_id)->first();

        if (!$news) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Review not found', 'toast' => true]);
        }
        // storage::delete($news->news_img);
        $news->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Review deleted successfully.', 'toast' => true]);
        // }
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Review delete API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getCoinVideo(Request $request)
  {
    try {
      $coin_id = $request->input('coin_id');
      $coinArr = CoinModel::where('id', $coin_id)->first();

      if ($coinArr) {
        $coin_video = $coinArr->video_url;

        if (!empty($coin_video)) {
          $data["video_url"] = getFileTemporaryURL("public/" . $coin_video);
        } else {
          $data["video_url"] = asset('assets/default/videos/channel_video.mp4');
        }

        return generateResponse([
          'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'video data retrieved successfully', 'toast' => false, 'data' => $data
        ]);
      } else {
        return generateResponse([
          'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'coin data not found', 'toast' => true,
        ]);
      }
    } catch (\Exception $e) {
      Log::info('Getcoin API error : ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true,
      ]);
    }
  }

  public function getInvestments(Request $request)
  {
    try {
      $coin_id = $request->input('coin_id');
      $currentDate = now();

      $totalInvestment = DB::table('coin_investment')
        ->where('coin_id', $coin_id)
        ->sum('investment_amount');

      $investmentLast7Days = DB::table('coin_investment')
        ->where('coin_id', $coin_id)
        ->where('investment_date', '>=', $currentDate->copy()->subDays(7))
        ->sum('investment_amount');

      $investmentLast30Days = DB::table('coin_investment')
        ->where('coin_id', $coin_id)
        ->where('investment_date', '>=', $currentDate->copy()->subDays(30))
        ->sum('investment_amount');

      $investmentLast90Days = DB::table('coin_investment')
        ->where('coin_id', $coin_id)
        ->where('investment_date', '>=', $currentDate->copy()->subDays(90))
        ->sum('investment_amount');

      $coinData = DB::table('coin')
        ->select('coin_name', 'coin_logo', 'coin_symbol')
        ->where('id', $coin_id)
        ->first();

      $response = ['type' => $totalInvestment > 0 ? 'success' : 'error','code' => 200,'status' => $totalInvestment > 0,'message' => $totalInvestment > 0 ? 'Investments retrieved successfully' : 'No investment data found for the specified coin','toast' => true,'data' => [
          'coin_data' => $coinData, 'totalInvestment' => $totalInvestment, 'last_7_days' => $investmentLast7Days, 'last_30_days' => $investmentLast30Days, 'last_90_days' => $investmentLast90Days,],];

      return generateResponse($response);
    } catch (\Exception $e) {
      Log::error('Error while retrieving investment data: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => ['coin_data' => $coinData ?? null,],
      ]);
    }
  }
}
