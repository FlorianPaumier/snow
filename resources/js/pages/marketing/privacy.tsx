import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { LegalPage, LegalSection } from '@/components/marketing/legal';
import { ROUTES } from '@/components/marketing/routes';
import TextLink from '@/components/text-link';

type Section = { title: string; content: ReactNode };

function PrivacyContact({ locale }: { locale: 'fr' | 'en' }) {
    const { legal } = usePage().props;
    const operatorName = legal.operatorName ?? 'Frostline';
    const officerTitle =
        legal.privacyOfficerTitle ??
        (locale === 'fr'
            ? 'Responsable de la protection des renseignements personnels'
            : 'Privacy Officer');

    return (
        <div className="space-y-2">
            <p>
                <strong className="text-foreground">{operatorName}</strong>
                {legal.mailingAddress ? (
                    <>
                        <br />
                        {legal.mailingAddress}
                    </>
                ) : null}
                <br />
                {officerTitle}
                {legal.privacyEmail ? (
                    <>
                        <br />
                        <a href={`mailto:${legal.privacyEmail}`}>
                            {legal.privacyEmail}
                        </a>
                    </>
                ) : null}
            </p>
            <p>
                {locale === 'fr'
                    ? 'Vous pouvez également transmettre une demande ou une plainte par notre '
                    : 'You may also submit a request or complaint through our '}
                <TextLink href={ROUTES.contact}>
                    {locale === 'fr' ? 'page de contact' : 'contact page'}
                </TextLink>
                .
            </p>
        </div>
    );
}

const content: Record<
    'fr' | 'en',
    { title: string; description: string; updated: string; sections: Section[] }
