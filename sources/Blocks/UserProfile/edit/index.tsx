/**
 * External dependencies
 */
import type { JSX } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
// @ts-expect-error
import ServerSideRender from '@wordpress/server-side-render';
// @ts-expect-error
import { useBlockProps } from '@wordpress/block-editor';

export function Edit(): JSX.Element {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender
				block="konomi/user-profile"
				attributes={ { dummy: true } }
			/>
		</div>
	);
}
