<?php

namespace PetScript\RxCheckout\Support;

final class Config
{
    public const PRESCRIPTION_TAG_SLUG = 'prescription-required';

    public const PATIENTS_TABLE = 'ps_rxc_patients';

    public const CLINICS_TABLE = 'ps_rxc_clinics';

    public const DB_VERSION_OPTION = 'ps_rxc_db_version';

    public const DB_VERSION = '1.0.0';

    public const NONCE_ACTION = 'ps_rxc_nonce';

    // WC session key holding a map of cart_item_key => assignment, since each
    // prescription line item in the cart can have its own patient/clinic.
    public const SESSION_ASSIGNMENTS_KEY = 'ps_rxc_assignments';

    // Order LINE ITEM meta (one snapshot per prescription line).
    public const ITEM_META_PATIENT_SNAPSHOT = '_ps_rxc_patient_snapshot';

    public const ITEM_META_CLINIC_SNAPSHOT = '_ps_rxc_clinic_snapshot';

    public const ITEM_META_APPROVAL = '_ps_rxc_approval';

    public const ITEM_META_GROUP_KEY = '_ps_rxc_group_key';

    // Order-level meta: JSON map of group_key => Pharmacy rx id, used both as
    // the idempotency guard and to know which groups still need (re)sending.
    public const ORDER_META_SENT_GROUPS = '_ps_rxc_sent_groups';

    public const APPROVAL_METHODS = ['contact_clinic', 'mail_prescription'];

    public const SHIP_BILL_TYPES = ['patient', 'clinic'];

    public static function patientsTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::PATIENTS_TABLE;
    }

    public static function clinicsTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::CLINICS_TABLE;
    }
}
