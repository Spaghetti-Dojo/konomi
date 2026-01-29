module.exports = function() {
	ensureGlobalWp();
	mockWpApiFetch();
};

function ensureGlobalWp() {
	// @ts-ignore
	if ( typeof globalThis.wp === 'undefined' ) {
		// @ts-ignore
		globalThis.wp = {};
	}
}

// TODO Use __mocks__
function mockWpApiFetch() {
	const apiFetch = () => void 0;
	apiFetch.use = () => void 0;
	// @ts-ignore
	globalThis.wp['apiFetch'] = apiFetch;
}
