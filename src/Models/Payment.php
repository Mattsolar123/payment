<?php

namespace Mralston\Payment\Models;

use App\File;
use Carbon\Carbon;
use GregoryDuckworth\Encryptable\EncryptableTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mralston\Payment\Enums\LookupField;
use Mralston\Payment\Events\PaymentUpdated;
use Mralston\Payment\Interfaces\PaymentParentModel;
use Mralston\Payment\Observers\PaymentObserver;

#[ObservedBy(PaymentObserver::class)]
class Payment extends Model
{
    use EncryptableTrait;
    use SoftDeletes;

    protected $dispatchesEvents = [
        'updated' => PaymentUpdated::class,
    ];

    protected $fillable = [
        'uuid',
        'parentable_type',
        'parentable_id',
        'reference',
        'total_cost',
        'amount',
        'deposit',
        'subsidy',
        'finance_lender_id',
        'finance_rate_id',
        'apr',
        'loan_term',
        'deferred_period',
        'monthly_payment',
        'total_payable',
        'payments_breakdown',
        'eligible',
        'gdpr_opt_in',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'marital_status',
        'homeowner_status',
        'has_mortgage',
        'british_citizen',
        'date_of_birth',
        'dependents_count',
        'bankrupt_or_iva',
        'email_address',
        'primary_telephone',
        'secondary_telephone',
        'addresses',
        'employment_status',
        'employer_ibc_ref',
        'employer_name',
        'employer_telephone',
        'employer_address',
        'employer_company_type',
        'employer_company_reg_date',
        'occupation',
        'time_with_employer',
        'gross_income_individual',
        'gross_income_household',
        'net_monthly_income_individual',
        'mortgage_monthly',
        'rent_monthly',
        'bank_name',
        'bank_account_holder_name',
        'bank_account_number',
        'bank_account_sort_code',
        'read_terms_conditions',
        'was_referred',
        'payment_status_id',
        'provider_foreign_id',
        'provider_request_data',
        'provider_response_data',
        'offer_expiration_date',
        'submitted_at',
        'decision_received_at',
        'signed_at',
        'sat_note_file_id',
        'credit_agreement_file_id',
        'payment_type_id',
        'provider_foreign_id',
        'first_payment',
        'monthly_payment',
        'term',
        'total_payable',
        'payment_status_id',
        'payment_provider_id',
        'created_at',
        'updated_at',
        'addresses',
        'prevent_payment_changes',
        'payment_link',
    ];

    protected $casts = [
        'total_price' => 'float',
        'loan_amount' => 'float',
        'deposit' => 'float',
        'subsidy' => 'float',
        'apr' => 'float',
        'loan_term' => 'integer',
        'deferred_period' => 'integer',
        'monthly_payment' => 'float',
        'total_payable' => 'float',
        'payments_breakdown' => 'array',
        'dependents_count' => 'integer',
        'addresses' => 'collection',
        'employer_address' => 'collection',
        'time_with_employer' => 'integer',
        'gross_income_individual' => 'float',
        'gross_income_household' => 'float',
        'net_monthly_income_individual' => 'float',
        'mortgage_monthly' => 'float',
        'rent_monthly' => 'float',
        'lender_response_data' => 'collection',
        'date_of_birth' => 'datetime',
        'offer_expiration_date' => 'datetime',
        'submitted_at' => 'datetime',
        'decision_received_at' => 'datetime',
        'signed_at' => 'datetime',
        'employer_company_reg_date' => 'datetime',
        'provider_request_data' => 'collection',
        'provider_response_data' => 'collection',
    ];

    protected $encryptable = [
        'bank_account_number',
        'bank_account_sort_code',
    ];

    protected $expirable = [
        'new',
        'pending',
        'referred',
        'conditional_accept',
        'accepted',
        'parked',
        'snagged',
    ];

    protected $attributes = [
        'payment_status_id' => 1
    ];

