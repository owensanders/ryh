<script setup lang="ts">
import LegalDocument from '@/components/legal/LegalDocument.vue';
import Tbc from '@/components/legal/Tbc.vue';
import type { CookieSettings, Legal } from '@/types/househub';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ legal: Legal; cookies: CookieSettings }>();

const sessionLasts = computed(() => {
    if (props.cookies.expireOnClose) {
        return 'Until you close your browser';
    }

    const minutes = props.cookies.sessionLifetimeMinutes;
    const span = minutes % 60 === 0 ? `${minutes / 60} hour${minutes === 60 ? '' : 's'}` : `${minutes} minutes`;

    return `${span} after your last visit to a page, or until you sign out`;
});

const sections = [
    { id: 'what-cookies-are', title: 'What cookies are' },
    { id: 'cookies-we-use', title: 'The cookies we use' },
    { id: 'local-storage', title: 'Storage on your device' },
    { id: 'third-parties', title: 'Other companies' },
    { id: 'consent', title: 'Why we don’t ask for consent' },
    { id: 'control', title: 'Controlling cookies' },
    { id: 'changes', title: 'Changes to this policy' },
    { id: 'contact', title: 'Contact' },
];
</script>

<template>
    <LegalDocument title="Cookie Policy" :updated="legal.cookiesUpdatedLabel" :version="legal.cookiesVersion" :sections="sections">
        <template #intro>
            <p>
                {{ legal.tradingName }} uses a small number of cookies, all of them needed to sign you in and keep your account secure. We don't use
                analytics, advertising or tracking cookies, and we don't let anyone else set them on our site.
            </p>
            <p class="mt-3">
                This policy lists every cookie and every piece of browser storage we use. It sits alongside our
                <Link :href="route('privacy')">Privacy Policy</Link>.
            </p>
        </template>

        <h2 id="what-cookies-are">What cookies are</h2>
        <p>
            Cookies are small text files a website asks your browser to store, so it can recognise you from one page to the next. Some are essential —
            without them you couldn't stay signed in. Others are used to track what you do, often across different websites. We only use the essential
            kind.
        </p>

        <h2 id="cookies-we-use">The cookies we use</h2>
        <p>All of these are first-party cookies, set by {{ legal.tradingName }} itself, and all are strictly necessary.</p>
        <div class="legal-table">
            <table>
                <thead>
                    <tr>
                        <th>Cookie</th>
                        <th>What it does</th>
                        <th>How long it lasts</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <code>{{ cookies.sessionCookie }}</code>
                        </td>
                        <td>
                            Keeps you signed in and remembers your place as you move between pages. It holds only a random identifier, not your
                            details, and it can't be read by scripts on the page.
                        </td>
                        <td>{{ sessionLasts }}</td>
                    </tr>
                    <tr>
                        <td><code>XSRF-TOKEN</code></td>
                        <td>
                            Protects you against cross-site request forgery — another website trying to submit forms to {{ legal.tradingName }} as
                            you.
                        </td>
                        <td>The same as the cookie above</td>
                    </tr>
                    <tr>
                        <td><code>remember_web_…</code></td>
                        <td>
                            Keeps you signed in between visits on this device. It is only set if you tick <strong>Remember me</strong> when signing
                            in, and it is removed when you sign out.
                        </td>
                        <td>Up to {{ cookies.rememberDays }} days, or until you sign out</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 id="local-storage">Storage on your device</h2>
        <p>
            We also save a couple of settings in your browser's local storage. Unlike cookies, they are never sent to us — they stay on your device.
        </p>
        <div class="legal-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>What it does</th>
                        <th>How long it lasts</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>appearance</code></td>
                        <td>Remembers whether you chose light or dark mode. It is only saved when you use the theme switch.</td>
                        <td>Until you clear your browser's site data</td>
                    </tr>
                    <tr>
                        <td><code>pwa-install-dismissed-at</code></td>
                        <td>
                            Remembers that you tapped <strong>Not now</strong> on the "Get the HouseHub app" card, so it doesn't keep appearing. It is
                            only saved when you dismiss the card.
                        </td>
                        <td>The card stays hidden for 30 days; the setting stays until you clear your browser's site data</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>
            Our <strong>service worker</strong> — a small script that lets {{ legal.tradingName }} be installed on your home screen — keeps a copy of
            the app's own code, styles and a "You're offline" page in your browser's cache, so pages load quickly and something sensible shows when
            you lose your connection. It holds none of your household's information. If you turn on push notifications, your browser also keeps the
            subscription it uses to receive them; you can turn that off in Settings → Notifications, and signing out turns it off too.
        </p>

        <h2 id="third-parties">Other companies</h2>
        <ul>
            <li>
                <strong>Stripe</strong> — when an Owner subscribes or manages billing, they are taken to Stripe's own secure pages, where Stripe sets
                its own cookies to process the payment and prevent fraud. Those cookies are on Stripe's website, not ours, and are covered by
                <a href="https://stripe.com/cookies-policy/legal" target="_blank" rel="noopener">Stripe's cookie policy</a>.
            </li>
            <li>
                <strong>Bunny Fonts</strong> — our typefaces are loaded from Bunny Fonts, which sets no cookies. Your browser's request does share
                your IP address with them, as explained in our <Link :href="route('privacy') + '#who-we-share-with'">Privacy Policy</Link>.
            </li>
        </ul>
        <p>There are no advertising, social media or analytics scripts on {{ legal.tradingName }}.</p>

        <h2 id="consent">Why we don’t ask for consent</h2>
        <p>
            Under the Privacy and Electronic Communications Regulations, a website must ask before setting cookies — unless they are strictly
            necessary to provide a service you've asked for. Every cookie we use falls into that exemption, the service worker's copy of the app is
            needed for the app to work as you'd expect, and the settings above are only saved when you choose them. Push notifications are only turned
            on when you ask, and your browser asks for your permission first. That's why you won't see a cookie banner here.
        </p>
        <p>If we ever want to use a cookie that isn't strictly necessary, we'll update this policy and ask for your consent before setting it.</p>

        <h2 id="control">Controlling cookies</h2>
        <p>
            You can see, block and delete cookies in your browser's settings. If you block or delete ours, you'll be signed out and won't be able to
            sign in again until you allow them — they're what make signing in work. Signing out removes the "Remember me" cookie, and clearing your
            browser's site data (or uninstalling the app) removes everything above, including the service worker and its cache.
        </p>
        <p>
            Your browser's help pages explain how:
            <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a>,
            <a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Safari</a>,
            <a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank" rel="noopener">Firefox</a> and
            <a
                href="https://support.microsoft.com/en-gb/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09"
                target="_blank"
                rel="noopener"
                >Edge</a
            >.
        </p>

        <h2 id="changes">Changes to this policy</h2>
        <p>We'll update this page whenever the cookies we use change. The version and date at the top tell you when it last changed.</p>

        <h2 id="contact">Contact</h2>
        <p>
            Questions about cookies or your privacy? Email <Tbc :value="legal.privacyEmail" label="Privacy email" />, or see
            <Link :href="route('support')">Support</Link>.
        </p>
    </LegalDocument>
</template>
