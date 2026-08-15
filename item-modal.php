<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="ps-rxc-item-modal" class="ps-rxc-modal-backdrop" hidden data-modal>
    <div class="ps-rxc-modal ps-rxc-modal--lg" role="dialog" aria-modal="true" aria-labelledby="ps-rxc-item-modal-title">
        <div class="ps-rxc-modal__header">
            <div>
                <h3 class="ps-rxc-modal__title" id="ps-rxc-item-modal-title"><?php esc_html_e('Prescription information', 'petscript-rx-checkout'); ?></h3>
                <p class="ps-rxc-modal__product" id="ps-rxc-item-modal-product"></p>
            </div>
            <button type="button" class="ps-rxc-modal__close" data-close-modal aria-label="<?php esc_attr_e('Close', 'petscript-rx-checkout'); ?>">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>

        <div class="ps-rxc-stepper" id="ps-rxc-stepper">
            <div class="ps-rxc-stepper__step" data-step-indicator="patient">
                <span class="ps-rxc-stepper__circle">1</span>
                <span class="ps-rxc-stepper__label"><?php esc_html_e('Patient', 'petscript-rx-checkout'); ?></span>
            </div>
            <div class="ps-rxc-stepper__line"></div>
            <div class="ps-rxc-stepper__step" data-step-indicator="clinic">
                <span class="ps-rxc-stepper__circle">2</span>
                <span class="ps-rxc-stepper__label"><?php esc_html_e('Veterinary Clinic', 'petscript-rx-checkout'); ?></span>
            </div>
            <div class="ps-rxc-stepper__line"></div>
            <div class="ps-rxc-stepper__step" data-step-indicator="review">
                <span class="ps-rxc-stepper__circle">3</span>
                <span class="ps-rxc-stepper__label"><?php esc_html_e('Review', 'petscript-rx-checkout'); ?></span>
            </div>
        </div>

        <div class="ps-rxc-modal__body">

            <div id="ps-rxc-item-general-error" class="ps-rxc-alert ps-rxc-alert--error" hidden></div>

            <!-- ================= STEP 1: Patient ================= -->
            <div class="ps-rxc-step" data-step="patient">

                <div id="ps-rxc-item-patient-list-view">
                    <h4 class="ps-rxc-step__title"><?php esc_html_e('Select a patient', 'petscript-rx-checkout'); ?></h4>
                    <p class="ps-rxc-step__subtitle"><?php esc_html_e('Choose an existing patient or add a new one.', 'petscript-rx-checkout'); ?></p>

                    <div class="ps-rxc-list-toolbar">
                        <div class="ps-rxc-search-wrap">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m17 17-3.5-3.5M15.33 9.17a6.17 6.17 0 1 1-12.33 0 6.17 6.17 0 0 1 12.33 0Z"/></svg>
                            <input type="search" id="ps-rxc-item-patient-search" class="ps-rxc-input"
                                   placeholder="<?php esc_attr_e('Search patients by name…', 'petscript-rx-checkout'); ?>" />
                        </div>
                        <button type="button" class="ps-rxc-btn ps-rxc-btn--secondary ps-rxc-btn--sm" data-show-form="patient">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <?php esc_html_e('Add Patient', 'petscript-rx-checkout'); ?>
                        </button>
                    </div>

                    <div id="ps-rxc-item-patient-list" class="ps-rxc-list"></div>

                    <div id="ps-rxc-patient-selected-summary" class="ps-rxc-selected-summary" hidden></div>
                </div>

                <div id="ps-rxc-item-patient-form-view" hidden>
                    <p class="ps-rxc-step__subtitle"><?php esc_html_e('Add a new patient. You can add more than one before you continue.', 'petscript-rx-checkout'); ?></p>
                    <div id="ps-rxc-item-patient-form-error" class="ps-rxc-alert ps-rxc-alert--error" hidden></div>
                    <form id="ps-rxc-item-patient-form" novalidate>
                        <div class="ps-rxc-grid-2">
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-name"><?php esc_html_e('Name', 'petscript-rx-checkout'); ?> <span class="req">*</span></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ip-name" name="name" required />
                            </div>
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-species"><?php esc_html_e('Species', 'petscript-rx-checkout'); ?> <span class="req">*</span></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ip-species" name="species" required />
                            </div>
                        </div>
                        <div class="ps-rxc-grid-2">
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-breed"><?php esc_html_e('Breed', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ip-breed" name="breed" />
                            </div>
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-sex"><?php esc_html_e('Sex', 'petscript-rx-checkout'); ?></label>
                                <select class="ps-rxc-select" id="ps-rxc-ip-sex" name="sex">
                                    <option value=""><?php esc_html_e('—', 'petscript-rx-checkout'); ?></option>
                                    <option value="male"><?php esc_html_e('Male', 'petscript-rx-checkout'); ?></option>
                                    <option value="female"><?php esc_html_e('Female', 'petscript-rx-checkout'); ?></option>
                                    <option value="unknown"><?php esc_html_e('Unknown', 'petscript-rx-checkout'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ps-rxc-grid-2">
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-weight"><?php esc_html_e('Weight (lb)', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="number" step="0.1" min="0" id="ps-rxc-ip-weight" name="weight_lbs" />
                            </div>
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ip-birthdate"><?php esc_html_e('Date of birth', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="date" id="ps-rxc-ip-birthdate" name="birthdate" />
                            </div>
                        </div>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ip-medications"><?php esc_html_e('Current medications', 'petscript-rx-checkout'); ?></label>
                            <textarea class="ps-rxc-textarea" id="ps-rxc-ip-medications" name="medications"></textarea>
                        </div>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ip-allergies"><?php esc_html_e('Allergies', 'petscript-rx-checkout'); ?></label>
                            <textarea class="ps-rxc-textarea" id="ps-rxc-ip-allergies" name="allergies"></textarea>
                        </div>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ip-conditions"><?php esc_html_e('Pre-existing conditions', 'petscript-rx-checkout'); ?></label>
                            <textarea class="ps-rxc-textarea" id="ps-rxc-ip-conditions" name="pre_existing_conditions"></textarea>
                        </div>
                    </form>
                    <div class="ps-rxc-section__actions">
                        <button type="button" id="ps-rxc-item-patient-back" class="ps-rxc-btn ps-rxc-btn--secondary ps-rxc-btn--sm"><?php esc_html_e('Back to list', 'petscript-rx-checkout'); ?></button>
                        <button type="button" id="ps-rxc-item-patient-save" class="ps-rxc-btn ps-rxc-btn--primary ps-rxc-btn--sm"><?php esc_html_e('Save patient', 'petscript-rx-checkout'); ?></button>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 2: Clinic ================= -->
            <div class="ps-rxc-step" data-step="clinic" hidden>

                <div id="ps-rxc-item-clinic-list-view">
                    <h4 class="ps-rxc-step__title"><?php esc_html_e('Select a veterinary clinic', 'petscript-rx-checkout'); ?></h4>
                    <p class="ps-rxc-step__subtitle"><?php esc_html_e('Choose the clinic where this patient receives care.', 'petscript-rx-checkout'); ?></p>

                    <div class="ps-rxc-list-toolbar">
                        <div class="ps-rxc-search-wrap">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m17 17-3.5-3.5M15.33 9.17a6.17 6.17 0 1 1-12.33 0 6.17 6.17 0 0 1 12.33 0Z"/></svg>
                            <input type="search" id="ps-rxc-item-clinic-search" class="ps-rxc-input"
                                   placeholder="<?php esc_attr_e('Search clinics by name…', 'petscript-rx-checkout'); ?>" />
                        </div>
                        <button type="button" class="ps-rxc-btn ps-rxc-btn--secondary ps-rxc-btn--sm" data-show-form="clinic">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <?php esc_html_e('Add Clinic', 'petscript-rx-checkout'); ?>
                        </button>
                    </div>

                    <div id="ps-rxc-item-clinic-list" class="ps-rxc-list"></div>

                    <div class="ps-rxc-selected-stack">
                        <div id="ps-rxc-clinic-step-patient-summary" class="ps-rxc-selected-summary"></div>
                        <div id="ps-rxc-clinic-selected-summary" class="ps-rxc-selected-summary" hidden></div>
                    </div>
                </div>

                <div id="ps-rxc-item-clinic-form-view" hidden>
                    <p class="ps-rxc-step__subtitle"><?php esc_html_e('Add a new clinic. You can add more than one before you continue.', 'petscript-rx-checkout'); ?></p>
                    <div id="ps-rxc-item-clinic-form-error" class="ps-rxc-alert ps-rxc-alert--error" hidden></div>
                    <form id="ps-rxc-item-clinic-form" novalidate>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ic-name"><?php esc_html_e('Clinic name', 'petscript-rx-checkout'); ?> <span class="req">*</span></label>
                            <input class="ps-rxc-input" type="text" id="ps-rxc-ic-name" name="name" required />
                        </div>
                        <div class="ps-rxc-grid-2">
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ic-phone"><?php esc_html_e('Phone', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ic-phone" name="phone" />
                            </div>
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ic-country"><?php esc_html_e('Country (2-letter code)', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="text" maxlength="2" id="ps-rxc-ic-country" name="country" placeholder="US" />
                            </div>
                        </div>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ic-address"><?php esc_html_e('Address', 'petscript-rx-checkout'); ?></label>
                            <input class="ps-rxc-input" type="text" id="ps-rxc-ic-address" name="address" />
                        </div>
                        <div class="ps-rxc-grid-2">
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ic-city"><?php esc_html_e('City', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ic-city" name="city" />
                            </div>
                            <div class="ps-rxc-field">
                                <label class="ps-rxc-label" for="ps-rxc-ic-state"><?php esc_html_e('State', 'petscript-rx-checkout'); ?></label>
                                <input class="ps-rxc-input" type="text" id="ps-rxc-ic-state" name="state" />
                            </div>
                        </div>
                        <div class="ps-rxc-field">
                            <label class="ps-rxc-label" for="ps-rxc-ic-postal"><?php esc_html_e('Postal code', 'petscript-rx-checkout'); ?></label>
                            <input class="ps-rxc-input" type="text" id="ps-rxc-ic-postal" name="postal_code" />
                        </div>
                    </form>
                    <div class="ps-rxc-section__actions">
                        <button type="button" id="ps-rxc-item-clinic-back" class="ps-rxc-btn ps-rxc-btn--secondary ps-rxc-btn--sm"><?php esc_html_e('Back to list', 'petscript-rx-checkout'); ?></button>
                        <button type="button" id="ps-rxc-item-clinic-save" class="ps-rxc-btn ps-rxc-btn--primary ps-rxc-btn--sm"><?php esc_html_e('Save clinic', 'petscript-rx-checkout'); ?></button>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 3: Review ================= -->
            <div class="ps-rxc-step" data-step="review" hidden>
                <h4 class="ps-rxc-step__title"><?php esc_html_e('Review your selection', 'petscript-rx-checkout'); ?></h4>
                <p class="ps-rxc-step__subtitle"><?php esc_html_e('Please confirm the information before saving.', 'petscript-rx-checkout'); ?></p>

                <div class="ps-rxc-review-grid">
                    <div class="ps-rxc-review-card">
                        <p class="ps-rxc-review-card__label"><?php esc_html_e('Patient', 'petscript-rx-checkout'); ?></p>
                        <div id="ps-rxc-review-patient"></div>
                        <button type="button" class="ps-rxc-link-btn" data-goto-step="patient"><?php esc_html_e('Edit', 'petscript-rx-checkout'); ?></button>
                    </div>
                    <div class="ps-rxc-review-card">
                        <p class="ps-rxc-review-card__label"><?php esc_html_e('Veterinary Clinic', 'petscript-rx-checkout'); ?></p>
                        <div id="ps-rxc-review-clinic"></div>
                        <button type="button" class="ps-rxc-link-btn" data-goto-step="clinic"><?php esc_html_e('Edit', 'petscript-rx-checkout'); ?></button>
                    </div>
                </div>

                <div class="ps-rxc-section">
                    <p class="ps-rxc-section__title"><?php esc_html_e('Preferences', 'petscript-rx-checkout'); ?></p>
                    <div class="ps-rxc-field">
                        <label class="ps-rxc-label" for="ps-rxc-item-approval-method"><?php esc_html_e('Approval method', 'petscript-rx-checkout'); ?></label>
                        <select id="ps-rxc-item-approval-method" class="ps-rxc-select">
                            <option value=""><?php esc_html_e('Select…', 'petscript-rx-checkout'); ?></option>
                            <option value="contact_clinic"><?php esc_html_e('Contact the clinic', 'petscript-rx-checkout'); ?></option>
                            <option value="mail_prescription"><?php esc_html_e('Mail/fax the prescription', 'petscript-rx-checkout'); ?></option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <div class="ps-rxc-modal__footer">
            <button type="button" class="ps-rxc-btn ps-rxc-btn--secondary" data-close-modal data-footer-btn="cancel"><?php esc_html_e('Cancel', 'petscript-rx-checkout'); ?></button>
            <button type="button" class="ps-rxc-btn ps-rxc-btn--secondary" data-footer-btn="back" hidden><?php esc_html_e('Back', 'petscript-rx-checkout'); ?></button>
            <button type="button" class="ps-rxc-btn ps-rxc-btn--primary" data-footer-btn="next-clinic"><?php esc_html_e('Next: Select Clinic', 'petscript-rx-checkout'); ?></button>
            <button type="button" class="ps-rxc-btn ps-rxc-btn--primary" data-footer-btn="next-review" hidden><?php esc_html_e('Next: Review', 'petscript-rx-checkout'); ?></button>
            <button type="button" id="ps-rxc-item-confirm" class="ps-rxc-btn ps-rxc-btn--primary" data-footer-btn="save" hidden>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v-3m0 0V9m0 3h3m-3 0H9m9 9H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2Z"/></svg>
                <?php esc_html_e('Save prescription information', 'petscript-rx-checkout'); ?>
            </button>
        </div>

        <p class="ps-rxc-trust-note">
            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1.5a5.5 5.5 0 0 0-5.5 5.5v2A2.5 2.5 0 0 0 2 11.5v5A2.5 2.5 0 0 0 4.5 19h11a2.5 2.5 0 0 0 2.5-2.5v-5A2.5 2.5 0 0 0 15.5 9V7A5.5 5.5 0 0 0 10 1.5ZM7 7a3 3 0 1 1 6 0v2H7V7Z" clip-rule="evenodd"/></svg>
            <?php esc_html_e('Your information is secure and encrypted.', 'petscript-rx-checkout'); ?>
        </p>
    </div>
</div>
