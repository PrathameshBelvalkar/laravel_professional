<?php

namespace App\Http\Controllers\API\V1\UserManagement;

use App\Models\Country;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserManagement\AddUserRequest;
use App\Http\Requests\UserManagement\SuspendUserRequest;
use App\Http\Requests\UserManagement\GetUserRequest;
use App\Http\Requests\UserManagement\UpdateAccountPasswordRequest;
use App\Http\Requests\UserManagement\UpdateContactInformationRequest;
use App\Http\Requests\UserManagement\UpdatePersonalInformationRequest;


class UserManagementController extends Controller
{
  public function getUsers(Request $request)
  {
    DB::beginTransaction();
    try {
      $query = User::query();
      $current_page = $request->input('current_page', 1);
      $perPage = $request->limit;

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        $query->where('username', 'like', "%$searchTerm%");
      }

      $query->whereNull('users.deleted_at')
        ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        ->select([
          'users.id',
          'users.username',
          'users.email',
          'user_profiles.phone_number',
          'users.status',
          'users.verify_email',
          'users.role_id'
        ])
        ->orderBy('users.id', 'desc');
      $total = $query->count();
      $offset = ($current_page - 1) * $perPage;
      $users = $query->skip($offset)->take($perPage)->get();
      $pagination = [
        'current_page' => $current_page,
        'per_page' => $perPage,
        'total' => $total,
        'last_page' => ceil($total / $perPage),
      ];

      $result = $users->map(function ($user, $key) {
        $user["user_id"] = $key + 1;
        return $user;
      });

      if ($users->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User data not found', 'toast' => true]);
      }

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users data retrieved successfully', 'toast' => false, 'data' => ['users' => $result, 'pagination' => $pagination],]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('public API error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function addUser(AddUserRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      //print_r($user);
      // die();
      $userFolder = "users/private/admin/{$user->id}/userManagement";
      Storage::makeDirectory($userFolder);

      $userManagement = new User();

      $userManagement->username = $request->username;
      $userManagement->email = $request->email;
      $userManagement->first_name = $request->first_name;
      $userManagement->last_name = $request->last_name;
      $userManagement->role_id = $request->role_id;

      if ($request->password_confirmation && $request->password_confirmation !== $request->password) {
        DB::rollBack();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Passwords do not match', 'toast' => true]);
      }
      $userManagement->password = Hash::make($request->password);
      $userManagement->save();


      $userProfile = new UserProfile();
      $userProfile->user_id = $userManagement->id;
      $userProfile->first_name = $userManagement->first_name;
      $userProfile->last_name = $userManagement->last_name;

      if ($request->country) {
        $country = Country::where('phonecode', $request->country)->first();
        if (!$country) {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Country not found', 'toast' => true]);
        }
        $userProfile->country = $request->country;
      }
      if ($request->hasFile('profile_image_path') && $request->file('profile_image_path')->isValid()) {
        $imageName = $request->profile_image_path->getClientOriginalName();
        $imagePath = $request->profile_image_path->storeAs($userFolder, $imageName);
        $userProfile->profile_image_path = $imagePath;
      }

      if ($request->phone_number) {
        $userProfile->phone_number = $request->phone_number;
      }
      $userProfile->save();
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User added successfully', 'toast' => true, 'data' => $userManagement]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Error while adding user ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function updateContactInformation(UpdateContactInformationRequest $request)
  {
    try {
      $users = $request->attributes->get('user');
      if (!in_array($users->role_id, [1, 2])) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You do not have access', 'toast' => true]);
      }

      $userId = $request->id;
      $user = User::where('id', $userId)->first();

      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);
      }

      $message = '';

      if ($request->has('email')) {
        $user->email = $request->email;
        $user->save();
        $message = 'Email updated successfully';
      }

      $userProfile = UserProfile::where('user_id', $userId)->first();
      if (!$userProfile) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User profile not found', 'toast' => true]);
      }

      if ($request->has('phone_number')) {
        $userProfile->phone_number = $request->phone_number;
        $userProfile->save();
        $message = 'Phone number updated successfully';
      }

      if ($request->has('verify_email')) {
        $current_verify_email = $user->verify_email;
        $new_verify_email = $current_verify_email === '0' ? '1' : '0';
        $user->verify_email = $new_verify_email;
        $user->save();
        $message = 'Verification status updated successfully';
      }

      if (empty($message)) {
        $message = 'No changes were made';
      }

