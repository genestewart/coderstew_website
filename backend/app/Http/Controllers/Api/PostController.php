<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        return PostResource::collection(Post::whereNotNull('published_at')->get());
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }
}
