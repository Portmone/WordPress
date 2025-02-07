jQuery(function () {
	(function ($) {

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
