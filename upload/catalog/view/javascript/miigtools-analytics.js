/**
 * Miigtools GA4 ecommerce helpers (requires gtag from header).
 */
(function (window, document) {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	window.miigGa = {
		event: function (name, params) {
			if (typeof window.gtag !== 'function' || !name) {
				return;
			}

			window.gtag('event', name, params || {});
		},

		fromAjax: function (json) {
			if (!json || !json.ga4 || !json.ga4.event) {
				return;
			}

			this.event(json.ga4.event, json.ga4.params || {});
		},

		fromPayload: function (payload) {
			if (!payload || !payload.event) {
				return;
			}

			this.event(payload.event, payload.params || {});
		}
	};

	ready(function () {
		var nodes = document.querySelectorAll('script[type="application/json"][data-miig-ga4]');

		for (var i = 0; i < nodes.length; i++) {
			try {
				window.miigGa.fromPayload(JSON.parse(nodes[i].textContent || '{}'));
			} catch (e) {
				// ignore malformed payloads
			}
		}

		var listRoot = document.querySelector('[data-miig-ga4-list]');

		if (listRoot) {
			var itemNodes = listRoot.querySelectorAll('[data-miig-ga4-item]');
			var items = [];

			for (var j = 0; j < itemNodes.length; j++) {
				try {
					var item = JSON.parse(itemNodes[j].getAttribute('data-miig-ga4-item') || '{}');

					if (item.item_id || item.item_name) {
						item.index = j + 1;
						items.push(item);
					}
				} catch (e2) {
					// skip
				}
			}

			if (items.length) {
				var listName = listRoot.getAttribute('data-miig-ga4-list') || 'Catalog';
				var value = 0;

				for (var k = 0; k < items.length; k++) {
					value += (parseFloat(items[k].price) || 0) * (parseInt(items[k].quantity, 10) || 1);
				}

				window.miigGa.event('view_item_list', {
					item_list_name: listName,
					currency: listRoot.getAttribute('data-miig-ga4-currency') || undefined,
					value: Math.round(value * 100) / 100,
					items: items
				});
			}
		}
	});
})(window, document);
