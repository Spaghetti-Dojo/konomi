import { jest, describe, it, expect } from '@jest/globals';
import { Effect } from '@external/effect-js';
import { z } from '@external/zod';
import {
	sanitizeState,
	sanitizeContext,
	sanitizeConfiguration,
} from '@konomi/schema';

import {
	getServerState,
	getServerContext,
	getContext,
	getConfig,
} from '@wordpress/interactivity';

jest.mock('@wordpress/interactivity', () => ({
	getServerState: jest.fn(),
	getServerContext: jest.fn(),
	getContext: jest.fn(),
	getConfig: jest.fn(),
}));

describe('sanitizeState', () => {
	const schema = z.object({ page: z.number(), perPage: z.number() });
	const storeName = 'testStore';

	it('returns sanitized state', async () => {
		const serverState = { page: 1, perPage: 10 };
		jest.mocked(getServerState).mockReturnValue(serverState as any);

		const result = await Effect.runPromise(sanitizeState(schema, storeName));

		expect(result).toEqual(serverState);
		expect(getServerState).toHaveBeenCalledWith(storeName);
	});

	it('fails on invalid state and calls onError', async () => {
		const onError = jest.fn();
		jest.mocked(getServerState).mockReturnValue({ page: 'invalid' } as any);

		await expect(
			Effect.runPromise(sanitizeState(schema, storeName, onError))
		).rejects.toThrow();

		expect(onError).toHaveBeenCalled();
		// TODO: Once pub/sub is integrated, verify message publication
	});
});

describe('sanitizeContext', () => {
	const schema = z.object({ isActive: z.boolean() });
	const storeName = 'testStore';

	it('assigns sanitized server context to store context', async () => {
		const serverContext = { isActive: true };
		const clientContext: Record<string, unknown> = {};

		jest.mocked(getServerContext).mockReturnValue(serverContext as any);
		jest.mocked(getContext).mockReturnValue(clientContext as any);

		const result = await Effect.runPromise(
			sanitizeContext(schema, storeName)
		);

		expect(result).toBeNull();
		expect(clientContext).toEqual(serverContext);
		expect(getContext).toHaveBeenCalled();
	});

	it('handles invalid context by calling error handler', async () => {
		const onError = jest.fn();
		jest.mocked(getServerContext).mockReturnValue({ isActive: 'yes' } as any);
		jest.mocked(getContext).mockReturnValue({});

		const result = await Effect.runPromise(
			sanitizeContext(schema, storeName, onError)
		);

		expect(result).toBeNull();
		expect(onError).toHaveBeenCalledWith({
			message: 'Failed to initialize context',
			severity: 'error',
		});
	});
});

describe('sanitizeConfiguration', () => {
	const schema = z.object({ apiUrl: z.string(), timeout: z.number() });
	const storeName = 'testStore';

	it('assigns sanitized configuration back to config store', async () => {
		const serverConfig = { apiUrl: 'https://api.example.com', timeout: 5000 };
		const clientConfig: Record<string, unknown> = {};
		let call = 0;

		jest.mocked(getConfig).mockImplementation(() => {
			if (call === 0) {
				call += 1;
				return serverConfig as any;
			}

			return clientConfig as any;
		});

		const result = await Effect.runPromise(
			sanitizeConfiguration(schema, storeName)
		);

		expect(result).toBeNull();
		expect(clientConfig).toEqual(serverConfig);
	});

	it('handles invalid configuration by calling error handler', async () => {
		const onError = jest.fn();
		jest.mocked(getConfig).mockReturnValue({ apiUrl: 123 } as any);

		const result = await Effect.runPromise(
			sanitizeConfiguration(schema, storeName, onError)
		);

		expect(result).toBeNull();
		expect(onError).toHaveBeenCalledWith({
			message: 'Failed to initialize configuration',
			severity: 'error',
		});
	});
});
