import { configuration } from '@konomi/configuration';
import type { APIFetchMiddleware } from '@wordpress/api-fetch';

// The parameters for the middleware exist outside our codebase.
// eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
export const catchErrors: APIFetchMiddleware = ( options, next ) => {
	const result = next( options );

	const isDebugMode = Boolean( configuration().get( 'isDebugMode', false ) );
	result.catch( ( error ) => {
		if ( isDebugMode ) {
			// eslint-disable-next-line no-console
			console.log( error );
		}
	} );

	return result;
};
