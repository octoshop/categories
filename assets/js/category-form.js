(function ($) { "use strict";

    var CategoryForm = function() {
        this.initLayout();
    }

    CategoryForm.prototype.initLayout = function() {
        $('#Form-secondaryTabs .tab-pane.layout-cell')
            .addClass('padded-pane');
    }

    $(document).ready(function() {
        var form = new CategoryForm();

        if ($.oc === undefined) {
            $.oc = {}
        }

        $.oc.shopCategoryForm = form;
    });

})(window.jQuery);
