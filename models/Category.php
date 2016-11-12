<?php namespace Octoshop\Treecat\Models;

use Model;

class Category extends Model
{
    use \October\Rain\Database\Traits\NestedTree;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;
    use \Octoshop\Core\Util\UrlMaker;

    public $is_subcategory;

    public $title;

    public $urlComponentName = 'shopCategories';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'octoshop_categories';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'slug', 'description', 'is_enabled', 'is_visible'];

    /**
     * @var array Purgeable fields
     */
    protected $purgeable = ['title', 'is_subcategory'];

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'name' => ['required', 'between:4,255'],
        'slug' => [
            'required',
            'alpha_dash',
            'between:1,255',
            'unique:octoshop_categories',
        ],
    ];

    /**
     * @var Relations
     */
    public $belongsToMany = [
        'products' => ['Octoshop\Core\Models\Product',
            'table' => 'octoshop_product_categories',
            'order' => 'available_at desc',
        ],
    ];
    public $belongsTo = [
        'parent' => ['Octoshop\TreeCat\Models\Category', 'key' => 'parent_id'],
    ];

    /**
     * Image attachments
     *
     * @var array
     */
    public $attachOne = [
        'primary_image' => ['System\Models\File'],
        'secondary_image' => ['System\Models\File'],
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        +$this->setUrlPageName('browse.htm');
    }

    public function afterFetch()
    {
        // Set the dummy is_subcategory value for the backend switch
        // so it defaults to on when category has a parent_id set
        $this->is_subcategory = !!$this->parent_id;

        // Backwards compatibility for themes
        $this->title = $this->name;
    }

    /**
     * Ensure new categories get added as a parent when no subcategory is set
     */
    public function beforeSave()
    {
        // This always used to be `if (!$this->is_subcategory)` but one day
        // that broke when we added the Purgeable trait, so it got changed
        // to `if (!$this->getOriginalPurgeValue('is_subcategory'))` which
        // fixed it for a very long time.
        // 9 months later, and we're back to the basic `if (!$this->subcategory)`.
        //
        // Why did it stop working first time? Was it a bug in October? Why did the fix
        // stop fixing? All these are questions I'll probably never be able to  answer.
        if (!$this->is_subcategory) {
            $this->parent_id = null;
        }

        $this->storeNewParent();
    }

    public function parseSlug($slug)
    {
        if (strpos($slug, '.') !== false) {
            $parts = explode('.', $slug);
            $slug = array_pop($parts);
        }

        return $slug;
    }

    public function scopeFindBySlug($q, $slug)
    {
        $category = $q->whereSlug($this->parseSlug($slug));

        return $category ? $category->first() : null;
    }

    public function scopeEnabled($q)
    {
        return $q->whereIsEnabled(true);
    }

    public function scopeVisible($q)
    {
        return $q->whereIsVisible(true);
    }

    public function scopeEnabledAndVisible($q)
    {
        return $q->enabled()->visible();
    }
}
