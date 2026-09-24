import { usePage } from '@inertiajs/react';
import { LegalPage, LegalSection } from '@/components/marketing/legal';
import { ROUTES } from '@/components/marketing/routes';
import TextLink from '@/components/text-link';

function LegalIdentity({ locale }: { locale: 'fr' | 'en' }) {
    const { legal } = usePage().props;

    return (
        <>
            <strong className="text-foreground">
                {legal.operatorName ?? 'Frostline'}
            </strong>
            {legal.mailingAddress ? ` — ${legal.mailingAddress}` : null}
            {legal.privacyEmail ? (
                <>
                    {' — '}
                    <a href={`mailto:${legal.privacyEmail}`}>
                        {legal.privacyEmail}
                    </a>
                </>
            ) : null}
            {!legal.privacyEmail ? (
                <>
                    {' — '}
                    <TextLink href={ROUTES.contact}>
                        {locale === 'fr' ? 'nous joindre' : 'contact us'}
                    </TextLink>
                </>
            ) : null}
        </>
    );
}

export default function Terms() {
    const locale = usePage().props.locale === 'en' ? 'en' : 'fr';

    if (locale === 'fr') {
        return <FrenchTerms />;
    }

    return (
        <LegalPage
            title="Terms of Service"
            route={ROUTES.terms}
            description="The agreement between your business and Frostline for using the Frostline OPS back-office service."
            updated="September 24, 2026"
            locale="en"
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
                    the trial. When the trial ends, your organization moves to
                    the Free plan: everything stays writable, subject to the
                    Free plan&rsquo;s caps (one user and fifteen clients), until
                    you pick a paid plan; nothing is deleted while a trial or
                    subscription lapses.
                </p>
                <p>
                    Paid plans are billed annually through our payment
                    processor, Stripe. Payments are handled entirely by Stripe;
                    we never see or store your full card details. Subscriptions
                    renew automatically at the end of each billing period unless
                    you cancel before then. You can cancel at any time from your
                    billing settings; Pro features continue until the end of the
                    period you have already paid for. Payments are
                    non-refundable except where required by law or expressly
                    stated in an offer. After the paid period, your organization
                    returns to the Free plan — your data stays, subject to the
                    Free plan's caps.
                </p>
                <p>
                    If a payment fails, your organization returns to the Free
                    plan until the billing issue is resolved — your data stays,
                    subject to the Free plan's caps. Prices may change with
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
                    Service for you, and never sell it. Self-service export is
                    currently limited to seasonal payment data. An owner may
                    request a broader export or organization deletion through
                    our contact page. We verify authority and identify records
                    retained for legal, accounting, or security purposes.
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

            <LegalSection number="10" title="Intellectual property">
                <p>
                    The Service, its software, brand, and documentation are
                    owned by us or used under licence. We grant you a limited,
                    non-exclusive, non-transferable right to use the Service
                    while your account remains active.
                </p>
            </LegalSection>

            <LegalSection number="11" title="Governing law and contact">
                <p>
                    These Terms are governed by the laws of Quebec and the
                    applicable federal laws of Canada, without limiting any
                    mandatory protections. The competent courts of Quebec have
                    jurisdiction, subject to mandatory rules.
                </p>
                <p>
                    Operator: <LegalIdentity locale="en" />. If a provision is
                    found unenforceable, the rest of the agreement continues to
                    apply.
                </p>
            </LegalSection>
        </LegalPage>
    );
}

