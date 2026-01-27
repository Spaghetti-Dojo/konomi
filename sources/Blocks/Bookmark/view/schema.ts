import { z } from '@external/zod';

export const contextSchema = z.object( {
	isActive: z.boolean(),
} );
