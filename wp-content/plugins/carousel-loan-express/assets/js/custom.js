$(document).ready(function() {
    $('.cloanexpress input[type="checkbox"]').iCheck({
        checkboxClass: 'icheckbox_square-green',
        labelHover: false,
        cursor: true
    });
    $('.close').click(function(){
        $(this).closest('.modal').hide();
    });
});
