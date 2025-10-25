export function assertAnchorElement(
	element: unknown
): asserts element is HTMLAnchorElement {
	if ( ! ( element instanceof HTMLAnchorElement ) ) {
		throw new Error( 'Expected an anchor element' );
	}
}

export function assertIsPositiveInt( value: unknown ): asserts value is number {
	assertIsNumber( value );
	assertIsInt( value );
}

function assertIsNumber( value: unknown ): asserts value is number {
	if ( typeof value !== 'number' ) {
		throw new Error( 'Expected a number' );
	}
}

function assertIsInt( value: number ): asserts value is number {
	if ( ! Number.isInteger( value ) ) {
		throw new Error( 'Expected an integer' );
	}
	if ( value < 1 ) {
		throw new Error( 'Expected a positive integer' );
	}
}
