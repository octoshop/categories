<?php namespace Octoshop\Categories;

use Backend;
use Event;
use Backend\Classes\FormTabs;
use Octoshop\Categories\Models\Category;
use Octoshop\Core\Components\Products as ProductList;
use Octoshop\Core\Controllers\Products;
use Octoshop\Core\Models\Product;
use Octoshop\Core\Models\ShopSetting;
use System\Classes\PluginBase;
use System\Controllers\Settings;

class Plugin extends PluginBase
{
    public $require = ['Octoshop.Core'];

    public function pluginDetails()
    {
        return [
            'name' => 'octoshop.categories::lang.plugin.name',
            'icon' => 'icon-shopping-cart',
            'author' => 'Dave Shoreman',
            'homepage' => 'http://octoshop.co/',
            'description' => 'octoshop.categories::lang.plugin.description',
        ];
    }

    public function registerPermissions()
    {
        return [
            'octoshop.core.access_categories' => [
                'tab' => 'octoshop.core::lang.plugin.name',
                'label' => 'octoshop.categories::lang.permissions.categories',
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
                        'tab' => 'octoshop.categories::lang.product.categories',
                        'type' => 'partial',
                        'path' => '$/octoshop/categories/controllers/products/_field_categories.htm',
                    ],
                ], FormTabs::SECTION_SECONDARY);
            }
        });

        Event::listen('backend.form.extendFields', function($form) {
            if ($form->getController() instanceof Settings && $form->model instanceof ShopSetting) {
                $form->addTabFields([
                    'inherit_child_count' => [
                        'label' => 'octoshop.categories::lang.settings.inheritChildCount',
                        'comment' => 'octoshop.categories::lang.settings.inheritChildCount_comment',
                        'type' => 'switch',
                        'tab' => 'octoshop.categories::lang.product.categories',
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
                    'label' => 'octoshop.categories::lang.product.categories',
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
                    'label'       => 'octoshop.categories::lang.plugin.menu',
                    'url'         => Backend::url('octoshop/categories/categories'),
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
                'Octoshop\Categories\Components\Categories' => 'shopCategories',
            ]);
        });

        ProductList::extend(function($component) {
            $component->addProperties([
                'category' => [
                    'title'       => 'octoshop.categories::lang.product.category',
                    'description' => 'octoshop.categories::lang.product.category_description',
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
            $controller->addDynamicProperty('relationConfig', '$/octoshop/categories/controllers/products/config_relation.yaml');
            $controller->implement[] = 'Backend.Behaviors.RelationController';

            $controller->addCss('/plugins/octoshop/categories/assets/css/modal-form.css');
        });
    }

    public function extendModels()
    {
        Product::extend(function($model) {
            $model->belongsToMany['categories'] = ['Octoshop\Categories\Models\Category',
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
