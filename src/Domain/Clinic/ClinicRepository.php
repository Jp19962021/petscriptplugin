<?php

namespace PetScript\RxCheckout\Domain\Clinic;

use PetScript\RxCheckout\Support\Config;

final class ClinicRepository
{
    /**
     * The customer's own clinics only (any status) — used to seed the
     * "your saved clinics" list in the modal.
     *
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

    /**
     * A clinic is valid for a customer when it is their own (any status,
     * so a just-added pending clinic never blocks their checkout) OR an
     * approved shared directory clinic (customer_id = 0).
     */
    public function find(int $id, int $customerId): ?Clinic
    {
        global $wpdb;

        $table = Config::clinicsTable();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE id = %d
                   AND (customer_id = %d OR (customer_id = 0 AND status = %s))",
                $id,
                $customerId,
                Config::CLINIC_STATUS_APPROVED
            )
        );

        return $row ? Clinic::fromRow($row) : null;
    }

    /**
     * Directory + own-clinic search by name or phone, ranked so exact ZIP
     * matches surface first, then same ZIP3 area, then alphabetical. No
     * geocoding — ZIP prefix proximity is intentionally the simple version.
     *
     * @return Clinic[]
     */
    public function search(int $customerId, string $query, string $zip, int $limit = 25): array
    {
        global $wpdb;

        $table = Config::clinicsTable();
        $like = '%' . $wpdb->esc_like($query) . '%';
        $zip = preg_replace('/[^0-9]/', '', $zip);
        $zip3 = $zip !== '' ? substr($zip, 0, 3) . '%' : '';

        $sql = "SELECT *,
                (CASE WHEN %s <> '' AND postal_code = %s THEN 2
                      WHEN %s <> '' AND postal_code LIKE %s THEN 1
                      ELSE 0 END) AS zip_rank
                FROM {$table}
                WHERE ((customer_id = 0 AND status = %s) OR customer_id = %d)";

        $params = [$zip, $zip, $zip3, $zip3, Config::CLINIC_STATUS_APPROVED, $customerId];

        if ($query !== '') {
            $sql .= ' AND (name LIKE %s OR phone LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY zip_rank DESC, name ASC LIMIT %d';
        $params[] = $limit;

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        return array_map([Clinic::class, 'fromRow'], $rows ?: []);
    }

    /**
     * @param array<string,mixed> $input
     */
    public function save(int $customerId, array $input, ?int $id = null, ?string $status = null): Clinic
    {
        global $wpdb;

        $table = Config::clinicsTable();
        $now = current_time('mysql');

        $data = [
            'customer_id' => $customerId,
            'name' => sanitize_text_field($input['name'] ?? ''),
            'vet_first_name' => self::nullableText($input['vet_first_name'] ?? null, 100),
            'vet_last_name' => self::nullableText($input['vet_last_name'] ?? null, 100),
            'phone' => self::nullableText($input['phone'] ?? null, 30),
            'address' => self::nullableText($input['address'] ?? null, 500),
            'city' => self::nullableText($input['city'] ?? null, 100),
            'state' => self::nullableText($input['state'] ?? null, 100),
            'postal_code' => self::nullableText($input['postal_code'] ?? null, 20),
            'country' => self::nullableCountry($input['country'] ?? null),
            'updated_at' => $now,
        ];

        if ($status !== null) {
            $data['status'] = $status;
        }

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

    // -- Admin: directory import & approval queue ---------------------------

    /**
     * Imports one directory row (customer_id = 0, approved). Dedupes on
     * name + postal_code among existing directory rows; returns false when
     * skipped as a duplicate or when the name is missing.
     *
     * @param array<string,mixed> $input
     */
    public function importDirectoryClinic(array $input): bool
    {
        global $wpdb;

        $table = Config::clinicsTable();
        $name = sanitize_text_field($input['name'] ?? '');

        if ($name === '') {
            return false;
        }

        $postal = sanitize_text_field((string) ($input['postal_code'] ?? ''));

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE customer_id = 0 AND name = %s AND COALESCE(postal_code, '') = %s LIMIT 1",
            $name,
            $postal
        ));

        if ($exists) {
            return false;
        }

        $now = current_time('mysql');

        $wpdb->insert($table, [
            'customer_id' => 0,
            'name' => $name,
            'vet_first_name' => self::nullableText($input['vet_first_name'] ?? null, 100),
            'vet_last_name' => self::nullableText($input['vet_last_name'] ?? null, 100),
            'phone' => self::nullableText($input['phone'] ?? null, 30),
            'address' => self::nullableText($input['address'] ?? null, 500),
            'city' => self::nullableText($input['city'] ?? null, 100),
            'state' => self::nullableText($input['state'] ?? null, 100),
            'postal_code' => $postal !== '' ? $postal : null,
            'country' => self::nullableCountry($input['country'] ?? null) ?? 'US',
            'status' => Config::CLINIC_STATUS_APPROVED,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return true;
    }

    /**
     * @return Clinic[]
     */
    public function pending(): array
    {
        global $wpdb;

        $table = Config::clinicsTable();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s AND customer_id > 0 ORDER BY created_at ASC",
            Config::CLINIC_STATUS_PENDING
        ));

        return array_map([Clinic::class, 'fromRow'], $rows ?: []);
    }

    /**
     * Approve = the customer's row becomes 'approved' (they keep it in their
     * own list) AND a copy is added to the shared directory so every other
     * customer can find it. Copying instead of re-owning keeps existing cart
     * assignments and the customer's saved list intact.
     */
    public function approvePending(int $id): bool
    {
        global $wpdb;

        $table = Config::clinicsTable();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND status = %s AND customer_id > 0",
            $id,
            Config::CLINIC_STATUS_PENDING
        ));

        if (!$row) {
            return false;
        }

        $wpdb->update(
            $table,
            ['status' => Config::CLINIC_STATUS_APPROVED, 'updated_at' => current_time('mysql')],
            ['id' => $id]
        );

        $this->importDirectoryClinic([
            'name' => $row->name,
            'vet_first_name' => $row->vet_first_name,
            'vet_last_name' => $row->vet_last_name,
            'phone' => $row->phone,
            'address' => $row->address,
            'city' => $row->city,
            'state' => $row->state,
            'postal_code' => $row->postal_code,
            'country' => $row->country,
        ]);

        return true;
    }

    /**
     * Dismiss = keep it usable for the customer who created it, but never
     * publish it to the directory and drop it from the queue.
     */
    public function dismissPending(int $id): bool
    {
        global $wpdb;

        $table = Config::clinicsTable();

        return (bool) $wpdb->update(
            $table,
            ['status' => Config::CLINIC_STATUS_PRIVATE, 'updated_at' => current_time('mysql')],
            ['id' => $id, 'status' => Config::CLINIC_STATUS_PENDING]
        );
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
