<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Technology;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ProjectCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProjectCrudController extends CrudController
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
        CRUD::setModel(Project::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/project');
        CRUD::setEntityNameStrings('project', 'projects');
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        // Basic columns
        CRUD::column('title')->type('text')->label('Title');
        
        CRUD::column('featured_image')
            ->type('image')
            ->label('Featured Image')
            ->height('50px')
            ->width('50px');
            
        CRUD::column('category')
            ->type('relationship')
            ->label('Category')
            ->attribute('name');
            
        CRUD::column('status')
            ->type('select_from_array')
            ->label('Status')
            ->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']);
            
        CRUD::column('is_featured')
            ->type('boolean')
            ->label('Featured')
            ->options([0 => 'No', 1 => 'Yes']);
            
        CRUD::column('project_date')
            ->type('date')
            ->label('Project Date');
            
        CRUD::column('created_at')
            ->type('datetime')
            ->label('Created');

        // Filters
        CRUD::filter('status')
            ->type('dropdown')
            ->values(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'status', $value);
            });

        CRUD::filter('is_featured')
            ->type('dropdown')
            ->values([1 => 'Featured', 0 => 'Not Featured'])
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'is_featured', $value);
            });

        CRUD::filter('category')
            ->type('select2')
            ->values(function () {
                return ProjectCategory::all()->pluck('name', 'id')->toArray();
            })
            ->whenActive(function ($value) {
                CRUD::addClause('where', 'project_category_id', $value);
            });
    }

    /**
     * Define what happens when the Create or Update operations are loaded.
     */
    protected function setupCreateOperation()
    {
        // Project basic information
        CRUD::field('title')
            ->type('text')
            ->label('Project Title')
            ->attributes(['required' => true]);

        CRUD::field('slug')
            ->type('text')
            ->label('Slug (URL)')
            ->hint('Leave empty to auto-generate from title');

        CRUD::field('excerpt')
            ->type('textarea')
            ->label('Excerpt')
            ->hint('Short description for listings');

        CRUD::field('description')
            ->type('ckeditor')
            ->label('Description')
            ->options([
                'autoGrow_minHeight' => 200,
                'autoGrow_maxHeight' => 400,
                'removePlugins' => 'elementspath',
                'resize_enabled' => true,
            ]);

        // Project URLs
        CRUD::field('project_url')
            ->type('url')
            ->label('Project URL')
            ->hint('Live website URL');

        CRUD::field('repository_url')
            ->type('url')
            ->label('Repository URL')
            ->hint('GitHub/GitLab repository');

        CRUD::field('demo_url')
            ->type('url')
            ->label('Demo URL')
            ->hint('Demo or preview URL');

        // Images
        CRUD::field('featured_image')
            ->type('upload')
            ->label('Featured Image')
            ->upload(true)
            ->disk('public')
            ->destination_path('projects/featured');

        CRUD::field('gallery_images')
            ->type('upload_multiple')
            ->label('Gallery Images')
            ->upload(true)
            ->disk('public')
            ->destination_path('projects/gallery');

        CRUD::field('video_url')
            ->type('url')
            ->label('Video URL')
            ->hint('YouTube or Vimeo video');

        // Relationships
        CRUD::field('project_category_id')
            ->type('select2')
            ->label('Category')
            ->entity('category')
            ->model(ProjectCategory::class)
            ->attribute('name')
            ->allows_null(true);

        CRUD::field('technologies')
            ->type('select2_multiple')
            ->label('Technologies')
            ->entity('technologies')
            ->model(Technology::class)
            ->attribute('name')
            ->pivot(true);

        // Project metadata
        CRUD::field('status')
            ->type('select_from_array')
            ->label('Status')
            ->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])
            ->default('draft');

        CRUD::field('is_featured')
            ->type('boolean')
            ->label('Featured Project')
            ->default(false);

        CRUD::field('sort_order')
            ->type('number')
            ->label('Sort Order')
            ->default(0)
            ->attributes(['step' => 1]);

        // Client and dates
        CRUD::field('client_name')
            ->type('text')
            ->label('Client Name');

        CRUD::field('project_date')
            ->type('date')
            ->label('Project Date');

        CRUD::field('completion_date')
            ->type('date')
            ->label('Completion Date');

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
    }

    /**
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::column('slug')->type('text')->label('Slug');
        CRUD::column('excerpt')->type('text')->label('Excerpt');
        CRUD::column('description')->type('html')->label('Description');
        CRUD::column('project_url')->type('url')->label('Project URL');
        CRUD::column('repository_url')->type('url')->label('Repository URL');
        CRUD::column('demo_url')->type('url')->label('Demo URL');
        CRUD::column('client_name')->type('text')->label('Client');
        CRUD::column('completion_date')->type('date')->label('Completed');
        
        CRUD::column('technologies')
            ->type('relationship')
            ->label('Technologies')
            ->attribute('name')
            ->wrapper(['class' => 'form-group col-md-12']);
    }
}
