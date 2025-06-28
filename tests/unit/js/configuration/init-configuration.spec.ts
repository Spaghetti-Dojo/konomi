import { jest, describe, it, expect, beforeAll, beforeEach } from '@jest/globals';

beforeAll(() => {
	document.body.innerHTML = `
		<script type="application/json" id="wp-script-module-data-@konomi/configuration">
			{
				"iconsUrl":"http://localhost:8888/wp-content/plugins/konomi/sources/Icons/icons",
				"iconsPath":"/var/www/html/wp-content/plugins/konomi/sources/Icons/icons",
				"isDebugMode":true
			}
		</script>
		`;
})

beforeEach( () => {
	jest.resetModules();
} );

describe( 'initConfiguration', () => {
	it( 'should parse and return a valid configuration', () => {
		const {initConfiguration} = require( '../../../../sources/Configuration/client/init-configuration' );
		const result = initConfiguration();
		expect( result ).toEqual( {
			iconsUrl: 'http://localhost:8888/wp-content/plugins/konomi/sources/Icons/icons',
			iconsPath: '/var/www/html/wp-content/plugins/konomi/sources/Icons/icons',
			isDebugMode: true,
		} );
	} );

	it( 'should return the cached configuration on subsequent calls', () => {
		const {initConfiguration} = require( '../../../../sources/Configuration/client/init-configuration' );
		const result1 = initConfiguration();
		const result2 = initConfiguration();
		expect( result1 ).toBe( result2 );
	} );

	it( 'should throw an error when parsing invalid JSON', () => {
		const {initConfiguration} = require( '../../../../sources/Configuration/client/init-configuration' );
		document.body.innerHTML = `
			<script type="application/json" id="wp-script-module-data-@konomi/configuration">
				{invalid json
			</script>
		`;
		expect( () => {
			initConfiguration()
		} ).toThrow( /Konomi invalid configuration/ );
	} );
} );
