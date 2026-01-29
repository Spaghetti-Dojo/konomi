type EffectType<T = unknown> = { run: () => T };

const isEffect = (value: unknown): value is EffectType =>
	Boolean(value) && typeof (value as EffectType).run === 'function';

const toPromise = <T = unknown>(value: unknown): Promise<T> => {
	try {
		const result = isEffect(value) ? (value as EffectType<T>).run() : (value as T);

		if (result instanceof Promise) {
			return result as Promise<T>;
		}

		return Promise.resolve(result as T);
	} catch (error) {
		return Promise.reject(error as T);
	}
};

const makeEffect = <T>(executor: () => T): EffectType<T> => ({
	run: executor,
});

const succeed = <T>(value: T): EffectType<T> => makeEffect(() => value);

const fail = <E = unknown>(error: E): EffectType<never> =>
	makeEffect(() => {
		throw error;
	});

const try_ = <T = unknown, E = unknown>(params: {
	try: () => T;
	catch?: (error: E) => T;
}): EffectType<T> =>
	makeEffect(() => {
		try {
			return params.try();
		} catch (error) {
			if (params.catch) {
				return params.catch(error as E);
			}

			throw error;
		}
	});

const runPromise = <T>(effect: unknown): Promise<T> => toPromise(effect);

const runSync = <T>(effect: unknown): T => {
	const result = isEffect(effect) ? (effect as EffectType<T>).run() : (effect as T);

	if (result instanceof Promise) {
		throw new Error('runSync received an async effect');
	}

	return result;
};

const flatMap = <A, B>(fn: (value: A) => EffectType<B> | Promise<B> | B) =>
	(effect: EffectType<A> | Promise<A> | A): EffectType<B> =>
		makeEffect(() => {
			const current = isEffect(effect) ? effect.run() : effect;

			if (current instanceof Promise) {
				return current.then((value) => toPromise<B>(fn(value))) as unknown as B;
			}

			const next = fn(current as A);
			return isEffect(next) ? next.run() : (next as B);
		});

const tap = <A>(fn: (value: A) => unknown) =>
	(effect: EffectType<A> | Promise<A> | A): EffectType<A> =>
		makeEffect(() => {
			const current = isEffect(effect) ? effect.run() : effect;

			if (current instanceof Promise) {
				return current.then(async (value) => {
					await fn(value as A);
					return value;
				}) as unknown as A;
			}

			fn(current as A);
			return current as A;
		});

const andThen = <A, B>(fn: (value: A) => EffectType<B> | Promise<B> | B) =>
	(effect: EffectType<A> | Promise<A> | A): EffectType<B> =>
		makeEffect(() => {
			const current = isEffect(effect) ? effect.run() : effect;

			if (current instanceof Promise) {
				return current.then((value) => toPromise<B>(fn(value))) as unknown as B;
			}

			const next = fn(current as A);
			return isEffect(next) ? next.run() : (next as B);
		});

const catchAllCause = <A, E = unknown>(fn: (error: E) => EffectType<A> | Promise<A> | A) =>
	(effect: EffectType<A> | Promise<A> | A): EffectType<A> =>
		makeEffect(() => {
			try {
				const current = isEffect(effect) ? effect.run() : effect;

				if (current instanceof Promise) {
					return current.catch((error) => toPromise<A>(fn(error as E))) as unknown as A;
				}

				return current as A;
			} catch (error) {
				const next = fn(error as E);
				return isEffect(next) ? next.run() : (next as A);
			}
		});

export const pipe = <T>(value: T, ...fns: Array<(input: any) => any>) =>
	fns.reduce((current, fn) => fn(current), value);

// Convenience re-exports to align with Effect style API
export type Cause = unknown;

export const Effect = {
	succeed,
	fail,
	try: try_,
	runPromise,
	runSync,
	flatMap,
	tap,
	andThen,
	catchAllCause,
};

export const Cause = {};

export type EffectTypeExport<T = unknown> = EffectType<T>;
