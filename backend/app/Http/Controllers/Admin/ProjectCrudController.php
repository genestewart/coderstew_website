<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProjectCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(Project::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/projects');
        CRUD::setEntityNameStrings('project', 'projects');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('title');
        CRUD::column('slug');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('title');
        CRUD::field('slug');
        CRUD::field('excerpt');
        CRUD::field('description')->type('textarea');
    }
}
