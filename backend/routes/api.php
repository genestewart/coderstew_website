<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\AuthController;

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project:slug}', [ProjectController::class, 'show']);
Route::get('/projects-filters', [ProjectController::class, 'filters']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post:slug}', [PostController::class, 'show']);

Route::post('/inquiries', [InquiryController::class, 'store'])
    ->middleware('contact.throttle');
Route::get('/inquiries/stats', [InquiryController::class, 'getStats'])
    ->middleware('auth:sanctum');
Route::post('/subscribe', [NewsletterController::class, 'store']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
