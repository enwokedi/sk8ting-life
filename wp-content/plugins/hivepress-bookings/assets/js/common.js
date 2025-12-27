(function ($) {
	'use strict';

	$(document).ready(function () {

		// Calendar
		hivepress.getComponent('calendar').each(function () {
			var dateFormatter = new DateFormatter(),
				dateFormat = 'Y-m-d H:i:s',
				container = $(this),
				settings = {
					height: 'auto',
					timeZone: 'UTC',
					locale: hivepressCoreData.language,
					editable: false,
					selectable: true,
					expandRows: true,
					events: container.data('events'),
					unselectCancel: '.fc-range-button',
					longPressDelay: 500,
					initialView: 'dayGridMonth',
					initialDate: container.data('min-date'),
					validRange: {
						start: container.data('min-date'),
						end: container.data('max-date'),
					},
					select: function (selection) {
						container.data('start-date', dateFormatter.formatDate(new Date(selection.start.toLocaleString('en-US', {
							timeZone: 'UTC',
						})), dateFormat));

						container.data('end-date', dateFormatter.formatDate(new Date(selection.end.toLocaleString('en-US', {
							timeZone: 'UTC',
						})), dateFormat));

						$('.fc-range-button').css('display', 'inline-block');
					},
					unselect: function (event) {
						$('.fc-range-button').hide();
					},
					selectOverlap: function (event) {
						return event.display === 'background';
					},
					headerToolbar: {
						right: 'block,unblock prev,next',
					},
					customButtons: {
						range: {
							icon: 'hp-price',
							text: hivepressBookingsData.changePriceText,
							click: function () {
								if (!container.data('start-date') || !container.data('end-date')) {
									return;
								}

								var form = $(container.data('range-url')).find('form');

								form.find('input[name=start_date]').val(container.data('start-date'));
								form.find('input[name=end_date]').val(container.data('end-date'));

								$.fancybox.close();
								$.fancybox.open({
									src: container.data('range-url'),
									touch: false,
								});
							},
						},
						block: {
							icon: 'hp-lock',
							text: hivepressBookingsData.blockText,
							click: function () {
								if (!container.data('start-date') || !container.data('end-date')) {
									return;
								}

								calendar.addEvent({
									groupId: 'blocked',
									start: container.data('start-date'),
									end: container.data('end-date'),
									allDay: container.data('view') === 'month',
									display: 'background',
									classNames: ['fc-blocked'],
								});

								$.ajax({
									url: container.data('block-url'),
									method: 'POST',
									data: {
										'start_date': container.data('start-date'),
										'end_date': container.data('end-date'),
									},
									beforeSend: function (xhr) {
										xhr.setRequestHeader('X-WP-Nonce', hivepressCoreData.apiNonce);
									},
								});
							},
						},
						unblock: {
							icon: 'hp-unlock',
							text: hivepressBookingsData.unblockText,
							click: function () {
								if (!container.data('start-date') || !container.data('end-date')) {
									return;
								}

								calendar.addEvent({
									groupId: 'unblocked',
									start: container.data('start-date'),
									end: container.data('end-date'),
									allDay: container.data('view') === 'month',
									display: 'background',
									classNames: ['fc-unblocked'],
								});

								$.ajax({
									url: container.data('unblock-url'),
									method: 'POST',
									data: {
										'start_date': container.data('start-date'),
										'end_date': container.data('end-date'),
									},
									beforeSend: function (xhr) {
										xhr.setRequestHeader('X-WP-Nonce', hivepressCoreData.apiNonce);
									},
								});
							},
						},
					},
				};

			if (container.data('options')) {
				$.extend(settings, container.data('options'));
			}

			if (container.data('view') === 'week') {
				$.extend(settings, {
					initialView: 'timeGridWeek',
					allDaySlot: false,
					displayEventTime: false,
					slotMinTime: container.data('min-time'),
					slotMaxTime: container.data('max-time'),
					slotDuration: container.data('slot-duration'),
				});
			}

			if (container.data('range-url')) {
				var ranges = container.data('ranges');

				if (ranges && ranges.length) {
					settings['dayCellContent'] = function (arg) {
						var time = Math.floor(arg.date.getTime() / 1000),
							price = null;

						$.each(ranges, function (index, range) {
							if (range.start <= time && time < range.end) {
								price = range.price;

								return false;
							}
						});

						if (price) {
							return arg.dayNumberText + ' (' + price + ')';
						}
					};
				}

				settings['headerToolbar']['right'] = 'range ' + settings['headerToolbar']['right'];
			}

			if (container.data('common')) {
				settings['headerToolbar']['right'] = 'prev,next';

				settings['eventDidMount'] = function (info) {
					$(info.el).attr('title', info.event.extendedProps.description);
				}
			}

			var calendar = new FullCalendar.Calendar(container.get(0), settings);

			calendar.render();
		});
	});
})(jQuery);
