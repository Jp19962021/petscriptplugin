<?php

namespace PetScript\RxCheckout\Install;

use PetScript\RxCheckout\Support\Config;

final class Installer
{
    public static function activate(): void
    {
        self::maybeUpgrade();
    }

    public static function maybeUpgrade(): void
    {
        if (get_option(Config::DB_VERSION_OPTION) === Config::DB_VERSION) {
            return;
        }

        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $patients = Config::patientsTable();
        $clinics = Config::clinicsTable();

        $sqlPatients = "CREATE TABLE {$patients} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            species VARCHAR(50) NOT NULL,
            breed VARCHAR(100) NULL,
            sex VARCHAR(20) NULL,
            weight_lbs DECIMAL(6,2) NULL,
            birthdate DATE NULL,
            medications VARCHAR(500) NULL,
            allergies VARCHAR(500) NULL,
            pre_existing_conditions VARCHAR(500) NULL,
            notes VARCHAR(1000) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id)
        ) {$charsetCollate};";

        $sqlClinics = "CREATE TABLE {$clinics} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(30) NULL,
            address VARCHAR(500) NULL,
            city VARCHAR(100) NULL,
            state VARCHAR(100) NULL,
            postal_code VARCHAR(20) NULL,
            country VARCHAR(2) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id)
        ) {$charsetCollate};";

        dbDelta($sqlPatients);
        dbDelta($sqlClinics);

        update_option(Config::DB_VERSION_OPTION, Config::DB_VERSION);
    }
}
