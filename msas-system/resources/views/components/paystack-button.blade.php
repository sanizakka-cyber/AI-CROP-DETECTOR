{{--
    Paystack Inline JS Payment Button Component
    Usage:
    <x-paystack-button
        module="subscription"
        :amount="5000"
        description="Pro Plan - Monthly"
        label="Pay ₦5,000"
        :metadata="['plan' => 'pro', 'billing_cycle' => 'monthly']"
        onSuccess="window.location='/subscription/dashboard'"
    />
--}}

@props([
    'module',
    'moduleId'    => null,
    'amount',
    'description',
    'label'       => 'Pay Now',
    'metadata'    => [],
    'onSuccess'   => null,
    'class'       => '',
])

@php
$buttonId = 'ps-btn-' . uniqid();
$amountKobo = (int) round($amount * 100);
@endphp

<button
    id="{{ $buttonId }}"
    type="button"
    class="{{ $class }}"
    onclick="msasPaystack_{{ $buttonId }}(this)"
    style="cursor:pointer;"
>
    {{ $label }}
</button>

<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
function msasPaystack_{{ $buttonId }}(btn) {
    btn.disabled = true;
    btn.textContent = 'Processing…';

    fetch('{{ route('payment.initiate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({
            module:      '{{ $module }}',
            module_id:   {{ $moduleId ?? 'null' }},
            amount:      {{ $amount }},
            description: '{{ addslashes($description) }}',
            metadata:    @json($metadata),
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Could not initialize payment. Please try again.');
            btn.disabled = false;
            btn.textContent = '{{ $label }}';
            return;
        }

        const handler = PaystackPop.setup({
            key:       data.public_key,
            email:     data.email,
            amount:    data.amount_kobo,
            ref:       data.reference,
            currency:  'NGN',
            label:     '{{ addslashes($description) }}',
            onSuccess: function(txn) {
                btn.textContent = 'Verifying…';
                fetch('{{ route('payment.verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ reference: txn.reference }),
                })
                .then(r => r.json())
                .then(vr => {
                    if (vr.success) {
                        @if($onSuccess)
                        {{ $onSuccess }};
                        @else
                        window.location = vr.receipt_url;
                        @endif
                    } else {
                        alert(vr.message || 'Payment verification failed. Contact support.');
                        btn.disabled = false;
                        btn.textContent = '{{ $label }}';
                    }
                });
            },
            onCancel: function() {
                btn.disabled = false;
                btn.textContent = '{{ $label }}';
            },
        });
        handler.openIframe();
    })
    .catch(() => {
        alert('Network error. Please check your connection and try again.');
        btn.disabled = false;
        btn.textContent = '{{ $label }}';
    });
}
</script>
