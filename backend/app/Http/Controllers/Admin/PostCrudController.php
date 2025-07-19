<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PostCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(Post::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/posts');
        CRUD::setEntityNameStrings('post', 'posts');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('title');
        CRUD::column('slug');
        CRUD::column('published_at');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('blog_category_id');
        CRUD::field('title');
        CRUD::field('slug');
        CRUD::field('excerpt');
        CRUD::field('body')->type('textarea');
        CRUD::field('published_at')->type('datetime');
    }
}
