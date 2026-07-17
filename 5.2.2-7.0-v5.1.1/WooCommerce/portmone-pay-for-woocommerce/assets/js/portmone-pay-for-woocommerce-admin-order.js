jQuery(function () {
	(function ($) {
		const { __, _x, _n, _nx } = wp.i18n;
		var val = $( this ).serializeArray();

		$(document).on('click', '.portmone-fast-capture-btn', function(e) {
			e.preventDefault();

			if ( ! confirm( portmone_pay_for_WooCommerce_admin_order_params.i18n.confirm_capture ) ) {
				return;
			}

			var $button = $(this);
			// Блокуємо кнопку та міняємо текст на "Обробка платежу..."
			$button.prop('disabled', true).text(portmone_pay_for_WooCommerce_admin_order_params.i18n.loading);

			// Блокуємо інтерфейс замовлення стандартним лоадером WooCommerce
			$button.closest('.wc-order-data-row').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			$.ajax({
				url: portmone_pay_for_WooCommerce_admin_order_params.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					// ВАЖЛИВО: ця назва має точно збігатися з кінцем хука wp_ajax_...
					action: 'portmone_pay_for_woocommerce_process_preauth',
					order_id: portmone_pay_for_WooCommerce_admin_order_params.order_id,
					nonce: portmone_pay_for_WooCommerce_admin_order_params.nonce
				},
				success: function(response) {
					// Оскільки в PHP у нас зараз стоїть wp_send_json_error,
					// цей запит ЗАВЖДИ буде повертати response.success = false
					if (response.success) {
						alert(response.data.message);
						window.location.reload();
					} else {
						// Виводимо повідомлення про помилку (у нашому тесті тут буде "тест ajax .......")
						alert(portmone_pay_for_WooCommerce_admin_order_params.i18n.error_title + response.data.message);

						// Розблоковуємо кнопку назад
						$button.prop('disabled', false).text(portmone_pay_for_WooCommerce_admin_order_params.i18n.button_text);
						$button.closest('.wc-order-data-row').unblock();
					}
				},
				error: function() {
					// Якщо сервер взагалі "впаде" або видасть 500 помилку
					alert(portmone_pay_for_WooCommerce_admin_order_params.i18n.critical_error);
					$button.prop('disabled', false).text(portmone_pay_for_WooCommerce_admin_order_params.i18n.button_text);
					$button.closest('.wc-order-data-row').unblock();
				}
			});

		});
	})(jQuery.noConflict());
});
