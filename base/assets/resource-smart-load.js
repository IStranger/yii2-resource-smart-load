;
(function () {
    "use strict";
    if (window.yiiResourceSmartLoadPrivateObj) {
        return; // Prevent reload of current js file (on AJAX request)
    }

    // Private global JS path (only for internal use):
    window.yiiResourceSmartLoadPrivateObj = (function ($) {
        var resourceSmartLoad = {

            /**
             * Is already initialized (for prevent repeated initializing)
             */
            isInitialized: false,

            /**
             * Contain common function, helpers
             */
            fn: {},

            /**
             * Options of extension (will be exported from server side).
             * Null values will be replaced after initialization (on client).
             *
             * Contain following options:
             * <ul>
             *     <li>{String} {@link extensionOptions.hashMethod}</li>
             *     <li>{Boolean} {@link extensionOptions.enableLog}</li>
             *     <li>{Boolean} {@link extensionOptions.activateOnAllPages}</li>
             *     <li>{String[]} {@link extensionOptions.alwaysReloadableResources}</li>
             * </ul>
             *
             * @type {Object}
             */
            extensionOptions: {
                /**
                 * Current hashing method (for resource names)
                 * @type {String}
                 */
                hashMethod: null,

                /**
                 * Types of resources, that will be tracked by current extension
                 * @type {String[]}
                 */
                resourceTypes: null,

                /**
                 * Enable log of registered/disabled resources
                 * @type {Boolean}
                 */
                enableLog: null,

                /**
                 * Activates "smart" disabling of resources on all pages
                 * @type {Boolean}
                 */
                activateOnAllPages: null,

                /**
                 * List of resources, that always should be loaded on client.
                 * @type {String[]}
                 */
                alwaysReloadableResources: null,

                /**
                 * Public path, that can be used for accessing to current global object.
                 * @type {String}
                 */
                jsGlobalObjPublicPath: null,

                /**
                 * Name of "client" variable, see detail {@link resourceSmartLoad.setClientVar}
                 * @type {String}
                 */
                clientVarName: null
            },

            /**
             * Resources, that already loaded on client
             *
             * @type {Object}
             * @see getResourceByHash
             * @see getResourcesHashList
             * @see addResource
             */
            resources: {},

            /**
             * Returns loaded resource with given hash
             *
             * @param {String} hash    Hash of resource
             * @returns {String|null}  Resource identifier (for js/css files: name/url of file). Returns null if not found
             * @see resources
             */
            getResourceByHash: function (hash) {
                var app = this;

                return app.resources[hash] || null;
            },

            /**
             * Returns list of hashes of all loaded resources
             *
             * @return {String[]} List of hashes
             * @see resources
             */
            getResourcesHashList: function () {
                var app = this,
                    result = [];

                $.each(app.resources, function (hash, resource) {
                    result.push(resource.hash);
                });
                return result;
            },

            /**
             * Adds given resource to list of loaded resources {@link resources}
             *
             * @param {String} hash         Hash of resource
             * @param {String} [resource]   Resource identifier: basename/url of js/css files or part of content
             *                              of inline js/css (supplemental information)
             * @param {String} [comment]    Description for internal using (on debug mode)
             *                              (supplemental information)
             * @see resources
             */
            addResource: function (hash, resource, comment) {
                var app = this,
                    isAlreadyLoaded = Boolean(app.getResourceByHash(hash));

                if (!isAlreadyLoaded) {
                    app.resources[hash] = {
                        resource: resource,
                        hash    : hash,
                        source  : comment
                    };
                }

                app.log('addResource = ', resource, !isAlreadyLoaded ? ' ...added' : ' ...skipped');
            },

            /**
             * Checks global $.ajaxSend handlers. If not found handler of current extension, binds it.
             * This is prevents duplicate binding.
             */
            bindAjaxSendHandlerIfNotDefined: function () {
                var jQ = jQuery, // assign actual global instance (for the case, when jQuery reloaded at AJAX request)
                    app = this,
                    clientVarName = app.extensionOptions.clientVarName,
                    isHandled = false,
                    ajaxSendEvents = jQ._data(document) && jQ._data(document).events
                        ? jQ._data(document).events.ajaxSend
                        : undefined;

                // hook for all ajax-requests, in "client" variable we send hashes of all loaded resources
                function resourceSmartLoadAjaxSendHandler(event, jqXHR, settings) {
                    var varsObj = {};
                    varsObj[clientVarName] = JSON.stringify(app.getResourcesHashList());
                    app.setClientVar(jqXHR, varsObj, settings.url);
                }

                if (ajaxSendEvents) {
                    ajaxSendEvents.map(function (event) {
                        if (event.handler.name === resourceSmartLoadAjaxSendHandler.name) {
                            isHandled = true;
                        }
                        return event.handler;
                    });
                }

                if (!isHandled) {
                    jQ(document).ajaxSend(resourceSmartLoadAjaxSendHandler);
                }
                app.log('Check of global $.ajaxSend handler: ', isHandled
                    ? 'already bind... skipped'
                    : 'not found... bind extension handler');
            },

            /**
             * Initializes extension
             *
             * @param {Object} extensionOptionsInit     Options, that exported from server side,
             *                                          see {@link resourceSmartLoad.extensionOptions}
             * @see resourceSmartLoad.extensionOptions
             */
            initExtension: function (extensionOptionsInit) {
                var app = this;

                // prevent duplicate initialization of extension
                if (!app.isInitialized) {
                    app.extensionOptions = extensionOptionsInit;
                    app.fn.assignPropertyInNamespace(app.extensionOptions.jsGlobalObjPublicPath, this);
                    app.isInitialized = true;
                }

                $(function () {
                    // This code after each AJAX request checks global $.ajaxSend handlers. If on AJAX request
                    // for any reason will be reloaded "jquery.js" (for example, at "activateOnAllPages" = false), all
                    // global handlers of jQuery events will be removed. In this case method restores handler of $.ajaxSend.
                    app.bindAjaxSendHandlerIfNotDefined();
                });
            },

            /**
             * Sets "client" variable for given AJAX request (inserts variable into HTTP headers and cookie).
             * Cookie will be deleted after complete of given request.
             * On server this variable can be accessed through Helper::getClientVar(name).
             *
             * @param {jqXHR}           jqXHR       jQuery.ajax-object of request (returns by function $.ajax,
             *                                      or passed as argument in handlers $.ajax.beforeSend, $.ajaxSend etc.).
             * @param {Object}          vars        Object with variables in format {name:value}.
             *                                      "value" cannot contain non english symbols.
             * @param {String}          [url='/']   URL, which will be sent to ajax-request
             *                                      (for this URL will be assigned a cookie).
             * @see http://api.jquery.com/jQuery.ajax/#jqXHR
             * @see http://api.jquery.com/jQuery.ajax/#jQuery-ajax-settings
             */
            setClientVar: function (jqXHR, vars, url) {
                var app = this,
                    prefix = 'clientvar';  // Should not contain extraneous characters, otherwise the cookie names/titles can be invalid
                url = url || '/';

                $.each(vars, function (key, value) {
                    jqXHR.setRequestHeader(prefix + key, value);
                    app.fn.setCookie(prefix + key, value, {path: url});
                });
                jqXHR.always(function () {
                    $.each(vars, function (key, value) {
                        app.fn.deleteCookie(prefix + key, url);
                    });
                });
            },

            /**
             * Adds to log given strings/variables
             *
             * @param {...*} *   Variable number of parameters
             */
            log: function () {
                var args,
                    app = this;

                if (app.extensionOptions.enableLog) {
                    args = ['ResourceSmartLoad >>  '];
                    $.each(arguments, function (key, val) { // copy values from "pseudo array" to normal array
                        args.push(val);
                    });
                    console.log.apply(this, args);
                }
            }
        };

        resourceSmartLoad.fn = {
            /**
             * Returns cookie by name.
             *
             * @param {String}  name    Cookie name
             * @returns {String}        Cookie value. If not found, returns undefined
             */
            getCookie: function (name) {
                var safeName = name.replace(/([\.$?*|{}\(\)\[\]\\\/\+\^])/g, '\\$1'),
                    matches = document.cookie.match(new RegExp("(?:^|; )" + safeName + "=([^;]*)"));

                return matches ? decodeURIComponent(matches[1]) : undefined;
            },

            /**
             * Sets cookie.
             *
             * @param {String} name         Cookie name
             * @param {String} value        Cookie value
             * @param {Object} [options={}] Additional options:
             * <ul>
             *     <li>
             *         <b>expires</b> - Expiry time of cookie. Is interpreted differently, depending on the type of:
             *         <ul>
             *             <li>{Number} — number of seconds. For example, expires: 3600 - Cookie for an hour.</li>
             *             <li>{Date} — expiration date.</li>
             *             <li>If expires in the past, the cookie will be deleted.</li>
             *             <li>If expires is undefined or 0, the cookie will be set as a session and will disappear
             *             when the browser is closed.</li>
             *         </ul>
             *     </li>
             *     <li><b>path</b> - Cookie path.</li>
             *     <li><b>domain</b> - Cookie domain.</li>
             *     <li><b>secure</b> - If =true, cookie will be sent over a secure connection.</li>
             * </ul>
             */
            setCookie: function (name, value, options) {
                options = options || {};

                var d, updatedCookie, propName, propValue,
                    expires = options.expires;

                if (typeof expires === "number" && expires) {
                    d = new Date();
                    d.setTime(d.getTime() + expires * 1000);
                    expires = options.expires = d;
                }
                if (expires && expires.toUTCString) {
                    options.expires = expires.toUTCString();
                }

                value = encodeURIComponent(value);

                updatedCookie = name + "=" + value;

                for (propName in options) {
                    if (options.hasOwnProperty(propName)) {
                        updatedCookie += "; " + propName;
                        propValue = options[propName];
                        if (propValue !== true) {
                            updatedCookie += "=" + propValue;
                        }
                    }
                }

                document.cookie = updatedCookie;
            },

            /**
             * Deletes cookie by name (for given path).
             * If the cookie is set on a certain path, one name is not enough to read/delete.
             *
             * @param {String} name         Cookie name
             * @param {String} [url]        URL for cookie
             */
            deleteCookie: function (name, url) {
                var fn = this,
                    value = {expires: -1};  // "Cookie expired"

                if (url) {
                    $.extend(value, {path: url});
                }
                fn.setCookie(name, "", value);
            },

            /**
             * Assigns given value to given property (into namespace).
             * If given namespaces does not exist, they will be created (only if createNamespaces === true).
             *
             * @param {String}  path                        Property path with namespaces
             * @param {*}       value                       Any value
             * @param {Boolean} [createNamespaces=false]    If =true, nonexistent namespaces will be created,
             *                                              otherwise - throws exception
             * @example:
             * <code>
             *      'yii.moduleName.property'           // property path with namespaces
             *      'window.yii.moduleName.property'    // equivalent path
             * </code>
             */
            assignPropertyInNamespace: function (path, value, createNamespaces) {
                createNamespaces = (createNamespaces === undefined) ? false : createNamespaces;

                var fn = this,
                    namespaces = path.split('.'),
                    targetProp = namespaces.pop(),
                    targetObj = window;

                if (namespaces[0] === 'window') {
                    namespaces.shift();
                }

                namespaces.map(function (namespace) {
                    var exceptPrefix = '[resourceSmartLoad.fn.assignPropertyInNamespace] >> ';
                    if (targetObj[namespace] === undefined) {
                        if (!createNamespaces) {
                            throw exceptPrefix + 'Undefined namespace "' + namespace + '". See param "createNamespaces"';
                        }
                        targetObj[namespace] = {};
                    } else if (!fn.isObject(targetObj[namespace])) {
                        throw exceptPrefix + 'Property "' + namespace + '" is not an object, therefore, cannot be used as namespace.';
                    }
                    targetObj = targetObj[namespace];
                });

                targetObj[targetProp] = value;
            },

            /**
             * Checks whether the given variable is an object.
             *
             * @param {*} mixedVar
             * @return {Boolean}    Returns =true, if given variable is an object
             */
            isObject: function (mixedVar) {
                if (Object.prototype.toString.call(mixedVar) === '[object Array]') {
                    return false;
                }
                return mixedVar !== null && typeof mixedVar === 'object';
            }
        };

        return resourceSmartLoad;
    }(jQuery));
}());