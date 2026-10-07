<script setup lang="ts">
import LegalDocument from '@/components/legal/LegalDocument.vue';
import Tbc from '@/components/legal/Tbc.vue';
import { formatPlanPrice } from '@/lib/househub';
import type { Legal, Plan } from '@/types/househub';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ legal: Legal; plan: Plan }>();

const entityKind = computed(() =>
    props.legal.entityType === 'limited_company'
        ? 'a company registered in England and Wales'
        : props.legal.entityType === 'sole_trader'
          ? 'a sole trader'
          : null,
);

const monthly = computed(() => formatPlanPrice(props.plan.priceMonthly));
const annual = computed(() => formatPlanPrice(props.plan.priceAnnual));

const sections = [
    { id: 'about', title: 'About us and these terms' },
    { id: 'eligibility', title: 'Who can use HouseHub' },
    { id: 'households', title: 'Households, roles and members' },
    { id: 'trial-and-subscription', title: 'Free trial and subscription' },
    { id: 'cancelling', title: 'Cancelling and refunds' },
    { id: 'acceptable-use', title: 'Acceptable use' },
    { id: 'your-content', title: 'Your content' },
    { id: 'ai-features', title: 'AI meal plans' },
    { id: 'availability', title: 'Availability and changes' },
    { id: 'suspension', title: 'Suspension and termination' },
    { id: 'liability', title: 'Our responsibility to you' },
    { id: 'changes', title: 'Changes to these terms' },
    { id: 'complaints', title: 'Complaints and disputes' },
    { id: 'general', title: 'General' },
];
</script>

