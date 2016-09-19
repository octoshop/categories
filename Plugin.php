<?php namespace Octoshop\TreeCat;

use Backend;
use Event;
use Backend\Classes\FormTabs;
use Octoshop\Core\Components\Products as ProductList;
use Octoshop\Core\Controllers\Products;
use Octoshop\Core\Models\Product;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $require = ['Octoshop.Core'];

    public function pluginDetails()
    {
        return [
            'name' => 'octoshop.treecat::lang.plugin.name',
            'icon' => 'icon-shopping-cart',
            'author' => 'Dave Shoreman',
            'homepage' => 'http://octoshop.co/',
            'description' => 'octoshop.treecat::lang.plugin.description',
        ];
    }

    public function boot()
    {
        $this->extendBackendForm();
        $this->extendBackendList();
        $this->extendBackendMenu();

        $this->extendComponents();
        $this->extendControllers();
        $this->extendModels();
    }

    public function extendBackendForm()
    {
        Event::listen('backend.form.extendFields', function($widget) {
            if (!($widget->getController() instanceof Products)
             || !($widget->model instanceof Product)) {
                return;
            }

            $widget->addFields([
                'categories' => [
                    'tab' => 'Categories',
                    'type' => 'partial',
                    'path' => '$/octoshop/treecat/controllers/products/_field_categories.htm',
                ],
            ], FormTabs::SECTION_SECONDARY);
        });
    }

    public function extendBackendList()
    {
        Event::listen('backend.list.extendColumns', function($widget) {
            if (!($widget->getController() instanceof Products)
             || !($widget->model instanceof Product)) {
                return;
            }

            $widget->addColumns([
                'category' => [
                    'label' => 'Categories',
                    'relation' => 'categories',
                    'select' => 'name',
                    'searchable' => true,
                    'sortable' => false,
                    'invisible' => true,
                ],
            ]);
        });
    }

    public function extendBackendMenu()
    {
        Event::listen('backend.menu.extendItems', function($manager) {
            $manager->addSideMenuItems('Octoshop.Core', 'octoshop', [
                'categories' => [
                    'label'       => 'Categories',
                    'url'         => Backend::url('octoshop/treecat/categories'),
                    'icon'        => 'icon-folder-o',
                    'order'       => 100,
                ],
            ]);
        });
    }

    public function extendComponents()
    {
        Event::listen('octoshop.core.extendComponents', function($plugin) {
            $plugin->addComponents([
                'Octoshop\Treecat\Components\Categories' => 'shopCategories',
            ]);
        });

        ProductList::extend(function($component) {
            $component->addProperties([
                'category' => [
                    'title'       => 'Category',
                    'description' => 'Category to filter the products by. Leave blank to show all products.',
                    'type'        => 'string',
                    'default'     => '{{ :slug }}'
                ],
            ]);

            $component->registerFilter('category', 'inCategory');
        });
    }

    public function extendControllers()
    {
        Products::extend(function($controller) {
            $controller->addDynamicProperty('relationConfig', '$/octoshop/treecat/controllers/products/config_relation.yaml');
            $controller->implement[] = 'Backend.Behaviors.RelationController';

            $controller->addCss('/plugins/octoshop/treecat/assets/css/modal-form.css');
        });
    }

    public function extendModels()
    {
        Product::extend(function($model) {
            $model->belongsToMany['categories'] = ['Octoshop\Treecat\Models\Category',
                'table' => 'octoshop_categories_products',
                'order' => 'name',
            ];

            $model->addDynamicMethod('scopeInCategory', function($query, $category) use ($model) {
                return $query->whereHas('categories', function($q) use ($category) {
                    $q->whereSlug($category);
                });
            });
        });
    }
}
