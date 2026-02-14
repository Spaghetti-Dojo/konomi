import { jest, describe, it, expect } from '@jest/globals';
import { Effect } from '@external/effect-js';
import { z } from '@external/zod';
import { sanitize } from '@konomi/schema';

jest.mock( '@wordpress/interactivity', () => ( {
	getConfig: jest.fn(),
	getContext: jest.fn(),
	getServerContext: jest.fn(),
	getServerState: jest.fn(),
} ) );

describe( 'sanitize', () => {
	describe( 'with valid data', () => {
		it( 'parses data matching the schema', async () => {
			const schema = z.object( { name: z.string(), age: z.number() } );
			const data = { name: 'John', age: 30 };
			const result = await Effect.runPromise( sanitize( data, schema ) );
			expect( result ).toEqual( data );
		} );

		it( 'handles nested objects', async () => {
			const schema = z.object( {
				user: z.object( {
					name: z.string(),
					email: z.string(),
				} ),
			} );
			const data = { user: { name: 'Jane', email: 'jane@example.com' } };
			const result = await Effect.runPromise( sanitize( data, schema ) );
			expect( result ).toEqual( data );
		} );

		it( 'handles arrays', async () => {
			const schema = z.object( { items: z.array( z.string() ) } );
			const data = { items: [ 'a', 'b', 'c' ] };
			const result = await Effect.runPromise( sanitize( data, schema ) );
			expect( result ).toEqual( data );
		} );

		it( 'supports optional and nullable fields', async () => {
			const schema = z.object( {
				required: z.string(),
				optional: z.string().optional(),
				nullable: z.string().nullable(),
			} );
			const data = { required: 'value', nullable: null };
			const result = await Effect.runPromise( sanitize( data, schema ) );
			expect( result ).toEqual( { required: 'value', nullable: null } );
		} );

		it( 'accepts empty objects when schema allows', async () => {
			const schema = z.object( {} );
			const result = await Effect.runPromise( sanitize( {}, schema ) );
			expect( result ).toEqual( {} );
		} );
	} );

	describe( 'with invalid data', () => {
		it( 'rejects when types do not match schema', async () => {
			const schema = z.object( { age: z.number() } );
			await expect(
				Effect.runPromise( sanitize( { age: 'not-a-number' }, schema ) ),
			).rejects.toThrow();
		} );

		it( 'rejects when required fields are missing', async () => {
			const schema = z.object( { name: z.string(), age: z.number() } );
			await expect(
				Effect.runPromise( sanitize( { name: 'John' }, schema ) ),
			).rejects.toThrow();
		} );

		it( 'rejects unexpected fields when schema is strict', async () => {
			const schema = z.object( { name: z.string() } ).strict();
			await expect(
				Effect.runPromise( sanitize( { name: 'John', extra: 'field' }, schema ) ),
			).rejects.toThrow();
		} );
	} );
} );
