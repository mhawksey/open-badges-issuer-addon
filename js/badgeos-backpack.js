// JavaScript Document
jQuery(document).ready(function($){
	badgeos_backpack_list();
	// Our main achievement list AJAX call
	$('.badgeos_backpack').live( 'click', function( event ) {
		event.preventDefault();
		$(this).attr("disabled", true);
		OpenBadges.issue([$(this).attr('data-uid')], function(errors, successes) {
			handle_backpack_response(errors, successes)
			console.log(successes); // return full uid of successes in array
			console.log(errors); // errors = [{assertion: "http://sites.hawksey.info/octel2/api/badge/assertion/?uid=100-1397727989-1",reason: "INACCESSIBLE"}];
		});
	});
	
	$('.badgeos_backpack_all').live( 'click', function( event ) {
		event.preventDefault();
		var values = $('input[name="badgeos_backpack_issues[]"]:checked').map(function () {
					  return this.value;
					}).get() 
		$(this).attr("disabled", true);
		OpenBadges.issue(values, function(errors, successes) {
			handle_backpack_response(errors, successes)
			console.log(successes);
		});
	});
	
	function handle_backpack_response(errors, successes){
		$.ajax({
			url: badgeos.ajax_url,
			data: {'action': 'open_badges_recorder',
				   'user_id': badgeos.user_id,
				   'successes': (successes) ? successes : false,
				   'errors': (errors) ? errors : false,
	
				  },
			type: "POST",
		    dataType: 'JSON',
			success: function( response ) {
				$('.badgeos_backpack.button').removeAttr('disabled');
				if (response.data.successes){
					var recorded = response.data.successes;
					var recorded_length = recorded.length
					for (i = 0; i < recorded_length; ++i) {
						$('*[data-uid="'+recorded[i]+'"]').text(response.data.resend_text);
					}
				}
			}
		});
	}
	
	function badgeos_backpack_list(){
		$.ajax({
			url: badgeos.json_url,
			data: {
				'user_id':     badgeos.user_id,
			},
			dataType: 'json',
			success: function( response ) {
				if ( window.console ) {
					console.log(response);
				}
				$('.badgeos-spinner').hide();
				if ( response.status !== 'ok' ) {
					console.log('No badge data returned');
				} else {
					$.each(response.achievements, function(index, value) {
						$('#badgeos-achievements-container').append( value.data );
					});
				}
			}
		});

	}
});
