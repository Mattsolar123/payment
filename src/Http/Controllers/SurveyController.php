<?php

namespace Mralston\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Mralston\Payment\Data\FinanceData;
use Mralston\Payment\Enums\LookupField;
use Mralston\Payment\Http\Requests\SubmitSurveyRequest;
use Mralston\Payment\Interfaces\PaymentHelper;
use Mralston\Payment\Models\PaymentLookupField;
use Mralston\Payment\Models\PaymentOffer;
use Mralston\Payment\Models\PaymentSurvey;
use Mralston\Payment\Traits\BootstrapsPayment;

class SurveyController
{
    use BootstrapsPayment;

    public function create(int $parent, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        // If there is already a survey, redirect to the existing survey
        if (!empty($parentModel->paymentSurvey)) {
            return redirect()->route('payment.surveys.edit', [
                'parent' => $parentModel,
                'survey' => $parentModel->paymentSurvey->id
            ]);
        }

        return Inertia::render('Survey/Edit', [
            'parentModel' => $parentModel,
            'paymentSurvey' => new PaymentSurvey()
                ->tap(function ($survey) use ($helper) {
                    $survey->customers = $helper->getCustomers();
                    $survey->addresses = $helper->getAddresses();
                }),
            'employmentStatuses' => PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                ->paymentLookupValues,
            'currentAccountForBusinesses' => PaymentLookupField::byIdentifier(LookupField::CURRENT_ACCOUNT_FOR_BUSINESS)
                ->paymentLookupValues,
            'titles' => PaymentLookupField::byIdentifier(LookupField::TITLE)
                ->paymentLookupValues,
            'allowSkip' => true,
            'showBasicQuestions' => true,
        ])->withViewData($helper->getViewData());
    }

    public function store(SubmitSurveyRequest $request, int $parent, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        $parentModel->paymentSurvey()
            ->create([
                ...$request->except(
                    'basicQuestionsCompleted',
                    'leaseQuestionsCompleted',
                    'financeQuestionsCompleted',
                    'leaseResponses',
                    'financeResponses',
                    'redirect',
                    'creditCheckConsent',
                ),
                ...($request->boolean('basicQuestionsCompleted') ? ['basic_questions_completed' => '1'] : []),
                ...($request->boolean('leaseQuestionsCompleted') ? ['lease_questions_completed' => '1'] : []),
                ...($request->boolean('financeQuestionsCompleted') ? ['finance_questions_completed' => '1'] : []),
                ...($request->boolean('creditCheckConsent') ? ['credit_check_consent' => '1'] : []),
                'lease_responses' => $request->get('leaseResponses'),
                'finance_responses' => $request->get('financeResponses'),
                'skipped' => false,
            ]);

        return redirect()
            ->route('payment.options', ['parent' => $parentModel]);
    }

    public function edit(Request $request, int $parent, PaymentSurvey $survey, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        return Inertia::render('Survey/Edit', [
            'parentModel' => $parentModel,
            'paymentSurvey' => $survey,
            'employmentStatuses' => PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                ->paymentLookupValues,
            'currentAccountForBusinesses' => PaymentLookupField::byIdentifier(LookupField::CURRENT_ACCOUNT_FOR_BUSINESS)
                ->paymentLookupValues,
            'titles' => PaymentLookupField::byIdentifier(LookupField::TITLE)
                ->paymentLookupValues,
            'allowSkip' => $request->get('proceed_to_checkout') ? false : true,
            'proceedToCheckout' => $request->get('proceed_to_checkout'),
            'showBasicQuestions' => true,
        ])->withViewData($helper->getViewData());
    }

    public function lease(Request $request, int $parent, PaymentSurvey $survey, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        return Inertia::render('Survey/Edit', [
            'parentModel' => $parentModel,
            'paymentSurvey' => $survey,
            'employmentStatuses' => PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                ->paymentLookupValues,
            'currentAccountForBusinesses' => PaymentLookupField::byIdentifier(LookupField::CURRENT_ACCOUNT_FOR_BUSINESS)
                ->paymentLookupValues,
            'titles' => PaymentLookupField::byIdentifier(LookupField::TITLE)
                ->paymentLookupValues,
            'title' => 'Lease Survey',
            'allowSkip' => false,
            'showBasicQuestions' => true,
            'redirect' => route('payment.lease.create', ['parent' => $parent, 'offerId' => $request->get('offerId')]),
            'basicIntroText' => 'We need you to answer these questions for your lease application.',
            'canChangePaymentMethod' => true,
        ])->withViewData($helper->getViewData());
    }

