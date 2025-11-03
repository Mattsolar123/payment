<?php

namespace Mralston\Payment\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public ?string $udprn = null,
        public ?string $uprn = null,
        public ?string $houseNumber = null,
        public ?string $street = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $town = null,
        public ?string $county = null,
        public ?string $postCode = null,
        public ?string $dateMovedIn = null,
        public bool $manual = false,
        public bool $homeAddress = false,
    ) {
        //
    }

    public function asString(): string
    {
        $map = [
            $this->houseNumber,
            $this->street,
            $this->address1,
            $this->address2,
            $this->town,
            $this->county,
            $this->postCode,
        ];

        return implode(' ', array_filter($map, function($value) {
            return $value !== null && trim($value) !== '';
        }));
    }   
    
}
