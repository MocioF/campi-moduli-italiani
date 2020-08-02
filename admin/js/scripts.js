jQuery( document ).ready(
	function($) {
		// imposto tutto a unchecked
		$( "input[type=checkbox][id^='gcmi-']" ).prop( 'checked', false );

		// imposto a checked solo quelle da aggiornare
		$( "input[type=hidden][id^='gcmi-updated-']" ).each(
			function( index ) {
				if ('false' == $( this ).val() ) {
					window.MySuffix = $( this ).attr( 'id' ).substring( ("gcmi-updated-").length, ($( this ).attr( 'id' ).length) );
					$( "input[type=checkbox][id='gcmi-" + window.MySuffix + "']" ).prop( 'checked', true );
				}
			}
		);
	}
);
