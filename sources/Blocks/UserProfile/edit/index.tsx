/**
 * External dependencies
 */
import type { JSX } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
// @ts-expect-error
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function Edit(): JSX.Element {
	return (
		<div { ...useBlockProps() }>
			<Placeholder
				label={ __( 'Konomi: User Profile', 'konomi' ) }
				instructions={ __(
					'This is a placeholder for the User Profile block.',
					'konomi'
				) }
			/>
		</div>
	);
}
