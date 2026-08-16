<?php

namespace PetScript\RxCheckout\Domain\RxAssignment;

final class RxAssignment
{
    public function __construct(
        public readonly int $patientId,
        public readonly int $clinicId,
        public readonly string $approvalMethod,
        public readonly string $shipToType,
        public readonly string $billToType,
    ) {
    }

    public function toArray(): array
    {
        return [
            'patient_id' => $this->patientId,
            'clinic_id' => $this->clinicId,
            'approval_method' => $this->approvalMethod,
            'ship_to_type' => $this->shipToType,
            'bill_to_type' => $this->billToType,
        ];
    }

    /**
     * Line items sharing this key can be submitted to Pharmacy's
     * /external/orders endpoint together, since that endpoint accepts only
     * one patient + one clinic per call.
     */
    public function groupKey(): string
    {
        return implode('|', [
            $this->patientId,
            $this->clinicId,
            $this->approvalMethod,
            $this->shipToType,
            $this->billToType,
        ]);
    }

    public static function fromArray(array $data): ?self
    {
        if (empty($data['patient_id']) || empty($data['clinic_id'])) {
            return null;
        }

        return new self(
            patientId: (int) $data['patient_id'],
            clinicId: (int) $data['clinic_id'],
            approvalMethod: (string) ($data['approval_method'] ?? ''),
            shipToType: (string) ($data['ship_to_type'] ?? ''),
            billToType: (string) ($data['bill_to_type'] ?? ''),
        );
    }
}
