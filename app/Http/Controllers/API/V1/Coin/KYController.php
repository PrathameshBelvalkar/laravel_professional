<?php

namespace App\Http\Controllers\API\V1\Coin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Coin\AddKYCRequest;
use App\Http\Requests\Coin\ResubmitKYCRequest;
use App\Models\coin\CoinCalendarYear;
use App\Models\coin\KYCModel;
use App\Models\User;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;


class KYController extends Controller
{
    public function submitKYC(AddKYCRequest $request)
      {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            if($user->id){
                
                $user_kyc = new KYCModel();
                $user_kyc->user_id = $user->id;
                $user_kyc->first_name = $request->first_name;
                $user_kyc->last_name = $request->last_name;
                $user_kyc->email = $request->email;
                $user_kyc->phone_no = $request->phone_no;
                $user_kyc->address_1 = $request->address_1;
                $user_kyc->address_2 = $request->address_2;
                $user_kyc->country = $request->country;
                $user_kyc->state = $request->state;
                $user_kyc->city = $request->city;
                $user_kyc->zip_code = $request->zip_code;
                $user_kyc->dob = $request->dob;
                if ($request->hasFile('doc_front')) {
                    $file = $request->file('doc_front');
                    $fileName = $file->getClientOriginalName();
                    $doc_front_Path = "users/private/{$user->id}/kyc_doc/{$fileName}";
                    Storage::put($doc_front_Path, file_get_contents($file));
                    $user_kyc->doc_front = $doc_front_Path;
                }
                if ($request->hasFile('doc_back')) {
                    $file = $request->file('doc_back');
                    $fileName = $file->getClientOriginalName();
                    $doc_back_Path = "users/private/{$user->id}/kyc_doc/{$fileName}";
                    Storage::put($doc_back_Path, file_get_contents($file));
                    $user_kyc->doc_back = $doc_back_Path;
                }
                
                $user_kyc->save();
                $admin_id = getadmindetails();
                addNotification($user->id, $admin_id, "Your KYC has been submitted successfully. We will notify you once the verification is complete.", null, null, '13',null);
                addNotification($admin_id, $admin_id, "A new KYC submission has been received and requires your review.",null, null, '14', '/admin-manage-coinexchange/user-kyc-data','1');
                if($user_kyc){
               
                    if($request->phone_no && $request->country){
                           $phoneCode = getPhoneCodeByCountryId($request->country);
                           $phonenumber = "+" . $phoneCode . $request->phone_no;
                           $commessage = 'Your KYC has been submitted successfully. We will notify you once the verification is complete.
                           ';
                            $sendsms =  send_sms($phonenumber,$commessage);
                    }
                    
                    $emailData['subject'] = "Successful Submission of Your KYC";
                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                    $emailData['title'] = "Successful Submission of Your KYC";
                    $emailData['view'] = 'mail-templates.submit-kyc';
                    $emailData['username'] = $user->username;
                    $emailData['projectName'] = config('app.app_name');
                    $emailData['supportMail'] = config('app.support_mail');
                    Mail::to($request->email)->send(new SendMail($emailData, $emailData['view']));
                }
            }
                DB::commit();
                $user_kyc = KYCModel::where('id', $user_kyc->id)->first();
                $user_kyc_data = [
                    'first_name' => $user_kyc->first_name,
                    'last_name' => $user_kyc->last_name,
                    'email' => $user_kyc->email,
                    'phone_no' => $user_kyc->phone_no,
                    'address_1' => $user_kyc->address_1,
                    'address_2' => $user_kyc->address_2,
                    'country' => $user_kyc->country,
                    'state' => $user_kyc->state,
                    'city' => $user_kyc->city,
                    'zipcode' => $user_kyc->zipcode,
                    'dob' => $user_kyc->dob,                
                ];
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Your KYC has been submitted successfully. We will notify you once the verification is complete.', 'toast' => true], ['KYC_data' => $user_kyc_data]);
            
        } catch (\Exception $e) {
            Log::info('User KYC API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function getKYCDetails(Request $request)
    {
        try {
            $page = null;
            if ($request->filled('kyc_id')) {
                $kyc_id = $request->input('kyc_id');
                $kycArr = KYCModel::where('id', $kyc_id)->first();
                if (!empty($kycArr)) {
                    $kyc[] = $kycArr->toArray();
                } else {
                    $kyc = [];
                }
                $getTotalCount = count($kyc);
            } else {
                $userkycquery = DB::table('user_kyc')
                    ->join('users', 'user_kyc.user_id', '=', 'users.id')
                    ->join('user_profiles', 'user_kyc.user_id', '=', 'user_profiles.user_id')
                    ->select('user_kyc.*', 'users.username', 'user_profiles.profile_image_path')
                    ->orderBy('user_kyc.created_at', 'desc');
    
                $getTotalCount = $userkycquery->count();
    
                if ($request->filled('search_keyword')) {
                    $searchKeyword = $request->search_keyword;
                    $keywords = explode(' ', $searchKeyword);
                    $userkycquery->where(function ($query) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $query->where(function ($query) use ($keyword) {
                                $query->where("user_kyc.first_name", "like", "%{$keyword}%")
                                    ->orWhere("user_kyc.last_name", "like", "%{$keyword}%")
                                    ->orWhere("user_kyc.email", "like", "%{$keyword}%");
                            });
                        }
                    });
                }
                if ($request->filled('page')) {
                    $start = ($request->page - 1) * $request->limit;
                    $userkycquery->skip($start);
                    $page = $request->page;
                }
                if ($request->filled('limit')) {
                    $userkycquery->take($request->limit);
                }
                $userkycquery->orderBy("id", "desc");
                $kyc = $userkycquery->get()->map(function($item) {
                    return (array)$item;
                })->toArray();
            }
    
            $result = array();
            if (!empty($kyc)) {
                foreach ($kyc as $key => $value) {
                    $value["doc_front"] = getFileTemporaryURL($value["doc_front"]);
                    $value["doc_back"] = getFileTemporaryURL($value["doc_back"]);
                    $value["country_name"] = getcountrynamebyid($value["country"]);
                    $value["state_name"] = getstatenamebyid($value["state"]);
                    $value["status"] = getstatus($value["status"]);
                    $result[] = $value;
                }
            }
    
            if ($result) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User KYC retrieved successfully', 'toast' => false, 'data' => ["coin" => $result, "page" => $page, "count" => $getTotalCount]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'KYC data not found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('GetKYC API error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    

    public function checkKYC(Request $request)
    {
        $user = $request->attributes->get('user');
        $userId = $user->id;
        if($userId){
            $kycEntry = DB::table('user_kyc')->where('user_id', $userId)->first();
            $userDetails = DB::table('users')->where('id', $userId)->first();
            $userProfile = DB::table('user_profiles')->where('user_id', $userId)->first();
        }
        $value = [];
        if($kycEntry){
            if($kycEntry->status == '0'){
                $value["status"] = 'pending';
            }else if($kycEntry->status == '1'){
                $value["status"] = 'approved';
            }else{
                $value["reject_message"] = 'Your KYC submission has been rejected. Please resubmit your documents at your earliest convenience.';
                $value["status"] = 'rejected';
            }
        $value["kyc_submitted"] = true;
        }else{
            $value["kyc_submitted"] = false;
        }

        if ($userDetails) {
            $value["email"] = $userDetails->email;
        } else {
            $value["email"] = null;
        }
    
        if ($userProfile) {
            $value["phone"] = $userProfile->phone_number;
        } else {
            $value["phone"] = null;
        }

        if ($userDetails) {
            $value["first_name"] = $userDetails->first_name;
        } else {
            $value["first_name"] = null;
        }

        if ($userDetails) {
            $value["last_name"] = $userDetails->last_name;
        } else {
            $value["last_name"] = null;
        }

        $result[] = $value;
      
        if ($result) {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'data retrieved successfully', 'toast' => true, 'data' => ['kyc_data' => $result]]);
        }else{
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true,]);
    }
    }


    public function approveRejectKYC(Request $request)
    {
        $kyc_id = $request->input('kyc_id');
        $status = $request->input('status');
        $rejectionReason = $request->input('rejection_reason');
       if($status == ''){
        $status = '0';
       }
        $kyc = DB::table('user_kyc')->where('id', $kyc_id)->first();

        if (!$kyc) {
            return response()->json([
                'status' => 'error',
                'message' => 'KYC record not found.',
            ], 404);
        }
        $getkycdata = getTabledata('user_kyc','id',$kyc_id);
        if($getkycdata){
            $getuserdata = getTabledata('users','id',$getkycdata->user_id);
            $getuserprofile= getTabledata('user_profiles','user_id',$getkycdata->user_id);
        }
        $UserId = $getkycdata->user_id;
        $admin_id = getadmindetails();
        if($status == '1'){
            DB::table('user_kyc')->where('id', $kyc_id)->update([
                'status' => '1',
            ]);
          
            if($kyc_id){
                
                addNotification($UserId, $admin_id, "Your KYC submission has been approved. You can now access all features.", null, null, '13', null);
                if($getkycdata->phone_no && $getkycdata->country){
                       $phoneCode = getPhoneCodeByCountryId($getkycdata->country);
                       $phonenumber = "+" . $phoneCode .$getkycdata->phone_no;
                       $commessage = 'Dear '.$getuserdata->username.', your KYC verification is approved! Your account is now fully verified. Thank you for choosing us.';
                       $sendsms =  send_sms($phonenumber,$commessage);
                }
                
                $emailData['subject'] = "KYC Verification Approved";
                $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                $emailData['title'] = "KYC Verification Approved";
                $emailData['view'] = 'mail-templates.kyc_approval';
                $emailData['username'] = $getuserdata->username;
                $emailData['projectName'] = config('app.app_name');
                $emailData['supportMail'] = config('app.support_mail');
                Mail::to($getkycdata->email)->send(new SendMail($emailData, $emailData['view']));
            }
    
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'KYC approved successfully.', 'toast' => true]);
        }else if($status == '2'){
            DB::table('user_kyc')->where('id', $kyc_id)->update([
                'status' => '2',
            ]);
            if($kyc_id){
               if($getkycdata->phone_no && $getkycdata->country){
                    $phoneCode = getPhoneCodeByCountryId($getkycdata->country);
                    $phonenumber = "+" . $phoneCode .$getkycdata->phone_no;
                    $commessage = 'Dear '.$getuserdata->username.', your KYC verification was rejected. Please check your email for more details and re-submit the required documents. For assistance, contact support at '.config('app.support_mail').
                    '';
                    $sendsms =  send_sms($phonenumber,$commessage);
             }
             if($rejectionReason){ 
                $emailData['subject'] = "KYC Verification Rejected";
                $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                $emailData['title'] = "KYC Verification Rejected";
                $emailData['view'] = 'mail-templates.kyc_rejection';
                $emailData['username'] = $getuserdata->username;
                $emailData['projectName'] = config('app.app_name');
                $emailData['supportMail'] = config('app.support_mail');
                $emailData['rejectionReason'] = $rejectionReason;
             }
             
             Mail::to($getkycdata->email)->send(new SendMail($emailData, $emailData['view']));
             $admin_id = getadmindetails();   
             addNotification($UserId, $admin_id, "Your KYC submission has been rejected. Please review your details and resubmit.", null, null, '13', '/update-kyc');
        }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'KYC rejected successfully.', 'toast' => true]);
        }else{
    return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'status not found', 'toast' => true]);

        }
    }

    public function getuserkycdata(Request $request)
{
    $user = $request->attributes->get('user');
    $userid = $user->id;
  
    $result = array();

    if ($userid) {
        $userkycdata = DB::table('user_kyc')->where('user_id', $userid)->first();
    }

    if (!empty($userkycdata)) {
        $userkycdata = (array)$userkycdata; 

        $userkycdata["doc_front_link"] = getFileTemporaryURL($userkycdata["doc_front"]);

        if (!empty($userkycdata["doc_back"])) {
            $userkycdata["doc_back_link"] = getFileTemporaryURL($userkycdata["doc_back"]);
        }

        $result[] = $userkycdata;
    }
    
    if ($result) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'data retrieved successfully', 'toast' => true, 'data' => ['user_kyc_data' => $result]]);
    } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'data not found', 'toast' => true]);
    }
}



      public function reSubmitkyc(ResubmitKYCRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
                $Userid = $user->id;
                $user_kyc = KYCModel::where('user_id', $Userid)->first();
                if($user_kyc){
                if($user_kyc->status == '2'){
                       if ($request->filled('first_name')) {
                    $user_kyc->first_name = $request->first_name;
                }
                if ($request->filled('last_name')) {
                    $user_kyc->last_name = $request->last_name;
                }
                if ($request->filled('email')) {
                    $user_kyc->email = $request->email;
                }
                if ($request->filled('phone_no')) {
                    $user_kyc->phone_no = $request->phone_no;
                }
                if ($request->filled('address_1')) {
                    $user_kyc->address_1 = $request->address_1;
                }
                if ($request->filled('address_2')) {
                    $user_kyc->address_2 = $request->address_2;
                }
                if ($request->filled('country')) {
                    $user_kyc->country = $request->country;
                }
                if ($request->filled('state')) {
                    $user_kyc->state = $request->state;
                }
                if ($request->filled('city')) {
                    $user_kyc->city = $request->city;
                }
                if ($request->filled('zip_code')) {
                    $user_kyc->zip_code = $request->zip_code;
                }
                 if ($request->filled('dob')) {
                    $user_kyc->dob = $request->dob;
                }
                $user_kyc->status = '0';
                if ($request->hasFile('doc_front')) {
                    if ($user_kyc->doc_front) {
                        Storage::delete($user_kyc->doc_front);
                    }
                    $file = $request->file('doc_front');
                    $fileName = $file->getClientOriginalName();
                    $user_kyc_logo_Path = "users/private/{$user->id}/kyc_doc/{$fileName}";
                    Storage::put($user_kyc_logo_Path, file_get_contents($file));
                    $user_kyc->doc_front = $user_kyc_logo_Path;
                }

                
                if ($request->hasFile('doc_back')) {
                    if ($user_kyc->doc_back) {
                        Storage::delete($user_kyc->doc_back);
                    }
                    $file = $request->file('doc_back');
                    $fileName = $file->getClientOriginalName();
                    $user_kyc_logo_Path = "users/private/{$user->id}/kyc_doc/{$fileName}";
                    Storage::put($user_kyc_logo_Path, file_get_contents($file));
                    $user_kyc->doc_back = $user_kyc_logo_Path;
                }
    
                $user_kyc->save();
                $admin_id = getadmindetails();
                addNotification($user->id, $admin_id, "Your Re-KYC has been submitted successfully. We will notify you once the verification is complete.", null, null, '13', '/update-kyc');
                addNotification($admin_id, $admin_id, "A new Re-KYC submission has been received and requires your review.",null, null, '14', '/admin-manage-coinexchange/user-kyc-data','1');
                }else{
                     return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User is already approved', 'toast' => true]);
                }
            }

             if($user_kyc){
                    if($request->phone_no && $request->country){
                           $phoneCode = getPhoneCodeByCountryId($request->country);
                           $phonenumber = "+" . $phoneCode . $request->phone_no;
                           $commessage = 'You are successfully reapplied for kyc. We will notify you once the verification is complete.
                           ';
                            $sendsms =  send_sms($phonenumber,$commessage);
                    }
                    
                    $emailData['subject'] = "Confirmation: KYC Reapplication Received";
                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                    $emailData['title'] = "Confirmation: KYC Reapplication Received";
                    $emailData['view'] = 'mail-templates.reapply-kyc';
                    $emailData['username'] = $user->username;
                    $emailData['projectName'] = config('app.app_name');
                    $emailData['supportMail'] = config('app.support_mail');
                    Mail::to($request->email)->send(new SendMail($emailData, $emailData['view']));
                }
             
                DB::commit();
    
                $user_kyc_data = KYCModel::where('id', $user_kyc->id)->first();
                $user_kyc_data = [
                    'id' => $user_kyc_data->id,
                    'user_id' => $user_kyc_data->user_id,
                    'first_name' => $user_kyc_data->first_name ,
                    'last_name' => $user_kyc_data->last_name,
                    'email' => $user_kyc_data->email,
                    'phone_no' => $user_kyc_data->phone_no,
                    'address_1' => $user_kyc_data->address_1,
                    'address_2' => $user_kyc_data->address_2,
                    'country' => $user_kyc_data->country,
                    'state' => $user_kyc_data->state,
                    'city' => $user_kyc_data->city,
                    'zip_code' => $user_kyc_data->zip_code,
                    'dob' => $user_kyc_data->dob,
                ];
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Successfully reapplied for KYC', 'toast' => true], ['reapply_kyc_data' => $user_kyc_data]);
           
        } catch (\Exception $e) {
            Log::info('Reapply KYC API error : ' . $e->getMessage());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    

}
