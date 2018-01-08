$(document).ready(function() {
    $('.cloanexpress input[type="checkbox"]').iCheck({
        checkboxClass: 'icheckbox_square-green',
        labelHover: false,
        cursor: true
    });
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });
    //$('[type="tel"]').mask('(00) 0000 0000');
    $("#loan-offers-frm").validate();
    $.validator.addMethod("validphone", function(value, element) {
        var phone = value.replace(/\D/g, '');
        return /^(0(2|3|4|7|8))?\d{8}$/.test(phone) || /^1(3|8)00\d{6}$/.test(phone) || /^13\d{4}$/.test(phone);
    }, "Your phone is not valid");
    $('[type="tel"]').rules("add", {
        minlength: 14,
        validphone: true,
    });
});
