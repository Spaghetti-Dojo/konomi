/**
 * External dependencies
 */
import { Effect } from '@external/effect-js';
import { sanitizeContext } from '@konomi/schema';

/**
 * WordPress dependencies
 */
import {
	getContext,
	getElement,
	useLayoutEffect,
	store,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { loginModalElement } from './elements/login-modal-element';
import { renderMessage } from './popover';
import { contextSchema } from './schema';

export type ResponseError = Readonly< {
	code: string;
	message: string;
	data: {
		status: number;
	};
} >;

export type Context = {
	id: number;
	type: string;
	isUserLoggedIn: boolean;
	loginRequired: boolean;
	error: {
		code: ResponseError[ 'code' ];
		message: ResponseError[ 'message' ];
	};
};

const STORE_NAME = 'konomi';

// eslint-disable-next-line max-lines-per-function
export function init(): void {
	store( STORE_NAME, {
		state: {},

		actions: {
			closeLoginModal: () => {
				const context = getContext< Context >( STORE_NAME );
				context.loginRequired = false;
			},
		},

		callbacks: {
			init(): void {
				Effect.runPromise(
					sanitizeContext( contextSchema, STORE_NAME )
				);
			},

			maybeRenderResponseError: (): void => {
				const context = getContext< Context >( STORE_NAME );
				// eslint-disable-next-line react-hooks/rules-of-hooks
				useLayoutEffect( () => {
					const element = getElement();
					if ( element.ref && context.error.code ) {
						renderMessage( element.ref ).finally( () => {
							context.error = {
								code: '',
								message: '',
							};
						} );
					}
					// eslint-disable-next-line react-hooks/exhaustive-deps
				}, [ context.error.code, context.error.message ] );
			},

			// eslint-disable-next-line complexity
			toggleLoginModal: (): void => {
				const element = getElement();
				if ( ! ( element.ref instanceof HTMLElement ) ) {
					return;
				}

				const context = getContext< Context >( STORE_NAME );
				if ( context.isUserLoggedIn ) {
					return;
				}

				const _loginModalElement = loginModalElement( element.ref );
				if ( context.loginRequired ) {
					_loginModalElement.showModal();
				} else {
					_loginModalElement.close();
				}
			},
		},
	} );
}
