<?php namespace Octoshop\Treecat\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

class Categories extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ReorderController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $bodyClass = 'compact-container';

    protected $assetsPath = '/plugins/octoshop/treecat/assets';

    public function __construct()
    {
        parent::__construct();

        $this->addCss($this->assetsPath.'/css/modal-form.css');
        $this->addJs($this->assetsPath.'/js/category-form.js');

        BackendMenu::setContext('Octoshop.Core', 'octoshop', 'categories');
    }
}