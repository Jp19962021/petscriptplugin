<?php

namespace PetScript\RxCheckout\Domain\Patient;

final class Patient
{
    public function __construct(
        public readonly int $id,
        public readonly int $customerId,
        public readonly string $name,
        public readonly string $species,
        public readonly ?string $breed,
        public readonly ?string $sex,
        public readonly ?float $weightLbs,
        public readonly ?string $birthdate,
        public readonly ?string $medications,
        public readonly ?string $allergies,
        public readonly ?string $preExistingConditions,
        public readonly ?string $notes,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            customerId: (int) $row->customer_id,
            name: (string) $row->name,
            species: (string) $row->species,
            breed: $row->breed !== null ? (string) $row->breed : null,
            sex: $row->sex !== null ? (string) $row->sex : null,
            weightLbs: $row->weight_lbs !== null ? (float) $row->weight_lbs : null,
            birthdate: $row->birthdate !== null ? (string) $row->birthdate : null,
            medications: $row->medications !== null ? (string) $row->medications : null,
            allergies: $row->allergies !== null ? (string) $row->allergies : null,
            preExistingConditions: $row->pre_existing_conditions !== null ? (string) $row->pre_existing_conditions : null,
            notes: $row->notes !== null ? (string) $row->notes : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed,
            'sex' => $this->sex,
            'weight_lbs' => $this->weightLbs,
            'birthdate' => $this->birthdate,
            'medications' => $this->medications,
            'allergies' => $this->allergies,
            'pre_existing_conditions' => $this->preExistingConditions,
            'notes' => $this->notes,
        ];
    }
}
