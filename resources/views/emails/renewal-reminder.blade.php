<x-mail::message>
# Your subscription renews soon

The HouseHub subscription for "{{ $householdName }}" renews automatically on **{{ $renewsOnLabel }}**, and we'll charge **{{ $amountLabel }}** to the card on file for another year.

There's nothing you need to do to keep it. If you'd rather not renew, cancel before {{ $renewsOnLabel }} from Manage billing on the House page — you'll keep access until the end of the year you've paid for.

<x-mail::button :url="$billingUrl">
Manage billing
</x-mail::button>

<x-slot:subcopy>
You're receiving this because you're an Owner of {{ $householdName }}. See our [Terms of Service]({{ $termsUrl }}) for how renewals, cancelling and refunds work.
</x-slot:subcopy>
</x-mail::message>
