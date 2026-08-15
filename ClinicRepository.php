<?php

namespace PetScript\RxCheckout\Domain\Clinic;

use PetScript\RxCheckout\Support\Config;

final class ClinicRepository
{
    /**
     * @return Clinic[]
     */
    public function forCustomer(int $customerId): array
    {
        global $wpdb;

        $table = Config::clinicsTable();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY name ASC",
                $customerId
            )
        );

        return array_map([Clinic::class, 'fromRow'], $rows ?: []);
    }

    public function find(int $id, int $customerId): ?Clinic
    {
        global $wpdb;

        $table = Config::clinicsTable();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND customer_id = %d",
                $id,
                $customerId
            )
        );

        return $row ? Clinic::fromRow($row) : null;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function save(int $customerId, array $input, ?int $id = null): Clinic
    {
        global $wpdb;

        $table = Config::clinicsTable();
        $now = current_time('mysql');

        $data = [
            'customer_id' => $customerId,
            'name' => sanitize_text_field($input['name'] ?? ''),
            'phone' => self::nullableText($input['phone'] ?? null, 30),
            'address' => self::nullableText($input['address'] ?? null, 500),
            'city' => self::nullableText($input['city'] ?? null, 100),
            'state' => self::nullableText($input['state'] ?? null, 100),
            'postal_code' => self::nullableText($input['postal_code'] ?? null, 20),
            'country' => self::nullableCountry($input['country'] ?? null),
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

        $table = Config::clinicsTable();

        return (bool) $wpdb->delete($table, ['id' => $id, 'customer_id' => $customerId]);
    }

    private static function nullableText(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(sanitize_text_field($value), 0, $maxLength);
    }

    private static function nullableCountry(mixed $value): ?string
    {
        if (!is_string($value) || strlen($value) !== 2) {
            return null;
        }

        return strtoupper(sanitize_text_field($value));
    }
}