<template>
    <LegalDocument title="Terms of Service" :updated="legal.termsUpdatedLabel" :version="legal.termsVersion" :sections="sections">
        <template #intro>
            <p>
                These terms are the agreement between you and us when you use {{ legal.tradingName }}. Please read them before you register — they
                explain what you can expect from us, what we expect from you, how billing and cancelling work, and the limits of our responsibility.
                Nothing in them affects your legal rights as a consumer.
            </p>
            <p class="mt-3">
                How we handle personal information is explained in our <Link :href="route('privacy')">Privacy Policy</Link>, which forms part of these
                terms.
            </p>
        </template>

        <h2 id="about">About us and these terms</h2>
        <p>
            {{ legal.tradingName }} is provided by <Tbc :value="legal.entityName" label="Legal name" /><template v-if="entityKind"
                >, {{ entityKind }}</template
            ><template v-if="legal.companyNumber"> (company number {{ legal.companyNumber }})</template>, whose address is
            <Tbc :value="legal.address" label="Address" /> ("we", "us", "our"). You can contact us at
            <Tbc :value="legal.supportEmail" label="Support email" />.
        </p>
        <p>
            A contract between you and us is formed when you create an account and tick the box to accept these terms. We record the date and the
            version you accepted. These terms are available in English only, and you can save or print this page at any time.
        </p>

        <h2 id="eligibility">Who can use HouseHub</h2>
        <ol>
            <li>You must be 18 or over and live in the UK to create an account or a household.</li>
            <li>
                A child or teenager under 18 may only use {{ legal.tradingName }} by joining a household when invited by an adult member. By inviting
                someone under 18, or approving their request to join, you confirm that you are their parent or guardian, or have that person's
                permission; that you agree to these terms on the child's behalf; and that you are responsible for their use of
                {{ legal.tradingName }}, including choosing a role appropriate to their age.
            </li>
            <li>{{ legal.tradingName }} is for personal and household use, not for business or commercial use.</li>
            <li>
                You must give accurate information when you register, keep your password secure and not share your account. Tell us straight away if
                you think someone else has accessed it. You are responsible for activity on your account unless it happened because of something we
                did wrong.
            </li>
        </ol>

        <h2 id="households">Households, roles and members</h2>
        <ol>
            <li>
                A household is a shared space. Members can see the household's chores, meals, lists, calendar, budget, documents and the names of
                other members, depending on their role (Owner, Adult, Teen or Child).
            </li>
            <li>
                Owners control who joins a household, what role each member has, the household's subscription, and whether it is deleted. Only invite
                and admit people you trust, and choose roles carefully.
            </li>
            <li>A household can have up to {{ plan.maxMembers }} members, counting pending invitations and join requests.</li>
            <li>
                If you leave or are removed from a household, you lose access to its contents, but your account and other households are unaffected.
                If the only Owner of a household deletes their account, the household and all of its contents are permanently deleted.
            </li>
        </ol>

        <h2 id="trial-and-subscription">Free trial and subscription</h2>
        <ol>
            <li>
                <strong>Free trial.</strong> Each new household gets a free trial of 30 days. You don't need to give card details to start, and you
                won't be charged anything for the trial. Each person can claim one free trial. When the trial ends, the household can't be used until
                an Owner subscribes; nothing is taken automatically.
            </li>
            <li>
                <strong>Price.</strong> A subscription to the {{ plan.name }} plan costs <strong>{{ monthly }} a month</strong> or
                <strong>{{ annual }} a year</strong>, including any VAT that applies. One subscription covers everyone in the household. The price
                you'll pay is shown before you confirm.
            </li>
            <li>
                <strong>Automatic renewal.</strong> Your subscription
                <strong>renews automatically at the end of each monthly or annual period, and we will charge your payment card each time</strong>,
                until you cancel. For annual subscriptions, we'll email you a reminder before each renewal, telling you the price and how to cancel.
            </li>
            <li>
                <strong>Payment.</strong> Payments are taken by our payment provider, Stripe. If a payment fails, we'll let you know and Stripe will
                retry it; if it still can't be taken, the household's access may be paused until the payment details are updated.
            </li>
            <li>
                <strong>Price changes.</strong> We may change the subscription price. We will tell you at least 30 days before a new price applies to
                you, and it will only take effect from your next renewal after that notice. If you don't want to pay the new price, you can cancel
                before it applies. Households that subscribed during our launch period keep their launch price for as long as their subscription stays
                active without a break.
            </li>
        </ol>

        <h2 id="cancelling">Cancelling and refunds</h2>
        <ol>
            <li>
                <strong>Cancel any time.</strong> An Owner can cancel the household's subscription at any time from the House page (Manage billing),
                or by contacting us. Cancelling is as easy as subscribing. The household keeps its access until the end of the period already paid
                for, and won't be charged again.
            </li>
            <li>
                <strong>Your 14-day right to cancel.</strong> As a consumer, you have a legal right to cancel a new subscription within 14 days of the
                day you first subscribe, without giving a reason. Because you have asked for the service to start straight away, the law would allow
                us to charge for the days you used it — but we don't: if you cancel within 14 days of your first payment, we will refund that payment
                in full, within 14 days of you telling us, using the same payment method. To cancel, email
                <Tbc :value="legal.supportEmail" label="Support email" /> with a clear statement that you are cancelling, or use the cancellation
                option in Manage billing and then email us to request your refund.
            </li>
            <li>
                <strong>Other refunds.</strong> Outside the 14-day period, payments already made aren't refundable for partly used periods, except
                where the law says otherwise — for example, if the service isn't provided with reasonable care and skill (see Our responsibility to
                you).
            </li>
            <li>
                <strong>Deleting your account</strong> (Settings → Profile) cancels the subscription of any household you are the only Owner of, but
                does not on its own give a refund.
            </li>
        </ol>

        <h2 id="acceptable-use">Acceptable use</h2>
        <p>You must not use {{ legal.tradingName }}, or allow anyone in your household to use it, to:</p>
        <ul>
            <li>break the law, or upload anything unlawful, including material that infringes someone else's rights;</li>
            <li>
                harass, bully, threaten or abuse anyone, or upload anything harmful or inappropriate for children who may be members of a household;
            </li>
            <li>upload viruses or other malicious code, or try to gain unauthorised access to our systems, other accounts or other households;</li>
            <li>
                copy, scrape, reverse-engineer or resell the service, or use automated means to access it other than through the features we provide;
            </li>
            <li>
                get around the limits of the service — such as household member limits, AI meal plan limits, reminder limits or upload sizes — or
                place an unreasonable load on it; or
            </li>
            <li>add personal information about other people that you have no right to share with your household.</li>
        </ul>

        <h2 id="your-content">Your content</h2>
        <ol>
            <li>
                Everything you add to {{ legal.tradingName }} — recipes, lists, events, documents and so on — stays yours. You give us a licence to
                store, copy and display it only as needed to provide the service to you and your household. This licence ends when the content is
                deleted, apart from copies in backups, which are overwritten as described in our Privacy Policy.
            </li>
            <li>You're responsible for your content and for making sure you have the right to upload it.</li>
            <li>
                We may remove content that we reasonably believe breaks these terms or the law, and we will tell you why unless the law prevents us.
            </li>
            <li>
                <strong>Keep your own copies.</strong> The document vault is a convenient place to keep household papers, but it is not a backup
                service. Keep originals or copies of anything important somewhere else as well.
            </li>
        </ol>

        <h2 id="ai-features">AI meal plans</h2>
        <ol>
            <li>
                AI meal plans are generated automatically by a third-party AI model, based on the preferences you enter. They can be wrong, incomplete
                or unsuitable.
            </li>
            <li>
                <strong>
                    Always check recipes and ingredients yourself — particularly for allergies, intolerances, medical diets and food safety — before
                    you cook or eat them.
                </strong>
                AI suggestions are not medical, nutritional or dietary advice. Estimated costs and cooking times are approximate.
            </li>
            <li>
                AI meal plans are limited in how often a household can generate them, and the feature may be paused, limited or withdrawn — for
                example if our provider is unavailable. You can always plan meals by hand.
            </li>
        </ol>

        <h2 id="availability">Availability and changes</h2>
        <ol>
            <li>
                We work hard to keep {{ legal.tradingName }} running, but it may sometimes be unavailable — for example during maintenance, updates or
                problems outside our control. You can check how things are running on our <Link :href="route('status')">Status</Link> page.
            </li>
            <li>
                We improve {{ legal.tradingName }} over time and may change, add or remove features. If we make a change that significantly reduces
                what your subscription gives you, we'll tell you in advance and you may cancel and receive a refund for any period you've paid for but
                not yet received.
            </li>
            <li>
                If we ever decide to close {{ legal.tradingName }}, we'll give you at least 60 days' notice, refund any period paid for beyond the
                closure date, and give you a reasonable opportunity to download your documents and information.
            </li>
        </ol>

        <h2 id="suspension">Suspension and termination</h2>
        <ol>
            <li>You can stop using {{ legal.tradingName }} and delete your account at any time from Settings → Profile.</li>
            <li>
                We may suspend or close an account or household if we reasonably believe it has seriously or repeatedly broken these terms, if
                required by law, or to protect other users or the service. Unless the law or a safety concern prevents us, we'll tell you why and,
                where it is reasonable, give you a chance to put things right first. If we close a household for a reason that isn't your fault, we'll
                refund any period paid for but not received.
            </li>
            <li>
                When an account or household is closed, its content is deleted as described in our Privacy Policy. Sections that by their nature
                should continue — such as Our responsibility to you and Complaints and disputes — continue to apply.
            </li>
        </ol>

        <h2 id="liability">Our responsibility to you</h2>
        <ol>
            <li>
                We must provide {{ legal.tradingName }} with reasonable care and skill, and as described to you, as required by the Consumer Rights
                Act 2015. If we don't, you may be entitled to have us put it right or, if we can't, to a refund of some or all of what you paid.
                Nothing in these terms affects these statutory rights. For more about your consumer rights, visit Citizens Advice.
            </li>
            <li>
                Nothing in these terms limits or excludes our liability for death or personal injury caused by our negligence, for fraud or fraudulent
                misrepresentation, or for anything else that cannot be limited or excluded by law.
            </li>
            <li>
                We are responsible for loss or damage you suffer that is a foreseeable result of us breaking these terms or failing to use reasonable
                care and skill. Loss or damage is foreseeable if it is obvious it will happen, or if both of us knew it might happen when the contract
                was made. We are not responsible for loss or damage that is not foreseeable.
            </li>
            <li>
                {{ legal.tradingName }} is for domestic and private use. We are not liable to you for any loss of profit, loss of business, business
                interruption or loss of business opportunity.
            </li>
            <li>
                If digital content or a service we provide damages a device or digital content belonging to you because we failed to use reasonable
                care and skill, we will either repair the damage or pay you compensation.
            </li>
            <li>
                We are not responsible for loss caused by something outside our reasonable control — such as a failure of the internet, a third-party
                provider's outage, or a natural disaster — but if such an event affects the service for an extended period, you may cancel and receive
                a refund for any period paid for but not received.
            </li>
            <li>
                Except as set out in 1, 2 and 5 above, our total liability to you under these terms is limited to the greater of the amount you paid
                us in the 12 months before the event giving rise to the claim, or £100.
            </li>
        </ol>

        <h2 id="changes">Changes to these terms</h2>
        <p>
            We may update these terms — for example, to reflect changes in the law or in the service. We'll give you at least 30 days' notice by email
            of any change that materially affects you, before it takes effect. If you don't agree to the change, you can cancel your subscription
            before it takes effect and we will refund any period paid for beyond that date. Minor changes, such as corrections or clearer wording, may
            take effect straight away. The version and date at the top of this page show when these terms last changed.
        </p>

        <h2 id="complaints">Complaints and disputes</h2>
        <ol>
            <li>
                If you have a problem, please tell us first at <Tbc :value="legal.supportEmail" label="Support email" />. We aim to resolve complaints
                quickly and will reply {{ legal.supportResponse }}.
            </li>
            <li>
                These terms are governed by the law of England and Wales. You can bring legal proceedings in the courts of England and Wales; if you
                live in Scotland or Northern Ireland, you can also bring proceedings in your local courts, and the mandatory consumer protection laws
                where you live still apply to you.
            </li>
        </ol>

        <h2 id="general">General</h2>
        <ol>
            <li>
                We may transfer our rights and obligations under these terms to another organisation, for example if {{ legal.tradingName }} is sold.
                We'll tell you, and this will not affect your rights. You may cancel and receive a refund for any period paid for but not received if
                you're unhappy with the transfer. You may only transfer your rights to someone else with our written consent.
            </li>
            <li>This contract is between you and us. No other person has any rights to enforce any of its terms.</li>
            <li>If a court finds part of these terms unlawful, the rest will continue in force.</li>
            <li>If we don't insist on something straight away, or delay in taking steps, we can still do so later.</li>
            <li>
                These terms, together with our <Link :href="route('privacy')">Privacy Policy</Link>, are the whole agreement between you and us about
                {{ legal.tradingName }}.
            </li>
        </ol>
    </LegalDocument>
</template>
