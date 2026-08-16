(function () {
	'use strict';

	var PAW_ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 12.75a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM8.25 8.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM15.75 8.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM19.5 12.75a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM12 12.75c-2.9 0-6 2.16-6 5.02 0 1.28 1.02 2.23 2.32 2.23.86 0 1.55-.32 2.3-.67.5-.24 1-.46 1.38-.46s.88.22 1.38.46c.75.35 1.44.67 2.3.67 1.3 0 2.32-.95 2.32-2.23 0-2.86-3.1-5.02-6-5.02Z"/></svg>';
	var CLINIC_ICON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M15 9h.01M15 13h.01M10.5 21v-4.5a1.5 1.5 0 0 1 3 0V21" /></svg>';

	var STEPS = ['patient', 'clinic', 'review'];

	document.addEventListener('DOMContentLoaded', function () {
		var panel = document.getElementById('ps-rxc-panel');

		if (!panel || typeof psRxcPanel === 'undefined') {
			return;
		}

		var state = {
			patients: parseJsonArray(panel.dataset.patients),
			clinics: parseJsonArray(panel.dataset.clinics),
			// Server-side directory search results currently shown in the
			// clinic list (own clinics from state.clinics when no search).
			clinicResults: null,
			items: psRxcPanel.items || {},
			current: null, // { key, patientId, clinicId }
			step: 'patient',
		};

		// The modal markup is normally printed once near the top of the cart
		// (inside the banner), but some themes wrap cart content in an
		// ancestor with `overflow`/`transform`, which silently breaks
		// `position: fixed` and makes the modal render off-screen or clipped.
		// Re-parenting it directly under <body> sidesteps that entirely.
		var itemModal = document.getElementById('ps-rxc-item-modal');
		if (itemModal && itemModal.parentElement !== document.body) {
			// Re-parenting drops it out of the .ps-rxc-scope wrapper it was
			// written in, so every scoped CSS rule below needs the class
			// re-applied directly on the modal itself.
			itemModal.classList.add('ps-rxc-scope');
			document.body.appendChild(itemModal);
		}

		// -- Open / configure a specific cart line -----------------------------

		// Delegated on `document` rather than bound per-button: WooCommerce
		// re-renders the cart items table via AJAX whenever the quantity
		// changes (and on some themes even on first load), which replaces
		// these buttons with brand-new DOM nodes that a direct
		// addEventListener() from page-load time would never be attached to.
		document.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-configure-item]');
			if (btn) {
				openItemModal(btn.dataset.configureItem);
			}
		});

		function el(id) {
			var node = document.getElementById(id);
			if (!node) {
				console.warn('[petscript-rx-checkout] missing element #' + id);
			}
			return node;
		}

		function openItemModal(itemKey) {
			var item = state.items[itemKey];

			if (!item) {
				console.warn('[petscript-rx-checkout] no item data for cart item key', itemKey);
				return;
			}

			var assignment = item.assignment || {};

			state.current = {
				key: itemKey,
				patientId: assignment.patient_id || null,
				clinicId: assignment.clinic_id || null,
			};

			var productEl = el('ps-rxc-item-modal-product');
			if (productEl) {
				productEl.textContent = item.name + (item.quantity > 1 ? ' × ' + item.quantity : '');
			}

			setSelectValue('ps-rxc-item-approval-method', assignment.approval_method);

			var errorBox = el('ps-rxc-item-general-error');
			if (errorBox) {
				hideError(errorBox);
			}

			patientSearchInput.value = '';
			clinicSearchInput.value = '';
			clinicZipInput.value = psRxcPanel.billingZip || '';
			state.clinicResults = null;
			showListView('patient');
			showListView('clinic');
			renderPatientList();
			renderClinicList();

			goToStep('patient');
			openModal(el('ps-rxc-item-modal'));
		}

		function setSelectValue(id, value) {
			var node = el(id);
			if (node) {
				node.value = value || '';
			}
		}

		// -- Modal open/close -------------------------------------------------

		document.querySelectorAll('[data-modal]').forEach(function (modal) {
			modal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
				btn.addEventListener('click', function () { closeModal(modal); });
			});
			modal.addEventListener('click', function (e) {
				if (e.target === modal) {
					closeModal(modal);
				}
			});
		});

		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') {
				return;
			}
			document.querySelectorAll('[data-modal]:not([hidden])').forEach(function (modal) { closeModal(modal); });
		});

		function openModal(modal) {
			if (!modal) {
				return;
			}
			modal.hidden = false;
			requestAnimationFrame(function () { modal.classList.add('is-open'); });
		}

		function closeModal(modal) {
			modal.classList.remove('is-open');
			window.setTimeout(function () { modal.hidden = true; }, 150);
		}

		// -- Step wizard (Patient → Veterinary Clinic → Review) -----------------

		var stepper = document.getElementById('ps-rxc-stepper');
		var footerCancel = document.querySelector('[data-footer-btn="cancel"]');
		var footerBack = document.querySelector('[data-footer-btn="back"]');
		var footerNextClinic = document.querySelector('[data-footer-btn="next-clinic"]');
		var footerNextReview = document.querySelector('[data-footer-btn="next-review"]');
		var footerSave = document.querySelector('[data-footer-btn="save"]');

		function goToStep(step) {
			state.step = step;
			var stepIndex = STEPS.indexOf(step);

			document.querySelectorAll('.ps-rxc-step').forEach(function (panelEl) {
				panelEl.hidden = panelEl.dataset.step !== step;
			});

			stepper.querySelectorAll('.ps-rxc-stepper__step').forEach(function (indicator) {
				var indicatorIndex = STEPS.indexOf(indicator.dataset.stepIndicator);
				indicator.classList.toggle('is-active', indicatorIndex === stepIndex);
				indicator.classList.toggle('is-done', indicatorIndex < stepIndex);
				indicator.querySelector('.ps-rxc-stepper__circle').textContent =
					indicatorIndex < stepIndex ? '✓' : String(indicatorIndex + 1);
			});

			footerCancel.hidden = step !== 'patient';
			footerBack.hidden = step === 'patient';
			footerNextClinic.hidden = step !== 'patient';
			footerNextReview.hidden = step !== 'clinic';
			footerSave.hidden = step !== 'review';

			if (step === 'clinic') {
				renderSelectedSummary('ps-rxc-clinic-step-patient-summary', 'patient', state.current.patientId, 'patient');
			}

			if (step === 'review') {
				renderReview();
			}

			hideError(el('ps-rxc-item-general-error'));
		}

		footerBack.addEventListener('click', function () {
			goToStep(state.step === 'review' ? 'clinic' : 'patient');
		});

		footerNextClinic.addEventListener('click', function () {
			if (!state.current.patientId) {
				showError(el('ps-rxc-item-general-error'), psRxcPanel.i18n.selectPatientFirst);
				return;
			}
			goToStep('clinic');
		});

		footerNextReview.addEventListener('click', function () {
			if (!state.current.clinicId) {
				showError(el('ps-rxc-item-general-error'), psRxcPanel.i18n.selectClinicFirst);
				return;
			}
			goToStep('review');
		});

		document.querySelectorAll('[data-goto-step]').forEach(function (btn) {
			btn.addEventListener('click', function () { goToStep(btn.dataset.gotoStep); });
		});

		document.querySelectorAll('[data-show-form]').forEach(function (btn) {
			btn.addEventListener('click', function () { showFormView(btn.dataset.showForm); });
		});

		function findClinic(clinicId) {
			var pool = state.clinics.concat(state.clinicResults || []);
			return pool.find(function (c) { return String(c.id) === String(clinicId); });
		}

		function renderReview() {
			var patient = state.patients.find(function (p) { return String(p.id) === String(state.current.patientId); });
			var clinic = findClinic(state.current.clinicId);

			el('ps-rxc-review-patient').innerHTML = patient ? entityRowHtml(PAW_ICON, patient.name, [patient.species, patient.breed]) : '';
			el('ps-rxc-review-clinic').innerHTML = clinic ? entityRowHtml(CLINIC_ICON, clinic.name, [vetName(clinic), clinic.city, clinic.state]) : '';
		}

		function vetName(clinic) {
			var parts = [];
			if (clinic.vet_first_name) { parts.push(clinic.vet_first_name); }
			if (clinic.vet_last_name) { parts.push(clinic.vet_last_name); }
			return parts.length ? 'Dr. ' + parts.join(' ') : '';
		}

		function entityRowHtml(icon, title, metaParts) {
			return '<div class="ps-rxc-list-row ps-rxc-list-row--plain">' +
				'<span class="ps-rxc-avatar">' + icon + '</span>' +
				'<span class="ps-rxc-list-row__body">' +
				'<span class="ps-rxc-list-row__title">' + escapeHtml(title) + '</span>' +
				'<span class="ps-rxc-list-row__meta">' + escapeHtml(metaParts.filter(Boolean).join(' · ')) + '</span>' +
				'</span></div>';
		}

		function renderSelectedSummary(containerId, entityType, entityId, changeGoesToStep) {
			var container = el(containerId);
			if (!container) {
				return;
			}

			var entity = entityType === 'patient'
				? state.patients.find(function (e) { return String(e.id) === String(entityId); })
				: findClinic(entityId);

			if (!entity) {
				container.hidden = true;
				container.innerHTML = '';
				return;
			}

			container.hidden = false;
			var icon = entityType === 'patient' ? PAW_ICON : CLINIC_ICON;
			var meta = entityType === 'patient' ? [entity.species, entity.breed] : [vetName(entity), entity.city, entity.state];
			var label = entityType === 'patient' ? psRxcPanel.i18n.selectedPatient : psRxcPanel.i18n.selectedClinic;

			container.innerHTML =
				'<span class="ps-rxc-avatar">' + icon + '</span>' +
				'<span class="ps-rxc-selected-summary__body">' +
				'<span class="ps-rxc-selected-summary__label">' + label + '</span>' +
				'<span class="ps-rxc-list-row__title">' + escapeHtml(entity.name) + '</span>' +
				'<span class="ps-rxc-list-row__meta">' + escapeHtml(meta.filter(Boolean).join(' · ')) + '</span>' +
				'</span>' +
				'<button type="button" class="ps-rxc-link-btn" data-change="' + entityType + '">' + psRxcPanel.i18n.change + '</button>';

			container.querySelector('[data-change]').addEventListener('click', function () {
				if (changeGoesToStep) {
					goToStep(changeGoesToStep);
					return;
				}

				if (entityType === 'patient') {
					state.current.patientId = null;
					renderPatientList();
				} else {
					state.current.clinicId = null;
					renderClinicList();
				}
			});
		}

		// -- List / form view switching (per section, within the one modal) ----

		function showListView(section) {
			document.getElementById('ps-rxc-item-' + section + '-list-view').hidden = false;
			document.getElementById('ps-rxc-item-' + section + '-form-view').hidden = true;
		}

		function showFormView(section) {
			document.getElementById('ps-rxc-item-' + section + '-list-view').hidden = true;
			document.getElementById('ps-rxc-item-' + section + '-form-view').hidden = false;
		}

		document.getElementById('ps-rxc-item-patient-back').addEventListener('click', function () { showListView('patient'); });
		document.getElementById('ps-rxc-item-clinic-back').addEventListener('click', function () { showListView('clinic'); });

		// -- Patient list ------------------------------------------------------

		var patientSearchInput = document.getElementById('ps-rxc-item-patient-search');

		patientSearchInput.addEventListener('input', renderPatientList);

		function renderPatientList() {
			var container = document.getElementById('ps-rxc-item-patient-list');
			var query = patientSearchInput.value.trim().toLowerCase();
			var list = query
				? state.patients.filter(function (p) { return p.name.toLowerCase().indexOf(query) !== -1; })
				: state.patients;

			container.innerHTML = '';

			if (list.length === 0) {
				container.innerHTML = '<p class="ps-rxc-empty">' + (query ? psRxcPanel.i18n.noResults : psRxcPanel.i18n.noPatients) + '</p>';
			}

			list.forEach(function (patient) {
				var row = document.createElement('button');
				row.type = 'button';
				row.className = 'ps-rxc-list-row' + (String(state.current.patientId) === String(patient.id) ? ' is-selected' : '');
				row.innerHTML =
					'<span class="ps-rxc-list-row__radio"></span>' +
					'<span class="ps-rxc-avatar">' + PAW_ICON + '</span>' +
					'<span class="ps-rxc-list-row__body">' +
					'<span class="ps-rxc-list-row__title">' + escapeHtml(patient.name) + '</span>' +
					'<span class="ps-rxc-list-row__meta">' + escapeHtml([patient.species, patient.breed].filter(Boolean).join(' · ')) + '</span>' +
					'</span>';
				row.addEventListener('click', function () {
					state.current.patientId = patient.id;
					renderPatientList();
				});
				container.appendChild(row);
			});

			renderSelectedSummary('ps-rxc-patient-selected-summary', 'patient', state.current.patientId);
		}

		// -- Clinic directory search (server-side) ----------------------------

		var clinicSearchInput = document.getElementById('ps-rxc-item-clinic-search');
		var clinicZipInput = document.getElementById('ps-rxc-item-clinic-zip');
		var clinicSearchTimer = null;
		var clinicSearchSeq = 0;

		clinicSearchInput.addEventListener('input', scheduleClinicSearch);
		clinicZipInput.addEventListener('input', scheduleClinicSearch);

		function scheduleClinicSearch() {
			if (clinicSearchTimer) {
				window.clearTimeout(clinicSearchTimer);
			}
			clinicSearchTimer = window.setTimeout(runClinicSearch, 300);
		}

		function runClinicSearch() {
			var query = clinicSearchInput.value.trim();
			var zip = clinicZipInput.value.trim();

			// Nothing typed: fall back to the customer's own saved clinics.
			if (query === '' && zip === '') {
				state.clinicResults = null;
				renderClinicList();
				return;
			}

			var container = document.getElementById('ps-rxc-item-clinic-list');
			container.innerHTML = '<p class="ps-rxc-empty">' + psRxcPanel.i18n.searching + '</p>';

			var seq = ++clinicSearchSeq;

			post({ action: 'ps_rxc_search_clinics', nonce: psRxcPanel.nonce, q: query, zip: zip }).then(function (response) {
				// A slower earlier request must never overwrite a newer one.
				if (seq !== clinicSearchSeq) {
					return;
				}

				state.clinicResults = (response.success && response.data && response.data.clinics) ? response.data.clinics : [];
				renderClinicList();
			}).catch(function () {
				if (seq !== clinicSearchSeq) {
					return;
				}
				state.clinicResults = [];
				renderClinicList();
			});
		}

		function renderClinicList() {
			var container = document.getElementById('ps-rxc-item-clinic-list');
			var list = state.clinicResults !== null ? state.clinicResults : state.clinics;

			container.innerHTML = '';

			if (list.length === 0) {
				container.innerHTML = '<p class="ps-rxc-empty">' + (state.clinicResults !== null ? psRxcPanel.i18n.noResults : psRxcPanel.i18n.noClinics) + '</p>';
			}

			list.forEach(function (clinic) {
				var row = document.createElement('button');
				row.type = 'button';
				row.className = 'ps-rxc-list-row' + (String(state.current.clinicId) === String(clinic.id) ? ' is-selected' : '');
				row.innerHTML =
					'<span class="ps-rxc-list-row__radio"></span>' +
					'<span class="ps-rxc-avatar">' + CLINIC_ICON + '</span>' +
					'<span class="ps-rxc-list-row__body">' +
					'<span class="ps-rxc-list-row__title">' + escapeHtml(clinic.name) + '</span>' +
					'<span class="ps-rxc-list-row__meta">' + escapeHtml([vetName(clinic), clinic.city, clinic.state, clinic.postal_code].filter(Boolean).join(' · ')) + '</span>' +
					'</span>';
				row.addEventListener('click', function () {
					state.current.clinicId = clinic.id;
					// Directory picks aren't in the customer's own list yet;
					// keep them resolvable for review/summary lookups.
					if (!state.clinics.some(function (c) { return String(c.id) === String(clinic.id); })) {
						state.clinics.push(clinic);
					}
					renderClinicList();
				});
				container.appendChild(row);
			});

			renderSelectedSummary('ps-rxc-clinic-selected-summary', 'clinic', state.current.clinicId);
		}

		// -- Google Places autocomplete on the clinic address field ------------

		if (psRxcPanel.hasPlaces && window.google && google.maps && google.maps.places) {
			attachClinicAddressAutocomplete();
		}

		function attachClinicAddressAutocomplete() {
			var addressInput = document.getElementById('ps-rxc-ic-address');

			if (!addressInput) {
				return;
			}

			var autocomplete = new google.maps.places.Autocomplete(addressInput, {
				types: ['address'],
				componentRestrictions: { country: 'us' },
				fields: ['address_components'],
			});

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
				document.getElementById('ps-rxc-ic-city').value = parts.locality || parts.sublocality_level_1;
				document.getElementById('ps-rxc-ic-state').value = parts.administrative_area_level_1;
				document.getElementById('ps-rxc-ic-postal').value = parts.postal_code;
				document.getElementById('ps-rxc-ic-country').value = parts.country;
			});

			// The Places dropdown (.pac-container) is appended to <body> and
			// must stack above the modal backdrop.
			var style = document.createElement('style');
			style.textContent = '.pac-container { z-index: 100001 !important; }';
			document.head.appendChild(style);
		}

		// -- Save patient / clinic (AJAX) — adds to catalog, auto-selects -------

		document.getElementById('ps-rxc-item-patient-save').addEventListener('click', function () {
			var form = document.getElementById('ps-rxc-item-patient-form');
			var errorBox = document.getElementById('ps-rxc-item-patient-form-error');

			if (!form.name.value.trim() || !form.species.value.trim()) {
				showError(errorBox, psRxcPanel.i18n.nameSpeciesRequired);
				return;
			}

			var data = { action: 'ps_rxc_save_patient', nonce: psRxcPanel.nonce };
			new FormData(form).forEach(function (value, key) { data[key] = value; });

			var button = this;
			toggleLoading(button, true);
			post(data).then(function (response) {
				toggleLoading(button, false);

				if (!response.success) {
					showError(errorBox, response.data && response.data.message);
					return;
				}

				hideError(errorBox);
				state.patients.push(response.data.patient);
				state.current.patientId = response.data.patient.id;
				form.reset();
				renderPatientList();
				showListView('patient');
			}).catch(function () {
				toggleLoading(button, false);
				showError(errorBox, psRxcPanel.i18n.networkError);
			});
		});

		document.getElementById('ps-rxc-item-clinic-save').addEventListener('click', function () {
			var form = document.getElementById('ps-rxc-item-clinic-form');
			var errorBox = document.getElementById('ps-rxc-item-clinic-form-error');

			if (!form.name.value.trim()) {
				showError(errorBox, psRxcPanel.i18n.clinicNameRequired);
				return;
			}

			if (!form.vet_first_name.value.trim() || !form.vet_last_name.value.trim()) {
				showError(errorBox, psRxcPanel.i18n.vetNameRequired);
				return;
			}

			var data = { action: 'ps_rxc_save_clinic', nonce: psRxcPanel.nonce };
			new FormData(form).forEach(function (value, key) { data[key] = value; });

			var button = this;
			toggleLoading(button, true);
			post(data).then(function (response) {
				toggleLoading(button, false);

				if (!response.success) {
					showError(errorBox, response.data && response.data.message);
					return;
				}

				hideError(errorBox);
				state.clinics.push(response.data.clinic);
				state.current.clinicId = response.data.clinic.id;
				// Show the customer's list (with the new clinic auto-selected)
				// rather than stale search results that won't contain it.
				state.clinicResults = null;
				clinicSearchInput.value = '';
				form.reset();
				renderClinicList();
				showListView('clinic');
			}).catch(function () {
				toggleLoading(button, false);
				showError(errorBox, psRxcPanel.i18n.networkError);
			});
		});

		// -- Save pet / vet info (final step) ---------------------------------

		footerSave.addEventListener('click', function () {
			var errorBox = document.getElementById('ps-rxc-item-general-error');
			var approvalMethod = document.getElementById('ps-rxc-item-approval-method').value;

			if (!state.current.patientId || !state.current.clinicId || !approvalMethod) {
				showError(errorBox, psRxcPanel.i18n.selectFields);
				return;
			}

			var data = {
				action: 'ps_rxc_save_assignment',
				nonce: psRxcPanel.nonce,
				cart_item_key: state.current.key,
				patient_id: state.current.patientId,
				clinic_id: state.current.clinicId,
				approval_method: approvalMethod,
				// Ship-to/bill-to are no longer asked in the UI — always the
				// customer per product decision, kept in the payload since
				// Pharmacy's contract still has the fields (nullable there).
				ship_to_type: 'patient',
				bill_to_type: 'patient',
			};

			var button = footerSave;
			toggleLoading(button, true);
			post(data).then(function (response) {
				toggleLoading(button, false);

				if (!response.success) {
					showError(errorBox, response.data && response.data.message);
					return;
				}

				// Always reload rather than patching the DOM in place: this
				// cart page has WooCommerce replacing row/column nodes via
				// its own AJAX behind our back, AND separately caches cart
				// fragments client-side (wc-cart-fragments) that can
				// silently overwrite an in-place patch moments later. A full
				// reload is the only way to guarantee the customer always
				// sees the correct, server-rendered state.
				window.location.reload();
			}).catch(function () {
				toggleLoading(button, false);
				showError(errorBox, psRxcPanel.i18n.networkError);
			});
		});

		// -- Helpers --------------------------------------------------------------

		function post(data) {
			return fetch(psRxcPanel.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams(data).toString(),
			}).then(function (response) { return response.json(); });
		}

		function showError(box, message) {
			box.textContent = message || psRxcPanel.i18n.genericError;
			box.hidden = false;
		}

		function hideError(box) {
			box.hidden = true;
		}

		function toggleLoading(button, isLoading) {
			button.disabled = isLoading;
			button.classList.toggle('is-loading', isLoading);
		}

		function escapeHtml(str) {
			var div = document.createElement('div');
			div.textContent = str || '';
			return div.innerHTML;
		}

		function parseJsonArray(raw) {
			try {
				var parsed = JSON.parse(raw || '[]');
				return Array.isArray(parsed) ? parsed : [];
			} catch (e) {
				return [];
			}
		}
	});
})();
