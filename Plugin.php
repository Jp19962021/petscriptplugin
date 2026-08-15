<?php

namespace PetScript\RxCheckout;

use PetScript\RxCheckout\Admin\SettingsPage;
use PetScript\RxCheckout\Domain\Cart\PrescriptionCartChecker;
use PetScript\RxCheckout\Domain\Clinic\ClinicRepository;
use PetScript\RxCheckout\Domain\Patient\PatientRepository;
use PetScript\RxCheckout\Domain\RxAssignment\ItemAssignmentStore;
use PetScript\RxCheckout\Http\Ajax\AbstractAjaxHandler;
use PetScript\RxCheckout\Http\Ajax\DeleteClinicHandler;
use PetScript\RxCheckout\Http\Ajax\DeletePatientHandler;
use PetScript\RxCheckout\Http\Ajax\ListClinicsHandler;
use PetScript\RxCheckout\Http\Ajax\ListPatientsHandler;
use PetScript\RxCheckout\Http\Ajax\SaveClinicHandler;
use PetScript\RxCheckout\Http\Ajax\SavePatientHandler;
use PetScript\RxCheckout\Http\Ajax\SaveRxAssignmentHandler;
use PetScript\RxCheckout\Install\Installer;
use PetScript\RxCheckout\Integration\PayloadMapper;
use PetScript\RxCheckout\Integration\PharmacyApiClient;
use PetScript\RxCheckout\WooCommerce\CartGate;
use PetScript\RxCheckout\WooCommerce\CartNotice;
use PetScript\RxCheckout\WooCommerce\CheckoutGuard;
use PetScript\RxCheckout\WooCommerce\OrderAdminColumn;
use PetScript\RxCheckout\WooCommerce\OrderSnapshot;
use PetScript\RxCheckout\WooCommerce\OrderSubmitter;

final class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function boot(): void
    {
        Installer::maybeUpgrade();

        load_plugin_textdomain('petscript-rx-checkout', false, dirname(plugin_basename(PS_RXC_FILE)) . '/languages');

        $patients = new PatientRepository();
        $clinics = new ClinicRepository();
        $assignments = new ItemAssignmentStore();
        $checker = new PrescriptionCartChecker();
        $mapper = new PayloadMapper();

        $this->registerAjaxHandlers($patients, $clinics, $assignments, $checker);

        (new CartGate($checker, $assignments))->register();
        (new CartNotice($checker, $assignments, $patients, $clinics))->register();
        (new CheckoutGuard($checker))->register();
        (new OrderSnapshot($assignments, $patients, $clinics))->register();

        $submitter = new OrderSubmitter($mapper, new PharmacyApiClient());
        $submitter->register();

        (new OrderAdminColumn($submitter, $mapper))->register();
        (new SettingsPage())->register();
    }

    private function registerAjaxHandlers(
        PatientRepository $patients,
        ClinicRepository $clinics,
        ItemAssignmentStore $assignments,
        PrescriptionCartChecker $checker,
    ): void {
        /** @var AbstractAjaxHandler[] $handlers */
        $handlers = [
            new SavePatientHandler($patients),
            new ListPatientsHandler($patients),
            new DeletePatientHandler($patients),
            new SaveClinicHandler($clinics),
            new ListClinicsHandler($clinics),
            new DeleteClinicHandler($clinics),
            new SaveRxAssignmentHandler($patients, $clinics, $assignments, $checker),
        ];

        foreach ($handlers as $handler) {
            $handler->register();
        }
    }
}
