<?php

namespace Mralston\Payment\Data\Hsbc;

use Mralston\Payment\Models\Payment;
use Mralston\Payment\Data\AddressData;


class HsbcApplicationRequestData
{
    public static function fromPayment(Payment $payment): array
    {
        return [
            'applicant' => self::buildApplicant($payment),
            'deposit_amount' => $payment->deposit ?? 0,
            'finance_plan_id' => $payment->paymentProduct->provider_foreign_id,
            'merchant_reference' => $payment->reference,
            'order_items' => self::buildOrderItems($payment),
            'urls' => self::buildUrls($payment),
        ];
    }

    private static function buildApplicant(Payment $payment): array
    {
        return [
            'addresses' => $payment->addresses
                ->values()
                ->map(fn($address) => [
                    'postcode' => AddressData::from($address)->postCode,
                    'text' => AddressData::from($address)->asString(),
                ])
                ->toArray(),
            'email' => $payment->email_address,
            'first_name' => $payment->first_name,
            'last_name' => $payment->last_name,
            'phone_number' => $payment->primary_telephone,
        ];
    }

    private static function buildOrderItems(Payment $payment): array
    {
        return $payment->parentable
            ->products
            ->filter(fn($product) => $product->quantity > 0)
            ->map(fn($product) => [
                'name' => $product->tool->name,
                'price' => (int)$product->price_for_quantity,
                'quantity' => $product->quantity,
            ])
            ->toArray();
    }

    private static function buildUrls(Payment $payment): array
    {
        return [
            'merchant_response_url' => route('payment.webhook.hsbc', $payment->uuid),
        ];
    }
}