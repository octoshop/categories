<?php namespace Octoshop\TreeCat;

use Backend;
use Event;
use Backend\Classes\FormTabs;
use Octoshop\Core\Components\Products as ProductList;
use Octoshop\Core\Controllers\Products;
use Octoshop\Core\Models\Product;
use Octoshop\Core\Models\ShopSetting;
use Octoshop\Treecat\Models\Category;
use System\Classes\PluginBase;
use System\Controllers\Settings;

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

    public function registerPermissions()
    {
        return [
            'octoshop.core.access_categories' => [
                'tab' => 'octoshop.core::lang.plugin.name',
                'label' => 'octoshop.treecat::lang.permissions.categories',
            ],
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
            if ($widget->getController() instanceof Products && $widget->model instanceof Product) {
                $widget->addFields([
                    'categories' => [
                        'tab' => 'Categories',
                        'type' => 'partial',
                        'path' => '$/octoshop/treecat/controllers/products/_field_categories.htm',
                    ],
                ], FormTabs::SECTION_SECONDARY);
            }
        });

        Event::listen('backend.form.extendFields', function($form) {
            if ($form->getController() instanceof Settings && $form->model instanceof ShopSetting) {
                $form->addTabFields([
                    'inherit_child_count' => [
                        'label' => 'Inherit product count from subcategories',
                        'comment' => 'Products from subcategories will count toward the number of products in each parent category. Products are only counted once for each category.',
                        'type' => 'switch',
                        'tab' => 'Categories',
                    ],
                ]);
            }
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
                    'permissions' => ['octoshop.core.access_categories'],
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

            $component->registerVar('category', function() use ($component) {
                if (!$slug = $component->property('category')) {
                    return null;
                }

                return Category::findBySlug($slug);
            });
        });
    }

    public function extendControllers()
    {
        Products::extend(function($controller) {
            // TODO: There's probably a better non-blocking way to add relations. Use it.
            $controller->addDynamicProperty('relationConfig', '$/octoshop/treecat/controllers/products/config_relation.yaml');
            $controller->implement[] = 'Backend.Behaviors.RelationController';

            $controller->addCss('/plugins/octoshop/treecat/assets/css/modal-form.css');
        });
    }

    public function extendModels()
    {
        Product::extend(function($model) {
            $model->belongsToMany['categories'] = ['Octoshop\Treecat\Models\Category',
                'table' => 'octoshop_product_categories',
                'order' => 'name',
            ];

            $model->addDynamicMethod('scopeInCategory', function($query, $category) use ($model) {
                if (!$category) {
                    return $query->whereDoesntHave('categories');
                }

                if ($category instanceof Category) {
                    $category = $category->id;
                }

                if (!is_numeric($category)) {
                    return $query->whereHas('categories', function($q) use ($category) {
                        $model = new Category;
                        $q->whereSlug($model->parseSlug($category));
                    });
                }

                return $query->inCategories([$category]);
            });

            $model->addDynamicMethod('scopeInCategories', function($query, array $categories, $column = 'id') use ($model) {
                return $query->whereHas('categories', function($q) use ($categories, $column) {
                    $q->whereIn($column, $categories);
                });
            });
        });
    }
}
