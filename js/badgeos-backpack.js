// JavaScript Document

var badgeos = badgeos || {};
// Returns the version of Internet Explorer or a -1
// (indicating the use of another browser).
//
// This code was taken from:
//
//   http://stackoverflow.com/a/17907562/1027723
function getInternetExplorerVersion()
{
  var rv = -1;
  if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
  else if (navigator.appName == 'Netscape')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("Trident/.*rv:([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
  return rv;
}

jQuery(document).ready(function($){
	badgeos_backpack_list();
	// Our main achievement list AJAX call
	$('.badgeos_backpack').live( 'click', function( event ) {
		event.preventDefault();
		$(this).attr("disabled", true);
		console.log($(this).attr('data-uid'));
		issueBadges([$(this).attr('data-uid')]);
		/*OpenBadges.issue([$(this).attr('data-uid')], function(errors, successes) {
			handle_backpack_response(errors, successes)
			console.log(successes); // return full uid of successes in array
			console.log(errors); // errors = [{assertion: "http://sites.hawksey.info/octel2/api/badge/assertion/?uid=100-1397727989-1",reason: "INACCESSIBLE"}];
		});*/
	});
	
	$('.badgeos_backpack_all').live( 'click', function( event ) {
		event.preventDefault();
		var values = $('input[name="badgeos_backpack_issues[]"]:checked').map(function () {
					  return this.value;
					}).get() 
		$(this).attr("disabled", true);
		issueBadges(values)
		/*OpenBadges.issue(values, function(errors, successes) {
			handle_backpack_response(errors, successes)
			console.log(successes);
		});*/
	});
	
	function issueBadges(assertions){
		// Issuer API can't do modal in IE https://github.com/mozilla/openbadges/issues/1002
		if (getInternetExplorerVersion() != -1){
			OpenBadges.issue_no_modal(assertions);
		} else {
			OpenBadges.issue(assertions, function(errors, successes) {
				handle_backpack_response(errors, successes)	
			});
		}
	}
	
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
