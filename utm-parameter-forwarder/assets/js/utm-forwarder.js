/*!
 * UTM Parameter Forwarder
 * Appends tracking parameters from the current URL to every internal link.
 * Runs entirely in the browser; values are never written into HTML.
 */
(function () {
	'use strict';

	var cfg = window.utmpfConfig || {};
	var NAME_RE = /^[a-z0-9_-]{1,64}$/;
	var MAX_VALUE_LENGTH = 500;
	// Upper bound for everything appended to one link, to avoid over-long URLs.
	var MAX_TOTAL_LENGTH = 2000;
	var STORAGE_KEY = 'utmpf_params';
	var IGNORE_CLASS = 'utmpf-ignore';
	// WordPress system endpoints that should never be decorated.
	var EXCLUDED_PATH_RE = /\/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-cron\.php)(\/|$)/i;

	if (!window.URL || !window.URLSearchParams) {
		return;
	}

	var allowed = [];
	if (Array.isArray(cfg.params)) {
		for (var i = 0; i < cfg.params.length; i++) {
			var n = String(cfg.params[i]).toLowerCase();
			if (NAME_RE.test(n) && allowed.indexOf(n) === -1) {
				allowed.push(n);
			}
		}
	}
	if (!allowed.length) {
		return;
	}

	var persist = cfg.persist === true;

	function normalizeHost(host) {
		return String(host || '').toLowerCase().replace(/^www\./, '');
	}

	var hosts = [normalizeHost(window.location.hostname)];
	if (Array.isArray(cfg.hosts)) {
		for (var h = 0; h < cfg.hosts.length; h++) {
			hosts.push(normalizeHost(cfg.hosts[h]));
		}
	}

	function cleanValue(value) {
		if (typeof value !== 'string') {
			return '';
		}
		// Strip control characters and HTML/JS-significant characters, then cap the length.
		// Real campaign values never need < > " ' ` or \, and removing them protects
		// third-party forms or themes that might print the value without escaping.
		value = value.replace(/[\u0000-\u001F\u007F<>"'`\\]/g, '').trim();
		return value.length > MAX_VALUE_LENGTH ? '' : value;
	}

	/** Collect allowed parameters from a query string. Keeps original key case. */
	function fromQuery(search) {
		var found = [];
		var sp;
		try {
			sp = new URLSearchParams(search);
		} catch (e) {
			return found;
		}
		sp.forEach(function (value, key) {
			var lower = key.toLowerCase();
			if (allowed.indexOf(lower) === -1) {
				return;
			}
			for (var j = 0; j < found.length; j++) {
				if (found[j][0].toLowerCase() === lower) {
					return; // first occurrence wins
				}
			}
			var v = cleanValue(value);
			if (v !== '') {
				found.push([key, v]);
			}
		});
		return found;
	}

	function save(list) {
		try {
			window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(list));
		} catch (e) { /* storage blocked: ignore */ }
	}

	function load() {
		var raw;
		try {
			raw = window.sessionStorage.getItem(STORAGE_KEY);
		} catch (e) {
			return [];
		}
		if (!raw) {
			return [];
		}
		var parsed;
		try {
			parsed = JSON.parse(raw);
		} catch (e) {
			return [];
		}
		if (!Array.isArray(parsed)) {
			return [];
		}
		// Re-validate everything read back from storage.
		var out = [];
		for (var k = 0; k < parsed.length && out.length < allowed.length; k++) {
			var pair = parsed[k];
			if (!Array.isArray(pair) || pair.length !== 2 || typeof pair[0] !== 'string') {
				continue;
			}
			var key = pair[0];
			var val = cleanValue(pair[1]);
			if (allowed.indexOf(key.toLowerCase()) !== -1 && val !== '') {
				out.push([key, val]);
			}
		}
		return out;
	}

	// Parameters in the current URL win (latest campaign); otherwise use the stored ones.
	var tracked = fromQuery(window.location.search);
	if (tracked.length) {
		if (persist) {
			save(tracked);
		}
	} else if (persist) {
		tracked = load();
	}
	// Enforce the total length budget.
	var budget = MAX_TOTAL_LENGTH;
	var limited = [];
	for (var q = 0; q < tracked.length; q++) {
		var cost = encodeURIComponent(tracked[q][0]).length + encodeURIComponent(tracked[q][1]).length + 2;
		if (cost <= budget) {
			limited.push(tracked[q]);
			budget -= cost;
		}
	}
	tracked = limited;
	if (!tracked.length) {
		return;
	}

	function hasParam(sp, name) {
		var lower = name.toLowerCase();
		var present = false;
		sp.forEach(function (v, key) {
			if (key.toLowerCase() === lower) {
				present = true;
			}
		});
		return present;
	}

	function decorate(link) {
		if (!link || !link.getAttribute) {
			return;
		}
		var href = link.getAttribute('href');
		if (!href || href.charAt(0) === '#') {
			return;
		}
		if (link.classList && link.classList.contains(IGNORE_CLASS)) {
			return;
		}
		if (link.hasAttribute('download')) {
			return;
		}

		var url;
		try {
			url = new URL(href, window.location.href);
		} catch (e) {
			return;
		}
		if (url.protocol !== 'http:' && url.protocol !== 'https:') {
			return; // mailto:, tel:, javascript: and the like
		}
		if (hosts.indexOf(normalizeHost(url.hostname)) === -1) {
			return; // external link
		}
		if (EXCLUDED_PATH_RE.test(url.pathname)) {
			return;
		}

		var changed = false;
		for (var t = 0; t < tracked.length; t++) {
			if (!hasParam(url.searchParams, tracked[t][0])) {
				url.searchParams.append(tracked[t][0], tracked[t][1]);
				changed = true;
			}
		}
		if (changed) {
			link.setAttribute('href', url.href);
		}
	}

	function decorateAll(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var links = scope.querySelectorAll('a[href], area[href]');
		for (var l = 0; l < links.length; l++) {
			decorate(links[l]);
		}
		if (root && root.matches && root.matches('a[href], area[href]')) {
			decorate(root);
		}
	}

	// Safety net: decorate at click time too (covers links created after load).
	function onInteract(event) {
		var el = event.target;
		while (el && el !== document) {
			if (el.tagName === 'A' || el.tagName === 'AREA') {
				decorate(el);
				return;
			}
			el = el.parentNode;
		}
	}

	function start() {
		decorateAll(document);

		document.addEventListener('click', onInteract, true);
		document.addEventListener('auxclick', onInteract, true);
		document.addEventListener('contextmenu', onInteract, true);

		if (window.MutationObserver) {
			var pending = [];
			var scheduled = false;
			new MutationObserver(function (mutations) {
				for (var m = 0; m < mutations.length; m++) {
					var added = mutations[m].addedNodes;
					for (var a = 0; a < added.length; a++) {
						if (added[a].nodeType === 1) {
							pending.push(added[a]);
						}
					}
				}
				if (pending.length && !scheduled) {
					scheduled = true;
					window.setTimeout(function () {
						var batch = pending;
						pending = [];
						scheduled = false;
						for (var b = 0; b < batch.length; b++) {
							decorateAll(batch[b]);
						}
					}, 50);
				}
			}).observe(document.documentElement, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
