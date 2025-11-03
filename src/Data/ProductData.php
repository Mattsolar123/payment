<?php

namespace Mralston\Payment\Data;

use Spatie\LaravelData\Data;
use Mralston\Payment\Models\PaymentProvider;

class ProductData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public string $identifier,
        public PaymentProvider $paymentProvider,
        public string $providerForeignId,
        public ?float $apr,
        public int $term,
        public ?int $deferred,
        public ?string $deferredType,
        public ?int $sortOrder,
    ) {
        //
    }
}