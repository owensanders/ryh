<script setup lang="ts">
import LegalDocument from '@/components/legal/LegalDocument.vue';
import Tbc from '@/components/legal/Tbc.vue';
import type { Legal } from '@/types/househub';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ legal: Legal }>();

const entityKind = computed(() =>
    props.legal.entityType === 'limited_company'
        ? 'a company registered in England and Wales'
        : props.legal.entityType === 'sole_trader'
          ? 'a sole trader'
          : null,
);

const sections = [
    { id: 'who-we-are', title: 'Who we are' },
    { id: 'what-we-collect', title: 'What we collect' },
    { id: 'how-we-use-it', title: 'How we use it, and our lawful basis' },
    { id: 'household-sharing', title: 'Sharing within your household' },
    { id: 'children', title: 'Children' },
    { id: 'sensitive-information', title: 'Sensitive information' },
    { id: 'ai-meal-plans', title: 'AI meal plans' },
    { id: 'who-we-share-with', title: 'Who we share it with' },
    { id: 'international-transfers', title: 'International transfers' },
    { id: 'retention', title: 'How long we keep it' },
    { id: 'your-rights', title: 'Your rights' },
    { id: 'cookies', title: 'Cookies and local storage' },
    { id: 'security', title: 'Security' },
    { id: 'changes', title: 'Changes to this policy' },
    { id: 'contact', title: 'Contact and complaints' },
];
</script>

