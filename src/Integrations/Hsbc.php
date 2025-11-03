<?php

namespace Mralston\Payment\Integrations;

use Mralston\Payment\Interfaces\FinanceGateway;
use Mralston\Payment\Interfaces\PaymentGateway;
use Mralston\Payment\Interfaces\PrequalifiesCustomer;
use Mralston\Payment\Interfaces\PaymentHelper;
use Mralston\Payment\Models\PaymentStatus;
use Mralston\Payment\Services\PaymentCalculator;
use Mralston\Payment\Events\OffersReceived;
use Mralston\Payment\Data\PrequalPromiseData;
use Mralston\Payment\Data\PrequalData;
use Mralston\Payment\Data\ProductData;
use Mralston\Payment\Data\OfferData;
use Mralston\Payment\Data\Hsbc\HsbcApplicationRequestData;
use Mralston\Payment\Models\PaymentSurvey;
use Mralston\Payment\Models\PaymentProvider;
use Mralston\Payment\Models\PaymentOffer;
use Mralston\Payment\Models\Payment;
use Mralston\Payment\Services\HsbcService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mralston\Payment\Mail\FinanceGatewayApplied;

class Hsbc implements PaymentGateway, FinanceGateway, PrequalifiesCustomer
{
    private array $endpoints = [
        'local' => 'https://stoplight.io/mocks/mindera/integrations/1142103226/',
        'dev' => 'https://stoplight.io/mocks/mindera/integrations/1142103226/',
        'testing' => 'https://stoplight.io/mocks/mindera/integrations/1142103226/',
        'production' => '',
    ];

    private $guzzleClient;
    private string $endpoint;

    public function __construct(
        private string $key,
        string $endpoint,
    ) {
        $this->endpoint = $this->endpoints[$endpoint];

        $this->guzzleClient = new Client([
            'base_uri' => $this->endpoint,
            'timeout' => 60
        ]);

        $this->hsbcService = app(HsbcService::class);
    }

    public function financeProducts(): Collection
    {
        $response = $this->guzzleClient->get('finance-plans', [
            'headers' => [
                'X-Divido-API-Key' => $this->key,
            ]
        ]);

        return collect(json_decode($response->getBody()->getContents(), true)['data'] ?? []);
    }

