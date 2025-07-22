<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProjectCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ProjectCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProjectCategoryCrudController extends CrudController
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
        CRUD::setModel(ProjectCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/project-category');
        CRUD::setEntityNameStrings('project category', 'project categories');
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
            
        CRUD::column('parent')
            ->type('relationship')
            ->label('Parent Category')
            ->attribute('name');
            
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
            
        CRUD::column('projects_count')
            ->type('relationship_count')
            ->label('Projects')
            ->suffix(' projects');

        // Filters
        CRUD::filter('parent_id')
            ->type('select2')
            ->values(function () {
                return ProjectCategory::whereNull('parent_id')->pluck('name', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                if ($value) {
                    CRUD::addClause('where', 'parent_id', $value);
                } else {
                    CRUD::addClause('whereNull', 'parent_id');
                }
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
            ->label('Category Name')
            ->attributes(['required' => true]);

        CRUD::field('slug')
            ->type('text')
            ->label('Slug (URL)')
            ->hint('Leave empty to auto-generate from name');

        CRUD::field('description')
            ->type('textarea')
            ->label('Description')
            ->hint('Brief description of the category');

        CRUD::field('icon')
            ->type('upload')
            ->label('Icon')
            ->upload(true)
            ->disk('public')
            ->destination_path('categories/icons');

        CRUD::field('color')
            ->type('color')
            ->label('Category Color')
            ->hint('Category color (hex code)');

        CRUD::field('parent_id')
            ->type('select2')
            ->label('Parent Category')
            ->entity('parent')
            ->model(ProjectCategory::class)
            ->attribute('name')
            ->allows_null(true)
            ->hint('Select a parent category to create hierarchy');

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

        // SEO fields
        CRUD::field('meta_title')
            ->type('text')
            ->label('Meta Title')
            ->hint('SEO title tag');

        CRUD::field('meta_description')
            ->type('textarea')
            ->label('Meta Description')
            ->hint('SEO meta description')
            ->attributes(['maxlength' => 160]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        
        // Prevent self-referencing parent categories
        CRUD::field('parent_id')
            ->type('select2')
            ->label('Parent Category')
            ->entity('parent')
            ->model(ProjectCategory::class)
            ->attribute('name')
            ->allows_null(true)
            ->options(function ($query) {
                if (request()->route('id')) {
                    return $query->where('id', '!=', request()->route('id'))->get();
                }
                return $query->get();
            });
    }

    /**
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::column('slug')->type('text')->label('Slug');
        CRUD::column('description')->type('text')->label('Description');
        CRUD::column('full_path')->type('text')->label('Full Path');
        CRUD::column('meta_title')->type('text')->label('Meta Title');
        CRUD::column('meta_description')->type('text')->label('Meta Description');
        
        CRUD::column('children')
            ->type('relationship')
            ->label('Subcategories')
            ->attribute('name');
    }
}
