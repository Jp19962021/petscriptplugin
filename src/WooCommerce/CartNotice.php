<?php

namespace PetScript\RxCheckout\WooCommerce;

use PetScript\RxCheckout\Domain\Cart\PrescriptionCartChecker;
use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Domain\Patient\PatientRepository;
use PetScript\RxCheckout\Domain\RxAssignment\ItemAssignmentStore;
use PetScript\RxCheckout\Support\Config;
use PetScript\RxCheckout\Support\Settings;

final class CartNotice
{
    public function __construct(
        private readonly PrescriptionCartChecker $checker,
        private readonly ItemAssignmentStore $assignments,
        private readonly PatientRepository $patients,
        private readonly ClinicRepository $clinics,
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_before_cart', [$this, 'renderBanner']);
        add_action('woocommerce_after_cart_item_name', [$this, 'renderItemButton'], 10, 2);
        add_action('woocommerce_after_cart_totals', [$this, 'renderSidebarWidget']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!function_exists('is_cart') || !is_cart()) {
            return;
        }

        if (!$this->checker->cartRequiresPrescription()) {
            return;
        }

        $gmapsKey = Settings::googleMapsApiKey();

        // Don't enqueue the Google Maps script here. This site also runs
        // other address-autocomplete plugins that load
        // maps.googleapis.com/maps/api/js on their own; loading it a
        // second time re-registers its Web Components and throws console
        // errors, breaking autocomplete on this page. panel.js is handed
        // the API key below and checks at runtime whether Maps is already
        // loading/loaded before ever adding its own script tag.

        // Bust the browser cache off each file's mtime instead of the static
        // plugin version, so edits during development are always picked up.
        wp_enqueue_style('ps-rxc-panel', PS_RXC_URL . 'assets/build/panel.css', [], (string) filemtime(PS_RXC_DIR . '/assets/build/panel.css'));
        wp_enqueue_script('ps-rxc-panel', PS_RXC_URL . 'assets/build/panel.js', [], (string) filemtime(PS_RXC_DIR . '/assets/build/panel.js'), true);

        $items = [];

        foreach ($this->checker->rxRequiredCartItems() as $item) {
            $items[$item['key']] = [
                'key' => $item['key'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'assignment' => $this->assignments->get($item['key'])?->toArray(),
            ];
        }

        $billingZip = '';

        if (function_exists('WC') && WC()->customer) {
            $billingZip = (string) WC()->customer->get_billing_postcode();
        }

        wp_localize_script('ps-rxc-panel', 'psRxcPanel', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(Config::NONCE_ACTION),
            'items' => $items,
            'hasPlaces' => $gmapsKey !== '',
            'gmapsApiKey' => $gmapsKey,
            'billingZip' => $billingZip,
            'i18n' => [
                'notSelected' => __('Not selected', 'petscript-rx-checkout'),
                'addPatient' => __('Add New Patient', 'petscript-rx-checkout'),
                'addClinic' => __('Add New Clinic', 'petscript-rx-checkout'),
                'nameSpeciesRequired' => __('Name and species are required.', 'petscript-rx-checkout'),
                'clinicNameRequired' => __('Clinic name is required.', 'petscript-rx-checkout'),
                'vetNameRequired' => __("Veterinarian's first and last name are required.", 'petscript-rx-checkout'),
                'networkError' => __('Network error. Please try again.', 'petscript-rx-checkout'),
                'genericError' => __('Something went wrong. Please try again.', 'petscript-rx-checkout'),
                'cardTitleRequired' => __('Prescription required', 'petscript-rx-checkout'),
                'cardTitleAdded' => __('Pet / vet info added', 'petscript-rx-checkout'),
                'addBtn' => __('Add Pet / Vet Info', 'petscript-rx-checkout'),
                'editBtn' => __('Edit Pet / Vet Info', 'petscript-rx-checkout'),
                'selectFields' => __('Please choose a patient, clinic, and fill in all preferences.', 'petscript-rx-checkout'),
                'selectPatientFirst' => __('Please select a patient to continue.', 'petscript-rx-checkout'),
                'selectClinicFirst' => __('Please select a veterinary clinic to continue.', 'petscript-rx-checkout'),
                'selectedPatient' => __('Selected patient', 'petscript-rx-checkout'),
                'selectedClinic' => __('Selected clinic', 'petscript-rx-checkout'),
                'change' => __('Change', 'petscript-rx-checkout'),
                'noResults' => __('No clinics found. You can add yours below.', 'petscript-rx-checkout'),
                'noPatients' => __('You don\'t have any saved patients yet.', 'petscript-rx-checkout'),
                'noClinics' => __('Search for your clinic above, or add a new one.', 'petscript-rx-checkout'),
                'searching' => __('Searching…', 'petscript-rx-checkout'),
            ],
        ]);
    }

    /**
     * The header banner + the (single, shared) configuration modal. Rendered
     * once above the cart table; the actual trigger buttons live inline per
     * product row via renderItemButton().
     */
    public function renderBanner(): void
    {
        if (!is_user_logged_in() || !$this->checker->cartRequiresPrescription()) {
            return;
        }

        $customerId = get_current_user_id();
        $patients = array_map(static fn ($p) => $p->toArray(), $this->patients->forCustomer($customerId));
        $clinics = array_map(static fn ($c) => $c->toArray(), $this->clinics->forCustomer($customerId));

        include PS_RXC_DIR . '/templates/cart-panel.php';
    }

    /**
     * @param array<string, mixed> $cartItem
     */
    public function renderItemButton(array $cartItem, string $cartItemKey): void
    {
        if (!is_user_logged_in() || !$this->checker->productRequiresPrescription((int) $cartItem['product_id'])) {
            return;
        }

        $isComplete = $this->assignments->isCompleteFor($cartItemKey);

        include PS_RXC_DIR . '/templates/parts/item-button.php';
    }

    public function renderSidebarWidget(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $rxItems = $this->checker->rxRequiredCartItems();

        if ($rxItems === []) {
            return;
        }

        $total = count($rxItems);
        $ready = 0;

        foreach ($rxItems as $item) {
            if ($this->assignments->isCompleteFor($item['key'])) {
                $ready++;
            }
        }

        include PS_RXC_DIR . '/templates/parts/sidebar-widget.php';
    }
}