      $responseData = [
        'email' => $user->email,
        'phone_number' => $userProfile->phone_number,
        'verify_email' => $user->verify_email,

      ];
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $message, 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while updating contact information: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function updatePersonalInformation(UpdatePersonalInformationRequest $request)
  {
    try {
      $users = $request->attributes->get('user');
      if (!in_array($users->role_id, [1, 2])) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You do not have access', 'toast' => true]);
      }

      $userId = $request->id;
      $user = User::where('id', $userId)->first();

      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);
      }

      if ($request->has('first_name')) {
        $user->first_name = $request->first_name;
      }
      if ($request->has('last_name')) {
        $user->last_name = $request->last_name;
      }

      $user->save();

      $userProfile = UserProfile::where('user_id', $userId)->first();
      if (!$userProfile) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User profile not found', 'toast' => true]);
      }

      if ($request->has('first_name')) {
        $userProfile->first_name = $request->first_name;
      }
      if ($request->has('last_name')) {
        $userProfile->last_name = $request->last_name;
      }
      if ($request->has('state')) {
        $userProfile->state = $request->state;
      }
      if ($request->has('city')) {
        $userProfile->city = $request->city;
      }
      if ($request->has('country')) {
        $userProfile->country = $request->country;
      }
      if ($request->has('about_me')) {
        $userProfile->about_me = $request->about_me;
      }
      if ($request->has('zip_code')) {
        $userProfile->zip_code = $request->zip_code;
      }
      $userProfile->save();

      $responseData = [
        'first_name' => $userProfile->first_name,
        'last_name' => $userProfile->last_name,
        'state' => $userProfile->state,
        'city' => $userProfile->city,
        'country' => $userProfile->country,
        'about_me' => $userProfile->about_me,
        'zip_code' => $userProfile->zip_code,
      ];

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Personal information updated successfully', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while updating contact information: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function updateAccountPassword(UpdateAccountPasswordRequest $request)
  {
    try {
      $users = $request->attributes->get('user');
      if (!in_array($users->role_id, [1, 2])) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You do not have access', 'toast' => true]);
      }

      $userId = $request->id;
      $user = User::where('id', $userId)->first();
      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);
      }

      if ($request->password_confirmation && $request->password_confirmation !== $request->password) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Passwords do not match', 'toast' => true]);
      }

      $user->password = Hash::make($request->password);
      $user->save();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Password updated successfully', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while updating account password: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function deleteUser(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      if (!in_array($user->role_id, [1, 2])) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You do not have access', 'toast' => true]);
      }

      $userId = $request->id;
      $userManagement = User::where('id', $userId)->first();
      if (!$userManagement) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);
      }

      // Update the deleted_at column instead of deleting the record
      $userManagement->deleted_at = now();
      $userManagement->save();

      UserProfile::where('user_id', $userId)->delete();

      //$userManagement->delete();

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User deleted successfully', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error while deleting user: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function userStatus(SuspendUserRequest $request)
  {
    try {
      $user_id = $request->id;
      $user = User::where('id', $user_id)->first();

      if ($request->status === '0') {
        $user->status = '0';
      } elseif ($request->status === '1') {
        $user->status = '1';
      } elseif ($request->status === '2') {
        $user->status = '2';
      } else {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Value must be 0 , 1 , 2', 'toast' => true]);
      }
      $user->save();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User suspension status changed', 'toast' => true, 'data' => $user]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Error while adding user ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getUser(GetUserRequest $request)
  {
    try {
      // $id = $request->id;
      $user = $request->attributes->get('user');
      $userId = $request->id;
      $user_data = User::where('id', $request->id)->first();
      $userProfile = UserProfile::where('user_id', $userId)->first();
      $user_data['phone_number'] = $userProfile->phone_number;
      $user_data['first_name1'] = $userProfile->first_name;
      $user_data['last_name1'] = $userProfile->last_name;
      $user_data['city1'] = $userProfile->city;
      $user_data['about_me'] = $userProfile->about_me;
      $user_data['zip_code1'] = $userProfile->zip_code;
      $user_data['state1'] = $userProfile->state;
      $user_data['country'] = $userProfile->country;


      if (!$user_data) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User with ID ' . $user->id . ' not found', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User retrieved successfully', 'toast' => true, 'data' => ['user' => $user_data->toArray()]]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
      ]);
    }
  }
  public function updateRole(Request $request)
  {
    try {
      $users = $request->attributes->get('user');
      if (!in_array($users->role_id, [1, 2])) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You do not have access', 'toast' => true]);
      }

      $username = $request->username;
      $user = User::where('username', $username)->first();

      if (!$user) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);
      }

      if ($request->has('role_id')) {
        $user->role_id = $request->role_id;
      }


      $user->save();

      $responseData = [
        'role_id' => $user->role_id,
      ];
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Role updated successfully', 'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while updating contact information: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
