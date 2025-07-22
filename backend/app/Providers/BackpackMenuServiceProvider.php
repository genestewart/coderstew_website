<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BackpackMenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add menu items to Backpack sidebar
        $this->app['view']->composer('backpack::inc.sidebar_content', function ($view) {
            \Menu::make('BackpackMenu', function ($menu) {
                
                // Dashboard
                $menu->add('Dashboard', route('backpack.dashboard'))
                    ->prepend('<i class="nav-icon la la-home"></i> ');

                // Portfolio Management Section
                $menu->add('Portfolio', '#')
                    ->prepend('<i class="nav-icon la la-briefcase"></i> ')
                    ->data('toggle', 'dropdown')
                    ->append('<b class="caret"></b>');

                $menu->portfolio->add('Projects', route('project.index'))
                    ->prepend('<i class="nav-icon la la-list"></i> ');

                $menu->portfolio->add('Categories', route('project-category.index'))
                    ->prepend('<i class="nav-icon la la-tags"></i> ');

                $menu->portfolio->add('Technologies', route('technology.index'))
                    ->prepend('<i class="nav-icon la la-code"></i> ');

                // Content Management Section
                $menu->add('Content', '#')
                    ->prepend('<i class="nav-icon la la-edit"></i> ')
                    ->data('toggle', 'dropdown')
                    ->append('<b class="caret"></b>');

                $menu->content->add('Blog Posts', route('posts.index'))
                    ->prepend('<i class="nav-icon la la-newspaper"></i> ');

                $menu->content->add('Blog Categories', route('blog-categories.index'))
                    ->prepend('<i class="nav-icon la la-list"></i> ');

                $menu->content->add('Tags', route('tags.index'))
                    ->prepend('<i class="nav-icon la la-tag"></i> ');

                // Lead Management Section
                $menu->add('Leads', '#')
                    ->prepend('<i class="nav-icon la la-users"></i> ')
                    ->data('toggle', 'dropdown')
                    ->append('<b class="caret"></b>');

                $menu->leads->add('Inquiries', route('inquiries.index'))
                    ->prepend('<i class="nav-icon la la-envelope"></i> ');

                $menu->leads->add('Newsletter Subscribers', route('newsletter-subscribers.index'))
                    ->prepend('<i class="nav-icon la la-at"></i> ');

                // Tools Section
                $menu->add('Tools', '#')
                    ->prepend('<i class="nav-icon la la-tools"></i> ')
                    ->data('toggle', 'dropdown')
                    ->append('<b class="caret"></b>');

                if (backpack_user()->can('browse_logs')) {
                    $menu->tools->add('Logs', route('log-viewer::dashboard'))
                        ->prepend('<i class="nav-icon la la-terminal"></i> ');
                }

                $menu->tools->add('File Manager', route('elfinder.tinymce5'))
                    ->prepend('<i class="nav-icon la la-folder"></i> ');

                // Settings Section
                $menu->add('Settings', '#')
                    ->prepend('<i class="nav-icon la la-cog"></i> ')
                    ->data('toggle', 'dropdown')
                    ->append('<b class="caret"></b>');

                if (backpack_user()->can('manage_users')) {
                    $menu->settings->add('Users', route('user.index'))
                        ->prepend('<i class="nav-icon la la-user"></i> ');
                }

                $menu->settings->add('Backup', route('backup.index'))
                    ->prepend('<i class="nav-icon la la-save"></i> ');

            })->filter(function ($item) {
                // Filter menu items based on user permissions
                return true;
            });
        });
    }
}