> = {
    fr: {
        title: 'Politique de confidentialité',
        description:
            'Comment Frostline OPS recueille, utilise, communique et protège les renseignements personnels.',
        updated: '24 septembre 2026',
        sections: [
            {
                title: 'Portée et rôles',
                content: (
                    <>
                        <p>
                            Cette politique vise les utilisateurs de comptes,
                            les personnes qui communiquent avec nous et les
                            renseignements sur les clients qu’une organisation
                            saisit dans Frostline OPS.
                        </p>
                        <p>
                            Nous sommes responsables des renseignements liés aux
                            comptes, à la facturation, au soutien et au site
                            Web. Chaque organisation cliente contrôle les
                            dossiers qu’elle saisit; nous les traitons selon ses
                            instructions afin de fournir le Service.
                        </p>
                    </>
                ),
            },
            {
                title: 'Renseignements et finalités',
                content: (
                    <ul className="list-disc space-y-2 pl-6">
                        <li>
                            Noms, courriels, entreprise, identifiants et rôles
                            pour créer, sécuriser et administrer les comptes.
                        </li>
                        <li>
                            Données sur les clients, contrats, adresses,
                            paiements, reçus et notes pour fournir les fonctions
                            demandées.
                        </li>
                        <li>
                            État de l’abonnement et références reçus de Stripe
                            pour la facturation; les données complètes de carte
                            vont directement à Stripe.
                        </li>
                        <li>
                            Nom, courriel, entreprise et message du formulaire
                            de contact pour répondre et effectuer le suivi
                            demandé.
                        </li>
                        <li>
                            Adresse IP, navigateur, horodatage et journaux pour
                            exploiter, protéger et diagnostiquer le Service.
                        </li>
                    </ul>
                ),
            },
            {
                title: 'Fournisseurs et transferts',
                content: (
                    <>
                        <p>
                            Nous utilisons des fournisseurs d’hébergement et de
                            stockage, de paiement (Stripe), de courriel
                            transactionnel et de génération de documents
                            (Gotenberg). Ils ne reçoivent que les renseignements
                            nécessaires et doivent les protéger. Une divulgation
                            peut aussi avoir lieu lorsque la loi l’exige ou avec
                            un consentement valide.
                        </p>
                        <p>
                            Certains fournisseurs peuvent traiter des
                            renseignements hors Québec ou hors Canada. Avant un
                            tel transfert, nous évaluons les risques et
                            appliquons des mesures contractuelles et techniques
                            appropriées. Communiquez avec nous pour connaître
                            les lieux et catégories de fournisseurs à jour.
                        </p>
                        <p>
                            Nous ne vendons pas les renseignements, ne les
                            utilisons pas pour la publicité et n’entraînons pas
                            de modèles d’apprentissage automatique avec les
                            dossiers clients.
                        </p>
                    </>
                ),
            },
            {
                title: 'Témoins et décisions automatisées',
                content: (
                    <p>
                        Nous utilisons uniquement des témoins essentiels pour la
                        session, la sécurité, la langue et les préférences
                        d’interface. Nous n’utilisons actuellement ni témoins
                        publicitaires, ni suivi comportemental intersite, ni
                        localisation, ni décision automatisée produisant des
                        effets juridiques ou comparables.
                    </p>
                ),
            },
            {
                title: 'Conservation et suppression',
                content: (
                    <>
                        <p>
                            Les données sont conservées pendant que le compte
                            demeure actif, y compris lorsqu’un essai passe au
                            forfait Gratuit plafonné. Les demandes de contact
                            sont supprimées selon notre calendrier de
                            conservation lorsqu’elles ne sont plus nécessaires.
                            Les journaux de sécurité et documents fiscaux,
                            comptables ou de facturation peuvent être conservés
                            plus longtemps lorsque requis.
                        </p>
                        <p>
                            La suppression complète d’une organisation n’est pas
                            libre-service. Le propriétaire peut la demander par
                            la page de contact. Nous vérifierons son autorité,
                            indiquerons ce qui doit être conservé et
                            confirmerons la fin du traitement. Les sauvegardes
                            expirent selon leur rotation protégée normale.
                        </p>
                    </>
                ),
            },
            {
                title: 'Vos droits',
                content: (
                    <>
                        <p>
                            Sous réserve de la loi, vous pouvez demander l’accès
                            ou la rectification, retirer un consentement,
                            connaître les divulgations et la durée de
                            conservation, demander la suppression ou déposer une
                            plainte. Le tableau de bord exporte actuellement les
                            paiements d’une saison; il ne fournit pas un export
                            complet de l’organisation.
                        </p>
                        <p>
                            Faites votre demande sur notre{' '}
                            <TextLink href={ROUTES.contact}>
                                page de contact
                            </TextLink>
                            . Nous vérifierons votre identité et répondrons dans
                            le délai prévu par la loi. Si le dossier appartient
                            à un de nos clients, adressez-vous d’abord à cette
                            entreprise.
                        </p>
                    </>
                ),
            },
            {
                title: 'Sécurité et incidents',
                content: (
                    <p>
                        Nous appliquons des mesures adaptées à la sensibilité
                        des renseignements, dont le chiffrement en transit, le
                        hachage des mots de passe, les contrôles d’accès et
                        l’isolation des organisations. Nous consignons les
                        incidents et avisons les personnes et autorités lorsque
                        la loi l’exige. Aucun système n’est totalement sécurisé.
                    </p>
                ),
            },
            {
                title: 'Responsable et plaintes',
                content: <PrivacyContact locale="fr" />,
            },
            {
                title: 'Modifications',
                content: (
                    <p>
                        Nous pouvons modifier cette politique selon l’évolution
                        du Service ou de la loi. Nous mettrons à jour la date et
                        aviserons à l’avance les propriétaires de compte des
                        changements importants lorsque cela est approprié.
                    </p>
                ),
            },
        ],
    },
    en: {
        title: 'Privacy Policy',
        description:
            'How Frostline OPS collects, uses, discloses, and protects personal information.',
        updated: 'September 24, 2026',
        sections: [
            {
                title: 'Scope and roles',
                content: (
                    <>
                        <p>
                            This policy covers account users, people who contact
                            us, and personal information about a customer’s
                            clients entered in Frostline OPS.
                        </p>
                        <p>
                            We are responsible for account, billing, support,
                            and website information. Each customer controls the
                            records it enters; we process them on its
                            instructions to provide the Service.
                        </p>
                    </>
                ),
            },
            {
                title: 'Information and purposes',
                content: (
                    <ul className="list-disc space-y-2 pl-6">
                        <li>
                            Names, emails, company names, credentials, and roles
                            to create, secure, and administer accounts.
                        </li>
                        <li>
                            Client, contract, address, payment, receipt, and
                            note data to provide requested features.
                        </li>
                        <li>
                            Subscription status and references from Stripe for
                            billing; full card data goes directly to Stripe.
                        </li>
                        <li>
                            Contact-form name, email, company, and message to
                            answer and follow up on the request.
                        </li>
                        <li>
                            IP address, browser, timestamps, and logs to
                            operate, protect, and diagnose the Service.
                        </li>
                    </ul>
                ),
            },
            {
                title: 'Providers and transfers',
                content: (
                    <>
                        <p>
                            We use providers for hosting and storage, payments
                            (Stripe), transactional email, and document
                            generation (Gotenberg). They receive only what their
                            function requires and must protect it. We may also
                            disclose information where law requires or with
                            valid consent.
                        </p>
                        <p>
                            Some providers may process information outside
                            Quebec or Canada. Before such a transfer, we assess
                            privacy risks and use appropriate contractual and
                            technical safeguards. Contact us for current
                            processing locations and provider categories.
                        </p>
                        <p>
                            We do not sell personal information, use customer
                            records for advertising, or use them to train
                            machine-learning models.
                        </p>
                    </>
                ),
            },
            {
                title: 'Cookies and automated decisions',
                content: (
                    <p>
                        We use only essential cookies for sessions, security,
                        locale, and interface preferences. We do not currently
                        use advertising cookies, cross-site behavioural
                        tracking, location tracking, or automated decisions with
                        legal or similarly significant effects.
                    </p>
                ),
            },
            {
                title: 'Retention and deletion',
                content: (
                    <>
                        <p>
                            Data is kept while the account is active, including
                            when a trial moves to the capped Free plan. Contact
                            inquiries are deleted under our retention schedule
                            when no longer needed. Security logs and tax,
                            accounting, or billing records may be retained
                            longer where required.
                        </p>
                        <p>
                            Organization-wide deletion is not self-service. An
                            owner may request it through the contact page. We
                            verify authority, identify records that must remain,
                            and confirm completion. Backups expire through their
                            normal protected rotation.
                        </p>
                    </>
                ),
            },
            {
                title: 'Your rights',
                content: (
                    <>
                        <p>
                            Subject to law, you may request access or
                            correction, withdraw consent, ask about disclosure
                            and retention, request deletion, or complain. The
                            dashboard currently exports seasonal payment data;
                            it does not provide a complete organization export.
                        </p>
                        <p>
                            Submit a request through our{' '}
                            <TextLink href={ROUTES.contact}>
                                contact page
                            </TextLink>
                            . We verify identity and respond within the legally
                            required time. If a customer controls the record,
                            contact that business first.
                        </p>
                    </>
                ),
            },
            {
                title: 'Security and incidents',
                content: (
                    <p>
                        We use safeguards appropriate to sensitivity, including
                        encryption in transit, hashed passwords, access
                        controls, and tenant separation. We record incidents and
                        notify affected people and regulators where law
                        requires. No system is absolutely secure.
                    </p>
                ),
            },
            {
                title: 'Privacy Officer and complaints',
                content: <PrivacyContact locale="en" />,
            },
            {
                title: 'Changes',
                content: (
                    <p>
                        We may update this policy as the Service or law changes.
                        We will update the date and give account owners advance
                        notice of material changes where appropriate.
                    </p>
                ),
            },
        ],
    },
};

export default function Privacy() {
    const locale = usePage().props.locale === 'en' ? 'en' : 'fr';
    const page = content[locale];

    return (
        <LegalPage
            title={page.title}
            route={ROUTES.privacy}
            description={page.description}
            updated={page.updated}
            locale={locale}
        >
            {page.sections.map((section, index) => (
                <LegalSection
                    key={section.title}
                    number={String(index + 1)}
                    title={section.title}
                >
                    {section.content}
                </LegalSection>
            ))}
        </LegalPage>
    );
}
