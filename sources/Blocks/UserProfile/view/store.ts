import { getServerState, store, withSyncEvent } from '@wordpress/interactivity';
import type { MouseEvent } from 'react';

// eslint-disable-next-line max-lines-per-function
export function init(): void {
	const { state } = store( 'konomiProfilePagination', {
		state: getServerState(),
		actions: {
			updatePagination: withSyncEvent( ( e: Readonly< MouseEvent > ) => {
				e.preventDefault();
				assertAnchorElement( e.currentTarget );
				state.page = extractPageIndexFromHrefAttribute(
					e.currentTarget
				);
			} ),
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
				} );
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

function assertAnchorElement(
	element: unknown
): asserts element is HTMLAnchorElement {
	if ( ! ( element instanceof HTMLAnchorElement ) ) {
		throw new Error( 'Expected an anchor element' );
	}
}
