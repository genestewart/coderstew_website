<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class TagCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(Tag::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/tags');
        CRUD::setEntityNameStrings('tag', 'tags');
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
