/**
 * Internal dependencies
 */
import type { KonomiConfiguration } from './types';

let configuration: KonomiConfiguration.Configuration | null = null;

export function initConfiguration(): KonomiConfiguration.Configuration {
	if ( configuration !== null ) {
		return configuration;
	}

	const configurationScriptElement = document.getElementById(
		'wp-script-module-data-@konomi/configuration'
	);
	const serializedConfiguration =
		configurationScriptElement?.textContent ?? '{}';

	configuration = parseConfiguration( serializedConfiguration );
	return configuration;
}

function parseConfiguration(
	serializedConfiguration: string
): KonomiConfiguration.Configuration {
	try {
		return JSON.parse( serializedConfiguration );
	} catch ( error ) {
		throw new Error(
			`Konomi invalid configuration: ${ extractErrorMessageFromError(
				error
			) }`
		);
	}
}

// eslint-disable-next-line complexity
function extractErrorMessageFromError( error: unknown ): string {
	let errorMessage = typeof error === 'string' ? error : undefined;
	errorMessage = error instanceof Error ? error.message : errorMessage;

	return errorMessage ?? 'unknown error';
}
