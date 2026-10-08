import { GreenwashingAudit } from '../types.js';

export const initialSampleAudits: GreenwashingAudit[] = [
  {
    id: 'gw_sample_1',
    url: 'https://alpes-menuiserie-durable.fr',
    cleanUrl: 'alpes-menuiserie-durable.fr',
    companyName: 'Alpes Menuiserie Durable (Menuiserie & Agencement)',
    sector: 'Bâtiment, Matériaux & Menuiserie Bois',
    region: 'Auvergne-Rhône-Alpes',
    department: '63 - Puy-de-Dôme',
    analyzedAt: '2026-09-29T10:30:00Z',
    riskLevel: 'critique',
    riskScore: 28, // 28/100 = Risque juridique très élevé
    totalBannedWordsDetected: 4,
    source: 'interne',
    prospectStatus: 'nouveau',
    assignedTo: 'collab_1',
    detectedClaims: [
      {
        id: 'claim_1',
        term: 'neutre en carbone',
        quoteInText: 'Nos fenêtres et parquets sont 100% neutres en carbone grâce à notre partenariat de plantation d’arbres.',
        category: 'neutralite',
        severity: 'interdit_strict',
        legalRisk: 'Interdiction stricte de l’allégation "neutre en carbone" sur la base de la seule compensation par plantation d’arbres sans bilan Scope 1-2-3 public ni trajectoire de réduction validée.',
        lawArticle: 'Décret n° 2022-539 du 13 avril 2022 (Loi Climat & Résilience art. L229-68) & Directive Green Claims',
        sanction: 'Amende administrative de 100 000 € par la DGCCRF, obligation d’affichage public du rectificatif.',
        requiredProof: 'Bilan Carbone® Scope 1-2-3 audité selon la méthode ADEME + FDES vérifiée par tierce partie sur la base INIES.',
        recommendedService: 'bilan_carbone',
      },
      {
        id: 'claim_2',
        term: 'matériau bas carbone',
        quoteInText: 'Le matériau bas carbone par excellence pour vos projets de construction et de rénovation.',
        category: 'materiau_fdes',
        severity: 'trompeur_sans_preuve',
        legalRisk: 'Dans le cadre de la RE2020, l’affirmation "matériau bas carbone" est illégale sans Fiche de Déclaration Environnementale et Sanitaire (FDES) publiée et vérifiée par un tiers.',
        lawArticle: 'Norme NF EN 15804+A2 & Réglementation Environnementale RE2020 (Art. R171-15 du CCH)',
        sanction: 'Rejet en appel d’offres public, exclusion des chantiers RE2020 et sanction DGCCRF pour tromperie.',
        requiredProof: 'FDES individuelle ou collective conforme NF EN 15804+A2 enregistrée sur la base nationale INIES.',
        recommendedService: 'fdes_epd',
      },
      {
        id: 'claim_3',
        term: 'éco-responsable',
        quoteInText: 'Une fabrication artisanale 100% éco-responsable respectueuse de la planète.',
        category: 'generique',
        severity: 'haut_risque',
        legalRisk: 'Allégation générique vague proscrite par la Directive européenne Green Claims en l’absence d’un label d’excellence environnementale certifié (ex: Écolabel européen).',
        lawArticle: 'Directive Green Claims (UE 2024/825) & Code de la consommation (Art. L121-2)',
        sanction: 'Jusqu’à 4% du chiffre d’affaires annuel en sanction européenne pour pratique commerciale trompeuse.',
        requiredProof: 'Démarche RSE structurée avec référentiel ISO 26000 et label tiers (Engagé RSE, B Corp, Lucie).',
        recommendedService: 'strategie_rse',
      },
      {
        id: 'claim_4',
        term: 'zéro émission',
        quoteInText: 'Un cycle de production en atelier garanti zéro émission.',
        category: 'neutralite',
        severity: 'interdit_strict',
        legalRisk: 'Allégation matériellement impossible : l’usinage, le séchage du bois, les colles et le transport génèrent inévitablement des gaz à effet de serre.',
        lawArticle: 'Article L229-68 du Code de l’environnement & Recommandations ARPP',
        sanction: 'Mise en demeure immédiate de la DGCCRF sous astreinte.',
        requiredProof: 'Analyse du Cycle de Vie (ACV) berceau-à-la-tombe quantifiant les flux réels.',
        recommendedService: 'acv',
      },
    ],
    businessOpportunities: [
      {
        serviceKey: 'fdes_epd',
        serviceTitle: 'Réalisation de FDES (Fiches de Déclaration Environnementale et Sanitaire) & EPD',
        relevanceScore: 98,
        reason: 'L’entreprise fabrique des menuiseries et matériaux bois sans FDES vérifiée sur la base INIES, ce qui bloque son accès aux chantiers RE2020 et l’expose à des sanctions.',
        potentialBudget: '6 000 € à 12 000 € (Selon le nombre de gabarits)',
        subsidiesEligible: 'Prise en charge possible via les aides régionales à l’éco-conception & Bpifrance.',
        deliverables: [
          'Modélisation ACV produit conforme à la norme NF EN 15804+A2',
          'Rédaction de la FDES officielle avec calcul du carbone biogène stocké',
          'Accompagnement jusqu’à la vérification par tierce partie indépendante',
          'Publication sur la base nationale INIES pour référencement auprès des architectes',
        ],
        salesArguments: [
          'Obligatoire pour remporter les appels d’offres de construction neuve RE2020',
          'Transforme une allégation illégale en argument commercial certifié et inattaquable',
          'Valorise le véritable atout bas carbone du bois face à l’aluminium et au PVC',
        ],
      },
      {
        serviceKey: 'bilan_carbone',
        serviceTitle: 'Bilan Carbone® Complet (Scopes 1, 2, 3) & Trajectoire Bas Carbone',
        relevanceScore: 92,
        reason: 'L’allégation "neutre en carbone" est strictement interdite sans Bilan Carbone officiel Scope 1-2-3 public selon le décret 2022-539.',
        potentialBudget: '5 000 € à 9 000 € (Finançable à 50% via Diag Décarbon’Action Bpifrance)',
        subsidiesEligible: 'Éligible au Diag Décarbon’Action de Bpifrance et l’ADEME (4 000 € d’aide directe pour les PME).',
        deliverables: [
          'Cartographie exhaustive des émissions (Énergie, achats de bois, transport, fin de vie)',
          'Rapport officiel Bilan Carbone® méthode ADEME',
          'Plan d’actions de décarbonation à 5 ans avec objectifs chiffrés',
          'Dossier de justification réglementaire conforme au Décret 2022-539',
        ],
        salesArguments: [
          'Sécurisation juridique totale face aux contrôles de la DGCCRF',
          'Coût réel divisé par deux grâce à la subvention Bpifrance Diag Décarbon’Action',
          'Réponse aux exigences RSE des donneurs d’ordres et grands comptes du BTP',
        ],
      },
      {
        serviceKey: 'credits_carbone_locaux',
        serviceTitle: 'Contribution Carbone Territoriale & Projets Verts (climatlocal.com)',
        relevanceScore: 95,
        reason: 'Particulièrement adapté pour cette entreprise qui plante déjà des arbres : réorienter ce budget vers des projets certifiés Label Bas-Carbone sur leur propre territoire (haies bocagères, transition agricole locale) pour communiquer en toute légalité sans fausse promesse de neutralité.',
        potentialBudget: '3 500 € à 7 000 € / an (Selon le volume de CO2 contribué)',
        subsidiesEligible: 'Valorisation RSE directe & Déductibilité fiscale mécénat environnemental.',
        deliverables: [
          'Sélection de fermes locales partenaires certifiées Label Bas-Carbone',
          'Attestation de contribution carbone territoriale inattaquable juridiquement',
          'Organisation d’une visite de terrain pour les salariés et clients sur les parcelles financées',
          'Kit de communication RSE conforme à la Directive (UE) 2024/825 (Point 4 septies)',
        ],
        salesArguments: [
          'Élimine 100% du risque de sanction DGCCRF lié à la fausse neutralité',
          'Ancrage territorial fort : les clients et élus locaux voient l’impact direct à 50 km de l’entreprise',
          'Levier puissant de fierté d’équipe et de marque employeur',
        ],
      },
      {
        serviceKey: 'raison_d_etre',
        serviceTitle: 'Raison d’Être & Société à Mission (Loi PACTE)',
        relevanceScore: 88,
        reason: 'Idéal pour formaliser les valeurs écologiques de l’entreprise dans ses statuts juridiques, sans dépendre d’allégations marketing risquées.',
        potentialBudget: '4 000 € à 7 500 €',
        subsidiesEligible: 'Éligible aux aides régionales d’accompagnement à la transition RSE.',
        deliverables: [
          'Ateliers collaboratifs de définition de la Raison d’Être avec les fondateurs et salariés',
          'Rédaction des objectifs environnementaux et sociétaux statutaires',
          'Mise en place du Comité de Mission et gouvernance RSE',
        ],
        salesArguments: [
          'Donne un cap stratégique pérenne et incontestable juridiquement',
          'Avantage concurrentiel déterminant en appel d’offres et recrutement de jeunes talents',
        ],
      },
      {
        serviceKey: 'sensibilisation_equipes',
        serviceTitle: 'Sensibilisation des Équipes & Ateliers Climat (Fresque & Communication Responsable)',
        relevanceScore: 82,
        reason: 'Former les équipes de vente, marketing et direction pour éviter toute nouvelle dérive de greenwashing et mobiliser les collaborateurs autour du plan de transition.',
        potentialBudget: '1 800 € à 3 500 € (Sessions d’une demi-journée à 1 journée)',
        subsidiesEligible: 'Prise en charge intégrale ou partielle OPCO (Formation professionnelle).',
        deliverables: [
          'Atelier Fresque du Climat ou Fresque de la Construction pour 15 à 30 salariés',
          'Module spécialisé "Communication Éco-Responsable & Pièges du Greenwashing"',
          'Guide des bonnes pratiques sémantiques remis à l’équipe commerciale',
        ],
        salesArguments: [
          'Embarque l’ensemble des salariés dans la réalité des enjeux écologiques',
          'Sécurise définitivement les écrits des commerciaux et du community manager',
          'Financement possible via le budget formation / OPCO de l’entreprise',
        ],
      },
      {
        serviceKey: 'acv',
        serviceTitle: 'Analyse du Cycle de Vie (ACV) Produit Normée ISO 14040/44',
        relevanceScore: 85,
        reason: 'Permet de justifier scientifiquement les déclarations sur l’éco-conception de leurs fenêtres et agencements.',
        potentialBudget: '4 500 € à 8 000 €',
        subsidiesEligible: 'Éligible aux aides ADEME Diag Éco-conception.',
        deliverables: [
          'Inventaire de cycle de vie (ICV) de la matière première au recyclage',
          'Calcul des 16 indicateurs d’impact (Réchauffement climatique, épuisement des ressources, eau)',
          'Pistes d’optimisation technique (colles sans solvant, chutes de bois)',
        ],
        salesArguments: [
          'Preuve scientifique irréfutable pour se démarquer de la concurrence',
          'Démonstration concrète sans risque d’accusation de greenwashing',
        ],
      },
    ],
    clientSummary: {
      headline: 'Alerte Réglementaire : 4 allégations écologiques à haut risque juridique détectées',
      riskVerdict: 'Votre site internet utilise des termes interdits par le Décret 2022-539 ("neutre en carbone") et la Directive Green Claims ("éco-responsable", "matériau bas carbone") sans les justificatifs exigés par la loi (Bilan Carbone public et FDES sur la base INIES). Vous risquez un contrôle DGCCRF et des sanctions financières.',
      keyUrgentAction: 'Retirer immédiatement la mention "neutre en carbone par plantation d’arbres" et lancer un Bilan Carbone® ou une FDES certifiée pour sécuriser vos contrats commerciaux.',
      immediateRisks: [
        'Amende administrative DGCCRF pouvant atteindre 100 000 € pour allégation de neutralité carbone non conforme (Décret 2022-539).',
        'Exclusion des marchés publics et chantiers soumis à la RE2020 par manque de FDES vérifiée sur INIES.',
        'Risque de plainte au Jury de Déontologie Publicitaire (JDP) et d’atteinte à la réputation pour greenwashing.',
      ],
      stepsToCompliance: [
        {
          step: 1,
          title: 'Audit & Nettoyage sémantique immédiat',
          description: 'Supprimer les termes interdits ("neutre", "zéro émission") de vos pages web et plaquettes.',
        },
        {
          step: 2,
          title: 'Réalisation du Bilan Carbone® officiel ou FDES',
          description: 'Mesurer vos émissions réelles avec Climat Local et bénéficier de la subvention Bpifrance Diag Décarbon’Action.',
        },
        {
          step: 3,
          title: 'Communication RSE sécurisée & certifiée',
          description: 'Valoriser des données scientifiques inattaquables pour remporter des marchés RE2020.',
        },
      ],
    },
    commercialProspectingKit: {
      targetPersona: 'Dirigeant / Directeur Commercial & RSE (Secteur Bâtiment / Menuiserie)',
      executiveSummary: 'Alpes Menuiserie Durable met en avant une démarche écologique remarquable, mais s’expose à des sanctions directes de la DGCCRF en utilisant des allégations prohibées ("neutre en carbone", "matériau bas carbone") sans FDES ni Bilan Carbone officiel. En leur proposant une mission FDES et Bilan Carbone (subventionnée à 50% par Bpifrance), Climat Local transforme leur risque juridique en un levier majeur de gain de marchés RE2020.',
      phonePitch: {
        hook: 'Bonjour Monsieur/Madame, Julien Lavaud du cabinet Climat Local. Je vous contacte car vos menuiseries en bois sont remarquables, mais votre site internet affiche des mentions comme "neutre en carbone" et "matériau bas carbone" qui sont désormais strictement visées par la DGCCRF et le Décret 2022-539.',
        keyFact: 'Sans FDES vérifiée sur la base INIES, vous ne pouvez pas répondre légalement aux chantiers RE2020, et l’allégation de neutralité carbone est sanctionnée jusqu’à 100 000 € d’amende.',
        legalWarning: 'La Directive européenne Green Claims interdit désormais les termes vagues comme "éco-responsable" sans certification tierce partie.',
        callToAction: 'Je vous propose un échange de 15 minutes pour vous expliquer comment sécuriser vos allégations et financer votre FDES et votre Bilan Carbone à 50% via les aides Bpifrance Décarbonation.',
        objectionResponses: [
          {
            objection: 'Mais nous plantons des arbres avec une association reconnue !',
            answer: 'C’est une excellente initiative citoyenne ! Cependant, depuis le Décret 2022-539, la loi interdit formellement de revendiquer la neutralité d’un produit sur la seule base de la compensation forestière sans un Bilan Carbone complet Scope 1-2-3 public. Nous vous aidons à valoriser votre démarche sans aucun risque juridique.',
          },
          {
            objection: 'Faire une FDES ou un Bilan Carbone, c’est trop lourd et trop cher pour une PME.',
            answer: 'C’est justement pour cela que Bpifrance et l’ADEME ont créé le Diag Décarbon’Action : vous bénéficiez de 4 000 € de subvention directe. Pour la FDES, elle devient rapidement rentable dès le premier chantier RE2020 remporté car les architectes l’exigent obligatoirement.',
          },
          {
            objection: 'Envoyez-moi un email, je transmettrai.',
            answer: 'Parfait ! Je vous fais parvenir notre fiche d’audit juridique confidentielle avec les articles de loi précis et le détail des aides financières. À quelle adresse puis-je vous l’adresser directement ?',
          },
        ],
      },
      emailProspecting: {
        legalSecurity: {
          subject: 'Sécurisation juridique de vos allégations environnementales (alpes-menuiserie-durable.fr)',
          body: `Bonjour Madame, Monsieur,

En visitant votre site alpes-menuiserie-durable.fr, nous avons relevé plusieurs mentions environnementales particulièrement engageantes pour vos clients : "100% neutre en carbone", "matériau bas carbone" et "fabrication éco-responsable".

Toutefois, la réglementation s'est considérablement durcie ces derniers mois :
1. Le Décret n° 2022-539 et l'article L229-68 du Code de l'environnement interdisent désormais formellement d'affirmer qu'un produit est "neutre en carbone" sans publier un Bilan Carbone complet (Scopes 1, 2, 3) et une trajectoire de réduction validée (sanction jusqu'à 100 000 € d'amende DGCCRF).
2. Dans le bâtiment (RE2020), la mention "matériau bas carbone" exige obligatoirement une FDES (Fiche de Déclaration Environnementale et Sanitaire) enregistrée sur la base nationale INIES.

Chez Climat Local, cabinet de conseil en transition écologique basé dans la région, nous accompagnons les entreprises pour transformer ces risques juridiques en opportunités commerciales majeures :
- Réalisation de votre FDES conforme pour remporter les chantiers RE2020
- Bilan Carbone® officiel (éligible au Diag Décarbon'Action Bpifrance avec 4 000 € d'aide)

Seriez-vous disponible pour un court échange de 15 minutes mardi ou jeudi prochain afin de faire le point en toute confidentialité ?

Bien cordialement,

Julien Lavaud
Consultant Transition Écologique & Climat | Climat Local
04 73 00 00 00 | contact@climat-local.fr
https://climat-local.fr`,
        },
        businessTransformation: {
          subject: 'Accédez aux marchés RE2020 grâce à vos FDES & Bilan Carbone subventionné',
          body: `Bonjour,

Les prescripteurs et architectes exigent désormais des preuves carbone vérifiées pour intégrer des menuiseries et matériaux dans les chantiers neufs soumis à la RE2020.

Les mentions génériques comme "durable" ou "éco-responsable" ne suffisent plus et sont désormais dans le viseur de la Directive européenne Green Claims.

Climat Local vous aide à structurer vos preuves environnementales :
- Réalisation et enregistrement de vos FDES sur la base INIES
- Bilan Carbone® Scopé 1, 2, 3
- Prise en charge jusqu'à 50% via les aides Bpifrance & ADEME

Quand auriez-vous 15 minutes cette semaine pour échanger sur vos gammes de produits et les subventions mobilisables ?

Cordialement,

Julien Lavaud | Climat Local`,
        },
        reputationProtection: {
          subject: 'Directive Green Claims & Loi Climat : protégez alpes-menuiserie-durable.fr du risque DGCCRF',
          body: `Madame, Monsieur la Direction,

La répression des fraudes (DGCCRF) et les ONG environnementales ciblent actuellement de nombreuses PME communiquant sur la "neutralité carbone" sans preuves audités.

Notre audit de conformité confidentiel sur alpes-menuiserie-durable.fr révèle 4 allégations à haut risque juridique selon la liste officielle des mots bannis EmpCO (Directive UE 2024/825).

Nous pouvons vous aider à assainir votre communication en quelques jours et à engager un Bilan Carbone certifié garantissant votre conformité.

Pouvons-nous en parler brièvement cette semaine ?

Respectueusement,

Julien Lavaud - Climat Local`,
        },
      },
      climatLocalProposal: {
        recommendedPackage: 'Pack Combiné : FDES Produit RE2020 + Bilan Carbone® Décarbon’Action',
        pricingEstimate: '10 000 € HT (Reste à charge 6 000 € après subvention Bpifrance de 4 000 €)',
        subsidies: 'Éligible Diag Décarbon’Action Bpifrance / ADEME & Aides régionales aux PME',
        timeline: 'Mission complète réalisée en 6 à 8 semaines',
      },
    },
  },
  {
    id: 'gw_sample_2',
    url: 'https://synapse-conseil-rh.fr',
    cleanUrl: 'synapse-conseil-rh.fr',
    companyName: 'Synapse Conseil & Stratégie (Cabinet Conseil RH & Organisation B2B)',
    sector: 'Services B2B, Tertiaire & Conseil en Management',
    region: 'Île-de-France',
    department: '75 - Paris',
    analyzedAt: '2026-09-29T11:45:00Z',
    riskLevel: 'eleve',
    riskScore: 42,
    totalBannedWordsDetected: 3,
    source: 'interne',
    prospectStatus: 'contacte',
    assignedTo: 'collab_2',
    detectedClaims: [
      {
        id: 'claim_201',
        term: 'cabinet éco-responsable',
        quoteInText: 'Notre cabinet de conseil est 100% éco-responsable et engagé pour un futur durable.',
        category: 'generique',
        severity: 'haut_risque',
        legalRisk: 'Allégation environnementale générique interdite par la Directive (UE) 2024/825 (Point 4 quater) sans certification reconnue ni preuve tangible.',
        lawArticle: 'Directive (UE) 2024/825 & Article L121-2 Code de la consommation',
        sanction: 'Injonction DGCCRF et risque de sanction administrative jusqu’à 4% du CA.',
        requiredProof: 'Formalisation de la Raison d’Être dans les statuts (Loi PACTE) et démarche RSE auditée (ISO 26000 / B Corp).',
        recommendedService: 'raison_d_etre',
      },
      {
        id: 'claim_202',
        term: 'missions zéro impact',
        quoteInText: 'Toutes nos missions de conseil sont menées avec un impact écologique nul garanti.',
        category: 'neutralite',
        severity: 'interdit_strict',
        legalRisk: 'Interdit formellement. Même une activité intellectuelle génère des émissions (datacenters, serveurs cloud, déplacements des consultants, chauffage des bureaux).',
        lawArticle: 'Décret 2022-539 & Point 4 quater Directive 2024/825',
        sanction: 'Amende jusqu’à 100 000 € DGCCRF pour tromperie.',
        requiredProof: 'Bilan Carbone® Scope 1-2-3 incluant le numérique responsable et les déplacements professionnels.',
        recommendedService: 'bilan_carbone',
      },
      {
        id: 'claim_203',
        term: 'engagé pour le climat',
        quoteInText: 'Nos équipes s’engagent activement pour le climat dans chaque projet client.',
        category: 'futur_sans_tiers',
        severity: 'haut_risque',
        legalRisk: 'Allégation d’engagement prospective sans plan vérifié ni formation attestée des consultants (Point 4 bis Directive 2024/825).',
        lawArticle: 'Directive (UE) 2024/825 (Point 4 bis)',
        sanction: 'Obligation de suppression sous astreinte.',
        requiredProof: 'Programme de formation des équipes (Fresque du Climat, Ateliers 2tonnes) et objectifs statutaires Société à Mission.',
        recommendedService: 'sensibilisation_equipes',
      },
    ],
    businessOpportunities: [
      {
        serviceKey: 'raison_d_etre',
        serviceTitle: 'Raison d’Être & Société à Mission (Loi PACTE)',
        relevanceScore: 98,
        reason: 'Typique des entreprises de services et du conseil B2B : peu vendeuse de biens physiques, son levier d’engagement le plus puissant et inattaquable est d’inscrire sa Raison d’Être dans ses statuts juridiques au lieu de simples allégations marketing.',
        potentialBudget: '4 500 € à 8 500 €',
        subsidiesEligible: 'Éligible aux aides régionales d’accompagnement à la transition RSE & gouvernance.',
        deliverables: [
          'Ateliers collaboratifs de définition de la Raison d’Être avec les associés et consultants',
          'Rédaction des objectifs statutaires sociétaux et environnementaux conformes à la Loi PACTE',
          'Modification des statuts et déclaration au greffe pour la qualité de Société à Mission',
          'Mise en place du Comité de Mission et baromètre de suivi',
        ],
        salesArguments: [
          'Solution idéale pour une société de services B2B sans catalogue de produits manufacturés',
          'Atout n°1 pour remporter les appels d’offres grands comptes et attirer les meilleurs consultants',
          'Sécurité juridique absolue : engagement statutaire inattaquable par la DGCCRF',
        ],
      },
      {
        serviceKey: 'sensibilisation_equipes',
        serviceTitle: 'Sensibilisation des Équipes & Ateliers Fresque du Climat (100% Financé OPCO)',
        relevanceScore: 95,
        reason: 'Les consultants sont en première ligne chez les clients : les former aux réalités climatiques et à la communication responsable crédibilise la posture du cabinet.',
        potentialBudget: '2 200 € à 4 500 €',
        subsidiesEligible: 'Prise en charge intégrale ou quasi-totale via l’OPCO (budget formation professionnelle des salariés).',
        deliverables: [
          'Animation de 2 sessions de Fresque du Climat / Ateliers 2tonnes pour l’ensemble des consultants',
          'Formation "Communication Responsable & Pièges du Greenwashing" pour le pôle commercial et communication',
          'Charte déontologique interne des écrits et propositions commerciales',
        ],
        salesArguments: [
          'Zéro impact sur la trésorerie grâce à la mobilisation des fonds formation OPCO',
          'Fédère les équipes autour d’un projet d’entreprise stimulant et moderne',
          'Évite tout dérapage sémantique sur LinkedIn ou dans les réponses à appels d’offres',
        ],
      },
      {
        serviceKey: 'bilan_carbone',
        serviceTitle: 'Bilan Carbone® Scopes 1, 2, 3 (Focus Numérique & Déplacements)',
        relevanceScore: 90,
        reason: 'Pour une société de conseil, les émissions se situent dans les déplacements des consultants (train, avion, voiture) et l’empreinte numérique (serveurs cloud, ordinateurs, visioconférences).',
        potentialBudget: '10 000 € HT',
        subsidiesEligible: 'Subventionné par le Diag Décarbon’Action Bpifrance / ADEME (4 000 € d’aide directe, reste à charge 6 000 € HT pour le client).',
        deliverables: [
          'Audit complet de la flotte informatique, des outils SaaS et des trajets consultants',
          'Bilan Carbone® certifié méthode ADEME',
          'Plan d’action sobre : politique voyage, matériel reconditionné, sobriété numérique',
        ],
        salesArguments: [
          'Permet de répondre aux questionnaires ESG de plus en plus exigeants des clients grands comptes',
          'Reste à charge minime pour le cabinet grâce à l’aide Bpifrance',
        ],
      },
      {
        serviceKey: 'credits_carbone_locaux',
        serviceTitle: 'Contribution Carbone Territoriale (climatlocal.com)',
        relevanceScore: 88,
        reason: 'Idéal pour concrétiser leur stratégie RSE : parrainer des fermes et haies bocagères locales en Île-de-France et régions voisines, avec visites terrain organisées pour leurs consultants et clients.',
        potentialBudget: '2 500 € à 5 000 € / an',
        subsidiesEligible: 'Mécénat environnemental et valorisation RSE dans le rapport annuel.',
        deliverables: [
          'Sélection de projets agricoles locaux Label Bas-Carbone certifiés',
          'Attestation de contribution locale officielle',
          'Organisation d’un team-building RSE sur la ferme partenaire',
        ],
        salesArguments: [
          'Donne un visage humain et local à la démarche écologique du cabinet',
          'Expérience mémorable pour fidéliser les clients lors d’une visite de terrain',
        ],
      },
    ],
    clientSummary: {
      headline: 'Alerte Risque : Allégations institutionnelles non étayées pour une société de conseil',
      riskVerdict: 'Votre site mentionne "éco-responsable" et "zéro impact" sans référentiel audité ni Bilan Carbone officiel. En tant que société de conseil B2B, vous vous exposez à des remises en cause de vos clients grands comptes et aux sanctions de la Directive Green Claims.',
      keyUrgentAction: 'Définir votre Raison d’Être statutaire (Loi PACTE) et former vos consultants via une Fresque du Climat financée par votre OPCO.',
      immediateRisks: [
        'Perte de crédibilité auprès des directions RSE et achats de vos clients grands comptes.',
        'Risque d’exclusion de panels de fournisseurs pour allégations trompeuses.',
        'Mise en demeure DGCCRF pour affirmation de neutralité ("zéro impact") sans Bilan Carbone Scope 1-2-3.',
      ],
      stepsToCompliance: [
        {
          step: 1,
          title: 'Sécurisation sémantique des propositions commerciales',
          description: 'Supprimer les termes vagues ("zéro impact") et harmoniser le discours des consultants.',
        },
        {
          step: 2,
          title: 'Raison d’Être & Statut Société à Mission',
          description: 'Inscrire votre mission sociale et environnementale dans vos statuts Loi PACTE avec Climat Local.',
        },
        {
          step: 3,
          title: 'Formation des équipes financée OPCO',
          description: 'Déployer des ateliers Fresque du Climat et engager un Bilan Carbone subventionné Bpifrance.',
        },
      ],
    },
    commercialProspectingKit: {
      targetPersona: 'Associé Fondateur / Directeur Général (Cabinet de Conseil B2B / ESN)',
      executiveSummary: 'Synapse Conseil met en avant des valeurs responsables mais utilise un vocabulaire à haut risque juridique ("zéro impact", "cabinet éco-responsable") qui peut nuire à sa réputation auprès des acheteurs grands comptes. Climat Local leur propose d’ancrer leur engagement dans la Loi PACTE (Société à Mission) et de former leurs consultants via un financement OPCO.',
      phonePitch: {
        hook: 'Bonjour, Julien Lavaud du cabinet Climat Local. Je vous contacte car votre cabinet de conseil porte des valeurs fortes, mais votre site affiche des mentions comme "missions zéro impact" et "éco-responsable" qui sont désormais scrutées par les acheteurs grands comptes et la Directive européenne Green Claims.',
        keyFact: 'De plus en plus de directions achats B2B disqualifient les prestataires de conseil dont les engagements RSE ne sont pas formalisés juridiquement.',
        legalWarning: 'La Directive (UE) 2024/825 interdit les allégations vagues sans preuves auditées.',
        callToAction: 'Je vous propose un échange de 15 minutes pour vous montrer comment inscrire votre démarche dans la Loi PACTE (Société à Mission) et financer la formation de vos équipes à 100% via votre OPCO.',
        objectionResponses: [
          {
            objection: 'Nous sommes une entreprise de conseil sans usines, nous n’avons pas d’impact.',
            answer: 'C’est une remarque fréquente ! Pourtant, entre les déplacements, les flottes d’ordinateurs, les serveurs cloud et les visioconférences, une société de conseil émet en moyenne 2 à 4 tonnes de CO2 par collaborateur. De plus, vos clients exigent désormais votre Bilan Carbone et une vraie Raison d’Être statutaire pour vous référencer.',
          },
          {
            objection: 'Nous n’avons pas de budget RSE cette année.',
            answer: 'C’est la force de notre approche : les ateliers de sensibilisation et formation de vos équipes sont pris en charge à 100% par votre OPCO formation, et le Bilan Carbone bénéficie de 4 000 € de subvention Bpifrance.',
          },
        ],
      },
      emailProspecting: {
        legalSecurity: {
          subject: 'Sécurisation de vos allégations RSE & référencement grands comptes (synapse-conseil-rh.fr)',
          body: `Bonjour,

En découvrant le positionnement de Synapse Conseil, nous avons noté votre volonté d'afficher un engagement écologique auprès de vos clients ("cabinet 100% éco-responsable", "missions zéro impact").

Cependant, avec l'entrée en vigueur de la Directive (UE) 2024/825 et les nouvelles exigences CSRD des grands donneurs d'ordres, ces formulations génériques deviennent un point de vulnérabilité lors des appels d'offres.

Pour les sociétés de conseil et de services B2B, la solution la plus valorisante et sécurisée consiste à :
1. Inscrire votre Raison d'Être dans vos statuts juridiques (Société à Mission - Loi PACTE)
2. Former vos consultants aux enjeux climatiques via des ateliers interactifs pris en charge à 100% par votre OPCO
3. Mesurer votre Bilan Carbone® (subventionné à 50% par Bpifrance Diag Décarbon'Action)

Seriez-vous ouvert à un rapide échange de 15 minutes cette semaine pour en discuter ?

Bien cordialement,

Julien Lavaud | Climat Local
04 73 00 00 00 • contact@climatlocal.com
https://climatlocal.com`,
        },
        businessTransformation: {
          subject: 'Société à Mission (Loi PACTE) & Ateliers OPCO pour vos consultants',
          body: `Bonjour,

Dans le conseil et les services B2B, l'attractivité des talents et le gain de nouveaux clients passent par des engagements vérifiables, loin du greenwashing.

Climat Local accompagne les cabinets de conseil pour :
- Formaliser leur Raison d'Être et adopter le statut de Société à Mission (Loi PACTE)
- Déployer la Fresque du Climat pour l'ensemble des collaborateurs (100% financé OPCO)
- Proposer des contributions carbone à des fermes locales de votre région via climatlocal.com

Quand seriez-vous disponible pour un court appel d'introduction ?

Julien Lavaud | Climat Local`,
        },
        localCarbonContribution: {
          subject: 'Engagez Synapse Conseil auprès des agriculteurs locaux de votre région',
          body: `Bonjour,

Plutôt que des slogans environnementaux flous, pourquoi ne pas permettre à vos consultants et clients de visiter une ferme partenaire financée par votre cabinet ?

Climat Local permet aux entreprises de financer directement des projets certifiés Label Bas-Carbone (haies bocagères, transition agro-écologique) sur votre territoire, avec un retour d'image concret et inattaquable.

Pouvons-nous en discuter lors d'un bref point téléphonique ?

Julien Lavaud - climatlocal.com`,
        },
      },
      climatLocalProposal: {
        recommendedPackage: 'Pack Société à Mission (Loi PACTE) + Ateliers Fresque du Climat (OPCO)',
        pricingEstimate: '5 500 € HT (Ateliers pris en charge par l’OPCO formation)',
        subsidies: 'Financement OPCO pour la formation + Aides régionales RSE',
        timeline: 'Accompagnement échelonné sur 4 à 6 semaines',
      },
    },
  },
  {
    id: 'gw_sample_3',
    url: 'https://coop-volcans-terroir.fr',
    cleanUrl: 'coop-volcans-terroir.fr',
    companyName: 'Coopérative Maraîchère & Terroirs des Volcans',
    sector: 'Agroalimentaire, Maraîchage & Distribution Locale',
    region: 'Auvergne-Rhône-Alpes',
    department: '63 - Puy-de-Dôme',
    analyzedAt: '2026-09-29T12:15:00Z',
    riskLevel: 'modere',
    riskScore: 56,
    totalBannedWordsDetected: 2,
    source: 'widget_wordpress',
    prospectStatus: 'rdv_fixe',
    assignedTo: 'collab_1',
    leadContact: {
      name: 'Claire Dumont',
      email: 'c.dumont@coop-volcans-terroir.fr',
      phone: '04 73 99 88 77',
      company: 'Coopérative Maraîchère des Volcans',
      message: 'Nous souhaitons certifier nos démarches pour les magasins bios et valoriser nos fermes partenaires.',
    },
    detectedClaims: [
      {
        id: 'claim_301',
        term: '100% naturel',
        quoteInText: 'Nos légumes et conserves artisanales sont 100% naturels et bons pour la planète.',
        category: 'produit_acv',
        severity: 'trompeur_sans_preuve',
        legalRisk: 'Confusion trompeuse entre naturalité et absence d’impact environnemental selon la DGCCRF.',
        lawArticle: 'Article L121-2 Code consommation & Directive 2024/825',
        sanction: 'Amende administrative pour tromperie sur les qualités substantielles.',
        requiredProof: 'ACV simplifiée de la production au bocal et audit de la filière d’approvisionnement.',
        recommendedService: 'acv',
      },
      {
        id: 'claim_302',
        term: 'bon pour la planète',
        quoteInText: 'Un modèle agricole régénératif 100% bon pour la planète.',
        category: 'generique',
        severity: 'haut_risque',
        legalRisk: 'Allégation d’excellence globale interdite sans Écolabel officiel de l’Union Européenne (Point 4 quater).',
        lawArticle: 'Directive (UE) 2024/825 - Point 4 quater',
        sanction: 'Injonction de retrait et amende.',
        requiredProof: 'Label Bas-Carbone officiel ou Bilan Carbone® de la filière maraîchère.',
        recommendedService: 'credits_carbone_locaux',
      },
    ],
    businessOpportunities: [
      {
        serviceKey: 'credits_carbone_locaux',
        serviceTitle: 'Crédits Carbone Locaux & Labellisation Bas-Carbone Agricole (climatlocal.com)',
        relevanceScore: 99,
        reason: 'Client déjà très engagé et ancré dans le territoire auvergnat : parfait pour certifier leurs pratiques agricoles via la méthode grandes cultures / haies Label Bas-Carbone sur climatlocal.com et générer des crédits carbone valorisables localement !',
        potentialBudget: '4 000 € à 8 000 €',
        subsidiesEligible: 'Éligible aux aides régionales Auvergne-Rhône-Alpes et fonds transition agricole.',
        deliverables: [
          'Diagnostic de séquestration carbone dans les sols et haies de la coopérative',
          'Dépôt du dossier Label Bas-Carbone auprès du Ministère de la Transition Écologique',
          'Monétisation des tonnes de carbone stockées auprès d’entreprises locales partenaires',
        ],
        salesArguments: [
          'Transforme une dépense écologique en nouvelle source de revenu pour les maraîchers',
          'Preuve certifiée par le Ministère, inattaquable juridiquement',
        ],
      },
      {
        serviceKey: 'bilan_carbone',
        serviceTitle: 'Bilan Carbone® de Filière & Trajectoire de Réduction',
        relevanceScore: 92,
        reason: 'Mesurer scientifiquement l’empreinte de la serre, des tracteurs, du transport et des emballages bocaux.',
        potentialBudget: '10 000 € HT',
        subsidiesEligible: 'Subventionné par le Diag Décarbon’Action Bpifrance / ADEME (4 000 € d’aide directe, reste à charge 6 000 € HT pour le client).',
        deliverables: [
          'Cartographie des flux de matières et d’énergie de la coopérative',
          'Plan de réduction des consommations énergétiques',
        ],
        salesArguments: [
          'Répond aux exigences des centrales d’achat et réseaux de distribution bio',
        ],
      },
      {
        serviceKey: 'sensibilisation_equipes',
        serviceTitle: 'Sensibilisation des Équipes & Ateliers Fresque Agri-Alimentation',
        relevanceScore: 85,
        reason: 'Mobiliser les salariés et coopérateurs autour des enjeux écologiques et de l’alimentation durable.',
        potentialBudget: '1 800 € à 3 200 €',
        subsidiesEligible: 'Prise en charge OPCO OCAPIAT.',
        deliverables: [
          'Ateliers Fresque de l’Alimentation et Fresque du Climat',
          'Guide des allégations sur les emballages pour l’équipe marketing',
        ],
        salesArguments: [
          'Financé par OCAPIAT sans toucher à la trésorerie',
        ],
      },
    ],
    clientSummary: {
      headline: 'Alerte sémantique : Allégations valorisantes vagues sur vos produits du terroir',
      riskVerdict: 'Vos produits sont de grande qualité, mais l’usage de "bon pour la planète" et "100% naturel" sans données d’ACV ou certification reconnue vous expose à des contrôles de la DGCCRF.',
      keyUrgentAction: 'Certifier vos pratiques via le Label Bas-Carbone agricole officiel pour sécuriser vos emballages et valoriser vos fermes locales.',
      immediateRisks: [
        'Risque de blocage d’emballages lors de contrôles de la répression des fraudes.',
        'Sanction financière pour allégation générique non démontrée.',
      ],
      stepsToCompliance: [
        {
          step: 1,
          title: 'Audit des packagings et étiquettes',
          description: 'Ajuster les mentions pour éviter les termes interdits.',
        },
        {
          step: 2,
          title: 'Labellisation Bas-Carbone agricole',
          description: 'Valoriser les haies et le non-labour avec climatlocal.com.',
        },
        {
          step: 3,
          title: 'Communication certifiée auprès des distributeurs',
          description: 'Afficher des réductions vérifiées pour remporter les linéaires.',
        },
      ],
    },
    commercialProspectingKit: {
      targetPersona: 'Directrice Générale / Responsable RSE & Qualité (Coopérative Agroalimentaire)',
      executiveSummary: 'La coopérative maraîchère a un ancrage local exemplaire. En certifiant ses pratiques agricoles via le Label Bas-Carbone sur climatlocal.com, Climat Local leur permet à la fois de sécuriser leurs emballages et de monétiser leurs crédits carbone auprès d’entreprises régionales.',
      phonePitch: {
        hook: 'Bonjour Claire Dumont, Julien Lavaud de Climat Local. Nous avons bien reçu votre demande de scan sur climatlocal.com concernant votre coopérative maraîchère.',
        keyFact: 'Vos pratiques sont remarquables, mais l’allégation "bon pour la planète" sur vos bocaux est visée par la nouvelle directive européenne si elle ne repose pas sur un label certifié.',
        legalWarning: 'La DGCCRF renforce ses contrôles sur l’agroalimentaire bio et local.',
        callToAction: 'Je vous propose de maintenir notre rendez-vous ce jeudi à 14h pour vous présenter comment valoriser vos fermes partenaires avec le Label Bas-Carbone.',
        objectionResponses: [
          {
            objection: 'Nous sommes déjà certifiés Agriculture Biologique !',
            answer: 'Le label Bio certifie l’absence de produits phytosanitaires de synthèse, ce qui est formidable. Mais la Directive européenne 2024/825 précise que le label Bio ne permet pas automatiquement de revendiquer un produit "bon pour la planète" ou "neutre" sans mesure d’impact carbone ou d’ACV.',
          },
        ],
      },
      emailProspecting: {
        legalSecurity: {
          subject: 'Suite à votre scan sur climatlocal.com : sécurisation de vos bocaux & Label Bas-Carbone',
          body: `Bonjour Claire,

Merci pour votre scan sur notre plateforme climatlocal.com.

Votre démarche de maraîchage local est exemplaire. Pour sécuriser vos packagings face à la Directive Green Claims, nous vous proposons d'engager une labellisation Bas-Carbone officielle pour vos fermes partenaires.

À jeudi 14h pour notre échange !

Julien Lavaud | Climat Local`,
        },
        businessTransformation: {
          subject: 'Monétisez le stockage carbone de vos maraîchers avec climatlocal.com',
          body: `Bonjour Claire,

Vos haies et sols captent du carbone chaque jour. Saviez-vous que vous pouvez faire certifier ces tonnes de CO2 via le Label Bas-Carbone pour les vendre à des entreprises locales engagées de notre réseau ?

Parlons-en ce jeudi !

Julien Lavaud | Climat Local`,
        },
        localCarbonContribution: {
          subject: 'Partenariat territorial Climat Local & Coopérative des Volcans',
          body: `Bonjour Claire,

Nous avons hâte de faire visiter vos parcelles à nos clients entreprises à la recherche de vrais projets écologiques locaux.

Bien cordialement,
Julien Lavaud`,
        },
      },
      climatLocalProposal: {
        recommendedPackage: 'Accompagnement Label Bas-Carbone Agricole + Bilan Carbone® Filière',
        pricingEstimate: '6 800 € HT (Subventions régionales & Bpifrance mobilisables)',
        subsidies: 'Éligible Diag Décarbon’Action & Aides régionales à la transition agro-écologique',
        timeline: 'Dossier déposé sous 8 semaines',
      },
    },
  },
];
