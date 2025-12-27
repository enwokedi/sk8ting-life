(function ($) {
	'use strict';

	$(document).on('hivepress:init', function (event, container) {

		// Reverse parent
		container.find('[data-reverse-parent]').each(function () {
			var container = $(this),
				parent = container.closest('form').find(':input[name="' + container.data('reverse-parent') + '"]');

			if (parent.length) {
				if (parent.prop('checked')) {
					container.hide();
				}

				parent.on('change', function () {
					if ($(this).prop('checked')) {
						container.hide();
					} else {
						container.show();
					}
				});
			}
		});
	});
})(jQuery);
