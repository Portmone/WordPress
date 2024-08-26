jQuery(function () {
    (function ($) {
        var portmone_payment_form = document.portmone_payment_form;
        if (portmone_payment_form && portmone_payment_form.length > 0) {
            $('.woocommerce-checkout').prepend('<div class="portmone-checkout-page-overlay"></div><div class="portmone-checkout-page-spin"></div>');
            portmone_payment_form.submit();
        }

        $('form').submit(function () {
            var val = $( this ).serializeArray();
            var name_input = [
                'enabled',
                'payee_id',
                'login',
                'password',
                'title',
                'description',
                'preauth_flag',
                'showlogo',
            ];
            var i;
            var error = 0;
            if (val!==undefined) {
                if (val[0].name == 'woocommerce_portmone_'+name_input[0] && val[0].value == '1'){
                    $('input').removeClass('woocommerce_portmone_input_error');
                    $('#message').remove();
                    for (i = 1; i < 4; i++) {
                        if (val[i].name == 'woocommerce_portmone_'+ name_input[i] && val[i].value =='' ) {
                            $('#woocommerce_portmone_' + name_input[i]).addClass('woocommerce_portmone_input_error');
                            error++;
                        }
                    }
                    if (val[6].name == 'woocommerce_portmone_convert_money' && val[6].value =='1' ) {
                        if (val[7].value =='' ) {
                            $('#woocommerce_portmone_exchange_rates').addClass('woocommerce_portmone_input_error');
                            error++;
                        }
                    }
					if (val[5].value == '') {
						$('#woocommerce_portmone_key').addClass('woocommerce_portmone_input_error');
						error++;
					}

                    if (error>0 ) {
                        $('#portmone-header').after('<div id="message" class="error" ><p><strong>Не заполнены обязательные поля</strong></p></div>');
                        return false;
                    }
                }
            }
        });

        $('h2.tabs').each(function() {
            var $active, $content, $links = $(this).find('a');
            $active = $($links.filter('[href="'+location.hash+'"]')[0] || $links[0]);
            $active.addClass('nav-tab-active');
            $content = $($active[0].hash);
            $links.not($active).each(function () {
                $(this.hash).hide();
            });

            $(this).on('click', 'a', function(e){
                $active.removeClass('nav-tab-active');
                $content.hide();
                $active = $(this);
                $content = $(this.hash);
                $active.addClass('nav-tab-active');
                $active.blur();
                $content.show();

                e.preventDefault();
            });
        });
    })(jQuery.noConflict());
});
