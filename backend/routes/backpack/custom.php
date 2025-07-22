<?php
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => config('backpack.base.middleware', ['web', 'auth']),
], function () {
    // Portfolio Management
    Route::crud('project', \App\Http\Controllers\Admin\ProjectCrudController::class);
    Route::crud('project-category', \App\Http\Controllers\Admin\ProjectCategoryCrudController::class);
    Route::crud('technology', \App\Http\Controllers\Admin\TechnologyCrudController::class);
    
    // Blog Management
    Route::crud('blog-categories', \App\Http\Controllers\Admin\BlogCategoryCrudController::class);
    Route::crud('posts', \App\Http\Controllers\Admin\PostCrudController::class);
    Route::crud('tags', \App\Http\Controllers\Admin\TagCrudController::class);
    
    // Lead Management
    Route::crud('inquiries', \App\Http\Controllers\Admin\InquiryCrudController::class);
    Route::crud('newsletter-subscribers', \App\Http\Controllers\Admin\NewsletterSubscriberCrudController::class);
});
