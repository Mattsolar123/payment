<x-mail::message>
# {{ $payment->paymentProvider->name }} Finance Application

Please follow the link below to view the application with {{ $payment->paymentProvider->name }}.

<x-mail::button :url="$payment->payment_link">View Application</x-mail::button>
Kind regards,<br>
{{ config('app.name') }}
</x-mail::message>
