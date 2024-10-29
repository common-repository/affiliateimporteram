var aidn_reload_page_after_ajax = false;
jQuery(function ($) {

	$(document).on("click", ".aidn-order-info", function () {
		var id = $(this).attr('id').split('-')[1];
		$.aidn_show_order(id);
		return false;
	});

	$.aidn_show_order = function (id) {
		$('<div id="aidn-dialog' + id + '"></div>').dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			title: "AffiliateImporterAm Info (ID: " + id + ")",
			open: function () {
				$('#aidn-dialog' + id).html('Please wait, data loads..');
				var data = {'action': 'aidn_order_info', 'id': id};

				$.post(ajaxurl, data, function (response) {
					//console.log('response: ', response);
					var json = jQuery.parseJSON(response);
					//console.log('result: ', json);

					if (json.state === 'error') {

						console.log(json);

					} else {
						//console.log(json);
						$('#aidn-dialog' + json.data.id).html(json.data.content.join('<br/>'));
					}

				});


			},
			close: function (event, ui) {
				$("#aidn-dialog" + id).remove();
			},
			buttons: {
				Ok: function () {
					$(this).dialog("close");
				}
			}
		});

		return false;

	};

});

