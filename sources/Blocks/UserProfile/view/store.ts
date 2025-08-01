import { store } from '@wordpress/interactivity';

export function init(): void {
	store( 'konomiProfile', {
		state: {},
		actions: {},
		callbacks: {},
	} );
}
