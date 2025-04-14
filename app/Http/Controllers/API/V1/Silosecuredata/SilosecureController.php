<?php

namespace App\Http\Controllers\API\V1\Silosecuredata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Silosecuredata\SilosecureConsultation;
use App\Models\Silosecuredata\ContactUs;
use App\Models\User;

class SilosecureController extends Controller
{
  public function getConsultation(Request $request)
  {

    try {
      $page = null;
      if ($request->filled('id')) {
        $id = $request->input('id');
        $dataarray = SilosecureConsultation::where('id', $id)->first();
        if (!empty($dataarray)) {
          $data[] = $dataarray->toArray();
        } else {
          $data = [];
        }
        $getTotalCount = count($data);
      } else {
        $query = SilosecureConsultation::query();
        $getTotalCount = $query->count();

        if ($request->filled('search_keyword')) {
          $searchKeyword = $request->search_keyword;
          $keywords = explode(' ', $searchKeyword);
          $query->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->where(function ($query) use ($keyword) {
                $query->where("full_name", "like", "%{$keyword}%")
                  ->orWhere("email", "like", "%{$keyword}%");
              });
            }
          });
        }
        if ($request->filled('page')) {
          $start = ($request->page - 1) * $request->limit;
          $query->skip($start);
          $page = $request->page;
        }
        if ($request->filled('limit')) {
          $query->take($request->limit);
        }
        $query->orderBy("id", "desc");
        $data = $query->get()->toArray();
      }

      $result = array();
      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $result[] = $value;
        }
      }
      
      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => false, 'data' => ["data" => $result, "page" => $page, "count" => $getTotalCount]]);
      } else {

        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('Getdata API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getContactUs(Request $request)
  {

    try {
      $page = null;
      if ($request->filled('id')) {
        $id = $request->input('id');
        $dataarray = ContactUs::where('id', $id)->first();
        if (!empty($dataarray)) {
          $data[] = $dataarray->toArray();
        } else {
          $data = [];
        }
        $getTotalCount = count($data);
      } else {
        $query = ContactUs::query();
        $getTotalCount = $query->count();

        if ($request->filled('search_keyword')) {
          $searchKeyword = $request->search_keyword;
          $keywords = explode(' ', $searchKeyword);
          $query->where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
              $query->where(function ($query) use ($keyword) {
                $query->where("first_name", "like", "%{$keyword}%")
                  ->orWhere("last_name", "like", "%{$keyword}%")
                  ->orWhere("email", "like", "%{$keyword}%");
              });
            }
          });
        }
        if ($request->filled('page')) {
          $start = ($request->page - 1) * $request->limit;
          $query->skip($start);
          $page = $request->page;
        }
        if ($request->filled('limit')) {
          $query->take($request->limit);
        }
        $query->orderBy("id", "desc");
        $data = $query->get()->toArray();
      }

      $result = array();
      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $result[] = $value;
        }
      }
      
      if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Data retrieved successfully', 'toast' => false, 'data' => ["data" => $result, "page" => $page, "count" => $getTotalCount]]);
      } else {

        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true,]);
      }
    } catch (\Exception $e) {
      Log::info('Getdata API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function deleteConsultation(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $id = $request->input('id');
        $data = SilosecureConsultation::where('id', $id)->first();

        if (!$data) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true]);
        }
        $data->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'data deleted successfully.', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Review delete API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function deleteContact(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $role_id = $user->role_id;
      if ($role_id == '1' || $role_id == '2') {
        $id = $request->input('id');
        $data = ContactUs::where('id', $id)->first();

        if (!$data) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true]);
        }
        $data->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'data deleted successfully.', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You dont have privilege to perform the task', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Review delete API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
