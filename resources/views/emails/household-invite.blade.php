<x-mail::message>
@if ($hasAccount)
# You're invited to join {{ $householdName }}

Someone has invited your HouseHub account to join the household "{{ $householdName }}". Accept below to be added — you'll be able to pick which household you want to view once you're in.

<x-mail::button :url="$actionUrl">
Accept invite
</x-mail::button>
@else
# Join {{ $householdName }} on HouseHub

You've been invited to join the household "{{ $householdName }}" on HouseHub. Create your account below and you'll be added to the household automatically.

<x-mail::button :url="$actionUrl">
Create your account
</x-mail::button>
@endif

<x-slot:subcopy>
This link expires in 7 days. If you weren't expecting this invite, you can safely ignore this email.
</x-slot:subcopy>
</x-mail::message>
