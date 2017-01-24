<?php

return [
    'plugin' => [
        'name' => 'Octoshop Categories',
        'menu' => 'Categories',
        'description' => 'Support for deep nesting of categories, perfect if you have a lot of them!',
    ],
    'permissions' => [
        'categories' => 'Manage shop categories',
    ],
    'categories' => [
        'title_list' => 'Manage Categories',
        'title_create' => 'Create Category',
        'title_update' => 'Edit Category',
        'title_sort' => 'Sort Categories',
        'defaultTab' => 'Manage',
        'id' => 'ID',
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'isEnabled' => 'Enable category',
        'isVisible' => 'Show in menus',
        'isSubcategory' => 'Make it a subcategory?',
        'parent' => 'Parent Category',
        'primaryImage' => 'Primary image',
        'secondaryImage' => 'Secondary image',
        'confirmDelete' => 'Do you really want to delete this category?',
        'deleting' => 'Deleting category...',
        'saving' => 'Saving category...',
        'create_button' => 'New Category',
        'sort_button' => 'Rearrange Categories',
        'return_link' => 'Return to categories list',
    ],
    'component' => [
        'name' => 'Category List',
        'description' => 'Displays a list of shop categories on the page.',
        'categoryPage' => 'Category page',
        'categoryPage_description' => 'The name of the page to use when generating category links.',
        'isPrimary' => 'Use for URLs?',
        ''
    ]
    'product' => [
        'categories' => 'Categories',
        'category' => 'Category',
        'category_description' => 'Category to filter the products by. Leave blank to show all products.',
    ],
    'settings' => [
        'inheritChildCount' => 'Inherit product count from subcategories',
        'inheritChildCount_comment' => 'Products from subcategories will count toward the number of products in each parent category. Products are only counted once for each category.',
    ],
];
