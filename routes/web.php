<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/clear-cache', function () {
    try {
        $cc = Artisan::call('config:clear');
        echo "cleared config $cc<br/>";
        $ccc = Artisan::call('cache:clear');
        echo "cleared cache $ccc<br/>";
        $ccs = Artisan::call('config:cache');
        echo "config cache done $ccs<br/>";
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
});
Route::get('/cookie', function () {
    try {
        if (!isset($_COOKIE['authToken'])) {
            echo "invalid access";
        }
        $authToken = $_COOKIE['authToken'];
        echo $authToken;
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
});
Route::get('file-download/{path}', function ($path) {
    return Storage::disk('local')->download($path);
})->where('path', '.*');

Route::get('get-file-content/{path}', function ($path) {
    $path = urldecode($path);
    $fileContent = Storage::disk('local')->get($path);
    $fileMimeType = Storage::disk('local')->mimeType($path);

    return response($fileContent)->header('Content-Type', $fileMimeType);
})->where('path', '.*')->name('file.url');

