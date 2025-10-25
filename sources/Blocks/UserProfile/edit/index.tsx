/**
 * External dependencies
 */
import type { JSX } from 'react';
import React from 'react';
import { SvgHeart, SvgBookmark } from '@konomi/icons';

/**
 * WordPress dependencies
 */
// @ts-expect-error
import { useBlockProps } from '@wordpress/block-editor';

export function Edit(): JSX.Element {
	return (
		<div { ...useBlockProps() }>
			<Table />
		</div>
	);
}

function Table(): JSX.Element {
	return (
		<figure className="wp-block-table is-style-stripes alignwide wp-block-konomi-user-profile">
			<table className="has-fixed-layout">
				<Thead />
				<TBody />
			</table>
		</figure>
	);
}

function Thead(): JSX.Element {
	return (
		<thead>
			<tr>
				<th className="has-text-align-left">Title</th>
				<th className="has-text-align-left">Excerpt</th>
				<th className="has-text-align-center">Actions</th>
			</tr>
		</thead>
	);
}

function TBody(): JSX.Element {
	return (
		<tbody>
			<TRow />
			<TRow />
			<TRow />
		</tbody>
	);
}

function TRow(): JSX.Element {
	return (
		<tr className="konomi-user-profile-item">
			<td className="konomi-user-profile-item__title">
				<a href="https://sample.com">Sample Page</a>
			</td>

			<td className="konomi-user-profile-item__excerpt">
				This is an example page. It’s different from a blog post because
				it will stay…
			</td>

			<td className="konomi-user-profile-item__actions has-text-align-center">
				<div className="konomi">
					<GroupBlock />
				</div>
			</td>
		</tr>
	);
}

function GroupBlock(): JSX.Element {
	return (
		<div
			className="wp-block-group is-nowrap is-layout-flex wp-block-group-is-layout-flex"
			style={ { justifyContent: 'center' } }
		>
			<Reaction />
			<Bookmark />
		</div>
	);
}

function Reaction(): JSX.Element {
	return (
		<div className="konomi-reaction">
			<button className="wp-block-konomi-reaction is-active">
				<SvgHeart />
				<span className="konomi-label screen-reader-text">
					Save this post
				</span>
			</button>
		</div>
	);
}

function Bookmark(): JSX.Element {
	return (
		<div className="konomi-bookmark">
			<button className="wp-block-konomi-bookmark is-active">
				<SvgBookmark />
				<span className="konomi-label screen-reader-text">
					Bookmark this post
				</span>
			</button>
		</div>
	);
}
