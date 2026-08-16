(function () {
	'use strict';

	// Google Places autocomplete on the WooCommerce (classic) checkout
	// street address fields. Selecting a suggestion fills street, city,
	// state, ZIP, and country so nothing address-related is hand-typed.

	document.addEventListener('DOMContentLoaded', function () {
		if (!window.google || !google.maps || !google.maps.places) {
			return;
		}

		attach('billing');
		attach('shipping');

		function attach(prefix) {
			var addressInput = document.getElementById(prefix + '_address_1');

			if (!addressInput) {
				return;
			}

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

			// WooCommerce watches these fields (select2 dropdowns included)
			// to recalculate shipping/taxes — plain .value changes are
			// invisible to it without a change event.
			var event = document.createEvent('HTMLEvents');
			event.initEvent('change', true, false);
			field.dispatchEvent(event);

			if (window.jQuery) {
				window.jQuery(field).trigger('change');
			}
		}
	});
})();
