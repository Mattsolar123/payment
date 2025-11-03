<?php

namespace Mralston\Payment\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Mralston\Payment\Enums\LookupField;
use Mralston\Payment\Models\PaymentLookupField;
use Propaganistas\LaravelPhone\Rules\Phone;
use Propaganistas\LaravelPhone\PhoneNumber;

class SubmitSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->boolean('basicQuestionsCompleted')) {
            $rules = [
                ...[
                    // Customer validation rules
                    'customers' => ['required', 'array', 'min:1'],
                    'customers.*.title' => ['required', Rule::in(
                        PaymentLookupField::byIdentifier(LookupField::TITLE)
                            ?->paymentLookupValues
                            ?->pluck('value')
                    )],
                    'customers.*.firstName' => ['required', 'string', 'max:255'],
                    'customers.*.middleName' => ['nullable', 'string', 'max:255'],
                    'customers.*.lastName' => ['required', 'string', 'max:255'],
                    'customers.*.email' => ['required', 'string', 'email', 'max:255'],
                    'customers.*.mobile' => ['required','string', 'numeric'],
                    'customers.*.landline' => ['nullable', 'string', 'numeric'],
                    'customers.*.dateOfBirth' => ['required', 'date', 'before_or_equal:' . Carbon::now()->subYears(18)->toDateString()],
                    'customers.*.grossAnnualIncome' => ['required', 'numeric'],
                    'customers.*.netMonthlyIncome' => ['required', 'numeric'],
                    'customers.*.dependants' => ['required', 'numeric'],
                    'customers.*.employmentStatus' => ['required', Rule::in(
                        PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                            ?->paymentLookupValues
                            ?->pluck('value')
                    )],
                    'customers.*.currentAccountForBusiness' => ['required_if:customers.*.employmentStatus,self_employed', Rule::in(
                        PaymentLookupField::byIdentifier(LookupField::CURRENT_ACCOUNT_FOR_BUSINESS)
                            ?->paymentLookupValues
                            ?->pluck('value')
                    )],

                    // Address validation rules
                    'addresses' => ['required', 'array', 'min:1'],
                    'addresses.*.houseNumber' => ['required', 'string', 'max:255'],
                    'addresses.*.street' => ['required', 'string', 'max:255'],
                    'addresses.*.postCode' => ['required', 'string', 'max:255'],
                    'addresses.*.dateMovedIn' => ['required', 'date'],
                    //'addresses.*.uprn' => ['required_unless:addresses.*.manual,true'],
                    'addresses.*.homeAddress' => ['nullable','boolean'],

                    'creditCheckConsent' => 'accepted',
                ]
            ];
        }

        if ($this->boolean('leaseQuestionsCompleted')) {
            $rules = [
                ...$rules,
                ...[

                ]
            ];
        }

        if ($this->boolean('financeQuestionsCompleted')) {
            $rules = [
                ...$rules,
                ...[
                    'customers.0.maritalStatus' => 'required',
                    'customers.0.residentialStatus' => 'required',
                    'customers.0.nationality' => 'required',
                    'customers.0.bankruptOrIva' => 'required',
                    'customers.0.employmentStatus' => ['required', Rule::in(
                        PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)
                            ?->paymentLookupValues
                            ?->pluck('value')
                    )],

                    'financeResponses.occupation' => ['required', 'string'],
                    'financeResponses.employerName' => ['required', 'string'],
                    'financeResponses.dateStartedEmployment' => ['required', 'date'],

                    'financeResponses.employerAddress.address1' => ['required', 'string', 'max:255'],
                    'financeResponses.employerAddress.address2' => ['nullable', 'string', 'max:255'],
                    'financeResponses.employerAddress.county' => ['nullable', 'string', 'max:255'],
                    'financeResponses.employerAddress.postCode' => ['required', 'string', 'max:255'],
                    'financeResponses.employerAddress.uprn' => ['required_unless:financeResponses.employerAddress.manual,true'],

                    'financeResponses.bankAccount.bankName' => ['required', 'string', 'max:255'],
                    'financeResponses.bankAccount.accountName' => ['required', 'string', 'max:255'],
                    'financeResponses.bankAccount.accountNumber' => ['required', 'numeric', 'digits:8'],
                    'financeResponses.bankAccount.sortCode' => ['required', 'string', 'regex:/^\d{6}$|^\d{2}-\d{2}-\d{2}$/'],

                    'financeResponses.yearlyHouseholdIncome' => ['required', 'numeric'],

                    'financeResponses.monthlyMortgage' => ['required', 'numeric'],
                    'financeResponses.monthlyRent' => ['required', 'numeric'],
                ]
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'customers.required' => 'You must enter at least one customer.',
            'customers.*.title.required' => 'You must enter a title.',
            'customers.*.firstName.required' => 'You must enter a first name.',
            'customers.*.lastName.required' => 'You must enter a last name.',
            'customers.*.email.required' => 'You must enter an email address.',
            'customers.*.email.email' => 'The email address you entered is not valid.',
            'customers.*.mobile.required' => 'You must enter a mobile number.',
            'customers.*.mobile.numeric' => 'The mobile number you entered is not valid.',
            'customers.*.landline.numeric' => 'The mobile number you entered is not valid.',
            'customers.*.dateOfBirth.required' => 'You must enter a date of birth.',
            'customers.*.dateOfBirth.date' => 'The date of birth is not a valid date.',
            'customers.*.dateOfBirth.before_or_equal' => 'You must be at least 18 years old.',
            'customers.*.grossAnnualIncome.required' => 'You must enter a gross annual income.',
            'customers.*.grossAnnualIncome.numeric' => 'The gross annual income must be a number.',
            'customers.*.netMonthlyIncome.required' => 'You must enter a net monthly income.',
            'customers.*.netMonthlyIncome.numeric' => 'The net monthly income must be a number.',
            'customers.*.employmentStatus.required' => 'You must select an employment status.',
            'customers.*.employmentStatus.in' => 'The selected employment status is not valid.',
            'customers.*.currentAccountForBusiness.required_if' => 'You must specify whether your personal current account is used for business.',
            'customers.*.currentAccountForBusiness.in' => 'Current account usage is not valid.',
            'customers.*.dependants.required' => 'You must specify how many dependants you have.',
            'customers.*.dependants.numeric' => 'The number of dependants must be a number.',
            'customers.*.maritalStatus.required' => 'You must select your marital status.',
            'customers.*.residentialStatus.required' => 'You must select your residential status.',
            'customers.*.nationality.required' => 'You must select your nationality.',
            'customers.*.bankruptOrIva.required' => 'You must select your bankrupt or IVA status.',

            'addresses.required' => 'You must enter at least one address.',
            'addresses.*.houseNumber.required' => 'You must enter a house number.',
            'addresses.*.street.required' => 'You must enter a street name.',
            'addresses.*.address1.required' => 'You must enter the first line of the address.',
            'addresses.*.town.required' => 'You must enter a town.',
            'addresses.*.postCode.required' => 'You must enter a post code.',
            'addresses.*.dateMovedIn.required' => 'You must enter a date moved in.',
            'addresses.*.dateMovedIn.date' => 'The date moved in is not a valid date.',
            //'addresses.*.uprn.required_unless' => 'You must use the post code lookup button to select an exact address.',

            'creditCheckConsent.accepted' => 'You must consent to the identity and credit check being performed.',

            'financeResponses.occupation.required' => 'You must enter your occupation.',
            'financeResponses.employerName.required' => 'You must enter the name of your employer.',
            'financeResponses.dateStartedEmployment.required' => 'You must enter the date you started employment.',

            'financeResponses.employerAddress.address1.required' => 'You must enter the first line.',
            'financeResponses.employerAddress.town.required' => 'You must enter the town.',
            'financeResponses.employerAddress.postCode.required' => 'You must enter the post code.',
            //'financeResponses.employerAddress.uprn.required_unless' => 'You must use the post code lookup button to select an exact address.',

            'financeResponses.bankAccount.bankName.required' => 'You must enter the name of the bank.',
            'financeResponses.bankAccount.accountName.required' => 'You must enter the name of the account holder.',
            'financeResponses.bankAccount.accountNumber.required' => 'You must enter the account number.',
            'financeResponses.bankAccount.accountNumber.numeric' => 'The account number must be numeric.',
            'financeResponses.bankAccount.accountNumber.digits' => 'The account number must be 8 digits.',
            'financeResponses.bankAccount.sortCode.required' => 'You must enter the sort code.',
            'financeResponses.bankAccount.sortCode.regex' => 'The sort code must be in the format 123456 or 12-34-56.',

            'financeResponses.yearlyHouseholdIncome.required' => 'You must enter an annual household income amount.',
            'financeResponses.yearlyHouseholdIncome.numeric' => 'The annual household income must be a number.',

            'financeResponses.monthlyMortgage.required' => 'You must enter a monthly mortgage amount.',
            'financeResponses.monthlyMortgage.numeric' => 'The monthly mortgage amount must be a number.',
            'financeResponses.monthlyRent.required' => 'You must enter a monthly rent amount.',
            'financeResponses.monthlyRent.numeric' => 'The monthly rent amount must be a number.',

        ];
    }

    /**
     * Adds validation of address dates
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        // Only apply these rules if the relevant form section was submitted.
        if (!$this->boolean('basicQuestionsCompleted')) {
            return;
        }

        $validator->after(function ($validator) {
            $addresses = $this->input('addresses');

            // The 'required', 'array', and 'min:1' rules should catch this,
            // but it's a safe check before we proceed.
            if (!is_array($addresses) || empty($addresses)) {
                return;
            }

            // Ensure exactly one homeAddress is selected
            $homeCount = collect($addresses)->where('homeAddress', true)->count();
            if ($homeCount !== 1) {
                $validator->errors()->add('addresses', 'You must select exactly one home address.');
            }

            // Sort addresses by dateMovedIn to check them chronologically.
            $sortedAddresses = collect($addresses)->sortBy('dateMovedIn')->values();

            // Check if the oldest address provides at least 3 years of history.
            try {
                $oldestDate = Carbon::parse($sortedAddresses->first()['dateMovedIn']);

                if ($oldestDate->gt(Carbon::now()->subYears(3))) {
                    $validator->errors()->add(
                        'addresses', // Add error to the general addresses field for better UX.
                        'Please provide at least 3 years of address history.'
                    );
                }
            } catch (\Exception $e) {
                // This can happen if the date format is invalid. The 'date' rule
                // should catch this, but we add a fallback error message.
                $validator->errors()->add('addresses', 'One or more of the move-in dates provided is invalid.');
                return; // Stop further processing if a date is unparseable.
            }
        });
    }
}
