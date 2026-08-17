(function () {
	'use strict';

	// Google Places autocomplete on the WooCommerce (classic) checkout
	// street address fields. Selecting a suggestion fills street, city,
	// state, ZIP, and country so nothing address-related is hand-typed.
	//
	// This site also runs other plugins that load the Google Maps JS API
	// on their own (address-autocomplete plugins). Loading
	// maps.googleapis.com/maps/api/js a second time re-registers its Web
	// Components (<gmp-place-autocomplete> etc.) and throws
	// "Element with name ... already defined" — and can leave OUR
	// autocomplete half-initialized. So: never assume we're the one
	// loading it. Check for an existing tag/instance first, and either
	// reuse it or load it ourselves — never both.
	function ensureGoogleMaps(apiKey, callback) {
		if (window.google && window.google.maps && window.google.maps.places) {
			callback();
			return;
		}

		var existing = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');

		if (existing) {
			waitForGoogleMaps(callback);
			return;
		}

		var script = document.createElement('script');
		script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&libraries=places';
		script.async = true;
		script.onload = callback;
		document.head.appendChild(script);
	}

	function waitForGoogleMaps(callback, attemptsLeft) {
		attemptsLeft = attemptsLeft === undefined ? 40 : attemptsLeft; // ~6s at 150ms

		if (window.google && window.google.maps && window.google.maps.places) {
			callback();
			return;
		}

		if (attemptsLeft <= 0) {
			return;
		}

		window.setTimeout(function () {
			waitForGoogleMaps(callback, attemptsLeft - 1);
		}, 150);
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof psRxcCheckoutAddress === 'undefined' || !psRxcCheckoutAddress.apiKey) {
			return;
		}

		ensureGoogleMaps(psRxcCheckoutAddress.apiKey, function () {
			attach('billing');
			attach('shipping');
		});

		function attach(prefix) {
			var addressInput = document.getElementById(prefix + '_address_1');

			if (!addressInput) {
				return;
			}

			// Another plugin's autocomplete may already be bound to this
			// same field. Don't double-attach.
			if (addressInput.dataset.psRxcAutocompleteAttached) {
				return;
			}
			addressInput.dataset.psRxcAutocompleteAttached = '1';

			var autocomplete = new google.maps.places.Autocomplete(addressInput, {
				types: ['address'],
				componentRestrictions: { country: 'us' },
				fields: ['address_components'],
			});

			// Browsers see autocomplete="street-address"/"address-line1" and
			// paint their own suggestion list on top of Google's. "off" is
			// ignored by Chrome, "new-password" reliably suppresses it.
			addressInput.setAttribute('autocomplete', 'new-password');

			autocomplete.addListener('place_changed', function () {
				var place = autocomplete.getPlace();

				if (!place || !place.address_components) {
					return;
				}

				var parts = { street_number: '', route: '', locality: '', sublocality_level_1: '', administrative_area_level_1: '', postal_code: '', country: '' };

				place.address_components.forEach(function (component) {
					component.types.forEach(function (type) {
						if (Object.prototype.hasOwnProperty.call(parts, type)) {
							parts[type] = type === 'administrative_area_level_1' || type === 'country'
								? component.short_name
								: component.long_name;
						}
					});
				});

				addressInput.value = (parts.street_number + ' ' + parts.route).trim();
				setField(prefix + '_city', parts.locality || parts.sublocality_level_1);
				setField(prefix + '_postcode', parts.postal_code);
				setField(prefix + '_country', parts.country);
				// Country must land before state: WooCommerce swaps the state
				// field's options based on the selected country.
				window.setTimeout(function () {
					setField(prefix + '_state', parts.administrative_area_level_1);
				}, 100);
			});
		}

		function setField(id, value) {
			var field = document.getElementById(id);

			if (!field || !value) {
				return;
			}

			field.value = value;

			var event = document.createEvent('HTMLEvents');
			event.initEvent('change', true, false);
			field.dispatchEvent(event);

			if (window.jQuery) {
				window.jQuery(field).trigger('change');
			}
		}
	});
})();
