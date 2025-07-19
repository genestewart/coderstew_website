<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlogCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class BlogCategoryCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(BlogCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/blogcategories');
        CRUD::setEntityNameStrings('blog category', 'blog categories');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('slug');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('name');
        CRUD::field('slug');
    }
}
