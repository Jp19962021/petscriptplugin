<?php

namespace PetScript\RxCheckout\Admin;

use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Support\Settings;

final class SettingsPage
{
    private const SLUG = 'petscript-rx-checkout';

    public function __construct(private readonly ClinicRepository $clinics)
    {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'maybeSave']);
        add_action('admin_init', [$this, 'maybeImportCsv']);
        add_action('admin_init', [$this, 'maybeReviewClinic']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('PetScript Rx Checkout', 'petscript-rx-checkout'),
            __('Rx Checkout', 'petscript-rx-checkout'),
            'manage_woocommerce',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function maybeSave(): void
    {
        if (!isset($_POST['ps_rxc_settings_nonce']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('ps_rxc_settings_save', 'ps_rxc_settings_nonce');

        Settings::updatePharmacyBaseUrl((string) ($_POST['ps_rxc_base_url'] ?? ''));
        Settings::updatePharmacyApiKey((string) ($_POST['ps_rxc_api_key'] ?? ''));
        Settings::updateGoogleMapsApiKey((string) ($_POST['ps_rxc_gmaps_key'] ?? ''));
        Settings::updateTestMode(!empty($_POST['ps_rxc_test_mode']));

        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-success"><p>' .
                esc_html__('Settings saved.', 'petscript-rx-checkout') .
                '</p></div>';
        });
    }

    /**
     * CSV columns (header row required, order-independent):
     * name, phone, address, city, state, postal_code, country,
     * vet_first_name, vet_last_name. Only `name` is mandatory per row.
     */
    public function maybeImportCsv(): void
    {
        if (!isset($_POST['ps_rxc_csv_nonce']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('ps_rxc_csv_import', 'ps_rxc_csv_nonce');

        if (empty($_FILES['ps_rxc_clinic_csv']['tmp_name'])) {
            add_action('admin_notices', static function (): void {
                echo '<div class="notice notice-error"><p>' .
                    esc_html__('Please choose a CSV file to import.', 'petscript-rx-checkout') .
                    '</p></div>';
            });
            return;
        }

        $handle = fopen($_FILES['ps_rxc_clinic_csv']['tmp_name'], 'r');

        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle);

        if (!is_array($header)) {
            fclose($handle);
            return;
        }

        // Strip the UTF-8 BOM Excel prepends, normalize header names.
        $header = array_map(
            static fn ($col) => strtolower(trim(str_replace("\u{FEFF}", '', (string) $col))),
            $header
        );

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $input = [];

            foreach ($header as $i => $column) {
                $input[$column] = $row[$i] ?? '';
            }

            if ($this->clinics->importDirectoryClinic($input)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        add_action('admin_notices', static function () use ($imported, $skipped): void {
            echo '<div class="notice notice-success"><p>' . esc_html(sprintf(
                /* translators: 1: imported count, 2: skipped count */
                __('Clinic import finished: %1$d imported, %2$d skipped (duplicates or missing name).', 'petscript-rx-checkout'),
                $imported,
                $skipped
            )) . '</p></div>';
        });
    }

    public function maybeReviewClinic(): void
    {
        if (!isset($_POST['ps_rxc_review_nonce']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('ps_rxc_clinic_review', 'ps_rxc_review_nonce');

        $clinicId = absint($_POST['ps_rxc_clinic_id'] ?? 0);
        $decision = sanitize_text_field($_POST['ps_rxc_decision'] ?? '');

        if ($clinicId === 0) {
            return;
        }

        $done = $decision === 'approve'
            ? $this->clinics->approvePending($clinicId)
            : $this->clinics->dismissPending($clinicId);

        if ($done) {
            add_action('admin_notices', static function () use ($decision): void {
                $message = $decision === 'approve'
                    ? __('Clinic approved and added to the shared directory.', 'petscript-rx-checkout')
                    : __('Clinic dismissed. The customer keeps it privately; it will not appear in the directory.', 'petscript-rx-checkout');
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }

    public function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $baseUrl = Settings::pharmacyBaseUrl();
        $maskedKey = Settings::maskedApiKey();
        $gmapsKey = Settings::googleMapsApiKey();
        $testMode = Settings::isTestMode();
        $endpoint = Settings::externalOrdersEndpoint();
        $pending = $this->clinics->pending();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('PetScript Rx Checkout', 'petscript-rx-checkout'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ps_rxc_settings_save', 'ps_rxc_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="ps_rxc_base_url"><?php esc_html_e('PetScript Pharmacy base URL', 'petscript-rx-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="ps_rxc_base_url" name="ps_rxc_base_url"
                                   class="regular-text" value="<?php echo esc_attr($baseUrl); ?>"
                                   placeholder="https://pharmacy.example.com" />
                            <?php if ($endpoint !== '') : ?>
                                <p class="description"><?php echo esc_html($endpoint); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ps_rxc_api_key"><?php esc_html_e('API Key (X-PetScript-Key)', 'petscript-rx-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ps_rxc_api_key" name="ps_rxc_api_key"
                                   class="regular-text" value="<?php echo esc_attr($maskedKey); ?>"
                                   autocomplete="off" />
                            <?php if (defined('PS_RXC_PHARMACY_API_KEY') && PS_RXC_PHARMACY_API_KEY !== '') : ?>
                                <p class="description">
                                    <?php esc_html_e('Currently overridden by the PS_RXC_PHARMACY_API_KEY constant in wp-config.php.', 'petscript-rx-checkout'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ps_rxc_gmaps_key"><?php esc_html_e('Google Maps API key', 'petscript-rx-checkout'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ps_rxc_gmaps_key" name="ps_rxc_gmaps_key"
                                   class="regular-text" value="<?php echo esc_attr($gmapsKey); ?>"
                                   autocomplete="off" />
                            <p class="description">
                                <?php esc_html_e('Enables address autocomplete on checkout and the clinic form. Needs the Places API enabled in Google Cloud. Leave blank to disable.', 'petscript-rx-checkout'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Test mode', 'petscript-rx-checkout'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ps_rxc_test_mode" value="1" <?php checked($testMode); ?> />
                                <?php esc_html_e('Log the payload instead of sending it to Pharmacy.', 'petscript-rx-checkout'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Clinic directory import', 'petscript-rx-checkout'); ?></h2>
            <p><?php esc_html_e('Upload a CSV of vet clinics to pre-load the searchable directory. Header row required. Columns: name, phone, address, city, state, postal_code, country, vet_first_name, vet_last_name. Duplicates (same name + ZIP) are skipped.', 'petscript-rx-checkout'); ?></p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('ps_rxc_csv_import', 'ps_rxc_csv_nonce'); ?>
                <input type="file" name="ps_rxc_clinic_csv" accept=".csv,text/csv" />
                <?php submit_button(__('Import clinics', 'petscript-rx-checkout'), 'secondary', 'ps_rxc_csv_submit', false); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Customer-added clinics awaiting approval', 'petscript-rx-checkout'); ?></h2>
            <?php if ($pending === []) : ?>
                <p><?php esc_html_e('No clinics waiting for review.', 'petscript-rx-checkout'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('These clinics were added by customers at checkout. They already work for that customer. Approving adds the clinic to the shared directory so all customers can find it; dismissing keeps it private to the customer.', 'petscript-rx-checkout'); ?></p>
                <table class="widefat striped" style="max-width: 1100px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Clinic', 'petscript-rx-checkout'); ?></th>
                            <th><?php esc_html_e('Veterinarian', 'petscript-rx-checkout'); ?></th>
                            <th><?php esc_html_e('Phone', 'petscript-rx-checkout'); ?></th>
                            <th><?php esc_html_e('Location', 'petscript-rx-checkout'); ?></th>
                            <th><?php esc_html_e('Added by', 'petscript-rx-checkout'); ?></th>
                            <th><?php esc_html_e('Actions', 'petscript-rx-checkout'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $clinic) :
                            $user = get_userdata($clinic->customerId);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($clinic->name); ?></strong><br /><small><?php echo esc_html((string) $clinic->address); ?></small></td>
                                <td><?php echo esc_html(trim(($clinic->vetFirstName ?? '') . ' ' . ($clinic->vetLastName ?? ''))); ?></td>
                                <td><?php echo esc_html((string) $clinic->phone); ?></td>
                                <td><?php echo esc_html(implode(', ', array_filter([$clinic->city, $clinic->state, $clinic->postalCode]))); ?></td>
                                <td><?php echo $user ? esc_html($user->display_name . ' (' . $user->user_email . ')') : '#' . esc_html((string) $clinic->customerId); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('ps_rxc_clinic_review', 'ps_rxc_review_nonce'); ?>
                                        <input type="hidden" name="ps_rxc_clinic_id" value="<?php echo esc_attr((string) $clinic->id); ?>" />
                                        <button type="submit" name="ps_rxc_decision" value="approve" class="button button-primary"><?php esc_html_e('Approve', 'petscript-rx-checkout'); ?></button>
                                        <button type="submit" name="ps_rxc_decision" value="dismiss" class="button"><?php esc_html_e('Dismiss', 'petscript-rx-checkout'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
