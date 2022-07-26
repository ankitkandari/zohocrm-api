<?php

use App\Http\Controllers\Api\V1\LeadsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(LeadsController::class)->prefix('v1')->group(function () {
    Route::get('/leads', 'getRecords');
    Route::post('/leads', 'insertRecords');
    Route::put('/leads', 'updateRecords');
    Route::get('/leads/{lead_id}/related_records', 'getRelatedRecords');
});