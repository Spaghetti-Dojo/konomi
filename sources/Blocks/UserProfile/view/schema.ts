import { z } from '@external/zod';

export const stateSchema = z.object( {
	page: z.number(),
	perPage: z.number(),
	updateReason: z.string(),
} );

export type UserProfileStateSchema = typeof stateSchema;
export type UserProfileState = z.infer< typeof stateSchema >;