function FrenchTerms() {
    return (
        <LegalPage
            title="Conditions d’utilisation"
            route={ROUTES.terms}
            description="L’entente qui encadre l’utilisation de Frostline OPS par votre entreprise."
            updated="24 septembre 2026"
            locale="fr"
        >
            <LegalSection number="1" title="Acceptation et langue">
                <p>
                    Ces conditions constituent une entente entre l’entreprise
                    qui crée le compte et l’exploitant de Frostline OPS (« nous
                    ») relativement au site, au tableau de bord et aux fonctions
                    connexes (le « Service »). La version française doit être
                    remise et accessible avant qu’une autre langue soit choisie.
                </p>
                <p>
                    En créant un compte ou en utilisant le Service, vous
                    acceptez ces conditions et notre{' '}
                    <TextLink href={ROUTES.privacy}>
                        politique de confidentialité
                    </TextLink>
                    . Une personne agissant pour une entreprise déclare avoir le
                    pouvoir de la lier. Tout choix d’utiliser une version
                    anglaise doit être exprès et distinct.
                </p>
            </LegalSection>
            <LegalSection number="2" title="Compte et utilisation acceptable">
                <p>
                    Vous devez avoir au moins 18 ans ou l’âge de la majorité
                    applicable, fournir des renseignements exacts et protéger
                    les accès de votre équipe. Vous ne tenterez pas d’accéder
                    aux données d’autrui, de contourner la sécurité, de
                    surcharger le Service, d’y déposer du contenu illicite ou de
                    revendre l’accès sans entente écrite.
                </p>
            </LegalSection>
            <LegalSection number="3" title="Essai, forfaits et facturation">
                <p>
                    Le Service commence par un essai gratuit sans carte, puis
                    passe au forfait Gratuit plafonné. Les forfaits payants sont
                    facturés annuellement par Stripe, taxes applicables en sus
                    sauf indication contraire au paiement.
                </p>
                <p>
                    Ils se renouvellent automatiquement jusqu’à leur annulation
                    avant le renouvellement. L’annulation prend effet à la fin
                    de la période payée. Les paiements ne sont pas
                    remboursables, sauf lorsque la loi l’exige ou qu’une offre
                    l’indique. Nous donnerons un préavis raisonnable avant une
                    modification de prix.
                </p>
            </LegalSection>
            <LegalSection number="4" title="Vos données">
                <p>
                    Votre entreprise conserve ses droits sur les dossiers saisis
                    et nous autorise seulement à les traiter pour fournir le
                    Service. Vous devez disposer du fondement requis pour
                    traiter les renseignements de vos clients.
                </p>
                <p>
                    L’export libre-service est limité aux paiements d’une
                    saison. Pour un export plus large ou la suppression de
                    l’organisation, le propriétaire doit utiliser la page de
                    contact. Nous vérifierons son autorité et préciserons les
                    éléments devant être conservés pour des motifs légaux,
                    comptables ou de sécurité.
                </p>
            </LegalSection>
            <LegalSection
                number="5"
                title="Disponibilité, suspension et résiliation"
            >
                <p>
                    Nous ne garantissons pas un Service ininterrompu ou sans
                    erreur. Vous pouvez annuler en tout temps. Nous pouvons
                    suspendre ou résilier un compte en cas de violation
                    importante, de risque légal ou de paiement non résolu.
                    Lorsque possible, nous donnerons un avis et l’occasion de
                    demander les données avant leur suppression selon notre
                    calendrier.
                </p>
            </LegalSection>
            <LegalSection number="6" title="Propriété intellectuelle">
                <p>
                    Le Service, sa marque, son logiciel et sa documentation nous
                    appartiennent ou sont utilisés sous licence. Nous vous
                    accordons un droit limité, non exclusif et non transférable
                    de l’utiliser pendant la durée du compte.
                </p>
            </LegalSection>
            <LegalSection number="7" title="Garanties et responsabilité">
                <p>
                    Le Service est fourni « tel quel » et « selon sa
                    disponibilité », sous réserve des garanties qui ne peuvent
                    être exclues. Dans la mesure permise par la loi, aucune
                    partie n’est responsable des dommages indirects et notre
                    responsabilité totale est limitée aux sommes payées au cours
                    des douze mois précédant la réclamation.
                </p>
            </LegalSection>
            <LegalSection number="8" title="Droit applicable et contact">
                <p>
                    Les lois du Québec et les lois fédérales du Canada
                    applicables régissent les conditions sans retirer les
                    protections impératives. Les tribunaux compétents du Québec
                    ont compétence, sous réserve des règles impératives.
                </p>
                <p>
                    Exploitant : <LegalIdentity locale="fr" />. Si une
                    disposition est déclarée inapplicable, les autres
                    dispositions continuent de s’appliquer.
                </p>
            </LegalSection>
        </LegalPage>
    );
}
