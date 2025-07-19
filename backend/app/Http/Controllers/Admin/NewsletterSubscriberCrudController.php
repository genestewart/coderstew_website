<?php

namespace App\Http\Controllers\Admin;

use App\Models\NewsletterSubscriber;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class NewsletterSubscriberCrudController extends CrudController
{
    public function setup(): void
    {
        CRUD::setModel(NewsletterSubscriber::class);
        CRUD::setRoute(config('backpack.base.route_prefix', 'admin').'/newsletter-subscribers');
        CRUD::setEntityNameStrings('subscriber', 'newsletter subscribers');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('email');
        CRUD::column('created_at');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('email');
    }
}
