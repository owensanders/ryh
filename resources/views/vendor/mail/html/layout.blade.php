<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<link href="https://fonts.bunny.net/css?family=figtree:400,600,700,800" rel="stylesheet" />
<style>
@media only screen and (max-width: 600px) {
.inner-body,
.footer {
width: 100% !important;
}

.header {
padding: 22px 22px 0 22px !important;
}

.inner-body .content-cell {
padding: 20px 22px 22px 22px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
text-align: center !important;
}
}

/* The theme is inlined, so dark mode has to win with !important. Colours are the
   app's dark `--hh-*` tokens, pre-blended against the dark card (#151a25). */
@media (prefers-color-scheme: dark) {
body,
.wrapper {
background-color: #07090f !important;
}

.inner-body {
background-color: #151a25 !important;
border-color: #2a2f39 !important;
}

.content-cell h1,
.content-cell h2,
.content-cell h3,
.content-cell p,
.content-cell td,
.brand-name,
.panel-content,
.panel-content p {
color: #f2f5f9 !important;
}

.brand-tagline,
.subcopy p,
.subcopy a {
color: #72767e !important;
}

.subcopy,
.table th {
border-color: #2a2f39 !important;
}

.panel-content {
background-color: #1a2130 !important;
}

.footer p,
.footer a {
color: #6a6c71 !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="480" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
