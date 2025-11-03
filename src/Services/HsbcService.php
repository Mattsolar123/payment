<?php

namespace Mralston\Payment\Services;

use Illuminate\Http\Request;
use Mralston\Payment\Data\OfferData;
use Mralston\Payment\Data\ProductData;
use Mralston\Payment\Models\Payment;
use Mralston\Payment\Models\PaymentProduct;
use Mralston\Payment\Models\PaymentOffer;
use Mralston\Payment\Models\PaymentSurvey;
use Mralston\Payment\Models\PaymentProvider;
use Mralston\Payment\Models\PaymentStatus;
use Illuminate\Support\Facades\Log;

class HsbcService
{
    public function handleWebhook(Request $request, string $uuid)
    {
        $payment = Payment::firstWhere('uuid', $uuid);

        if (!$payment) {
            Log::error('HSBC Webhook: Payment not found', ['uuid' => $uuid]);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $signature = $request->header('X-Divido-HMAC-SHA256');
        $payload = $request->getContent();
        
        $expectedSignature = hash_hmac('sha256', $payload, config('payment.hsbc.api_key'));
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('HSBC Webhook: Invalid HMAC signature', [
                'payment_uuid' => $uuid,
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payment->update([
            'payment_status_id' => PaymentStatus::byIdentifier($this->translateStatus($request->input('status')))?->id,
        ]);

        return response()->json(['message' => 'Webhook received'], 200);

    }

    public function createProduct(ProductData $productData) : PaymentProduct
    {
        return $productData->paymentProvider
            ->paymentProducts()
            ->firstOrCreate([
                'identifier' => $productData->identifier,
            ], [
                'name' => $productData->name,
                'apr' => $productData->apr,
                'term' => $productData->term,
                'deferred' => $productData->deferred,
                'deferred_type' => $productData->deferredType,
                'provider_foreign_id' => $productData->providerForeignId,
            ]);
    }

    public function createOffer(OfferData $offerData) : PaymentOffer
    {
        return $offerData->survey->parentable
            ->paymentOffers()
            ->updateOrCreate([
                'payment_product_id' => $offerData->paymentProvider->id,
            ], [
                'payment_survey_id' => $offerData->survey->id,
                'payment_product_id' => $offerData->paymentProductId,
                'payment_provider_id' => $offerData->paymentProvider->id,
                'name' => $offerData->name,
                'type' => $offerData->type,
                'reference' => $offerData->reference,
                'total_cost' => $offerData->totalCost,
                'total_repayable' => $offerData->totalRepayable,
                'amount' => $offerData->amount,
                'deposit' => $offerData->deposit,
                'apr' => $offerData->apr,
                'term' => $offerData->term,
                'deferred' => $offerData->deferred,
                'deferred_type' => $offerData->deferredType,
                'first_payment' => $offerData->firstPayment,
                'monthly_payment' => $offerData->monthlyPayment,
                'final_payment' => $offerData->finalPayment,
                'total_payable' => $offerData->totalRepayable,
                'status' => $offerData->status,
            ]);
    }

    public function translateStatus(string $status): string
    {
        return match ($status) {
            'ACCEPTED' => 'accepted',
            'ACTIVATED' => 'pending',
            'CANCELLED' => 'cancelled',
            'DECLINED' => 'declined',
            'PROPOSAL' => 'pending',
            'READY' => 'accepted',
            'REFERRED' => 'referred',
            'REFUNDED' => 'cancelled',
            'SIGNED' => 'live',
            default => 'error',
        };
    }
}
