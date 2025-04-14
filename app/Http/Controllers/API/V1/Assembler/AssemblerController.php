<?php

namespace App\Http\Controllers\API\V1\Assembler;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assembler\AssemblerFileUploadRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssemblerController extends Controller
{
    public function uploadUserAssemblerFile(AssemblerFileUploadRequest $request)
    {
        try {
            $userId = $request->user_id;
            $userAssemblerFolder = "users/private/{$userId}/Assembler";
            $assemblerFile = $request->file;

            if (!Storage::disk('local')->exists($userAssemblerFolder)) {
                Storage::disk('local')->makeDirectory($userAssemblerFolder);
            }
            $assemblerFileName = $assemblerFile->getClientOriginalName();
            $assemblerFile->storeAs($userAssemblerFolder, $assemblerFileName);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'File Uploaded successfully.', 'toast' => true]);

        } catch (\Exception $e) {
            Log::error('Error on processing: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error on uploading the file', 'toast' => true]);
        }
    }
}
