<?php

namespace PetScript\RxCheckout\Domain\Clinic;

final class Clinic
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $postalCode,
        public readonly ?string $country,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            customerId: (int) $row->customer_id,
            name: (string) $row->name,
            phone: $row->phone !== null ? (string) $row->phone : null,
            address: $row->address !== null ? (string) $row->address : null,
            city: $row->city !== null ? (string) $row->city : null,
            state: $row->state !== null ? (string) $row->state : null,
            postalCode: $row->postal_code !== null ? (string) $row->postal_code : null,
            country: $row->country !== null ? (string) $row->country : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }
}
