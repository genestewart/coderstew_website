<?php

namespace App\Http\Controllers\Admin;

use App\Models\Technology;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class TechnologyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TechnologyCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(Technology::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/technology');
        CRUD::setEntityNameStrings('technology', 'technologies');
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->type('text')->label('Name');
        
        CRUD::column('icon')
            ->type('image')
            ->label('Icon')
            ->height('30px')
            ->width('30px');
            
        CRUD::column('type')
            ->type('select_from_array')
            ->label('Type')
            ->options(Technology::getTypes());
            
        CRUD::column('color')
            ->type('color')
            ->label('Color');
            
        CRUD::column('is_active')
            ->type('boolean')
            ->label('Active')
            ->options([0 => 'No', 1 => 'Yes']);
            
        CRUD::column('sort_order')
            ->type('number')
            ->label('Sort Order');

        // Filters
        CRUD::filter('type')
            ->type('dropdown')
            ->values(Technology::getTypes())
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'type', $value);
            });

        CRUD::filter('is_active')
            ->type('dropdown')
            ->values([1 => 'Active', 0 => 'Inactive'])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'is_active', $value);
            });
    }

    /**
     * Define what happens when the Create or Update operations are loaded.
     */
    protected function setupCreateOperation()
    {
        CRUD::field('name')
            ->type('text')
            ->label('Technology Name')
            ->attributes(['required' => true]);

        CRUD::field('slug')
            ->type('text')
            ->label('Slug (URL)')
            ->hint('Leave empty to auto-generate from name');

        CRUD::field('description')
            ->type('textarea')
            ->label('Description')
            ->hint('Brief description of the technology');

        CRUD::field('icon')
            ->type('upload')
            ->label('Icon/Logo')
            ->upload(true)
            ->disk('public')
            ->destination_path('technologies/icons');

        CRUD::field('color')
            ->type('color')
            ->label('Brand Color')
            ->hint('Technology brand color (hex code)');

        CRUD::field('website_url')
            ->type('url')
            ->label('Official Website')
            ->hint('Official technology website');

        CRUD::field('type')
            ->type('select_from_array')
            ->label('Technology Type')
            ->options(Technology::getTypes())
            ->default('other');

        CRUD::field('sort_order')
            ->type('number')
            ->label('Sort Order')
            ->default(0)
            ->attributes(['step' => 1])
            ->hint('Lower numbers appear first');

        CRUD::field('is_active')
            ->type('boolean')
            ->label('Active')
            ->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::column('slug')->type('text')->label('Slug');
        CRUD::column('description')->type('text')->label('Description');
        CRUD::column('website_url')->type('url')->label('Website');
        
        CRUD::column('projects_count')
            ->type('relationship_count')
            ->label('Projects Using This')
            ->suffix(' projects');
    }
}
