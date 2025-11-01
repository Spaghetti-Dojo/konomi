import React from 'react';
import { jest, describe, it, expect } from '@jest/globals';
import { render } from '@testing-library/react';
import { Edit } from '../../../../../../sources/Blocks/UserProfile/edit/index';

jest.mock( '@wordpress/block-editor', () => ( {
	// @ts-ignore
	useBlockProps: ( ...props ) => ( {
		className: 'mock-block-props',
		...props,
	} ),
} ) );

describe( 'BlockComponent', () => {
	it( 'should match snapshot', () => {
		const props = {}
		const { container } = render( <Edit { ...props } /> );
		expect( container ).toMatchSnapshot();
	} );
} );
