<?php

use App\Http\Controllers\API\V1\Coin\InvestmentController;
use App\Http\Controllers\API\V1\Coin\ReportController;
use App\Http\Controllers\API\V1\Wallet\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\API\V1\UserManagement\UserManagementController;
use App\Http\Controllers\API\V1\Coin\CoinController;
use App\Http\Controllers\API\V1\Coin\NewsController;
use App\Http\Controllers\API\V1\Coin\KYController;
use App\Http\Controllers\API\V1\Silosecuredata\SilosecureController;

Route::group(['middleware' => ['admin']], function () {
  Route::group(["prefix" => "admin"], function () {
    Route::post("/service", [AdminSubscriptionController::class, "getService"]);
    Route::post("/addservice", [AdminSubscriptionController::class, "addService"]);
    Route::post("/deleteservice", [AdminSubscriptionController::class, "deleteService"]);
    Route::post("/updateservice", [AdminSubscriptionController::class, "updateService"]);
    Route::post("/inactiveservice", [AdminSubscriptionController::class, "inactiveService"]);
    Route::post("/addpackage", [AdminSubscriptionController::class, "addPackage"]);
    Route::post("/package", [AdminSubscriptionController::class, "getPackage"]);
    Route::post("/deletepackage", [AdminSubscriptionController::class, "deletePackage"]);
    Route::post("/updatepackage", [AdminSubscriptionController::class, "updatePackage"]);

    Route::group(['prefix' => 'user-management'], function () {
      Route::post('/get-user', [UserManagementController::class, "getUsers"]);
      Route::post('/add-user', [UserManagementController::class, "addUser"]);
      Route::post("/status-user", [UserManagementController::class, "userStatus"]);
      Route::post("/update-contact-Information", [UserManagementController::class, "updateContactInformation"]);
      Route::post("/update-personal-Information", [UserManagementController::class, "updatePersonalInformation"]);
      Route::post("/update-Account-Password", [UserManagementController::class, "updateAccountPassword"]);
      Route::post('/delete-user', [UserManagementController::class, "deleteUser"]);
      Route::post('/get-specific-user', [UserManagementController::class, "getUser"]);
      Route::post('/update-role', [UserManagementController::class, "updateRole"]);
    });
  });

  Route::group(['prefix' => 'coin'], function () {
    Route::post('/get-admin-coin', [CoinController::class, "getAdminCoin"]);
    Route::post('/register-coin', [CoinController::class, "registerCoin"]);
    Route::post('/update-coin', [CoinController::class, "updateCoin"]);
    Route::post('/delete-coin', [CoinController::class, "deleteCoin"]);
    Route::post('/review-approve', [CoinController::class, "reviewApproval"]);
    Route::get('/delete-review', [CoinController::class, "deleteReview"]);
    Route::post('/get-admin-reviews', [CoinController::class, "getadminreviews"]);
    Route::post('/add-news', [NewsController::class, "addNews"]);
    Route::post('/update-news', [NewsController::class, "updateNews"]);
    Route::post('/delete-news', [NewsController::class, "deleteNews"]);
    Route::post('/get-coin-investment', [InvestmentController::class, "coinInvestor"]);
    Route::post('/get-admin-news', [NewsController::class, "getNews"]);
  });
  Route::group(['prefix' => 'kyc'], function () {
    Route::post('/kyc-approve-reject', [KYController::class, "approveRejectKYC"]);
    Route::post('/get-kyc-data', [KYController::class, "getKYCDetails"]);
  });

  Route::group(['prefix' => 'reports'], function () {
    Route::post('/add-reports', [ReportController::class, "addReports"]);
    Route::post('/update-reports', [ReportController::class, "updateReport"]);
    Route::post('/delete-reports', [ReportController::class, "deleteReport"]);
    Route::post('/get-admin-reports', [ReportController::class, "getAdminReports"]);
  });

  Route::group(['prefix' => 'silosecure'], function () {
    Route::post('/get-consultation', [SilosecureController::class, "getConsultation"]);
    Route::post('/get-contact-request', [SilosecureController::class, "getContactUs"]);
    Route::post('/delete-consultation-request', [SilosecureController::class, "deleteConsultation"]);
    Route::post('/delete-contact-request', [SilosecureController::class, "deleteContact"]);
  });
  Route::group(['prefix' => 'wallet'], function () {
    Route::post('/cash-request-list', [TransactionController::class, "cashRequestList"]);
    Route::post('/approve-cash-request', [TransactionController::class, "approveCashRequest"]);
  });
});
