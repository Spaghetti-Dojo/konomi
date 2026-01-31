/**
 * External dependencies
 */
import { Effect, pipe } from '@external/effect-js';
import { sanitizeContext, type ErrorMessage } from '@konomi/schema';

/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import { doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { addReaction } from './add-reaction-command';
import type {
	Context as OuterContext,
	ResponseError,
} from '../../Konomi/view/store';
import { contextSchema } from './schema';

export type Context = {
	isActive: boolean;
	count: number;
};

const STORE_NAME = 'konomiReaction';

// eslint-disable-next-line max-lines-per-function
export function init(): void {
	const { actions } = store( STORE_NAME, {
		state: {},

		actions: {
			toggleStatus: (): void => {
				const context = getContext< Context >( STORE_NAME );

				context.isActive = ! context.isActive;
				context.count = context.isActive
					? context.count + 1
					: context.count - 1;

				actions.updateUserPreferences();
			},

			// eslint-disable-next-line max-lines-per-function,complexity
			*updateUserPreferences(): Generator< Promise< void > > {
				const outerContext = getContext< OuterContext >( 'konomi' );

				if ( ! outerContext.isUserLoggedIn ) {
					outerContext.loginRequired = true;
					actions.revertStatus();
					return;
				}

				try {
					const reactionContext = getContext< Context >( STORE_NAME );
					yield addReaction( {
						meta: {
							_reaction: {
								id: outerContext.id,
								type: outerContext.type,
								isActive: reactionContext.isActive,
							},
						},
					} );
				} catch ( error: any ) {
					const responseError = error as ResponseError;
					outerContext.error = {
						code: responseError.code,
						message: responseError.message,
					};
					actions.revertStatus();
				}
			},

			revertStatus: (): void => {
				const context = getContext< Context >( STORE_NAME );
				context.count = context.isActive
					? context.count - 1
					: context.count + 1;
				context.isActive = ! context.isActive;
			},
		},

		callbacks: {
			init(): void {
				void pipe(
					sanitizeContext( contextSchema, STORE_NAME ),
					Effect.catchAllCause( () => {
						const errorMessage: ErrorMessage = {
							message: 'Failed to initialize context',
							severity: 'error',
						};
						doAction( 'konomi.sanitizationError', errorMessage );
						return Effect.succeed( null );
					} ),
					Effect.runPromise
				);
			},
		},
	} );
}
