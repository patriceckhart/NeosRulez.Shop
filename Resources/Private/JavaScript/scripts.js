$(document).ready(function(){

    $('body').append('<div id="spinner"><div class="d-flex h-100 align-items-center"><div class="d-block w-100 text-center"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div></div></div>');

    $(document).on('change', '.quantity', function(){
        $('body').toggleClass('body-hide');
        $(this).parent().submit();
    });

    $(document).on('click', '.product_detail .dropdown-item', function(){
        $('body').toggleClass('body-hide');
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

    $(document).on('change', '#shipping_address_checkbox', function(){
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

    $(document).on('change', '#shipping', function(){
        $('body').toggleClass('body-hide');
        $('#updateOrder').submit();
        // renderCart();
    });

    if($('.shipping_form').is(':visible')) {
        $(document).on('change', '#shipping_country', function(){
            country = $(this).val();
            $('#selected_country').val(country);
            $('body').toggleClass('body-hide');
            $('#updateOrder').submit();
            // renderCart();
        });
    } else {
        $(document).on('change', '#country', function(){
            country = $(this).val();
            $('#shipping_country').val(country);
            $('#selected_country').val(country);
            $('body').toggleClass('body-hide');
            $('#updateOrder').submit();
            // renderCart();
        });
    }

    $(document).on('change', '#country', function(){
        $('body').toggleClass('body-hide');
        if($('.shipping_form').is(':visible')) {

        } else {
            country = $(this).val();
            $('#shipping_country').val(country);
            $('#selected_country').val(country);
            $('body').toggleClass('body-hide');
            $('#updateOrder').submit();
            // renderCart();
        }
    });

    $(document).on('change', '#shipping_country', function(){
        if($('.shipping_form').is(':visible')) {
            country = $(this).val();
            $('#selected_country').val(country);
            $('body').toggleClass('body-hide');
            $('#updateOrder').submit();
            // renderCart();
        }
    });

    if($('#country__modal').length) {
        var countryModal = new bootstrap.Modal(document.getElementById('country__modal'), {
            keyboard: false,
            backdrop: 'static'
        });

        if($('.product_detail').length) {
            countryModal.show();
        }
    }

    if($('#country').val() === null) {
        $('#country').prop('selectedIndex', 1).change();
    } else {
        if ($('#shipping').val() === null) {
            $('#shipping').prop('selectedIndex', 1).change();
        }
    }

    $('#updateOrder').submit(function() {
        $('#submit_btn').addClass('submitted');
    });

    setInterval(function() {
        $('.form-control').each(function() {
            attr = $(this).attr('required');
            val = $(this).val();
            if(attr=='required') {
                if(val=='') {
                    $(this).addClass('is-invalid');
                } else {
                    if(val === null) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                }
            }
        });
    }, 500);
});
