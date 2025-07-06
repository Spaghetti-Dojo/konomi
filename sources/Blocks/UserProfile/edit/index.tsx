/**
 * External dependencies
 */
import type { JSX } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
// @ts-expect-error
import { useBlockProps } from '@wordpress/block-editor';

export function Edit(): JSX.Element {
	return (
		<div { ...useBlockProps() }>
			<Placeholder
				instructions={ __(
					'User Profile Placeholder block',
					'konomi'
				) }
			/>
		</div>
	);
}