<template>
    <LegalDocument title="Privacy Policy" :updated="legal.privacyUpdatedLabel" :version="legal.privacyVersion" :sections="sections">
        <template #intro>
            <p>
                {{ legal.tradingName }} is a family home organiser. To do that job it holds the everyday details of your household — who lives there,
                what's for dinner, who's doing which chore. This policy explains what we collect, why, who else sees it, and the rights you have over
                it under UK data protection law (the UK GDPR, the Data Protection Act 2018 and the Privacy and Electronic Communications Regulations).
            </p>
            <p class="mt-3">
                The short version: we only use your information to run {{ legal.tradingName }} for you. We don't sell it, we don't show adverts, and
                we don't use it to profile you or your children.
            </p>
        </template>

        <h2 id="who-we-are">Who we are</h2>
        <p>
            {{ legal.tradingName }} is provided by <Tbc :value="legal.entityName" label="Legal name" /><template v-if="entityKind"
                >, {{ entityKind }}</template
            ><template v-if="legal.companyNumber"> (company number {{ legal.companyNumber }})</template>, of
            <Tbc :value="legal.address" label="Address" />
            ("we", "us", "our").
        </p>
        <p>
            We are the <strong>controller</strong> of the personal information described in this policy, which means we decide how and why it is used
            and we are responsible for protecting it. We are registered with the Information Commissioner's Office (ICO) under registration number
            <Tbc :value="legal.icoRegistration" label="ICO registration number" />.
        </p>
        <p>For anything about your personal information, email <Tbc :value="legal.privacyEmail" label="Privacy email" />.</p>

        <h2 id="what-we-collect">What we collect</h2>
        <h3>Information you give us</h3>
        <ul>
            <li><strong>Account details</strong> — your name, email address and password (stored only as a one-way hash we cannot read).</li>
            <li>
                <strong>Household details</strong> — the households you create or join, each member's name, initials, colour and role (Owner, Adult,
                Teen or Child), invitations, join codes and join requests.
            </li>
            <li>
                <strong>What you add to {{ legal.tradingName }}</strong> — chores and who they're assigned to; meal plans, recipes and ingredients;
                shopping lists; calendar events and who's attending; budget income and spending categories; and documents you upload to the document
                vault.
            </li>
            <li><strong>Messages to us</strong> — anything you send when you contact support.</li>
        </ul>
        <h3>Information created as you use the service</h3>
        <ul>
            <li>In-app notifications about activity in your household (for example, a chore assigned to you).</li>
            <li>
                If you turn on push notifications on a device, the push address your browser gives us for that device and the keys used to encrypt
                messages to it, plus which kinds of notification (chores, calendar, meals) you've turned off.
            </li>
            <li>Records of when your account was created, when you verified your email, and which version of our Terms you accepted.</li>
            <li>For Teen and Child members, a record of which adult confirmed they are the child's parent or guardian, and when.</li>
            <li>
                Technical information needed to keep you signed in and secure — your IP address, browser and device type (user agent), and session
                identifiers. We also briefly use IP addresses to limit repeated sign-in attempts and other abuse.
            </li>
        </ul>
        <h3>Payment information</h3>
        <p>
            Subscriptions are paid through <strong>Stripe</strong>. You enter your card details on Stripe's own secure pages — they never pass through
            or are stored on our systems. Stripe tells us your subscription status, billing interval, the card brand and its last four digits, and
            gives us a customer reference we use to link your household to its subscription.
        </p>
        <p>We do not collect your date of birth, and we don't ask for special category information (see Sensitive information below).</p>

        <h2 id="how-we-use-it">How we use it, and our lawful basis</h2>
        <p>Data protection law requires us to have a lawful basis for each way we use personal information. Ours are:</p>
        <div class="legal-table">
            <table>
                <thead>
                    <tr>
                        <th>What we do</th>
                        <th>Lawful basis</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Create and run your account and households, store what you add, and show it to the members of your household.</td>
                        <td>Performance of our contract with you</td>
                    </tr>
                    <tr>
                        <td>
                            Send emails you need — email verification, password resets, household invitations, billing and subscription notices, and
                            important changes to the service or these terms.
                        </td>
                        <td>Performance of our contract with you</td>
                    </tr>
                    <tr>
                        <td>
                            Send push notifications to the devices you've turned them on for. You choose to turn them on, your browser asks your
                            permission first, and you can turn them off at any time.
                        </td>
                        <td>Performance of our contract with you</td>
                    </tr>
                    <tr>
                        <td>Take payment and manage your subscription through Stripe.</td>
                        <td>Performance of our contract with you</td>
                    </tr>
                    <tr>
                        <td>Generate AI meal plans when you ask for one.</td>
                        <td>Performance of our contract with you</td>
                    </tr>
                    <tr>
                        <td>
                            Keep the service secure and working — preventing fraud and abuse, rate-limiting, diagnosing faults, and keeping backups.
                        </td>
                        <td>Legitimate interests (keeping {{ legal.tradingName }} and its users safe)</td>
                    </tr>
                    <tr>
                        <td>Answer your support requests.</td>
                        <td>Legitimate interests (helping you), or our contract with you where it concerns your account</td>
                    </tr>
                    <tr>
                        <td>Keep billing and accounting records.</td>
                        <td>Legal obligation (tax and accounting law)</td>
                    </tr>
                    <tr>
                        <td>Respond to lawful requests from authorities, and establish or defend legal claims.</td>
                        <td>Legal obligation, or legitimate interests</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>
            Where we rely on legitimate interests, we have weighed them against your rights and expectations, and you can object at any time (see Your
            rights). We do not send marketing emails. If we ever want to, we will ask for your consent first and you can withdraw it whenever you
            like.
        </p>
        <p>We do not make decisions about you based solely on automated processing that have legal or similarly significant effects.</p>

        <h2 id="household-sharing">Sharing within your household</h2>
        <p>
            {{ legal.tradingName }} is a shared space. Anything added to a household — chores, meals, lists, events, budget figures, documents and the
            names of its members — can be seen by the other members of that household, subject to their role. Owners can invite and remove members and
            change roles. Please only add information about other people that you're entitled to share with your household.
        </p>
        <p>
            If you are a household's Owner, you decide who joins it and what they can see. If you add information about someone who is not a member
            (for example, a child's school club in the calendar), you are responsible for having a good reason to do so.
        </p>

        <h2 id="children">Children</h2>
        <p>
            Accounts can only be created by people aged 18 or over, except that a child or teenager may join a household as a Teen or Child member
            when invited by an adult. Before a Teen or Child is added, that adult must confirm they are the child's parent or guardian (or have their
            permission), take responsibility for the child's use of {{ legal.tradingName }}, and agree to our Terms on the child's behalf. We keep a
            record of that confirmation for as long as the child is a member. We designed the Teen and Child roles so that younger members can see and
            tick off what's relevant to them while adults manage the household.
        </p>
        <p>In line with the ICO's Age Appropriate Design Code (the Children's Code), for every member, including children:</p>
        <ul>
            <li>we collect only what is needed to run the household features they use;</li>
            <li>we never show advertising, and never profile or track them for marketing;</li>
            <li>we don't share their information with anyone outside their household except the service providers listed below;</li>
            <li>we don't use design tricks to encourage them to share more or spend more time in the app; and</li>
            <li>a parent or guardian can ask us to access, correct or delete a child's information at any time.</li>
        </ul>
        <p>If you believe a child has created an account without a parent or guardian's involvement, please contact us and we will delete it.</p>

        <h2 id="sensitive-information">Sensitive information</h2>
        <p>
            We don't ask for "special category" information, such as health details, religious beliefs or ethnicity. But because
            {{ legal.tradingName }}
            is a free-form household organiser, you might choose to record some — for example, a hospital appointment in the calendar, a dietary
            requirement in a meal plan, or a medical letter in the document vault. If you do, it is used only to provide the feature you put it in,
            and shown only to your household. Please think carefully before adding sensitive information about other people, and don't upload anything
            you don't need to store.
        </p>

        <h2 id="ai-meal-plans">AI meal plans</h2>
        <p>
            When an adult in your household asks for an AI meal plan, we send the preferences entered for that request — number of portions, weekly
            food budget, diets, ingredients to avoid or use up, cooking confidence, kitchen equipment, any notes, and the names of recently planned
            meals — to our AI provider, <strong>Anthropic</strong>, to generate suggestions. We do not send names, email addresses or other details
            that identify who is in your household, so please don't include them in the notes.
        </p>
        <p>
            Under its commercial terms, Anthropic processes this information only to provide the service to us. It does not use it to train its
            models, and keeps it only for a limited period for safety and abuse monitoring. AI suggestions are just that — suggestions. You decide
            what to keep.
        </p>

        <h2 id="who-we-share-with">Who we share it with</h2>
        <p>
            We don't sell personal information, and we don't share it with advertisers or data brokers. We share it only with service providers who
            help us run {{ legal.tradingName }} ("processors"), under written contracts that require them to keep it secure and use it only on our
            instructions:
        </p>
        <div class="legal-table">
            <table>
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>What for</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><Tbc :value="legal.hostingProvider" label="Hosting provider" /></td>
                        <td>Hosting the application and its database</td>
                    </tr>
                    <tr>
                        <td>Amazon Web Services</td>
                        <td>Storing documents uploaded to the document vault</td>
                    </tr>
                    <tr>
                        <td><Tbc :value="legal.mailProvider" label="Email provider" /></td>
                        <td>Delivering account, invitation and billing emails</td>
                    </tr>
                    <tr>
                        <td>Stripe</td>
                        <td>
                            Taking subscription payments. Stripe also acts as an independent controller for some purposes, such as fraud prevention
                            and meeting its own legal obligations — see Stripe's privacy policy.
                        </td>
                    </tr>
                    <tr>
                        <td>Anthropic</td>
                        <td>Generating AI meal plans (see above)</td>
                    </tr>
                    <tr>
                        <td>Bunny Fonts (BunnyWay d.o.o., EU)</td>
                        <td>Serving the typefaces our pages use. Your browser's request reveals your IP address to them; they don't set cookies.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>
            <strong>Push notifications</strong> are delivered through the push service run by your browser or phone's maker — Google for Chrome and
            Android, Apple for Safari and iPhone, Mozilla for Firefox and Microsoft for Edge. Your browser chooses the service, not us. Each message
            is encrypted on our servers so that only your device can read it; the push service sees only that a message was sent to your device's push
            address, and when.
        </p>
        <p>
            We may also disclose information if the law requires it, to protect someone's safety, or to a buyer or successor if
            {{ legal.tradingName }} is sold or reorganised — in which case this policy would continue to apply to your information, and we would tell
            you.
        </p>

        <h2 id="international-transfers">International transfers</h2>
        <p>
            Some of our providers are based in, or use, countries outside the UK, including the European Economic Area and the United States. Where
            personal information is transferred outside the UK, we make sure it is protected by one of the safeguards UK law requires: a UK adequacy
            regulation (which covers the EEA, and US organisations certified under the UK Extension to the EU–US Data Privacy Framework), or the ICO's
            International Data Transfer Agreement or Addendum to the EU Standard Contractual Clauses. You can ask us for details of the safeguard for
            any provider.
        </p>

        <h2 id="retention">How long we keep it</h2>
        <ul>
            <li><strong>Your account</strong> — for as long as you keep it.</li>
            <li>
                <strong>When you delete your account</strong> — it is deleted straight away. If you are the only Owner of a household, the household
                and everything in it (chores, meals, lists, calendar, budget and documents) is deleted too and its subscription cancelled. If the
                household has another Owner, it carries on without you.
            </li>
            <li>
                <strong>Backups</strong> — deleted information can remain in our backups for up to {{ legal.backupRetentionDays }} days before it is
                overwritten. We don't restore from backups except to recover from a fault.
            </li>
            <li>
                <strong>Billing records</strong> — invoices and payment records are kept for six years after the end of the tax year they relate to,
                as UK tax law requires. Stripe keeps its own records under its own policy.
            </li>
            <li><strong>Sign-in sessions</strong> — expire after a period of inactivity, or when you sign out.</li>
            <li>
                <strong>Push notification addresses</strong> — kept until you turn notifications off on that device, sign out on it, or delete your
                account, or until the push service tells us the address no longer works.
            </li>
            <li><strong>Support emails</strong> — kept for up to two years after your query is resolved, unless they relate to a dispute.</li>
        </ul>
        <p>
            Notifications, households you leave and members removed from a household are handled by the same rules: removing someone from a household
            removes their access, not their account.
        </p>

        <h2 id="your-rights">Your rights</h2>
        <p>Under UK data protection law you have the right to:</p>
        <ul>
            <li><strong>access</strong> the personal information we hold about you and get a copy of it;</li>
            <li><strong>rectification</strong> — have inaccurate information corrected (you can change most of it yourself in Settings);</li>
            <li><strong>erasure</strong> — have your information deleted (you can delete your account yourself in Settings → Profile);</li>
            <li><strong>restrict</strong> how we use your information in certain circumstances;</li>
            <li><strong>data portability</strong> — receive information you gave us in a structured, machine-readable format;</li>
            <li><strong>object</strong> to our use of your information where we rely on legitimate interests; and</li>
            <li><strong>withdraw consent</strong> at any time, where we rely on consent.</li>
        </ul>
        <p>You can turn push notifications on or off, for each device and each kind of notification, in Settings → Notifications.</p>
        <p>
            To exercise any of these rights, email <Tbc :value="legal.privacyEmail" label="Privacy email" />. We'll reply within one month (extendable
            by two months for complex requests, in which case we'll tell you why). It's free, unless a request is clearly unfounded or excessive. We
            may need to confirm your identity first. A parent or guardian can make a request on behalf of their child.
        </p>
        <p>
            Some information in a household is about more than one person (a shared calendar event, for example). We'll balance everyone's rights when
            responding to a request that involves other members' information.
        </p>

        <h2 id="cookies">Cookies and local storage</h2>
        <p>
            We use only the cookies that are strictly necessary to sign you in and keep your account secure, plus a couple of settings saved on your
            device (light or dark mode, and whether you've dismissed the "Get the app" card) and a copy of the app's own files so it loads quickly and
            can show a page when you're offline. We do not use analytics, advertising or tracking cookies. Our
            <Link :href="route('cookies')">Cookie Policy</Link> lists each one, what it does and how long it lasts.
        </p>

        <h2 id="security">Security</h2>
        <p>
            We protect your information with measures appropriate to the risk, including encryption in transit (HTTPS), hashed passwords, role-based
            access within households, signed and expiring invitation links, limits on repeated sign-in attempts, and restricting access to production
            systems to the people who run the service. No system is perfectly secure, but if a breach puts your rights at risk we will tell you and
            the ICO as the law requires.
        </p>
        <p>
            You can help by using a strong, unique password and only inviting people you trust into your household. The document vault is not a
            substitute for keeping your own copies of important papers.
        </p>

        <h2 id="changes">Changes to this policy</h2>
        <p>
            We'll update this policy if what we do with personal information changes. The version and date at the top tell you when it last changed.
            If a change is significant, we'll email account holders before it takes effect.
        </p>

        <h2 id="contact">Contact and complaints</h2>
        <p>
            Questions or concerns about your personal information? Email <Tbc :value="legal.privacyEmail" label="Privacy email" />, or see
            <Link :href="route('support')">Support</Link> for other ways to reach us. You can also write to us at
            <Tbc :value="legal.address" label="Address" />.
        </p>
        <p>
            If you're unhappy with how we've handled your information, you have the right to complain to the Information Commissioner's Office —
            <a href="https://ico.org.uk/make-a-complaint/" target="_blank" rel="noopener">ico.org.uk/make-a-complaint</a> or 0303 123 1113. We'd
            appreciate the chance to put things right first, so please contact us before going to the ICO.
        </p>
    </LegalDocument>
</template>
