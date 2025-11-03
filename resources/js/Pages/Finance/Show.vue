<script setup>

import {Head, router} from "@inertiajs/vue3";
import {computed, onMounted, ref} from "vue";
import {useEcho} from "@laravel/echo-vue";
import {formatCurrency} from "../../Helpers/Currency.js";
import {ArrowPathIcon} from "@heroicons/vue/16/solid/index.js";
import Card from "../../Components/Card.vue";
import Modal from "../../Components/Modal.vue";
import {decompress} from "../../Helpers/Compression.js";

const props = defineProps({
    parentModel: Object,
    survey: Object,
    payment: Object,
    offer: Object,
    disableChangePaymentMethodAfterCancellation: Boolean,
    disableChangePaymentMethodAfterCancellationReason: String,
});

const payment = ref(props.payment);

const cancelModal = ref(null);
const cancellationReason = ref();

onMounted(() => {
    payment.value = props.payment;
});

function cancel()
{
    if (!cancellationReason.value) {
        alert('You must provide a reason for cancelling this payment.');
        return;
    }

    router.post(route('payment.cancel', {
        parent: props.parentModel.id,
        payment: props.payment.id
    }), {
        payment_status_identifier: 'customer_cancelled',
        cancellation_reason: cancellationReason.value,
        disableChangePaymentMethodAfterCancellation: props.disableChangePaymentMethodAfterCancellation,
        source: 'rep',
        redirect: route('payment.start', {parent: props.parentModel.id}),
    }, {
        onSuccess: () => {
            console.log('Payment cancelled');
        },
        onError: (errors) => {
            console.error('Failed to cancel payment:', errors);
        }
    });
}

function restart()
{
    router.get(route('payment.start', {parent: props.parentModel.id}));
}

useEcho(
    `payments.${props.payment.id}`,
    'PaymentUpdated',
    (e) => {
        payment.value = decompress(e.payload);
    }
);

const disabledCancellationButtons = computed(() => {
    if (!cancellationReason.value) {
        return ['yes'];
    }

    return [];
});

console.log('Payment', payment.value.provider_response_data.errors);

</script>