    private function financeProduct(string $financeProductId, float $totalCost, float $deposit): array
    {
        $response = $this->guzzleClient->get(
            'calculate?id=' . $financeProductId . '&price=' . $totalCost . '&deposit=' . $deposit . '&startdate=2025-10-03', [
            'headers' => [
                'X-Divido-API-Key' => $this->key
            ]
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true
        )['data'] ?? [];
    }

    public function prequal(PaymentSurvey $survey, float $totalCost): PrequalPromiseData|PrequalData
    {
        dispatch(function () use ($survey, $totalCost) {
            $paymentProvider = PaymentProvider::byIdentifier('hsbc');
            $helper = app(PaymentHelper::class)
                ->setParentModel($survey->parentable);

            $offers = collect();

            foreach ($this->financeProducts() as $financeProduct) {

                $paymentProduct = $this->hsbcService->createProduct(
                    new ProductData(
                        name: $financeProduct['description'],
                        description: $financeProduct['description'],
                        identifier: 'hsbc_' . $financeProduct['interest_rate_percentage'] .
                            '_' . $financeProduct['agreement_duration_months'] .
                            ($financeProduct['deferral_period_months'] > 0 ? '+' . $financeProduct['deferral_period_months'] : ''),
                        paymentProvider: $paymentProvider,
                        providerForeignId: $financeProduct['id'],
                        apr: 9.9, //$financeProduct['interest_rate_percentage'],
                        term: 240, //$financeProduct['agreement_duration_months'],
                        deferred: $financeProduct['deferral_period_months'] > 0 ?
                            $financeProduct['deferral_period_months'] : null,
                        deferredType: $financeProduct['deferral_period_months'] > 0 ? 'payments' : null,
                        sortOrder: null,
                    ),
                );
                
                // If the product has been soft deleted, don't store the offer
                // This allows us to disable products we don't want to offer to customers
                if ($paymentProduct->trashed()) {
                    Log::channel('finance')->debug('Hsbc product soft deleted', $financeProduct);
                    return null;
                }

                $payment = $this->financeProduct(
                    $financeProduct['id'],
                    $totalCost,
                    $survey->finance_deposit
                );

                $offers->push($this->hsbcService->createOffer(
                    new OfferData(
                        amount: $totalCost - $survey->finance_deposit,
                        totalCost: $helper->getTotalCost(),
                        totalRepayable: $payment['amounts']['total_repayable_amount'],
                        deposit: $survey->finance_deposit,
                        reference: $helper->getReference() . '-' . Str::of(Str::random(5))->upper(),
                        type: 'finance',
                        name: $financeProduct['description'],
                        paymentProvider: $paymentProvider,
                        survey: $survey,
                        paymentProductId: $paymentProduct->id,
                        term: 240, //$payment['agreement_duration_months'],
                        apr: 9.9, //$payment['interest_rate_percentage'],
                        monthlyPayment: 1000, //$payment['amounts']['monthly_payment_amount'],
                        deferred: $payment['deferral_period_months'] > 0 ?
                            $payment['deferral_period_months'] : null,
                        firstPayment: 1,
                        finalPayment: 1,
                        status: 'final',
                    )));
            }

            event(new OffersReceived(
                gateway: static::class,
                type: 'finance',
                surveyId: $survey->id,
                offers: $offers,
            ));
        });

        return new PrequalPromiseData(
            gateway: static::class,
            type: 'finance',
            surveyId: $survey->id,
        );
    }

    public function apply(Payment $payment): Payment
    {
        $requestData = HsbcApplicationRequestData::fromPayment($payment);

        //dd($requestData);

        try {

            $response = $this->guzzleClient->post('applications', [
                'headers' => [
                    'X-Divido-HMAC-SHA256' => hash_hmac(
                        'sha256',
                        json_encode($requestData),
                        $this->key
                    ),
                    'X-Divido-API-Key' => $this->key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $requestData
            ]);

            $response = json_decode(
                $response->getBody()->getContents(),
                true
            )['data'];

        } catch (\GuzzleHttp\Exception\RequestException $ex) {

            $payment->update([
                'payment_status_id' => PaymentStatus::byIdentifier('error')?->id,
                'provider_request_data' => $requestData,
                'provider_response_data' => ['errors' => ['error' => [$ex->getMessage()]]],
            ]);

            return $payment;
        }

        $payment->update([
            'payment_link' => $response['urls']['application_form_url'] ?? null,
            'provider_foreign_id' => $response['id'],
            'payment_status_id' => PaymentStatus::byIdentifier(
                $this->hsbcService->translateStatus($response['current_status'] ?? 'ACTIVATED')
            )->id,
            'provider_request_data' => $requestData,
            'provider_response_data' => $response,
            'submitted_at' => Carbon::now(),
        ]);

        Mail::to($payment->email_address)
            ->send(new FinanceGatewayApplied($payment));
            
        return $payment;

    }

    public function getRequestData(): ?array
    {
        return null;
    }

    public function getResponseData(): ?array
    {
        return null;
    }

    public function healthCheck(): bool
    {
        return true;
    }

    public function cancel(Payment $payment, ?string $reason = null): bool
    {
        return true;
    }

    public function pollStatus(Payment $payment): array
    {
        return [];
    }
    
    public function calculatePayments(int $loanAmount, float $apr, int $loanTerm, ?int $deferredPeriod = null): array
    {
        return [];
    }

    public function cancelOffer(PaymentOffer $paymentOffer): void
    {
    }
}
