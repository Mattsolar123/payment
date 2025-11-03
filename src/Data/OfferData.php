<?php

namespace Mralston\Payment\Data;

use Mralston\Payment\Models\PaymentSurvey;
use Mralston\Payment\Models\PaymentProvider;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class OfferData extends Data
{
    public function __construct(
        public PaymentSurvey $survey,
        public PaymentProvider $paymentProvider,
        public int $amount,
        public int $totalCost,
        public int $totalRepayable,
        public int $deposit,
        public string $reference,
        public string $type,
        public string $name,
        public string $paymentProductId,
        public int $term,
        public float $apr,
        public float $monthlyPayment,
        public ?int $deferred = null,
        public ?string $deferredType = null,
        public ?float $firstPayment = null,
        public ?float $finalPayment = null,
        public ?string $status = null,
        public ?string $preapprovalId = null,
        public ?int $priority = null,
        public ?Collection $minimumPayments = null,
    ) {
        //
    }
}
