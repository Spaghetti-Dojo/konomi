import { z } from '@external/zod';

export const contextSchema = z.object( {
	id: z.number(),
	type: z.string(),
	isUserLoggedIn: z.boolean(),
	loginRequired: z.boolean(),
	error: z.object( {
		code: z.string(),
		message: z.string(),
	} ),
} );
