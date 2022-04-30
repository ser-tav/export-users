jQuery(document).ready(function ($) {
    $('.select_all_btn').click(function(){
        $('.settings_body input:checkbox').prop('checked', true);
    });
    $('.clear_all_btn').click(function(){
        $('.settings_body input:checkbox').prop('checked', false);
    });
});