<?php

namespace App\Http\Controllers\Admin;

use App\Models\Technology;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class TechnologyCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(Technology::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/technologies');
        CRUD::setEntityNameStrings('technology', 'technologies');
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
