/**
 * External dependencies
 */
import { Effect } from '@external/effect-js';
import { sanitizeContext } from '@konomi/schema';

/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { addBookmark } from './add-bookmark-command';
import { contextSchema } from './schema';
import type {
	Context as OuterContext,
	ResponseError,
} from '../../Konomi/view/store';

export type Context = {
	isActive: boolean;
};

const STORE_NAME = 'konomiBookmark';

// eslint-disable-next-line max-lines-per-function
export function init(): void {
	const { actions } = store( STORE_NAME, {
		state: {},

		actions: {
			toggleStatus: (): void => {
				const context = getContext< Context >( STORE_NAME );
				context.isActive = ! context.isActive;
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
					const context = getContext< Context >( STORE_NAME );
					yield addBookmark( {
						meta: {
							_bookmark: {
								id: outerContext.id,
								type: outerContext.type,
								isActive: context.isActive,
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
				context.isActive = ! context.isActive;
			},
		},

		callbacks: {
			init(): void {
				Effect.runPromise(
					sanitizeContext( contextSchema, STORE_NAME )
				);
			},
		},
	} );
}
