function cartCount() {
    if($('#cartcount').length) {
        var response = '';
        $.ajax({
            type: 'GET',
            url: '/countcart',
            async: false,
            success: function (text) {
                response = text;
                if (response > 0) {
                    $('#cartcount').text(response);
                } else {
                    $('#cartcount').remove();
                }
            }
        });
    }
}

$(document).ready(function(){

    $(function () {
        $('#product_form').on('submit', function (e) {
            $('body').toggleClass('body-hide');
            var form = $(this);
            var post_url = form.attr('action');
            var post_data = form.serialize();
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: post_url,
                data: post_data,
                success: function(data) {
                    if($('.cart-alert').length) {
                        $('.cart-alert').slideDown();
                    }
                    cartCount();
                    $('body').toggleClass('body-hide');
                }
            });
        });
    });

    cartCount();

});