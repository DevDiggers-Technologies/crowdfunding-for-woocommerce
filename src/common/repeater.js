/**
 * Repeatable reward rows. Rows are cloned from the text/html script template
 * inside each .ddwccf-repeater, with __INDEX__ replaced by a fresh index.
 * data-max caps the rows; the server enforces the same cap on save.
 */
export const initRepeaters = ( root = document ) => {
	root.querySelectorAll( '.ddwccf-repeater' ).forEach( ( repeater ) => {
		const rows = repeater.querySelector( '.ddwccf-repeater__rows' );
		const template = repeater.querySelector( 'script[type="text/html"]' );
		const max = parseInt( repeater.dataset.max || '0', 10 );

		const refresh = () => repeater.classList.toggle( 'is-full', max > 0 && rows.children.length >= max );

		repeater.querySelector( '.ddwccf-repeater__add' ).addEventListener( 'click', () => {
			if ( repeater.classList.contains( 'is-full' ) ) {
				return;
			}
			const index = parseInt( repeater.dataset.next || '0', 10 );
			repeater.dataset.next = index + 1;
			rows.insertAdjacentHTML( 'beforeend', template.innerHTML.replace( /__INDEX__/g, index ) );
			const first = rows.lastElementChild && rows.lastElementChild.querySelector( 'input:not([type="hidden"]), textarea' );
			if ( first ) {
				first.focus();
			}
			refresh();
		} );

		// Keep the row heading in sync with the reward title.
		rows.addEventListener( 'input', ( e ) => {
			if ( e.target.name && /\[title\]$/.test( e.target.name ) ) {
				const heading = e.target.closest( '.ddwccf-repeater__row' ).querySelector( '.ddwccf-repeater__head strong' );
				if ( heading ) {
					heading.textContent = e.target.value.trim() || heading.dataset.empty || heading.textContent;
				}
			}
		} );

		rows.addEventListener( 'click', ( e ) => {
			const remove = e.target.closest( '.ddwccf-repeater__remove' );
			if ( remove ) {
				remove.closest( '.ddwccf-repeater__row' ).remove();
				refresh();
			}
		} );

		refresh();
	} );
};
