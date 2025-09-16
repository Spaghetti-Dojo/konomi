/**
 * External dependencies
 */
import type { MouseEvent } from 'react';

/**
 * WordPress dependencies
 */
import { getServerState, store, withSyncEvent } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { assertAnchorElement, assertIsPositiveInt } from './asserts';

const enum STATE_UPDATE_REASON {
	UPDATE_CURRENT_PAGE = 'page-update',
	HISTORY_CHANGE = 'history-change',
}

export type State = {
	page: number;
	perPage: number;
	updateReason: string;
};

// eslint-disable-next-line max-lines-per-function
export function init(): void {
	const { state }: { state: State } = store( 'konomiProfilePagination', {
		state: getServerState(),
		actions: {
			updatePagination: withSyncEvent( ( e: Readonly< MouseEvent > ) => {
				e.preventDefault();
				assertAnchorElement( e.currentTarget );
				state.page = extractPageIndexFromHrefAttribute(
					e.currentTarget
				);
				state.updateReason = STATE_UPDATE_REASON.UPDATE_CURRENT_PAGE;
			} ),

			updatePaginationByHistory: ( e: Readonly< PopStateEvent > ) => {
				const { state: { konomi = null } = {} } = e;

				if ( konomi === null ) {
					return;
				}

				try {
					assertIsPositiveInt( konomi.page );
				} catch ( error ) {
					konomi.page = 1;
				}

				state.page = konomi.page;
				state.updateReason = STATE_UPDATE_REASON.HISTORY_CHANGE;
			},
		},

		callbacks: {
			updateTableRows: () => {
				const rows = document.querySelectorAll(
					'.konomi-user-profile-item'
				);
				if ( ! rows.length ) {
					return;
				}

				const { perPage, page } = state;

				/*
				 * Calculate the start and the end indexes for the table rows to show
				 * based on the current page value.
				 */
				const startIndex = ( page - 1 ) * perPage;
				const endIndex = startIndex + perPage;

				rows.forEach( ( row: Readonly< Element >, index: number ) => {
					const insidePage = index >= startIndex && index < endIndex;
					row.setAttribute(
						'aria-hidden',
						insidePage ? 'false' : 'true'
					);
				} );
			},

			updatePaginationLinks: () => {
				const { page } = state;

				// Update active pagination link
				const paginationLinks = document.querySelectorAll(
					'.konomi-user-profile-pagination a'
				);

				paginationLinks.forEach( ( anchor: Readonly< Element > ) => {
					assertAnchorElement( anchor );
					const linkPage =
						extractPageIndexFromHrefAttribute( anchor );

					anchor.setAttribute(
						'aria-disabled',
						linkPage === page ? 'true' : 'false'
					);
					anchor.toggleAttribute( 'aria-current', linkPage === page );
				} );
			},

			updateHistory: () => {
				if (
					state.updateReason !==
					STATE_UPDATE_REASON.UPDATE_CURRENT_PAGE
				) {
					return;
				}

				const currentUrl = new URL( window.location.href );
				currentUrl.searchParams.set(
					'user-profile-page',
					`${ state.page }`
				);
				window.history.pushState(
					{
						konomi: { page: state.page },
					},
					'',
					currentUrl
				);
			},
		},
	} );
}

function extractPageIndexFromHrefAttribute(
	element: Readonly< HTMLAnchorElement >
): number {
	const hrefMatch = element.href.match( /\/page-(\d+)/ );
	return hrefMatch ? parseInt( hrefMatch[ 1 ] ?? '1', 10 ) : 1;
}
