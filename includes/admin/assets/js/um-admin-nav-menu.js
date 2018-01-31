jQuery(document).ready(function ($) {

    var template = wp.template('um-nav-menus-fields');

    $(document).on('menu-item-added', function (e, $menuMarkup) {
       var id = $($menuMarkup).attr('id').substr(10);
        $('fieldset.field-move', $($menuMarkup)).before(template({menuItemID: id,restriction_data:{um_nav_public:0,um_nav_roles:[]}}));
    });

    $('ul#menu-to-edit > li').each(function () {
        var id = $(this).attr('id').substr(10);
        $('fieldset.field-move', $(this)).before(template({menuItemID: id,restriction_data:um_menu_restriction_data[id]}));
    });

});