    /**
     * Ensure route model binding uses a fully-qualified column name,
     * avoiding ambiguous "id" when global scopes add joins.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName(); // default 'id' unless customized

        // qualifyColumn('id') -> 'payments.id'
        return $query->where($this->qualifyColumn($field), $value);
    }

    public function parentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentProvider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function paymentCancellations(): HasMany
    {
        return $this->hasMany(PaymentCancellation::class);
    }

    public function lastCancellation(): Attribute
    {
        return Attribute::get(function () {
            return Cache::remember('payment-' . ($this->id ?? 0) . '-last_cancellation', 1, function () {
                return $this->paymentCancellations()
                    ->orderBy('created_at', 'DESC')
                    ->first();
            });
        });
    }

    public function paymentOffer(): BelongsTo
    {
        return $this->belongsTo(PaymentOffer::class);
    }

	public function paymentProduct(): BelongsTo
	{
		return $this->belongsTo(PaymentProduct::class)
            ->withTrashed();
	}

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentLookupValue::class, 'marital_status', 'value')
            ->where('payment_lookup_field_id', PaymentLookupField::byIdentifier(LookupField::MARITAL_STATUS)->id);
    }

    public function residentialStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentLookupValue::class, 'residential_status', 'value')
            ->where('payment_lookup_field_id', PaymentLookupField::byIdentifier(LookupField::RESIDENTIAL_STATUS)->id);
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentLookupValue::class, 'employment_status', 'value')
            ->where('payment_lookup_field_id', PaymentLookupField::byIdentifier(LookupField::EMPLOYMENT_STATUS)->id);
    }

    public function nationalityValue(): BelongsTo
    {
        return $this->belongsTo(PaymentLookupValue::class, 'nationality', 'value')
            ->where('payment_lookup_field_id', PaymentLookupField::byIdentifier(LookupField::NATIONALITY)->id);
    }

    public function ibcRef(): Attribute
    {
        return Attribute::get(fn() => Str::of($this->uuid)->replace('-', '')->__toString());
    }

    public function setParent(PaymentParentModel $parent): static
    {
        $this->parentable()->associate($parent);

        return $this;
    }

    public function withPaymentType(PaymentType $paymentType): static
    {
        $this->payment_type_id = $paymentType->id;

        return $this;
    }

    public function withPaymentProduct(PaymentProduct $paymentProduct): static
    {
        $this->payment_product_id = $paymentProduct->id;

        return $this;
    }

    public function withSurvey(PaymentSurvey $survey): static
    {
        $customer = $survey->customers->first();

        $this->title = $customer['title'];
        $this->first_name = $customer['firstName'];
        $this->middle_name = $customer['middleName'];
        $this->last_name = $customer['lastName'];
        $this->marital_status = $customer['maritalStatus'] ?? null;
        $this->residential_status = $customer['residentialStatus'] ?? null;
        $this->date_of_birth = $customer['dateOfBirth'];
        $this->dependants = $customer['dependants'];
        $this->bankrupt_or_iva = filter_var($customer['bankruptOrIva'] ?? null, FILTER_VALIDATE_BOOLEAN);
        $this->email_address = $customer['email'];
        $this->primary_telephone = $customer['mobile'] ?? null;
        $this->secondary_telephone = $customer['landline'] ?? null;
        $this->addresses = $survey->addresses;
        $this->employment_status = $customer['employmentStatus'];
        $this->employer_name = $survey->finance_responses?->employerName;
        $this->employer_address = $survey->finance_responses?->employerAddress;
        $this->occupation = $survey->finance_responses?->occupation;
        $this->time_with_employer = floor(Carbon::parse($survey->finance_responses?->dateStartedEmployment)->diffInMonths());
        $this->gross_income_individual = $customer['grossAnnualIncome'];
        $this->gross_income_household = $survey->finance_responses->yearlyHouseholdIncome ?? $survey->customers->sum('grossAnnualIncome');
        $this->net_monthly_income_individual = $customer['netMonthlyIncome'];
        $this->mortgage_monthly = $survey->finance_responses?->monthlyMortgage;
        $this->rent_monthly = $survey->finance_responses?->monthlyRent;
        $this->bank_name = $survey->finance_responses?->bankAccount->bankName;
        $this->bank_account_holder_name = $survey->finance_responses?->bankAccount->accountName;
        $this->bank_account_number = $survey->finance_responses?->bankAccount->accountNumber;
        $this->bank_account_sort_code = $survey->finance_responses?->bankAccount->sortCode;

        return $this;
    }

    public function withOffer(PaymentOffer $offer): static
    {
        $this->reference = $offer->reference;
        $this->amount = $offer->amount;
        $this->payment_provider_id = $offer->payment_provider_id;
        $this->payment_product_id = $offer->payment_product_id;
        $this->payment_offer_id = $offer->id;
        $this->apr = $offer->apr;
        $this->term = $offer->term;
        $this->deferred = $offer->deferred;
        $this->upfront_payment = $offer->upfront_payment;
        $this->first_payment = $offer->first_payment;
        $this->monthly_payment = $offer->monthly_payment;
        $this->final_payment = $offer->final_payment;
        $this->total_payable = $offer->total_payable;
        $this->provider_foreign_id = $offer->provider_application_id;

        return $this;
    }

    public function landline(): Attribute
    {
        return Attribute::get(function () {
            if (preg_match('/0[1-6,8-9][0-9]{8,9}/', $this->primary_telephone)) {
                return $this->primary_telephone;
            }

            if (preg_match('/0[1-6,8-9][0-9]{8,9}/', $this->secondary_telephone)) {
                return $this->secondary_telephone;
            }

            return null;
        });
    }

    public function mobile(): Attribute
    {
        return Attribute::get(function () {
            if (preg_match('/07[0-9]{9}/', $this->primary_telephone)) {
                return $this->primary_telephone;
            }

            if (preg_match('/07[0-9]{9}/', $this->secondary_telephone)) {
                return $this->secondary_telephone;
            }

            return null;
        });
    }

    public function satNoteFile()
    {
        // TODO: Make the class dynamic
        return $this->hasOne(File::class, 'id', 'sat_note_file_id');
    }
}
