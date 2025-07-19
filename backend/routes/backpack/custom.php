<?php
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => config('backpack.base.middleware', ['web', 'auth']),
], function () {
    Route::crud('projects', \App\Http\Controllers\Admin\ProjectCrudController::class);
    Route::crud('project-categories', \App\Http\Controllers\Admin\ProjectCategoryCrudController::class);
    Route::crud('technologies', \App\Http\Controllers\Admin\TechnologyCrudController::class);
    Route::crud('blog-categories', \App\Http\Controllers\Admin\BlogCategoryCrudController::class);
    Route::crud('posts', \App\Http\Controllers\Admin\PostCrudController::class);
    Route::crud('tags', \App\Http\Controllers\Admin\TagCrudController::class);
    Route::crud('inquiries', \App\Http\Controllers\Admin\InquiryCrudController::class);
    Route::crud('newsletter-subscribers', \App\Http\Controllers\Admin\NewsletterSubscriberCrudController::class);
});
