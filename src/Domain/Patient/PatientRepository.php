<?php

namespace PetScript\RxCheckout\Domain\Patient;

use PetScript\RxCheckout\Support\Config;

final class PatientRepository
{
    private const ALLOWED_SEX = ['male', 'female', 'unknown'];

    /**
     * @return Patient[]
     */
    public function forCustomer(int $customerId): array
    {
        global $wpdb;

        $table = Config::patientsTable();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY name ASC",
                $customerId
            )
        );

        return array_map([Patient::class, 'fromRow'], $rows ?: []);
    }

    public function find(int $id, int $customerId): ?Patient
    {
        global $wpdb;

        $table = Config::patientsTable();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND customer_id = %d",
                $id,
                $customerId
            )
        );

        return $row ? Patient::fromRow($row) : null;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function save(int $customerId, array $input, ?int $id = null): Patient
    {
        global $wpdb;

        $table = Config::patientsTable();
        $now = current_time('mysql');

        $data = [
            'customer_id' => $customerId,
            'name' => sanitize_text_field($input['name'] ?? ''),
            'species' => sanitize_text_field($input['species'] ?? ''),
            'breed' => self::nullableText($input['breed'] ?? null, 100),
            'sex' => self::sanitizeSex($input['sex'] ?? null),
            'weight_lbs' => self::nullableFloat($input['weight_lbs'] ?? null),
            'birthdate' => self::nullableDate($input['birthdate'] ?? null),
            'medications' => self::nullableText($input['medications'] ?? null, 500),
            'allergies' => self::nullableText($input['allergies'] ?? null, 500),
            'pre_existing_conditions' => self::nullableText($input['pre_existing_conditions'] ?? null, 500),
            'notes' => self::nullableText($input['notes'] ?? null, 1000),
            'updated_at' => $now,
        ];

        if ($id === null) {
            $data['created_at'] = $now;
            $wpdb->insert($table, $data);
            $id = (int) $wpdb->insert_id;
        } else {
            $wpdb->update($table, $data, ['id' => $id, 'customer_id' => $customerId]);
        }

        return $this->find($id, $customerId);
    }

    public function delete(int $id, int $customerId): bool
    {
        global $wpdb;

        $table = Config::patientsTable();

        return (bool) $wpdb->delete($table, ['id' => $id, 'customer_id' => $customerId]);
    }

    private static function sanitizeSex(mixed $value): ?string
    {
        if (!is_string($value) || !in_array($value, self::ALLOWED_SEX, true)) {
            return null;
        }

        return $value;
    }

    private static function nullableText(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(sanitize_text_field($value), 0, $maxLength);
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private static function nullableDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp ? gmdate('Y-m-d', $timestamp) : null;
    }
}
