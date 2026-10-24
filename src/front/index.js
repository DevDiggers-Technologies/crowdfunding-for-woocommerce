import './front.less';

const settings = window.ddwccfFrontObj || { i18n: {} };

const initContribution = ( box ) => {
	const input = box.querySelector( 'input[name="ddwccf_contribution_amount"]' );
	const presets = box.querySelectorAll( '.ddwccf-preset' );
	const rewards = box.querySelectorAll( 'input[name="ddwccf_reward_id"]' );
	const dynamic = '1' === box.dataset.dynamic;
	const base = parseFloat( box.dataset.minimum || '0' );

	const refresh = () => {
		presets.forEach( ( item ) => item.classList.toggle( 'is-active', input && parseFloat( item.dataset.amount ) === parseFloat( input.value ) ) );
		box.querySelectorAll( '.ddwccf-reward' ).forEach( ( card ) => card.classList.toggle( 'is-selected', card.querySelector( 'input' ).checked ) );
	};

	presets.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			input.value = button.dataset.amount;
			refresh();
		} );
	} );

	rewards.forEach( ( radio ) => {
		radio.addEventListener( 'change', () => {
			const minimum = Math.max( base, parseFloat( radio.dataset.amount || '0' ) );
			if ( dynamic && input ) {
				input.min = minimum || 0.01;
				if ( parseFloat( input.value || '0' ) < minimum ) {
					input.value = minimum;
				}
			}
			refresh();
		} );
	} );

	if ( input ) {
		input.addEventListener( 'input', refresh );
	}

	refresh();
};

const initCountdown = ( element ) => {
	const end = new Date( element.dataset.end ).getTime();
	const units = [ [ 'days', 86400 ], [ 'hours', 3600 ], [ 'mins', 60 ], [ 'secs', 1 ] ];
	let timer = null;

	const tick = () => {
		let left = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );

		if ( ! left ) {
			element.textContent = settings.i18n.ended;
			clearInterval( timer );
			return;
		}

		element.innerHTML = units.map( ( [ key, size ] ) => {
			const value = Math.floor( left / size );
			left -= value * size;
			return `<span><strong>${ String( value ).padStart( 2, '0' ) }</strong>${ settings.i18n[ key ] }</span>`;
		} ).join( '' );
	};

	timer = setInterval( tick, 1000 );
	tick();
};

document.addEventListener( 'DOMContentLoaded', () => {
	document.querySelectorAll( '.ddwccf-contribution' ).forEach( initContribution );
	document.querySelectorAll( '.ddwccf-countdown' ).forEach( initCountdown );

	document.addEventListener( 'click', ( e ) => {
		const copy = e.target.closest( '.ddwccf-copy' );

		if ( ! copy ) {
			return;
		}

		const done = () => {
			copy.classList.add( 'is-copied' );
			copy.setAttribute( 'data-tooltip', settings.i18n.copied );
			setTimeout( () => {
				copy.classList.remove( 'is-copied' );
				copy.removeAttribute( 'data-tooltip' );
			}, 1500 );
		};

		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( copy.dataset.copy ).then( done );
		} else {
			window.prompt( '', copy.dataset.copy );
		}
	} );
} );
