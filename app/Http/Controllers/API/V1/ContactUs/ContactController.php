<?php

namespace App\Http\Controllers\API\V1\ContactUs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ContactUs\ContactUs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ContactController  extends Controller
{

  public function getContactUs(Request $request)
  {
    try {
      $page = $request->input('page', 1);
      $limit = $request->input('limit', 10);
      $isRequest = $request->input('is_request');

      $contactQuery = ContactUs::query();

      if ($isRequest === '1' || $isRequest === '0') {
        $contactQuery->where('is_request', $isRequest);
      } else if ($isRequest === 'null') {
        $contactQuery->where('is_request', '!=', '1');
      }

      if ($request->filled('search_keyword')) {
        $searchKeyword = $request->input('search_keyword');
        $keywords = explode(' ', $searchKeyword);
        $contactQuery->where(function ($query) use ($keywords) {
          foreach ($keywords as $keyword) {
            $query->where(function ($query) use ($keyword) {
              $query->where("first_name", "like", "%{$keyword}%")
                ->orWhere("last_name", "like", "%{$keyword}%")
                ->orWhere("email", "like", "%{$keyword}%");
            });
          }
        });
      }

      $getTotalCount = $contactQuery->count();

      $contactQuery->orderBy("id", "desc");
      $contacts = $contactQuery->skip(($page - 1) * $limit)->take($limit)->get()->toArray();

      $result = [];
      if (!empty($contacts)) {
        foreach ($contacts as $key => $value) {
          $result[] = $value;
        }
      }

      if ($result) {
        return response()->json([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Data retrieved successfully',
          'toast' => false,
          'data' => [
            'contact' => $result,
            'page' => $page,
            'limit' => $limit,
            'count' => $getTotalCount
          ]
        ]);
      } else {
        return response()->json([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Data not found',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Get ContactUs API error: ' . $e->getMessage());
      return response()->json([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }

  public function deleteFeedback(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $request_id = $request->input('request_id');
        $news = ContactUs::where('id', $request_id)->first();

        if (!$news) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Request not found', 'toast' => true]);
        }
        $news->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Request deleted successfully.', 'toast' => true]);
        // }
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Request delete API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
