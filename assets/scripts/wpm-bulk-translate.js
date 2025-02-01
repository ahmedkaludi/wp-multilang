jQuery(document).ready(function($){

	var t = this;

	$(document).on('click', '#doaction, #doaction2', function(e){

		t.bulkButtonId 	=	 $( this ).attr( 'id' );
		var name 		=	t.bulkButtonId.substr( 2 );

		if ( 'wpm_translate_action' === $( 'select[name="' + name + '"]' ).val() ) {
			e.preventDefault();

			if ( typeof inlineEditPost !== 'undefined' ) { // Not available for media.
				inlineEditPost.revert(); // Close Bulk edit and Quick edit if open.
			}

			$( '#wpm-translate-tr td' ).attr( 'colspan', $( 'th:visible, td:visible', '.widefat:first thead' ).length );
			$( 'table.widefat tbody' ).prepend( $( '#wpm-translate-tr' ) ).prepend( '<tr class="hidden"></tr>' );

		}else{
			$( '#wpm-translate-tr' ).find( '.cancel' ).trigger( 'click' );
		}

	});

	// Cancel the bulk translation form
	$( '#wpm-translate-tr' ).on('click', '.cancel', function(){
		$( '#wpm-translate-tr' ).siblings( '.hidden' ).remove();
		$( '#wpm-bulk-translate' ).append( $( '#wpm-translate-tr' ) ); 
		$( '#' + t.bulkButtonId ).trigger( 'focus' );
	});

	// Clean DOM in case of file download
	$( '#posts-filter' ).on('submit', function () {
			$( '.settings-error' ).remove();
			setTimeout(
				function () {
					$( 'input[type=checkbox]:checked' ).attr( 'checked', false );
					$( '#wpm-translate-tr' ).find( '.cancel' ).trigger( 'click' );
				},
				500
			);
		}
	);

});