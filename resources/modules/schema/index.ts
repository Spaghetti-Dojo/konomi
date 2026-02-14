import type { Cause } from '@external/effect-js';
import * as z from '@external/zod';
import { Effect, pipe } from '@external/effect-js';
import {
	getConfig,
	getContext,
	getServerContext,
	getServerState,
} from '@wordpress/interactivity';

export type SanitizationError = Readonly< Cause.Cause< Error > >;

// eslint-disable-next-line @typescript-eslint/max-params
export function sanitize< S extends z.ZodType >(
	configuration: unknown,
	schema: S
): Readonly< Effect.Effect< z.infer< S >, Error > > {
	return Effect.try( {
		try: () => schema.parse( configuration ),
		catch: ( error ) => {
			if ( error instanceof z.ZodError ) {
				const zodError = z.prettifyError( error );
				throw new Error(
					`Configuration validation failed: ${ zodError }`
				);
			}
			throw error;
		},
	} );
}

export function sanitizeState< S extends z.ZodType >(
	schema: S,
	storeName: string,
	onError?: ( error: Readonly< Cause.Cause< Error > > ) => void
): Effect.Effect< z.infer< S >, Cause.Cause< Error > > {
	return pipe(
		Effect.succeed( getServerState( storeName ) ),
		Effect.flatMap( ( serverState ) => sanitize( serverState, schema ) ),
		// eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
		Effect.catchAllCause( ( error ) =>
			catchInitializationIssues( error, storeName, onError )
		)
	);
}

export function sanitizeContext< S extends z.ZodType >(
	schema: S,
	storeName: string,
	onError?: ( error: Readonly< Cause.Cause< Error > > ) => void
): Effect.Effect< null, Cause.Cause< Error > > {
	return pipe(
		Effect.succeed( getServerContext() ),
		Effect.flatMap( ( context: any ) => sanitize( context, schema ) ),
		Effect.tap( ( sanitizedContext ) => {
			const context = getContext();
			Object.assign( context, sanitizedContext );
		} ),
		Effect.andThen( () => null ),
		// eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
		Effect.catchAllCause( ( error ) =>
			catchInitializationIssues( error, storeName, onError )
		)
	);
}

export function sanitizeConfiguration< S extends z.ZodType >(
	schema: S,
	storeName: string,
	onError?: ( error: Readonly< Cause.Cause< Error > > ) => void
): Effect.Effect< null, Cause.Cause< Error > > {
	return pipe(
		Effect.succeed( getConfig() ),
		Effect.flatMap( ( config: any ) => sanitize( config, schema ) ),
		Effect.tap( ( sanitizedConfig ) => {
			const config = getConfig();
			Object.assign( config, sanitizedConfig );
		} ),
		Effect.andThen( () => null ),
		// eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
		Effect.catchAllCause( ( error ) =>
			catchInitializationIssues( error, storeName, onError )
		)
	);
}

function catchInitializationIssues(
	error: Readonly< Cause.Cause< Error > >,
	_storeName: string,
	callback?: ( error: Readonly< Cause.Cause< Error > > ) => void
): Effect.Effect< never, Cause.Cause< Error >, never > {
	if ( callback ) {
		callback( error );
	}

	return Effect.fail( error );
}