    public function finance(Request $request, int $parent, PaymentSurvey $survey, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        if (empty($survey->finance_responses)) {
            $survey->finance_responses = app(FinanceData::class);
        }

        return Inertia::render('Survey/Edit', [
            'parentModel' => $parentModel,
            'paymentSurvey' => $survey,
            'title' => 'Finance Survey',
            'financeResponses' => $survey->financeResponses ?? app(FinanceData::class),
            'maritalStatuses' => PaymentLookupField::byIdentifier(LookupField::MARITAL_STATUS)
                ->paymentLookupValues,
            'employmentStatuses' => PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                ->paymentLookupValues,
            'residentialStatuses' => PaymentLookupField::byIdentifier(LookupField::RESIDENTIAL_STATUS)
                ->paymentLookupValues,
            'nationalities' => PaymentLookupField::byIdentifier(LookupField::NATIONALITY)
                ->paymentLookupValues,
            'bankruptOrIvas' => PaymentLookupField::byIdentifier(LookupField::BANKRUPT_OR_IVA)
                ->paymentLookupValues,
            'currentAccountForBusinesses' => PaymentLookupField::byIdentifier(LookupField::CURRENT_ACCOUNT_FOR_BUSINESS)
                ->paymentLookupValues,
            'titles' => PaymentLookupField::byIdentifier(LookupField::TITLE)
                ->paymentLookupValues,
            'allowSkip' => false,
            'showBasicQuestions' => ! $survey->basic_questions_completed,
            'showFinanceQuestions' => true,
            'canChangePaymentMethod' => true,
            'redirect' => route('payment.finance.create', ['parent' => $parent, 'offerId' => $request->get('offerId')]),
        ])->withViewData($helper->getViewData());
    }

    public function update(SubmitSurveyRequest $request, int $parent, PaymentHelper $helper)
    {
        $parentModel = $this->bootstrap($parent, $helper);

        $survey = $parentModel->paymentSurvey;

        $survey
            ->update([
                ...$request->except(
                    'basicQuestionsCompleted',
                    'leaseQuestionsCompleted',
                    'financeQuestionsCompleted',
                    'leaseResponses',
                    'financeResponses',
                    'redirect',
                    'creditCheckConsent',
                ),
                ...($request->boolean('basicQuestionsCompleted') ? ['basic_questions_completed' => '1'] : []),
                ...($request->boolean('leaseQuestionsCompleted') ? ['lease_questions_completed' => '1'] : []),
                ...($request->boolean('financeQuestionsCompleted') ? ['finance_questions_completed' => '1'] : []),
                ...($request->boolean('creditCheckConsent') ? ['credit_check_consent' => '1'] : []),
                'lease_responses' => $request->get('leaseResponses'),
                'finance_responses' => $request->get('financeResponses'),
            ]);


        if ($request->get('proceed_to_checkout')) {

            $survey
                ->update([
                    'skipped' => false,
                ]);

            $offerId = PaymentOffer::where('payment_survey_id', $survey->id)
                ->where('selected', true)
                ->first()
                ->id;

                return \Inertia\Inertia::location(
                    route('payment.finance.create', ['parent' => $parentModel, 'offerId' => $offerId])
                );
        }

        if ($request->get('redirect')) {
            return redirect($request->get('redirect'));
        }

        // Clear offers if the survey was changed so that the options page is refreshed
        if ($survey->wasChanged()) {
            Log::debug('Clearing offers');
            $parentModel->paymentOffers()->delete();
            Log::debug('Offers cleared', ['offer_count' => $parentModel->paymentOffers()->count() ?? '0']);
        }

        return redirect()
            ->route('payment.options', ['parent' => $parentModel]);
    }
}
