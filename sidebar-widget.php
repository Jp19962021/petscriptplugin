<?php
/**
 * @var int $total
 * @var int $ready
 */

if (!defined('ABSPATH')) {
    exit;
}

$missing = $total - $ready;
$percent = $total > 0 ? ($ready / $total) * 100 : 0;
$circumference = 2 * M_PI * 34;
$offset = $circumference - ($percent / 100) * $circumference;
?>
<div class="ps-rxc-scope">
    <div class="ps-rxc-sidebar-widget" id="ps-rxc-sidebar-widget">
        <p class="ps-rxc-sidebar-widget__title"><?php esc_html_e('Prescription Status', 'petscript-rx-checkout'); ?></p>

        <div class="ps-rxc-sidebar-widget__row">
            <div class="ps-rxc-gauge">
                <svg width="76" height="76" viewBox="0 0 76 76">
                    <circle cx="38" cy="38" r="34" fill="none" stroke-width="7" class="ps-rxc-gauge__track" />
                    <circle cx="38" cy="38" r="34" fill="none" stroke-width="7" stroke-linecap="round"
                            class="ps-rxc-gauge__fill" id="ps-rxc-gauge-fill"
                            style="stroke-dasharray: <?php echo esc_attr($circumference); ?>; stroke-dashoffset: <?php echo esc_attr($offset); ?>;" />
                </svg>
                <div class="ps-rxc-gauge__label">
                    <span class="ps-rxc-gauge__number" id="ps-rxc-gauge-ready"><?php echo esc_html($ready); ?></span>
                    <span class="ps-rxc-gauge__of"><?php esc_html_e('of', 'petscript-rx-checkout'); ?> <span id="ps-rxc-gauge-total"><?php echo esc_html($total); ?></span></span>
                    <span class="ps-rxc-gauge__ready-label"><?php esc_html_e('Ready', 'petscript-rx-checkout'); ?></span>
                </div>
            </div>

            <ul class="ps-rxc-sidebar-widget__list">
                <li>
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="ps-rxc-sidebar-widget__icon ps-rxc-sidebar-widget__icon--ok"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                    <span id="ps-rxc-count-ready"><?php echo esc_html($ready); ?></span>&nbsp;<?php esc_html_e('Ready', 'petscript-rx-checkout'); ?>
                </li>
                <li>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="ps-rxc-sidebar-widget__icon ps-rxc-sidebar-widget__icon--warn"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0Z"/></svg>
                    <span id="ps-rxc-count-missing"><?php echo esc_html($missing); ?></span>&nbsp;<?php esc_html_e('Missing', 'petscript-rx-checkout'); ?>
                </li>
                <li class="ps-rxc-sidebar-widget__hint">
                    <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd"/></svg>
                    <?php esc_html_e('Complete all prescriptions to continue.', 'petscript-rx-checkout'); ?>
                </li>
            </ul>
        </div>
    </div>
</div>
