<?php
/**
 * @var string     $cartItemKey
 * @var bool       $isComplete
 * @var array|null $assignmentPatient  Patient::toArray(), only when $isComplete
 * @var array|null $assignmentClinic   Clinic::toArray(), only when $isComplete
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<span class="ps-rxc-scope">
    <div id="ps-rxc-item-card-<?php echo esc_attr($cartItemKey); ?>"
         class="ps-rxc-item-card <?php echo $isComplete ? 'ps-rxc-item-card--complete' : 'ps-rxc-item-card--incomplete'; ?>">

        <p class="ps-rxc-item-card__status" data-card-status>
            <?php if ($isComplete) : ?>
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                <span data-card-title><?php esc_html_e('Pet / vet info added', 'petscript-rx-checkout'); ?></span>
            <?php else : ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0Z"/></svg>
                <span data-card-title><?php esc_html_e('Prescription required', 'petscript-rx-checkout'); ?></span>
            <?php endif; ?>
        </p>

        <button type="button" class="ps-rxc-item-card__btn" data-configure-item="<?php echo esc_attr($cartItemKey); ?>">
            <?php if ($isComplete) : ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                <span data-card-btn-label><?php esc_html_e('Edit Pet / Vet Info', 'petscript-rx-checkout'); ?></span>
            <?php else : ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span data-card-btn-label><?php esc_html_e('Add Pet / Vet Info', 'petscript-rx-checkout'); ?></span>
            <?php endif; ?>
        </button>
    </div>
</span>
