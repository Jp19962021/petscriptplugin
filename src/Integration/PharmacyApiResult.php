<?php

namespace PetScript\RxCheckout\Integration;

final class PharmacyApiResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $rxId,
        public readonly ?string $message,
    ) {
    }

    public static function success(?string $rxId): self
    {
        return new self(true, $rxId, null);
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }
}
