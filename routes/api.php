<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController; // IMPORTANT: Must be imported

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned the 
| "api" middleware group.
|
*/
 
// GET /api/categories -> Retrieves all categories (for initial load)
// POST /api/categories -> Creates a new category (for form submission)
Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']); 


// Optional: Keep the default User route if you need it later
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});