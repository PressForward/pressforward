(function() {
	// Remove the 'is-open' class from all code wrappers.
	const allCodeWrappers = document.querySelectorAll( '.nominate-this-bookmarklet-code' );
	if ( allCodeWrappers ) {
		allCodeWrappers.forEach( function( codeWrapper ) {
			codeWrapper.classList.remove( 'is-open' );
		} );
	}

	const showCodeToggle = document.querySelector( '.js-show-nominate-this-code-wrap' );
	if ( showCodeToggle ) {
		showCodeToggle.addEventListener( 'click', function( event ) {
			const clickedElement = event.target;
			const wrapper = clickedElement.closest( '.nominate-this-bookmarklet-code' );

			wrapper.classList.toggle( 'is-open' );
		} );
	}

	const allCodeFields = document.querySelectorAll( '.js-nominate-this-code' );
	if ( allCodeFields ) {
		allCodeFields.forEach( function( codeField ) {
			codeField.addEventListener( 'click', function() {
				this.select();
			} );
		} );
	}
})();
