import './admin.less';
import { initRepeaters } from '../common/repeater';

document.addEventListener( 'DOMContentLoaded', () => {
	const settings = window.ddwccfAdminObject || { i18n: {} };

	// Copy buttons in the Shortcodes tab.
	document.addEventListener( 'click', ( e ) => {
		const copy = e.target.closest( '.ddwccf-copy' );
		if ( ! copy || ! navigator.clipboard ) {
			return;
		}
		navigator.clipboard.writeText( copy.dataset.copy ).then( () => {
			const label = copy.textContent;
			copy.textContent = settings.i18n.copied;
			setTimeout( () => ( copy.textContent = label ), 1500 );
		} );
	} );

	initRepeaters();
} );