<template>

    <Head>
        <title>Finance</title>
    </Head>

    <Modal title="Cancel Application" :buttons="['yes', 'no']" :disabled-buttons="disabledCancellationButtons" ref="cancelModal" @yes="cancel">
        <p class="text-sm text-gray-500 mb-4">
            Are you sure you want to cancel this payment?
        </p>
        <p v-if="disableChangePaymentMethodAfterCancellation" class="text-red-500 font-bold mb-4">
            WARNING: You cannot choose another payment method after cancelling.
            <br v-if="disableChangePaymentMethodAfterCancellationReason">
            {{ disableChangePaymentMethodAfterCancellationReason }}
        </p>
        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                Reason
            </label>
            <textarea
                id="reason"
                v-model="cancellationReason"
                placeholder="Please provide a reason for cancelling this payment..."
                rows="3"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
            ></textarea>
        </div>
    </Modal>

    <div class="p-4">

        <img v-if="payment.payment_status.processing && payment.payment_provider.animated_logo"
             :src="payment.payment_provider.animated_logo"
             class="max-w-1/3 h-14 mb-6"
             :alt="payment.payment_provider.name">
        <img v-else-if="payment.payment_provider.logo"
             :src="payment.payment_provider.logo"
             class="max-w-1/3 h-14 mb-6"
             :alt="payment.payment_provider.name">
        <h1 v-else class="text-4xl font-bold mb-6">
            {{ payment.payment_provider.name }}
        </h1>

        <div v-if="payment.payment_status.processing">

            <p class="mb-4">
                <ArrowPathIcon v-if="!payment.payment_provider.animated_logo" class="inline-block h-6 w-6 mr-2 text-black animate-spin"/>
                Your application is being processed. This page will update automatically. You are welcome to check back later.
            </p>

        </div>

        <div v-else-if="payment.payment_status.error">

            <button v-if="payment.payment_status.active && payment.payment_status.cancellable"
                    type="button"
                    class="float-right rounded bg-gray-600 px-2 py-1 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
                    @click="cancelModal.show">
                Cancel &amp; Restart Journey
            </button>
            <button v-else
                    type="button"
                    class="float-right rounded bg-gray-600 px-2 py-1 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
                    @click="restart">
                Restart Journey
            </button>

            <p class="mb-4">Looks like something went wrong with this application.</p>

            <table class="mb-4 w-full md:w-1/2">
                <tbody>
                    <tr>
                        <th class="bg-gray-100 p-1 mr-2">Reference</th>
                        <td class="bg-gray-100 p-1">
                            {{ payment.reference }}
                        </td>
                    </tr>
                    <tr>
                        <th class="p-1 mr-2">Plan</th>
                        <td class="p-1">
                            {{ offer?.name ?? payment.payment_product?.name }}
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-gray-100 p-1 mr-2">Status</th>
                        <td class="bg-gray-100 p-1">
                            {{ payment.payment_status.name }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <ul v-if="payment.provider_response_data?.errors" v-for="field in payment.provider_response_data?.errors" class="list-disc list-inside text-red-500">
                <li v-for="error in field">
                    {{ error }}
                </li>
            </ul>

            <ul v-else-if="payment.provider_response_data?.response?.RETURN_MESSAGE" class="list-disc list-inside text-red-500">
                <li>
                    {{ payment.provider_response_data?.response?.RETURN_MESSAGE.MESSAGE_TEXT }}
                </li>
            </ul>


            <Card v-else-if="payment.provider_response_data" class="mb-8 w-1/2" header-class="bg-gray-100" :collapsed="true">
                <template v-slot:header><b>More Info</b></template>
                <pre class="max-h-96 overflow-auto">{{ payment.provider_response_data }}</pre>
            </Card>

            <div v-else class="text-red-500">
                We don't have any further details about what went wrong. Please try again later.
            </div>

        </div>

        <div v-else>

            <button v-if="payment.payment_status.cancellable"
                    type="button"
                    class="float-right rounded bg-gray-600 px-2 py-1 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600"
                    @click="cancelModal.show">
                Cancel Application
            </button>

            <p class="mb-4">
                Great news! {{ payment.payment_provider.name }} have received your application.
                <span v-if="payment.payment_status.referred">They will be in touch soon.</span>
                <span v-else>Look out for further communications.</span>
            </p>

            <table class="mb-4 w-full md:w-1/2">
                <tbody>
                    <tr>
                        <th class="bg-gray-100 p-1 mr-2">Reference</th>
                        <td class="bg-gray-100 p-1">
                            {{ payment.reference }}
                        </td>
                    </tr>
                    <tr>
                        <th class="p-1 mr-2">Plan</th>
                        <td class="p-1">
                            {{ offer?.name ?? payment.payment_product?.name }}
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-gray-100 p-1 mr-2">Status</th>
                        <td class="bg-gray-100 p-1">
                            {{ payment.payment_status.name }}
                        </td>
                    </tr>
                    <tr v-if="offer?.first_payment">
                        <th class="p-1 mr-2">First Payment</th>
                        <td class="p-1">
                            {{ formatCurrency(offer.first_payment) }}
                        </td>
                    </tr>
                    <tr v-if="offer?.monthly_payment">
                        <th class="bg-gray-100 p-1 mr-2">Monthly Payment</th>
                        <td class="bg-gray-100 p-1">
                            {{ formatCurrency(offer.monthly_payment) }}
                        </td>
                    </tr>
                    <tr v-if="offer?.final_payment">
                        <th class="p-1 mr-2">Final Payment</th>
                        <td class="p-1">
                            {{ formatCurrency(offer.final_payment) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!--            <button class="mt-10 rounded-md bg-blue-600 px-3 py-2 text-center text-sm/6 font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">-->
            <!--                Pay Deposit-->
            <!--            </button>-->

        </div>

    </div>

</template>

<style scoped>

</style>
