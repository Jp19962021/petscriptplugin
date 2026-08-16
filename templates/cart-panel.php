<?php
/**
 * Header banner + the single shared configuration modal. The actual
 * trigger buttons are rendered per product row by templates/parts/item-button.php
 * (see CartNotice::renderItemButton).
 *
 * @var array $patients
 * @var array $clinics
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ps-rxc-scope" id="ps-rxc-panel"
     data-patients="<?php echo esc_attr(wp_json_encode($patients)); ?>"
     data-clinics="<?php echo esc_attr(wp_json_encode($clinics)); ?>">

    <div class="ps-rxc-banner">
        <span class="ps-rxc-banner__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </span>
        <div>
            <p class="ps-rxc-banner__title"><?php esc_html_e('Prescription information required', 'petscript-rx-checkout'); ?></p>
            <p class="ps-rxc-banner__subtitle">
                <?php esc_html_e('The items below require a veterinary prescription. Look for the button under each product to add the patient and clinic before you can check out.', 'petscript-rx-checkout'); ?>
            </p>
        </div>
    </div>

    <?php require PS_RXC_DIR . '/templates/parts/item-modal.php'; ?>
</div>
