@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}">
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<img src="{{ asset('images/email/logo.png') }}" class="logo" width="36" height="36" alt="">
</td>
<td class="brand-cell">
<span class="brand-name">{{ config('app.name') }}</span>
<span class="brand-tagline">The home, organised.</span>
</td>
</tr>
</table>
</a>
</td>
</tr>
