<?php namespace Octoshop\Categories\Components;

use Lang;
use Cms\Classes\Page;
use Octoshop\Categories\Models\Category;
use Octoshop\Core\Components\ComponentBase;
use Octoshop\Core\Models\Product;
use Octoshop\Core\Models\ShopSetting;

class Categories extends ComponentBase
{
    public $categoryPage;

    public $categories;

    protected $inheritChildCount;

    public function componentDetails()
    {
        return [
            'name'        => Lang::get('octoshop.categories::lang.component.name'),
            'description' => Lang::get('octoshop.categories::lang.component.description'),
        ];
    }

    public function defineProperties()
    {
        return [
            'categoryPage' => [
                'title'       => Lang::get('octoshop.categories::lang.component.categoryPage'),
                'description' => Lang::get('octoshop.categories::lang.component.categoryPage_description'),
                'type'        => 'dropdown',
                'default'     => 'shop/category',
                'group'       => 'Links',
            ],
            'isPrimary' => [
                'title' => Lang::get('octoshop.categories::lang.component.isPrimary'),
                'type'  => 'checkbox',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    public function prepareVars()
    {
        $this->inheritChildCount = ShopSetting::get('inherit_child_count', false);

        $this->setPageProp('categoryPage');
        $this->setPageProp('categories', $this->listCategories());
    }

    public function listCategories()
    {
        $categories = Category::enabledAndVisible();

        if (!$this->inheritChildCount) {
            $categories->with(['products' => function ($query) {
                $query->enabled();
            }]);
        }

        return $this->fillExtraData(
            $categories->getNested()
        );
    }

    public function fillExtraData($categories, $rootCategory = true)
    {
        return $categories->each(function ($c) use ($rootCategory) {
            $c->setUrlPageName($this->categoryPage);

            if (!$this->inheritChildCount) {
                $c->productCount = count($c->products);
            } else {
                $id = $c->id;
                $l = $c->nest_left;
                $r = $c->nest_right;

                $c->productCount = Product::enabled()->where(function($query) use ($id, $l, $r) {
                    $query->whereHas(
                        'categories',
                        function ($query) use ($l, $r) {
                            $query->where('nest_left', '>', $l);
                            $query->where('nest_right', '<', $r);
                        }
                    );
                    $query->orWhereHas('categories', function($q) use ($id) {
                        $q->whereIn('id', [$id]);
                    });
                })->count();
            }

            if ($c->children) {
                $c->children = $this->fillExtraData($c->children, false);
            }
        });
    }
}
