import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "./resources/modules/schema/index.ts":
/*!*******************************************!*\
  !*** ./resources/modules/schema/index.ts ***!
  \*******************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.a(module, async (__webpack_handle_async_dependencies__, __webpack_async_result__) => { try {
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   sanitize: () => (/* binding */ sanitize),
/* harmony export */   sanitizeConfiguration: () => (/* binding */ sanitizeConfiguration),
/* harmony export */   sanitizeContext: () => (/* binding */ sanitizeContext),
/* harmony export */   sanitizeState: () => (/* binding */ sanitizeState)
/* harmony export */ });
/* harmony import */ var _external_zod__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @external/zod */ "@external/zod");
/* harmony import */ var _external_effect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @external/effect-js */ "@external/effect-js");
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");
var __webpack_async_dependencies__ = __webpack_handle_async_dependencies__([_external_zod__WEBPACK_IMPORTED_MODULE_0__, _external_effect_js__WEBPACK_IMPORTED_MODULE_1__]);
([_external_zod__WEBPACK_IMPORTED_MODULE_0__, _external_effect_js__WEBPACK_IMPORTED_MODULE_1__] = __webpack_async_dependencies__.then ? (await __webpack_async_dependencies__)() : __webpack_async_dependencies__);



// eslint-disable-next-line @typescript-eslint/max-params
function sanitize(configuration, schema) {
  return _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.try({
    try: () => schema.parse(configuration),
    catch: error => {
      if (error instanceof _external_zod__WEBPACK_IMPORTED_MODULE_0__.ZodError) {
        const zodError = _external_zod__WEBPACK_IMPORTED_MODULE_0__.prettifyError(error);
        throw new Error(`Configuration validation failed: ${zodError}`);
      }
      throw error;
    }
  });
}
function sanitizeState(schema, storeName, onError) {
  return (0,_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.pipe)(_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.succeed((0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__.getServerState)(storeName)), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.flatMap(serverState => sanitize(serverState, schema)),
  // eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
  _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.catchAllCause(error => catchInitializationIssues(error, storeName, onError)));
}
function sanitizeContext(schema, storeName, onError) {
  return (0,_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.pipe)(_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.succeed((0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__.getServerContext)()), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.flatMap(context => sanitize(context, schema)), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.tap(sanitizedContext => {
    const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__.getContext)();
    Object.assign(context, sanitizedContext);
  }), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.andThen(() => null),
  // eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
  _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.catchAllCause(error => catchInitializationIssues(error, storeName, onError)));
}
function sanitizeConfiguration(schema, storeName, onError) {
  return (0,_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.pipe)(_external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.succeed((0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__.getConfig)()), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.flatMap(config => sanitize(config, schema)), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.tap(sanitizedConfig => {
    const config = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_2__.getConfig)();
    Object.assign(config, sanitizedConfig);
  }), _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.andThen(() => null),
  // eslint-disable-next-line @typescript-eslint/prefer-readonly-parameter-types
  _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.catchAllCause(error => catchInitializationIssues(error, storeName, onError)));
}
function catchInitializationIssues(error, _storeName, callback) {
  if (callback) {
    callback(error);
  }
  return _external_effect_js__WEBPACK_IMPORTED_MODULE_1__.Effect.fail(error);
}
__webpack_async_result__();
} catch(e) { __webpack_async_result__(e); } });

/***/ }),

/***/ "@external/effect-js":
/*!**************************************!*\
  !*** external "@external/effect-js" ***!
  \**************************************/
/***/ ((module) => {

module.exports = import("@external/effect-js");;

/***/ }),

/***/ "@external/zod":
/*!********************************!*\
  !*** external "@external/zod" ***!
  \********************************/
/***/ ((module) => {

module.exports = import("@external/zod");;

/***/ }),

