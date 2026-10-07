<x-mail::message>
# We couldn't take your payment

We tried to renew the HouseHub subscription for "{{ $householdName }}", but the payment didn't go through. This usually means the card has expired or been replaced.

We'll try again over the next few days. To keep your household running without interruption, please update the card from Manage billing on the House page.

<x-mail::button :url="$billingUrl">
Update payment details
</x-mail::button>

<x-slot:subcopy>
You're receiving this because you're an Owner of {{ $householdName }}. If you've already updated your card, you can ignore this email.
</x-slot:subcopy>
</x-mail::message>
