import { LegalPage, LegalSection } from '@/components/marketing/legal';
import { ROUTES } from '@/components/marketing/routes';
import TextLink from '@/components/text-link';

export default function Privacy() {
    return (
        <LegalPage
            title="Privacy Policy"
            route={ROUTES.privacy}
            description="What personal information Frostline collects through Frostline OPS, how it is used, and the choices you have."
            updated="September 2, 2026"
        >
            <LegalSection number="1" title="Overview">
                <p>
                    Frostline OPS is back-office software for snow removal
                    businesses: your team records clients, contracts, payments,
                    and receipts so the season stays organized. This policy
                    explains the personal information we collect to provide the
                    Service, what we do with it, and the rights you have over
                    it. It applies to the people who administer and use accounts
                    (you and your team) and to the personal information about
                    your own clients that your team stores in the Service.
                </p>
                <p>
                    We are based in Canada and design the Service around
                    Quebec&rsquo;s privacy law (the Act respecting the
                    protection of personal information in the private sector) as
                    well as other applicable privacy laws.
                </p>
            </LegalSection>

            <LegalSection number="2" title="Information we collect">
                <p>
                    <strong className="text-foreground">
                        Account information.
                    </strong>{' '}
                    When you register, we collect the name, email address, and
                    company name you provide, along with sign-in credentials and
                    role assignments for the users you invite.
                </p>
                <p>
                    <strong className="text-foreground">
                        Business records you enter.
                    </strong>{' '}
                    Your team enters and stores records about your operation —
                    which may include the names, addresses, contact details,
                    contract terms, and payment records of your own clients.
                    This information is processed on your instructions as part
                    of providing the Service, and your organization controls it.
                </p>
                <p>
                    <strong className="text-foreground">Billing.</strong>{' '}
                    Subscriptions are processed by Stripe. Payment card details
                    go directly to Stripe and are never stored on our servers;
                    we receive only your subscription status and billing
                    reference.
                </p>
                <p>
                    <strong className="text-foreground">Technical data.</strong>{' '}
                    Like most web services, we keep server logs (such as IP
                    address, browser type, and timestamps) for security and
                    troubleshooting, and use essential session cookies to keep
                    you signed in.
                </p>
            </LegalSection>

            <LegalSection number="3" title="How we use information">
                <p>We use the information above to:</p>
                <ul className="list-disc space-y-1.5 pl-6">
                    <li>Operate, maintain, and secure the Service</li>
                    <li>
                        Store and organize the business records your team
                        creates
                    </li>
                    <li>
                        Manage subscriptions and send billing-related notices
                    </li>
                    <li>
                        Send transactional email, such as login codes and email
                        verification
                    </li>
                    <li>Provide support and respond to your requests</li>
                    <li>
                        Meet legal obligations and protect the rights and safety
                        of our customers and our business
                    </li>
                </ul>
                <p>
                    We do not sell personal information and we do not use your
                    business records for advertising or to train machine
                    learning models.
                </p>
            </LegalSection>

            <LegalSection number="4" title="How we share information">
                <p>
                    We share personal information only with the service
                    providers that help us run the Service — currently our
                    payment processor (Stripe), our email delivery provider, and
                    our hosting infrastructure — each bound to process it only
                    on our instructions. We may also disclose information where
                    required by law, or with your consent. If we ever engage a
                    new provider, it will be under equivalent obligations.
                </p>
            </LegalSection>

            <LegalSection number="5" title="Retention">
                <p>
                    We keep your organization&rsquo;s data for as long as your
                    account is active — including while a trial has lapsed into
                    the capped Free plan — so your season history is there when
                    you come back. When you request deletion or close your
                    account, we delete the organization&rsquo;s data, except
                    where we must retain a minimal record for legal, accounting,
                    or security reasons.
                </p>
            </LegalSection>

            <LegalSection number="6" title="Your rights">
                <p>
                    Subject to applicable law, you may request access to the
                    personal information we hold about you, ask for corrections,
                    request its deletion, or ask us to stop processing it. If
                    you are an account owner, you can also manage and export
                    your organization&rsquo;s records directly from the
                    dashboard. To exercise a right, contact us through our{' '}
                    <TextLink href={ROUTES.contact}>contact page</TextLink>; we
                    will respond within a reasonable time frame. If you are a
                    client of one of our customers, please direct your request
                    to the business you deal with — they control the records
                    they entered about you.
                </p>
            </LegalSection>

            <LegalSection number="7" title="Security">
                <p>
                    We protect personal information with measures appropriate to
                    its sensitivity, including encryption in transit, access
                    controls that scope each organization&rsquo;s data to its
                    own members, and hashed passwords. No system is perfectly
                    secure, but we treat a breach of your data as an incident to
                    be logged, remedied, and — where required by law — disclosed
                    to you.
                </p>
            </LegalSection>

            <LegalSection number="8" title="Changes to this policy">
                <p>
                    We may update this policy as the Service evolves. When we
                    do, we will update the date at the top of this page and, for
                    material changes, notify account owners by email before the
                    change takes effect.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
