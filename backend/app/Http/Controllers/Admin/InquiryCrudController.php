<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inquiry;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class InquiryCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(Inquiry::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/inquiries');
        CRUD::setEntityNameStrings('inquiry', 'inquiries');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('email');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field('message')->type('textarea');
    }
}
