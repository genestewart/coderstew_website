<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProjectCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProjectCategoryCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(ProjectCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/projectcategories');
        CRUD::setEntityNameStrings('project category', 'project categories');
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
