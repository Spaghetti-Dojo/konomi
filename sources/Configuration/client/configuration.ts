/**
 * External dependencies
 */
import { initConfiguration } from './init-configuration';

/**
 * Internal dependencies
 */
import type { KonomiConfiguration } from './types';

type Get = ( key: string, defaultValue: any ) => unknown;

export function configuration(): { get: Get } {
	let _configuration: KonomiConfiguration.Configuration = {};

	try {
		_configuration = initConfiguration();
	} catch ( error ) {}

	return Object.freeze( {
		get( key: string, defaultValue: any ) {
			if ( ! Object.hasOwn( _configuration, key ) ) {
				return defaultValue;
			}

			return _configuration[ key ];
		},
	} );
}
