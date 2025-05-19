jQuery(function () {
	(function ($) {


		$('form').submit(function () {
			const { __, _x, _n, _nx } = wp.i18n;
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
				'exp_time',
			];
			var i;
			var error = 0;
			var error_exp_time = '';
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
					for (i = 1; i < name_input.length; i++) {
						if (val[i].name === 'woocommerce_portmone_exp_time') {
							if (val[i].value !== '' &&  isNaN(+val[i].value)) {
								$('#woocommerce_portmone_exp_time').addClass('woocommerce_portmone_input_error');
								error_exp_time = __( "Час на оплату має бути числом", "portmone-pay-for-woocommerce" );
								break;
							}

							if (val[i].value < 0) {
								$('#woocommerce_portmone_exp_time').addClass('woocommerce_portmone_input_error');
								error_exp_time =  __( "Час на оплату має бути більшим за нуль", "portmone-pay-for-woocommerce" );
							}
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

					if ( error > 0 ) {
						$('#portmone-header').after('<div id="message" class="error" ><p><strong>' + __( "Не заповнені обов'язкові поля", "portmone-pay-for-woocommerce" ) + ' </strong></p></div>');
						return false;
					}

					if ( error_exp_time !== '' ) {
						$('#portmone-header').after('<div id="message" class="error" ><p><strong>'+error_exp_time+'</strong></p></div>');
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
