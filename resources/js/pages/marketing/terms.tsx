import { LegalPage, LegalSection } from '@/components/marketing/legal';
import { ROUTES } from '@/components/marketing/routes';
import TextLink from '@/components/text-link';

export default function Terms() {
    return (
        <LegalPage
            title="Terms of Service"
            route={ROUTES.terms}
            description="The agreement between your business and Frostline for using the Frostline OPS back-office service."
            updated="September 2, 2026"
        >
            <LegalSection number="1" title="Accepting these terms">
                <p>
                    These Terms of Service (&ldquo;Terms&rdquo;) are an
                    agreement between you (the business or organization that
                    registers an account) and Frostline (&ldquo;we&rdquo;,
                    &ldquo;us&rdquo;) and govern your access to and use of the
                    Frostline OPS software service, including the website,
                    dashboard, and related features (the &ldquo;Service&rdquo;).
                </p>
                <p>
                    By creating an account, subscribing to a plan, or otherwise
                    using the Service, you agree to these Terms and to our{' '}
                    <TextLink href={ROUTES.privacy}>Privacy Policy</TextLink>.
                    If you register on behalf of a company, you represent that
                    you have the authority to bind that company to these Terms.
                </p>
            </LegalSection>

            <LegalSection number="2" title="Your account">
                <p>
                    You must provide accurate and complete information when
                    registering — including your name, company name, and a valid
                    email address — and keep it up to date. You are responsible
                    for safeguarding the sign-in credentials of every user you
                    invite, for all activity that happens under your account,
                    and for keeping your team&rsquo;s access limited to people
                    who need it.
                </p>
                <p>
                    You must be at least 18 years old, or the age of legal
                    majority in your jurisdiction, to create an account.
                </p>
            </LegalSection>

            <LegalSection number="3" title="Subscriptions, trials, and billing">
                <p>
                    The Service starts with a free trial, so you can explore the
                    back office before paying anything — no card required for
                    the trial. When the trial ends, your organization&rsquo;s
                    data stays available in a read-only view until you pick up a
                    plan; nothing is deleted while a trial or subscription
                    lapses.
                </p>
                <p>
                    Paid plans are billed on a recurring monthly or annual basis
                    through our payment processor, Stripe. Payments are handled
                    entirely by Stripe; we never see or store your full card
                    details. Subscriptions renew automatically at the end of
                    each billing period unless you cancel before then. You can
                    cancel at any time from your billing settings; access
                    continues until the end of the period you have already paid
                    for.
                </p>
                <p>
                    If a payment fails, we may suspend access to paid features
                    until the billing issue is resolved. Prices may change with
                    reasonable notice before your next renewal.
                </p>
            </LegalSection>

            <LegalSection number="4" title="Acceptable use">
                <p>
                    You agree to use the Service only for lawful business
                    purposes — running your snow removal operation — and to not
                    use it to store unlawful content, infringe on the rights of
                    others, attempt to access other organizations&rsquo; data,
                    probe or overload the Service, or resell access to it
                    without a written agreement with us.
                </p>
            </LegalSection>

            <LegalSection number="5" title="Your data">
                <p>
                    The clients, contracts, payments, and receipts you record in
                    the Service belong to your business. We treat that content
                    as confidential, use it only to provide and support the
                    Service for you, and never sell it. You may export or
                    request the deletion of your organization&rsquo;s data at
                    any time by contacting us.
                </p>
                <p>
                    You are responsible for keeping the personal information
                    about your own clients that you store in the Service
                    accurate and lawfully obtained.
                </p>
            </LegalSection>

            <LegalSection number="6" title="Service availability">
                <p>
                    We work hard to keep the Service dependable through storm
                    season and off-season alike, but we do not guarantee that
                    the Service will be uninterrupted or error-free. Planned
                    maintenance and events beyond our reasonable control may
                    affect availability.
                </p>
            </LegalSection>

            <LegalSection
                number="7"
                title="Changes to the Service or these Terms"
            >
                <p>
                    We may improve or change the Service over time. If a change
                    materially reduces a paid feature you rely on, we will
                    notify you in advance. We may update these Terms from time
                    to time; when we do, we will update the date at the top of
                    this page and, for material changes, notify account owners
                    by email before the change takes effect. Continuing to use
                    the Service after new Terms take effect means you accept
                    them.
                </p>
            </LegalSection>

            <LegalSection number="8" title="Termination">
                <p>
                    You may close your account and stop using the Service at any
                    time. We may suspend or terminate an account if you
                    materially breach these Terms, fail to resolve a billing
                    issue, or create legal risk for us or other customers. Where
                    practical, we will give you notice and a chance to export
                    your data first.
                </p>
            </LegalSection>

            <LegalSection
                number="9"
                title="Disclaimers and limitation of liability"
            >
                <p>
                    The Service is provided &ldquo;as is&rdquo; and &ldquo;as
                    available&rdquo;, without warranties of any kind, except as
                    required by applicable law. To the maximum extent permitted
                    by law, neither party is liable to the other for indirect,
                    incidental, or consequential damages, and our total
                    liability arising out of or relating to the Service is
                    limited to the amounts you paid us in the twelve months
                    before the claim.
                </p>
            </LegalSection>

            <LegalSection number="10" title="Contact">
                <p>
                    Questions about these Terms can be sent through our{' '}
                    <TextLink href={ROUTES.contact}>contact page</TextLink>. If
                    a specific provision of these Terms is found unenforceable,
                    the rest of the agreement still applies.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
