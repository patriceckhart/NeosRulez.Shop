$(document).ready(function(){

    $('body').append('<div id="spinner"><div class="d-flex h-100 align-items-center"><div class="d-block w-100 text-center"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div></div></div>');

    $('.sub_products').click(function(){
        $(this).find('.sub_products_dropdown').slideDown();
    });

    $('.quantity').change(function() {
        $(this).parent().submit();
    });

    if($('.shipping_form').is(':visible')) {
        required_change = 1;
        $('.shipping_form').find('.form-control').attr('required', 'required');
        $('#shipping_notes').removeAttr('required');
        $('#shipping_company').removeAttr('required');
    } else {
        required_change = 0;
        $('.shipping_form').find('.form-control').removeAttr('required');
    }

    $('#shipping_address').change(function() {
        $('.shipping_form').slideToggle().removeClass('d-block');
        if(required_change==0) {
            $('.shipping_form').find('.form-control').attr('required', 'required');
            required_change = 1;
        } else {
            $('.shipping_form').find('.form-control').val('');
            $('.shipping_form').find('.form-control').removeAttr('required');
            required_change = 0;
        }
        $('#shipping_notes').removeAttr('required');
        $('#shipping_company').removeAttr('required');
    });

    $('#shipping').change(function() {
        $('#updateOrder').submit();
    });

    if($('.shipping_form').is(':visible')) {
        $('#shipping_country').change(function() {
            country = $(this).val();
            console.log(country);
            $('#selected_country').val(country);
            $('#updateOrder').submit();
        });
    } else {
        $('#country').change(function() {
            country = $(this).val();
            console.log(country);
            $('#shipping_country').val(country);
            $('#selected_country').val(country);
            $('#updateOrder').submit();
        });
    }

    $('#country').change(function() {
        if($('.shipping_form').is(':visible')) {

        } else {
            country = $(this).val();
            console.log(country);
            $('#shipping_country').val(country);
            $('#selected_country').val(country);
            $('#updateOrder').submit();
        }
    });

    $('#shipping_country').change(function() {
        if($('.shipping_form').is(':visible')) {
            country = $(this).val();
            console.log(country);
            $('#selected_country').val(country);
            $('#updateOrder').submit();
        }
    });

    setInterval(function() {
        $('.form-control').each(function() {
            attr = $(this).attr('required');
            val = $(this).val();
            if(attr=='required') {
                if(val=='') {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            }
        });
//         if($('.is-invalid').length) {
//             $('#submit_btn').attr('disabled', true);
//         } else {
//             $('#submit_btn').removeAttr('disabled');
//         }
    }, 500);

    /*$(function () {
        $('.xsubproduct_link').on('click', function (e) {
            var link = $(this);
            var link_url = link.attr('href');
            var link_data = link.serialize();
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: link_url,
                data: link_data,
                success: function(data) {
                    var result = $(data).find('.product_detail');
                    $('.product_detail').html(result);
                    console.log('success');
                }
            });
        });
    });*/

});