/***/ "@wordpress/interactivity":
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
/***/ ((module) => {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/async module */
/******/ (() => {
/******/ 	var hasSymbol = typeof Symbol === "function";
/******/ 	var webpackQueues = hasSymbol ? Symbol("webpack queues") : "__webpack_queues__";
/******/ 	var webpackExports = hasSymbol ? Symbol("webpack exports") : "__webpack_exports__";
/******/ 	var webpackError = hasSymbol ? Symbol("webpack error") : "__webpack_error__";
/******/ 	
/******/ 	
/******/ 	var resolveQueue = (queue) => {
/******/ 		if(queue && queue.d < 1) {
/******/ 			queue.d = 1;
/******/ 			queue.forEach((fn) => (fn.r--));
/******/ 			queue.forEach((fn) => (fn.r-- ? fn.r++ : fn()));
/******/ 		}
/******/ 	}
/******/ 	var wrapDeps = (deps) => (deps.map((dep) => {
/******/ 		if(dep !== null && typeof dep === "object") {
/******/ 	
/******/ 			if(dep[webpackQueues]) return dep;
/******/ 			if(dep.then) {
/******/ 				var queue = [];
/******/ 				queue.d = 0;
/******/ 				dep.then((r) => {
/******/ 					obj[webpackExports] = r;
/******/ 					resolveQueue(queue);
/******/ 				}, (e) => {
/******/ 					obj[webpackError] = e;
/******/ 					resolveQueue(queue);
/******/ 				});
/******/ 				var obj = {};
/******/ 	
/******/ 				obj[webpackQueues] = (fn) => (fn(queue));
/******/ 				return obj;
/******/ 			}
/******/ 		}
/******/ 		var ret = {};
/******/ 		ret[webpackQueues] = x => {};
/******/ 		ret[webpackExports] = dep;
/******/ 		return ret;
/******/ 	}));
/******/ 	__webpack_require__.a = (module, body, hasAwait) => {
/******/ 		var queue;
/******/ 		hasAwait && ((queue = []).d = -1);
/******/ 		var depQueues = new Set();
/******/ 		var exports = module.exports;
/******/ 		var currentDeps;
/******/ 		var outerResolve;
/******/ 		var reject;
/******/ 		var promise = new Promise((resolve, rej) => {
/******/ 			reject = rej;
/******/ 			outerResolve = resolve;
/******/ 		});
/******/ 		promise[webpackExports] = exports;
/******/ 		promise[webpackQueues] = (fn) => (queue && fn(queue), depQueues.forEach(fn), promise["catch"](x => {}));
/******/ 		module.exports = promise;
/******/ 		var handle = (deps) => {
/******/ 			currentDeps = wrapDeps(deps);
/******/ 			var fn;
/******/ 			var getResult = () => (currentDeps.map((d) => {
/******/ 	
/******/ 				if(d[webpackError]) throw d[webpackError];
/******/ 				return d[webpackExports];
/******/ 			}))
/******/ 			var promise = new Promise((resolve) => {
/******/ 				fn = () => (resolve(getResult));
/******/ 				fn.r = 0;
/******/ 				var fnQueue = (q) => (q !== queue && !depQueues.has(q) && (depQueues.add(q), q && !q.d && (fn.r++, q.push(fn))));
/******/ 				currentDeps.map((dep) => (dep[webpackQueues](fnQueue)));
/******/ 			});
/******/ 			return fn.r ? promise : getResult();
/******/ 		}
/******/ 		var done = (err) => ((err ? reject(promise[webpackError] = err) : outerResolve(exports)), resolveQueue(queue))
/******/ 		body(handle, done);
/******/ 		queue && queue.d < 0 && (queue.d = 0);
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/define property getters */
/******/ (() => {
/******/ 	// define getter functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/hasOwnProperty shorthand */
/******/ (() => {
/******/ 	__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ })();
/******/ 
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
/******/ 
/******/ // startup
/******/ // Load entry module and return exports
/******/ // This entry module used 'module' so it can't be inlined
/******/ var __webpack_exports__ = __webpack_require__("./resources/modules/schema/index.ts");
/******/ __webpack_exports__ = await __webpack_exports__;
/******/ const __webpack_exports__sanitize = __webpack_exports__.sanitize;
/******/ const __webpack_exports__sanitizeConfiguration = __webpack_exports__.sanitizeConfiguration;
/******/ const __webpack_exports__sanitizeContext = __webpack_exports__.sanitizeContext;
/******/ const __webpack_exports__sanitizeState = __webpack_exports__.sanitizeState;
/******/ export { __webpack_exports__sanitize as sanitize, __webpack_exports__sanitizeConfiguration as sanitizeConfiguration, __webpack_exports__sanitizeContext as sanitizeContext, __webpack_exports__sanitizeState as sanitizeState };
/******/ 

//# sourceMappingURL=konomi-schema.js.map