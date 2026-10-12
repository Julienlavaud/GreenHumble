-- ==============================================================================
-- BASE DE DONNÉES COMPLÈTE CLIMATLOCAL (Version 2.6 - Référentiels Métier & Prospection)
-- Conforme Directive Européenne EmpCO (UE 2024/825), Green Claims, CSRD, RE2020, AGEC, ISO
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table des Paramètres Généraux du Système
DROP TABLE IF EXISTS cl_settings;
CREATE TABLE cl_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    category VARCHAR(50) DEFAULT 'general',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des Équipes et Pôles Métier
DROP TABLE IF EXISTS cl_teams;
CREATE TABLE cl_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    region VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    manager_name VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des Utilisateurs et Consultants / Auditeurs RSE
DROP TABLE IF EXISTS cl_users;
CREATE TABLE cl_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'collaborateur',
    phone VARCHAR(50) NULL,
    cabinet_name VARCHAR(150) NULL,
    avatar_color VARCHAR(50) DEFAULT 'bg-emerald-700',
    team_id INT NULL,
    assigned_prospects_count INT DEFAULT 0,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES cl_teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des Entreprises Clientes & Prospects (Répertoire SIREN)
DROP TABLE IF EXISTS cl_companies;
CREATE TABLE cl_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    siren VARCHAR(20) NULL,
    siret VARCHAR(30) NULL,
    naf_code VARCHAR(20) NULL,
    legal_form VARCHAR(100) NULL,
    sector VARCHAR(150) NULL,
    headcount VARCHAR(50) NULL,
    turnover VARCHAR(50) NULL,
    region VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    website VARCHAR(255) NULL,
    clean_url VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    postal_code VARCHAR(20) NULL,
    city VARCHAR(100) NULL,
    pappers_url VARCHAR(255) NULL,
    contact_name VARCHAR(100) NULL,
    contact_email VARCHAR(150) NULL,
    contact_phone VARCHAR(50) NULL,
    contact_role VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_siren (siren),
    INDEX idx_sector (sector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table des Contacts Entreprises Spécifiques
DROP TABLE IF EXISTS cl_company_contacts;
CREATE TABLE cl_company_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    roleTitle VARCHAR(100) NULL,
    contactType ENUM('RSE', 'QSE', 'Direction', 'Achats', 'Communication', 'Autre') DEFAULT 'RSE',
    linkedinUrl VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES cl_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table des Audits RSE & Conformité Green Claims (6 Piliers)
DROP TABLE IF EXISTS cl_audits;
CREATE TABLE cl_audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL,
    user_id INT NULL,
    url VARCHAR(255) NOT NULL,
    clean_url VARCHAR(255) NULL,
    score INT DEFAULT 50,
    riskLevel ENUM('critique', 'eleve', 'modere', 'faible') DEFAULT 'modere',
    total_banned_words INT DEFAULT 0,
    summary TEXT NULL,
    financial_impact TEXT NULL,
    headcount VARCHAR(50) NULL,
    turnover VARCHAR(50) NULL,
    status ENUM('scan_termine', 'en_analyse', 'rapport_transmis', 'valide', 'nouveau', 'contacte', 'relance', 'rdv_fixe', 'devis_envoye', 'signe') DEFAULT 'scan_termine',
    source ENUM('interne', 'widget_wordpress', 'scan_public') DEFAULT 'interne',
    assigned_to VARCHAR(100) NULL,
    notes TEXT NULL,
    pillar_1_score INT DEFAULT 50, -- Mots bannis EmpCO
    pillar_2_score INT DEFAULT 50, -- Justification & Preuves
    pillar_3_score INT DEFAULT 50, -- Clarté & Transparence
    pillar_4_score INT DEFAULT 50, -- Risque Légal & DGCCRF
    pillar_5_score INT DEFAULT 50, -- Poids & Éco-conception Web
    pillar_6_score INT DEFAULT 50, -- Recommandations Actionnables
    raw_response LONGTEXT NULL,
    analyzedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES cl_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES cl_users(id) ON DELETE SET NULL,
    INDEX idx_url (url),
    INDEX idx_score (score),
    INDEX idx_risk (riskLevel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table des Allégations & Mots Bannis Détectés
DROP TABLE IF EXISTS cl_claims;
CREATE TABLE cl_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    term VARCHAR(255) NOT NULL,
    quoteInText TEXT NULL,
    category ENUM('neutralite', 'generique', 'produit_acv', 'materiau_fdes', 'label_non_certifie', 'futur_sans_tiers', 'autre') DEFAULT 'generique',
    severity ENUM('interdit_strict', 'haut_risque', 'trompeur_sans_preuve', 'vigilance') DEFAULT 'haut_risque',
    legalRisk TEXT NULL,
    lawArticle VARCHAR(255) NULL,
    sanction VARCHAR(255) NULL,
    requiredProof TEXT NULL,
    recommendedService VARCHAR(100) NULL,
    isUnfounded BOOLEAN DEFAULT FALSE,
    correctionNote TEXT NULL,
    consultantValidationStatus ENUM('valide', 'non_fonde', 'justifie_par_preuve', 'en_attente') DEFAULT 'en_attente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table des Opportunités Commerciales et Recommandations
DROP TABLE IF EXISTS cl_business_opportunities;
CREATE TABLE cl_business_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    serviceKey VARCHAR(100) NOT NULL,
    serviceTitle VARCHAR(255) NOT NULL,
    relevanceScore INT DEFAULT 80,
    reason TEXT NULL,
    potentialBudget VARCHAR(150) NULL,
    subsidiesEligible TEXT NULL,
    deliverables_json TEXT NULL,
    salesArguments_json TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table des Kits de Prospection Commerciale IA (Scripts & Emails)
DROP TABLE IF EXISTS cl_prospecting_kits;
CREATE TABLE cl_prospecting_kits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NULL,
    company_id INT NULL,
    user_id INT NULL,
    company_name VARCHAR(200) NULL,
    url VARCHAR(255) NULL,
    tone VARCHAR(50) DEFAULT 'bienveillant',
    targetPersona VARCHAR(255) NULL,
    executiveSummary TEXT NULL,
    phoneHook TEXT NULL,
    phoneFact TEXT NULL,
    phoneWarning TEXT NULL,
    phoneCta TEXT NULL,
    phoneObjections_json TEXT NULL,
    emailLegal_subject VARCHAR(255) NULL,
    emailLegal_body TEXT NULL,
    emailBusiness_subject VARCHAR(255) NULL,
    emailBusiness_body TEXT NULL,
    emailReputation_subject VARCHAR(255) NULL,
    emailReputation_body TEXT NULL,
    recommendedPackage VARCHAR(255) NULL,
    pricingEstimate VARCHAR(150) NULL,
    subsidies VARCHAR(255) NULL,
    timeline VARCHAR(150) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES cl_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES cl_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table des Tâches & Workflow d'Audit (Checklist)
DROP TABLE IF EXISTS cl_audit_tasks;
CREATE TABLE cl_audit_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category ENUM('qualification', 'audit_juridique', 'contact_dirigeant', 'proposition_mission', 'cloture') DEFAULT 'qualification',
    isDone BOOLEAN DEFAULT FALSE,
    completedAt DATETIME NULL,
    completedBy VARCHAR(100) NULL,
    mandatory BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table des Logs d'Historique et Audit Trail
DROP TABLE IF EXISTS cl_audit_logs;
CREATE TABLE cl_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    authorName VARCHAR(100) NOT NULL,
    actionType VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Table des Rôles Assignés par Audit
DROP TABLE IF EXISTS cl_role_assignments;
CREATE TABLE cl_role_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    roleKey ENUM('lead_referent', 'auditeur_carbone', 'expert_rse', 'expert_fdes') DEFAULT 'lead_referent',
    roleTitle VARCHAR(100) NOT NULL,
    collaboratorId VARCHAR(50) NULL,
    collaboratorName VARCHAR(100) NOT NULL,
    assignedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('en_attente', 'validee', 'refusee') DEFAULT 'en_attente',
    validatedBy VARCHAR(100) NULL,
    validatedAt DATETIME NULL,
    notes TEXT NULL,
    FOREIGN KEY (audit_id) REFERENCES cl_audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Table des Référentiels Juridiques et Cadres Réglementaires (CSRD, EmpCO, RE2020...)
DROP TABLE IF EXISTS cl_regulatory_frameworks;
CREATE TABLE cl_regulatory_frameworks (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    reference VARCHAR(255) NOT NULL,
    jurisdiction ENUM('europe', 'france', 'international', 'sectoriel') DEFAULT 'europe',
    family VARCHAR(100) NOT NULL,
    subgroup VARCHAR(100) NULL,
    sanction TEXT NULL,
    description TEXT NOT NULL,
    relevant_sectors TEXT NULL,
    key_obligations TEXT NULL,
    default_applicability ENUM('obligatoire_direct', 'echeance_2026', 'recommande_marches', 'non_assujetti') DEFAULT 'obligatoire_direct',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Table des Dictionnaires de Mots Bannis & Textes Personnalisés
DROP TABLE IF EXISTS cl_texts;
CREATE TABLE cl_texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NULL,
    category VARCHAR(50) DEFAULT 'mots_bannis',
    content LONGTEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- INSERTION DES DONNÉES INITIALES (MÉTIER & PROSPECTION)
-- ==============================================================================

-- Équipes initiales
INSERT INTO cl_teams (id, name, region, email, phone, website, manager_name) VALUES
(1, 'Pôle Auvergne & Grand Centre', 'Auvergne-Rhône-Alpes', 'auvergne@climatlocal.com', '04 73 00 00 01', 'https://climatlocal.com', 'Julien Lavaud'),
(2, 'Pôle Île-de-France & Nord', 'Île-de-France', 'idf@climatlocal.com', '01 80 00 00 02', 'https://climatlocal.com', 'Sarah Benali'),
(3, 'Pôle Sud & Méditerranée', 'Occitanie', 'sud@climatlocal.com', '04 67 00 00 03', 'https://climatlocal.com', 'Thomas Roche');

-- Utilisateurs initiaux
INSERT INTO cl_users (id, username, email, password_hash, role, phone, cabinet_name, avatar_color, team_id, assigned_prospects_count) VALUES
(1, 'Julien Lavaud', 'julien@climatlocal.com', '$2y$10$Nqm3ErRyHkVEySDv7muzJ.nn02KKdC7Rg9j8o/KgeITPzeku8Q4eu', 'admin', '04 73 00 00 01', 'ClimatLocal Siège', 'bg-emerald-700', 1, 4),
(2, 'Sarah Benali', 'sarah.benali@climatlocal.com', '$2y$10$9HuUAmWNiwJokXRVP7Cs5.MSio1FrYSXZ37XjMW9oYGvRVFRxsqxa', 'collaborateur', '04 73 00 00 02', 'ClimatLocal Paris', 'bg-teal-600', 2, 2),
(3, 'Thomas Roche', 'thomas.roche@climatlocal.com', '$2y$10$9HuUAmWNiwJokXRVP7Cs5.MSio1FrYSXZ37XjMW9oYGvRVFRxsqxa', 'collaborateur', '04 73 00 00 03', 'Expert ACV / FDES', 'bg-indigo-600', 1, 1),
(4, 'Admin', 'admin@climatlocal.com', '$2y$10$Nqm3ErRyHkVEySDv7muzJ.nn02KKdC7Rg9j8o/KgeITPzeku8Q4eu', 'admin', '04 73 00 00 00', 'Console Centrale', 'bg-slate-900', 1, 0);

-- Paramètres globaux
INSERT INTO cl_settings (setting_key, setting_value, category) VALUES
('agency_name', 'ClimatLocal SAS', 'agency'),
('agency_email', 'contact@climatlocal.com', 'agency'),
('agency_phone', '04 73 00 00 00', 'agency'),
('agency_website', 'https://climatlocal.com', 'agency'),
('slogan', 'Audit & Certification de Conformité aux Allégations Environnementales', 'agency'),
('default_banned_words', '100% écologique, Neutre en carbone, Zéro impact, Éco-responsable absolu, Vert par nature, Bon pour la planète, Ami de la nature, 100% vert, Garanti sans carbone, Propre pour la planète', 'audit');

-- Dictionnaires de mots bannis & Textes personnalisés
INSERT INTO cl_texts (text_key, title, category, content) VALUES
('banned_words_empco', 'Mots Bannis Directive EmpCO (UE 2024/825)', 'mots_bannis', '100% écologique, Neutre en carbone, Zéro impact, Éco-responsable, Bon pour la planète, Vert par nature, Ami de l''environnement, Zéro émission, 100% vert, Propre, Naturel sans impact'),
('banned_words_textile', 'Mots Interdits Secteur Mode & Textile', 'mots_bannis', 'Coton 100% propre, Matière verte, Zéro pollution, Fibre écologique absolue, Teinture propre'),
('banned_words_btp', 'Mots Bannis Matériaux & BTP (RE2020)', 'mots_bannis', 'Matériau neutre, Béton propre, Isolant 100% vert, Bois sans carbone sans FDES, Zéro empreinte chantier'),
('welcome_message', 'Message d''accueil plateforme', 'general', 'Bienvenue sur ClimatLocal, la solution de référence pour auditer, sécuriser et valoriser vos allégations environnementales en conformité avec les directives européennes et le barème DGCCRF.');

-- ==============================================================================
-- RÉFÉRENTIELS JURIDIQUES ET CADRES COMPLETS (UE, FRANCE, INTERNATIONAL, SECTEURS)
-- ==============================================================================

INSERT INTO cl_regulatory_frameworks (id, title, reference, jurisdiction, family, subgroup, sanction, description, relevant_sectors, key_obligations, default_applicability) VALUES
-- 🇪🇺 I. TEXTES EUROPÉENS (UE)
-- A. Reporting & Transparence
('ue_csrd', 'CSRD — Corporate Sustainability Reporting Directive', 'Directive (UE) 2022/2464 + Omnibus 2026/470', 'europe', 'Reporting & Transparence', 'Europe / Reporting', 'Sanctions financières jusqu’à 75 000 €, exclusion de marchés publics et mise en cause de la responsabilité des dirigeants', 'Cadre unifié européen de reporting extra-financier de durabilité standardisé selon les normes ESRS avec audit par un tiers indépendant.', 'Toutes entreprises assujetties et chaînes de valeur (Scope 3)', 'Publication d''un rapport de durabilité annuel audité avec matrice de double matérialité et indicateurs ESRS.', 'obligatoire_direct'),
('ue_esrs', 'ESRS — European Sustainability Reporting Standards', 'Règlement délégué (UE) 2023/2772 (2023-2026)', 'europe', 'Reporting & Transparence', 'Europe / Reporting', 'Rejet du rapport de durabilité et non-conformité CSRD', 'Normes techniques européennes de reporting sur 12 domaines (E1 Climat, E2 Pollution, E4 Biodiversité, S1-S4 Social, G1 Gouvernance).', 'Toutes entreprises sous CSRD', 'Renseignement des points de données obligatoires sur les émissions GES, objectifs de réduction et gouvernance.', 'obligatoire_direct'),
('ue_taxonomie', 'Taxonomie Européenne Verte', 'Règlement (UE) 2020/852', 'europe', 'Reporting & Transparence', 'Europe / Finance & Taxonomie', 'Disqualification des financements verts européens et pénalités investisseurs', 'Système de classification standardisé des activités économiques durables contribuant à 6 objectifs environnementaux.', 'Entreprises cotées, ETI, Banques, Fonds', 'Calcul des ratios d''alignement CapEx, OpEx et Chiffre d''affaires vert.', 'obligatoire_direct'),
('ue_sfdr', 'SFDR — Sustainable Finance Disclosure Regulation', 'Règlement (UE) 2019/2088', 'europe', 'Reporting & Transparence', 'Europe / Finance', 'Sanctions de l''AMF et des régulateurs financiers européens', 'Obligations de transparence pour les acteurs des marchés financiers sur l''intégration des risques de durabilité (Articles 6, 8, 9).', 'Gestion d''actifs, Banques, Assurances, Fonds', 'Publication des Principales Incidences Négatives (PAI) et transparence ESG.', 'obligatoire_direct'),
('ue_nfrd', 'NFRD — Non Financial Reporting Directive', 'Directive 2014/95/UE (Remplacée par CSRD)', 'europe', 'Reporting & Transparence', 'Europe / Reporting', 'Sanctions prévues par les transpositions nationales', 'Ancien cadre de reporting extra-financier désormais transitoirement remplacé par la directive CSRD.', 'Grandes entreprises cotées', 'Déclaration de performance extra-financière historique.', 'obligatoire_direct'),

-- B. Carbone & Climat
('ue_macf', 'MACF / CBAM — Mécanisme d’Ajustement Carbone aux Frontières', 'Règlement (UE) 2023/956', 'europe', 'Carbone & Climat', 'Europe / Carbone', 'Pénalités de 10 à 50 € par tonne de CO2 non déclarée et blocage en douane', 'Taxe carbone aux frontières de l''UE imposant aux importateurs de déclarer le contenu carbone des produits importés.', 'Acier, Aluminium, Ciment, Engrais, Électricité, Hydrogène', 'Déclaration trimestrielle des émissions directes et indirectes intégrées dans les produits importés.', 'obligatoire_direct'),
('ue_ets', 'EU ETS — Système d’échange de quotas d’émission', 'Directive 2003/87/CE modifiée 2023', 'europe', 'Carbone & Climat', 'Europe / Quotas', 'Amende de 100 € par tonne excédentaire + restitution des quotas manquants', 'Marché du carbone européen plafonnant les émissions des installations industrielles lourdes, de l''aviation et du maritime.', 'Industrie lourde, Énergie, Aérien, Maritime', 'Surveillance, déclaration annuelle et restitution des quotas d''émissions vérifiés.', 'obligatoire_direct'),
('ue_crcf', 'CRCF — Certification des absorptions carbone', 'Règlement (UE) 2024 (Modules 2026)', 'europe', 'Carbone & Climat', 'Europe / Carbone', 'Interdiction d''utilisation des crédits non certifiés dans la communication', 'Cadre européen de certification des absorptions permanentes de carbone, du stockage agricole et des produits durables.', 'Projets carbone, Forêt, Agriculture, Biochar, DAC', 'Respect des critères QU.A.L.ITY (Quantification, Additionnalité, Stockage Long Terme, Durabilité).', 'echeance_2026'),
('ue_esr', 'Effort Sharing Regulation (Répartition de l’effort)', 'Règlement (UE) 2018/842', 'europe', 'Carbone & Climat', 'Europe / Climat', 'Procédures d''infraction européennes contre les États membres', 'Fixation d''objectifs nationaux contraignants de réduction des émissions GES pour les secteurs hors ETS.', 'Bâtiment, Transport, Agriculture, Déchets', 'Contribution aux objectifs nationaux de neutralité carbone 2050.', 'obligatoire_direct'),
('ue_lulucf', 'LULUCF — Secteur des Terres et Forêts', 'Règlement (UE) 2018/841', 'europe', 'Carbone & Climat', 'Europe / Forêt & Sol', 'Non-conformité des bilans d''émissions territoriaux', 'Règlementation sur l’utilisation des terres, le changement d’affectation des terres et la foresterie.', 'Foresterie, Agriculture, Aménagement foncier', 'Préservation et renforcement des puits de carbone naturels européens.', 'obligatoire_direct'),

-- C. Produits, ACV, FDES/EPD
('ue_en15804', 'EN 15804 — Norme Européenne ACV Produits de Construction', 'Norme NF EN 15804+A2 (CEN/TC 350)', 'europe', 'Produits, ACV, FDES/EPD', 'Europe / ACV Bâtiment', 'Rejet des déclarations environnementales et recours pour allégation infondée', 'Norme de référence européenne fixant les règles de calcul de l’Analyse du Cycle de Vie (ACV) des produits du bâtiment.', 'Construction, BTP, Matériaux, Menuiserie', 'Calcul des 16 indicateurs d’impact environnemental sur l’ensemble du cycle de vie berceau-à-tombe.', 'obligatoire_direct'),
('ue_cpr', 'Construction Products Regulation (CPR)', 'Règlement (UE) 305/2011 révisé 2024', 'europe', 'Produits, ACV, FDES/EPD', 'Europe / BTP', 'Retrait du marché des produits de construction non conformes et interdiction de marquage CE', 'Règlement européen harmonisant les conditions de commercialisation des produits de construction et leurs performances environnementales.', 'Fabricants et distributeurs de produits de construction', 'Déclaration des performances (DOP) intégrant les critères de durabilité et d''empreinte carbone.', 'obligatoire_direct'),
('ue_espr', 'ESPR — Règlement Écoconception Produits Durables', 'Règlement (UE) 2024/1781 (Remplace 2009/125/CE)', 'europe', 'Produits, ACV, FDES/EPD', 'Europe / Écoconception', 'Interdiction de mise sur le marché européen et destruction des invendus sanctionnée', 'Cadre établissant des exigences d’écoconception (durabilité, réparabilité, recyclabilité, Passeport Numérique Produit - DPP).', 'Textile, Électronique, Meuble, Chimie, Plastique', 'Mise en place du Passeport Produit Numérique et conformité aux critères de circularité.', 'obligatoire_direct'),
('ue_ecolabel', 'Écolabel Européen Officiel', 'Règlement (CE) 66/2010', 'europe', 'Produits, ACV, FDES/EPD', 'Europe / Labels', 'Poursuites pour contrefaçon de label officiel et publicité trompeuse', 'Seul label écologique officiel commun à tous les pays de l’Union Européenne garantissant une excellence environnementale certifiée.', 'Hébergements touristiques, Produits d''entretien, Textile, Papier', 'Audit sur site par un organisme certificateur indépendant accrédité.', 'recommande_marches'),

-- D. Greenwashing & Consommateurs
('ue_empco', 'Directive EmpCo / ECGT — Empowering Consumers for Green Transition', 'Directive (UE) 2024/825 du 28 février 2024', 'europe', 'Greenwashing & Consommateurs', 'Europe / Greenwashing', 'Amendes jusqu’à 4% du CA annuel dans l’UE, exclusion des marchés publics et confiscation des revenus', 'Interdiction stricte des allégations environnementales génériques sans preuve d’excellence et de la neutralité carbone basée sur la seule compensation.', 'Tous secteurs B2C et B2B diffusant des allégations publiques', 'Interdiction formelle des termes "vert", "neutre", "zéro impact" sans dossier justificatif vérifié par un tiers.', 'obligatoire_direct'),
('ue_green_claims', 'Directive Green Claims (Allégations explicites)', 'Proposition Directive COM(2023) 166', 'europe', 'Greenwashing & Consommateurs', 'Europe / Greenwashing', 'Sanctions financières dissuasives et obligation de rectification publique', 'Obligation de faire valider et certifier toute allégation écologique par un vérificateur accrédité indépendant AVANT toute diffusion marketing.', 'Toutes entreprises communiquant sur l’environnement', 'Audit préalable ex-ante de chaque mention environnementale sur base d''ACV normée.', 'echeance_2026'),
('ue_pratiques_loyales', 'Directive Pratiques Commerciales Déloyales', 'Directive 2005/29/CE modifiée par 2024/825', 'europe', 'Greenwashing & Consommateurs', 'Europe / Consommation', 'Amendes administratives lourdes et peines correctionnelles', 'Texte cadre européen réprimant les tromperies, omissions trompeuses et fausses affirmations sur les caractéristiques essentielles des biens.', 'Tous professionnels', 'Garantie de véracité, loyauté et transparence des informations délivrées aux consommateurs.', 'obligatoire_direct'),

-- E. Vigilance & Droits humains
('ue_cs3d', 'CS3D — Corporate Sustainability Due Diligence', 'Directive (UE) 2024/1760 du 13 juin 2024', 'europe', 'Vigilance & Droits Humains', 'Europe / Vigilance', 'Amendes pouvant atteindre 5% du chiffre d’affaires mondial net de l’entreprise', 'Devoir de vigilance européen obligeant les grandes entreprises à prévenir et atténuer les atteintes aux droits humains et à l’environnement.', 'Grandes entreprises de l’UE et filiales mondiales', 'Adoption et mise en œuvre d’un plan de transition climatique aligné sur 1.5°C et cartographie des risques fournisseurs.', 'obligatoire_direct'),
('ue_conflict_minerals', 'Conflict Minerals Regulation (Minerais de Conflit)', 'Règlement (UE) 2017/821', 'europe', 'Vigilance & Droits Humains', 'Europe / Chaîne d''approvisionnement', 'Saisie douanière et sanctions financières', 'Obligations de diligence raisonnable dans la chaîne d’approvisionnement pour les importateurs d’étain, tantale, tungstène et or.', 'Industrie, Électronique, Automobile, Joaillerie', 'Traçabilité rigoureuse des fonderies et raffineries d’origine.', 'obligatoire_direct'),

-- F. Déchets & Économie circulaire
('ue_paquet_circulaire', 'Paquet Économie Circulaire Européen', 'Directives 2018/851 & 2018/852', 'europe', 'Déchets & Économie Circulaire', 'Europe / Déchets', 'Mise en demeure et pénalités de gestion non conforme des flux', 'Ensemble de mesures fixant des objectifs de recyclage contraignants (65% des déchets municipaux d’ici 2035).', 'Collectivités, Industriels, Emballeurs', 'Tri à la source et réemploi prioritaire des matières premières secondaires.', 'obligatoire_direct'),
('ue_directive_dechets', 'Directive Cadre Déchets', 'Directive 2008/98/CE révisée', 'europe', 'Déchets & Économie Circulaire', 'Europe / Déchets', 'Poursuites pénales pour gestion illégale de déchets', 'Hiérarchie des modes de traitement : Prévention > Réemploi > Recyclage > Valorisation > Élimination.', 'Toutes entreprises productrices de déchets', 'Tenue d''un registre des déchets et justification de valorisation.', 'obligatoire_direct'),
('ue_emballages', 'Directive Emballages & Déchets d’Emballages (PPWR)', 'Directive 94/62/CE & Règlement PPWR 2024', 'europe', 'Déchets & Économie Circulaire', 'Europe / Emballages', 'Interdiction de commercialisation des emballages non recyclables', 'Objectifs stricts de réduction du suremballage, interdiction de plastiques à usage unique et incorporation de matière recyclée.', 'Agroalimentaire, E-commerce, Cosmétique, Distribution', '100% des emballages recyclables d''ici 2030 et respect des seuils de réemploi.', 'obligatoire_direct'),
('ue_deee', 'Directive DEEE — Déchets Équipements Électriques', 'Directive 2012/19/UE', 'europe', 'Déchets & Économie Circulaire', 'Europe / DEEE', 'Amendes administratives et obligation de reprise financière', 'Obligation de collecte, traitement et valorisation écologique des équipements électriques et électroniques en fin de vie.', 'Fabricants, Importateurs d''équipements high-tech et électroménager', 'Adhésion à un éco-organisme agréé et affichage du pictogramme poubelle barrée.', 'obligatoire_direct'),
('ue_rohs', 'Directive RoHS — Limitation des Substances Dangereuses', 'Directive 2011/65/UE', 'europe', 'Déchets & Économie Circulaire', 'Europe / Substances', 'Retrait immédiat du marché européen et sanctions pénales', 'Interdiction de 10 substances dangereuses (plomb, mercure, cadmium, phtalates...) dans les équipements électriques.', 'Électronique, Éclairage, Électroménager, Industrie', 'Dossier technique de conformité RoHS et marquage CE.', 'obligatoire_direct'),

-- G. Eau, Air, Pollution
('ue_qualite_air', 'Directive Qualité de l’Air Ambiant', 'Directive 2008/50/CE révisée 2024', 'europe', 'Eau, Air & Pollution', 'Europe / Air', 'Condamnations d’États et fermeture d’installations émettrices', 'Alignement progressif des seuils de particules fines PM2.5, PM10 et NO2 sur les recommandations de l’OMS.', 'Transports, Industrie, Chantiers, Chauffage urbain', 'Respect des valeurs limites d’émissions atmosphériques.', 'obligatoire_direct'),
('ue_ied', 'Directive Émissions Industrielles (IED)', 'Directive 2010/75/UE révisée 2024', 'europe', 'Eau, Air & Pollution', 'Europe / Industrie', 'Suspension d''autorisation d''exploiter et amendes administratives journalières', 'Encadrement des rejets polluants dans l’eau, l’air et le sol des grandes installations industrielles et élevages intensifs.', 'Chimie, Métallurgie, Agro-industrie, Traitement de déchets', 'Application obligatoire des Meilleures Techniques Disponibles (MTD/BREF).', 'obligatoire_direct'),
('ue_eau_potable', 'Directive Eau Potable', 'Directive (UE) 2020/2184', 'europe', 'Eau, Air & Pollution', 'Europe / Eau', 'Interdiction de distribution d’eau non conforme', 'Protection de la ressource en eau destinée à la consommation humaine et suivi renforcé des polluants émergents (PFAS, microplastiques).', 'Services d’eau, Agroalimentaire, Embouteilleurs', 'Plan de gestion de la sécurité sanitaire de l’eau.', 'obligatoire_direct'),
('ue_eaux_residuaires', 'Directive Eaux Résiduaires Urbaines', 'Directive 91/271/CEE révisée 2024', 'europe', 'Eau, Air & Pollution', 'Europe / Eau', 'Pénalités financières et mise aux normes obligatoire des rejets', 'Traitement et épuration des eaux usées urbaines et industrielles raccordées.', 'Collectivités, Industriels raccordés au réseau public', 'Surveillance des micropolluants et responsabilité élargie des pollueurs.', 'obligatoire_direct'),


-- 🇫🇷 II. TEXTES FRANÇAIS (OBLIGATIONS & CADRES NATIONAUX)
-- A. Reporting & Transparence
('fr_dpef', 'DPEF — Déclaration de Performance Extra-Financière', 'Code de commerce art. L. 225-102-1', 'france', 'Reporting & Transparence', 'France / Reporting', 'Injonction sous astreinte et engagement de la responsabilité des dirigeants', 'Rapport RSE obligatoire en France pour les sociétés dépassant les seuils légaux (remplacé par CSRD pour les grandes entités).', 'Sociétés cotées > 500 salariés et non cotées > 500 salariés + 100M€ bilan', 'Publication des politiques, risques et résultats ESG avec vérification par un OTI.', 'obligatoire_direct'),
('fr_beges', 'Décret BEGES — Bilan GES Réglementaire Scope 1-2-3', 'Décret n° 2022-982 (Code environnement L. 229-25)', 'france', 'Reporting & Transparence', 'France / Carbone', 'Amende administrative de 10 000 € portée à 20 000 € en cas de récidive', 'Obligation de réaliser et publier un Bilan d’Émissions de Gaz à Effet de Serre complet incluant le Scope 3 et un plan de transition.', 'Entreprises > 500 salariés (250 en outre-mer) et collectivités > 50 000 hab', 'Publication quadriennale sur la plateforme officielle de l''ADEME.', 'obligatoire_direct'),
('fr_grenelle2', 'Loi Grenelle II (Article 225)', 'Loi n° 2010-788 du 12 juillet 2010', 'france', 'Reporting & Transparence', 'France / Historique', 'Cadre historique précurseur du reporting RSE', 'Texte fondateur ayant instauré la transparence extra-financière et l’obligation d’audit RSE par organisme tiers en France.', 'Grandes entreprises', 'Historique du cadre de reporting national.', 'obligatoire_direct'),

-- B. Vigilance & Droits humains
('fr_vigilance', 'Loi sur le Devoir de Vigilance', 'Loi n° 2017-399 du 27 mars 2017', 'france', 'Vigilance & Droits Humains', 'France / Vigilance', 'Action en responsabilité civile, réparation des préjudices et astreinte judiciaire', 'Obligation pour les grandes sociétés françaises d’établir et mettre en œuvre un plan de vigilance contre les atteintes aux droits humains et à l’environnement.', 'Sociétés > 5 000 salariés en France ou 10 000 dans le monde', 'Cartographie des risques, procédures d’évaluation des filiales et sous-traitants, mécanisme d’alerte.', 'obligatoire_direct'),
('fr_sapin2', 'Loi Sapin II — Transparence & Éthique', 'Loi n° 2016-1691 du 9 décembre 2016', 'france', 'Vigilance & Droits Humains', 'France / Éthique', 'Sanctions AFA jusqu’à 1 million d’euros pour les personnes morales', 'Loi relative à la transparence, à la lutte contre la corruption et à la modernisation de la vie économique.', 'Entreprises > 500 salariés et CA > 100 M€', 'Code de conduite, dispositif d’alerte professionnelle et évaluation des tiers.', 'obligatoire_direct'),

-- C. Climat & Transition
('fr_climat_resilience', 'Loi Climat & Résilience (Article L229-68)', 'Loi n° 2021-1104 du 22 août 2021', 'france', 'Climat & Transition', 'France / Climat & Pub', 'Amende jusqu’à 100 000 € (et jusqu’au montant des dépenses publicitaires engagées)', 'Interdiction stricte d’affirmer dans une publicité qu’un produit ou service est neutre en carbone sans bilan GES complet et trajectoire publiée.', 'Tous annonceurs et entreprises communicantes', 'Publication d’un bilan d’émissions couvrant l’ensemble du cycle de vie et trajectoire de réduction annuelle.', 'obligatoire_direct'),
('fr_snbc', 'Stratégie Nationale Bas-Carbone (SNBC)', 'Code de l’énergie art. L. 222-1 B (SNBC 2 & 3)', 'france', 'Climat & Transition', 'France / Stratégie Climat', 'Budgets carbone contraignants imposés par secteur aux politiques publiques', 'Feuille de route officielle de la France pour atteindre la neutralité carbone à l’horizon 2050 avec budgets carbone sectoriels.', 'Tous secteurs économiques français', 'Alignement des plans de transition d''entreprise sur les trajectoires sectorielles SNBC.', 'obligatoire_direct'),
('fr_ppe', 'Programmation Pluriannuelle de l’Énergie (PPE)', 'Code de l’énergie art. L. 141-1', 'france', 'Climat & Transition', 'France / Énergie', 'Cadre contraignant de développement des filières énergétiques', 'Outil de pilotage de la transition énergétique française fixant les objectifs de réduction des énergies fossiles et déploiement des renouvelables.', 'Secteur énergétique, Industrie, Bâtiment', 'Sobriété énergétique et substitution des énergies fossiles.', 'obligatoire_direct'),
('fr_code_energie', 'Code de l’Énergie & Décret Tertiaire', 'Décret n° 2019-771 (Décret Éco Énergie Tertiaire)', 'france', 'Climat & Transition', 'France / Tertiaire', 'Publication des mauvais élèves ("Name & Shame") et amende jusqu’à 7 500 € par bâtiment', 'Obligation de réduction progressive des consommations d’énergie des bâtiments tertiaires (-40% en 2030, -50% en 2040, -60% en 2050).', 'Bâtiments tertiaires de plus de 1 000 m²', 'Déclaration annuelle des consommations sur la plateforme OPERAT de l’ADEME.', 'obligatoire_direct'),

-- D. Produits, ACV, FDES/EPD
('fr_fdes_inies', 'FDES & Base Nationale INIES', 'Arrêté du 23 décembre 2013 & Norme NF EN 15804', 'france', 'Produits, ACV, FDES/EPD', 'France / FDES', 'Application automatique de Données Environnementales par Défaut (DED) très pénalisantes', 'Fiche de Déclaration Environnementale et Sanitaire obligatoire pour valoriser les performances carbone réelles des matériaux dans le bâtiment.', 'Fabricants de matériaux de construction et équipements', 'Vérification par tierce partie indépendante et dépôt sur la base nationale INIES.', 'obligatoire_direct'),
('fr_re2020', 'Réglementation Environnementale RE2020', 'Décret n° 2021-1004 (Code construction et habitation)', 'france', 'Produits, ACV, FDES/EPD', 'France / RE2020', 'Refus de permis de construire, non-conformité à la livraison et interdiction d’exploitation', 'Obligation de réaliser l’Analyse du Cycle de Vie (ACV) dynamique complète de chaque bâtiment neuf avec respect des seuils carbone ICconstruction.', 'Promoteurs, Constructeurs, Architectes, BTP', 'Calcul de l’impact carbone des composants via les FDES/PEP et respect des seuils dégressifs 2025-2028-2031.', 'obligatoire_direct'),
('fr_epd_france', 'EPD — Déclarations Environnementales Produits', 'Normes NF EN 15804 / ISO 14025', 'france', 'Produits, ACV, FDES/EPD', 'France / EPD', 'Rejet des dossiers techniques en marchés publics', 'Déclaration environnementale normalisée pour les produits industriels et équipements techniques.', 'Industrie, Électronique, Câblerie, BTP', 'Attestation d’impact carbone vérifiée par un programme d’enregistrement reconnu.', 'obligatoire_direct'),
('fr_cch_acv', 'Code de la Construction — ACV Bâtiment', 'Code de la construction et de l’habitation Livre I', 'france', 'Produits, ACV, FDES/EPD', 'France / Réglementation', 'Non-délivrance de l’attestation de conformité d’achèvement des travaux', 'Dispositions législatives encadrant les performances énergétiques et l’empreinte carbone des constructions neuves et rénovations.', 'Maîtres d’ouvrage, Bureaux d’études thermiques/carbone', 'Fourniture de l’attestation de prise en compte des exigences RE2020 à l’achèvement.', 'obligatoire_direct'),

-- E. Déchets & Économie circulaire
('fr_agec', 'Loi AGEC — Anti-Gaspillage Économie Circulaire', 'Loi n° 2020-105 du 10 février 2020', 'france', 'Déchets & Économie Circulaire', 'France / AGEC', 'Amendes administratives jusqu’à 15 000 € pour les personnes morales et retrait des produits', 'Fin des plastiques à usage unique, interdiction d’élimination des invendus non alimentaires, indice de réparabilité et affichage du logo Triman.', 'Toutes entreprises produisant ou distribuant des biens de consommation', 'Affichage obligatoire du logo Triman + Info-Tri et interdiction de la mention "biodégradable".', 'obligatoire_direct'),
('fr_rep', 'REP — Responsabilités Élargies du Producteur', 'Code de l’environnement art. L. 541-10', 'france', 'Déchets & Économie Circulaire', 'France / REP', 'Sanction de 7 500 € par tonne mise sur le marché sans identifiant unique (IDU)', 'Principe pollueur-payeur imposant aux metteurs sur le marché de pourvoir ou contribuer à la fin de vie de leurs produits (plus de 25 filières REP).', 'Emballages, Textile, BTP, Meubles, Électronique, Piles, Jouets...', 'Adhésion à un éco-organisme agréé et paiement des éco-contributions.', 'obligatoire_direct'),
('fr_tri_5flux', 'Décret Tri 5 / 7 / 8 Flux Déchets', 'Décret n° 2021-950 du 16 juillet 2021', 'france', 'Déchets & Économie Circulaire', 'France / Tri', 'Amende de 5e classe et fermeture administrative temporaire', 'Obligation de tri à la source des déchets : papier/carton, métal, plastique, verre, bois, fraction minérale, plâtre et biodéchets.', 'Tous professionnels et établissements produisant des déchets', 'Attestation annuelle de collecte et de valorisation délivrée par le prestataire de déchets.', 'obligatoire_direct'),
('fr_decret_emballages', 'Décret Réduction des Emballages Plastiques', 'Décret n° 2021-517 (Objectifs 3R)', 'france', 'Déchets & Économie Circulaire', 'France / Emballages', 'Contrôles DDPP et sanctions prévues au code de l’environnement', 'Stratégie nationale 3R (Réduction, Réemploi, Recyclage) fixant une baisse de 20% des emballages plastiques à usage unique.', 'Conditionneurs, Grande distribution, Restauration', 'Plan de prévention et d’éco-conception quinquennal des emballages.', 'obligatoire_direct'),

-- F. Eau, Air, Pollution
('fr_icpe', 'ICPE — Installations Classées pour la Protection de l’Environnement', 'Code de l’environnement art. L. 511-1 et suivants', 'france', 'Eau, Air & Pollution', 'France / ICPE', 'Sanctions pénales, consignation de sommes et fermeture d’usine', 'Régime d’autorisation, d’enregistrement ou de déclaration imposé à toute exploitation industrielle ou agricole susceptible de créer des nuisances.', 'Usines, Entrepôts, Ateliers, Élevages, Carrières', 'Respect des prescriptions techniques d’exploitation et garanties financières.', 'obligatoire_direct'),
('fr_ppa', 'Plan de Protection de l’Atmosphère (PPA)', 'Code de l’environnement art. L. 222-4', 'france', 'Eau, Air & Pollution', 'France / PPA', 'Arrêtés préfectoraux de restriction immédiate d’activité en pic de pollution', 'Plans locaux obligatoires dans les agglomérations de plus de 250 000 habitants pour ramener les concentrations de polluants sous les valeurs limites.', 'Entreprises situées dans les zones PPA (Grand Paris, Lyon, Marseille...)', 'Respect des restrictions d’émissions industrielles et des Zones à Faibles Émissions (ZFE).', 'obligatoire_direct'),
('fr_code_env', 'Code de l’Environnement — Livres II et V', 'Code de l’environnement (Livre II Eau, Livre V Prévention)', 'france', 'Eau, Air & Pollution', 'France / Code Environnement', 'Peines correctionnelles jusqu’à 3 ans de prison et 1 000 000 € d’amende', 'Cadre légal réprimant les pollutions accidentelles ou chroniques des cours d’eau, nappes phréatiques, rejets dans l’air et émissions sonores.', 'Tous exploitants industriels et agricoles', 'Mesures de prévention contre tout rejet toxique dans le milieu naturel.', 'obligatoire_direct'),

-- G. Achats responsables & Marchés publics
('fr_commande_publique', 'Code de la Commande Publique & Critères RSE', 'Code de la commande publique art. L. 2152-7', 'france', 'Achats Responsables & Marchés Publics', 'France / Marchés Publics', 'Rejet automatique des offres non conformes aux critères environnementaux', 'Intégration obligatoire de critères d’attribution prenant en compte les caractéristiques environnementales et le coût du cycle de vie.', 'Toutes entreprises candidates aux marchés publics', 'Justification des performances écologiques des produits ou prestations proposées.', 'obligatoire_direct'),
('fr_achats_responsables', 'Décret Achats Responsables (PNAD)', 'Plan National pour des Achats Durables (2022-2025)', 'france', 'Achats Responsables & Marchés Publics', 'France / Achats', 'Obligation de Schéma de Promotion des Achats Responsables (SPAR)', 'Objectif de 100% des marchés publics comprenant une disposition environnementale et 30% une disposition sociale.', 'Acheteurs publics et collectivités > 50 M€ d''achats', 'Adoption d’un SPAR et sourcing de prestataires éco-responsables.', 'obligatoire_direct'),
('fr_clauses_2026', 'Obligation Clauses Environnementales Marchés Publics 2026', 'Loi Climat & Résilience art. 35 (Entrée en vigueur 2026)', 'france', 'Achats Responsables & Marchés Publics', 'France / 2026', 'Inéligibilité légale des candidats ne fournissant pas de garanties environnementales', 'Obligation universelle pour les acheteurs publics de prévoir au moins un critère environnemental de choix des offres.', 'Tous fournisseurs de la commande publique', 'Obligation de fournir Bilans Carbone, FDES ou attestations de durabilité dans toute réponse d’appel d’offres.', 'echeance_2026'),

-- H. Consommateurs & Publicité
('fr_loi_climat_pub', 'Encadrement Publicité & Allégations RSE', 'Loi Climat art. L. 229-68 & Décret 2022-539', 'france', 'Consommateurs & Publicité', 'France / Publicité', 'Amende de 100 000 € et affichage public de la condamnation', 'Obligation de rendre public le rapport de synthèse justifiant toute affirmation de neutralité carbone.', 'Tous annonceurs, agences de com et marques', 'Interdiction des allégations de compensation sans démarche préalable de réduction.', 'obligatoire_direct'),
('fr_code_conso', 'Code de la Consommation (Article L. 121-2)', 'Code de la consommation art. L. 121-2 & L. 132-2', 'france', 'Consommateurs & Publicité', 'France / Consommation', 'Jusqu’à 2 ans d’emprisonnement et 300 000 € d’amende (portée à 10% du CA annuel)', 'Sanction des pratiques commerciales trompeuses reposant sur des allégations environnementales fausses ou de nature à induire en erreur.', 'Tous professionnels vendant aux consommateurs', 'Charge de la preuve intégrale pesant sur l’entreprise qui émet l’allégation.', 'obligatoire_direct'),
('fr_arpp', 'Recommandations ARPP Développement Durable', 'Code de l’Autorité de Régulation Professionnelle de la Publicité', 'france', 'Consommateurs & Publicité', 'France / Déontologie', 'Avis défavorable, demande de modification ou cessation de diffusion de la campagne', 'Règles déontologiques professionnelles sur la véracité, la proportionnalité, la clarté et la loyauté des arguments environnementaux.', 'Annonceurs, Médias, Agences de communication', 'Proportionnalité du message et clarté des bénéfices réels apportés.', 'recommande_marches'),


-- 🧩 III. NORMES INTERNATIONALES (RÉFÉRENTIELS D'AUDIT & SCIENTIFIQUES)
-- A. ACV & Empreinte carbone
('iso_14040', 'ISO 14040 / 14044 — Analyse du Cycle de Vie (ACV)', 'Normes internationales ISO 14040:2006 & ISO 14044:2006', 'international', 'ACV & Empreinte Carbone', 'International / ACV', 'Contestation scientifique de l’allégation et rejet des certifications', 'Méthodologie internationale normalisée en 4 étapes (Objectifs, Inventaire ICV, Évaluation des impacts, Interprétation) pour mesurer l’empreinte écologique globale.', 'Tous produits et services', 'Modélisation multicritère berceau-à-tombe et revue critique indépendante pour diffusion publique.', 'recommande_marches'),
('iso_14067', 'ISO 14067 — Empreinte Carbone des Produits (CFP)', 'Norme internationale ISO 14067:2018', 'international', 'ACV & Empreinte Carbone', 'International / Carbone Produit', 'Non-conformité de la déclaration carbone produit', 'Exigences et lignes directrices pour la quantification et la communication de l’empreinte carbone des produits.', 'Biens manufacturés, Agroalimentaire, Biens d''équipement', 'Quantification des émissions de GES spécifiques au produit selon les règles des catégories de produits (PCR).', 'recommande_marches'),
('ghg_protocol', 'GHG Protocol — Scopes 1, 2 et 3', 'WRI / WBCSD Greenhouse Gas Protocol Corporate Standard', 'international', 'ACV & Empreinte Carbone', 'International / Carbone Standard', 'Non-alignement sur les standards internationaux d''audit carbone', 'Standard mondial de comptabilisation des gaz à effet de serre divisé en Scope 1 (direct), Scope 2 (énergie) et Scope 3 (chaîne de valeur).', 'Toutes entreprises et organisations', 'Inventaire exhaustif des 15 catégories d’émissions du Scope 3.', 'recommande_marches'),
('pas_2050', 'PAS 2050 — Évaluation des Émissions GES des Biens et Services', 'BSI / DEFRA Publicly Available Specification 2050', 'international', 'ACV & Empreinte Carbone', 'International / BSI', 'Rejet des bilans carbone produits anglo-saxons', 'Spécification pionnière britannique pour l’évaluation de l’empreinte carbone des produits et services en B2B et B2C.', 'Biens de grande consommation', 'Calcul du cycle de vie carbone produit.', 'recommande_marches'),

-- B. Management environnemental
('iso_14001', 'ISO 14001 — Système de Management Environnemental (SME)', 'Norme internationale ISO 14001:2015', 'international', 'Management Environnemental', 'International / Management', 'Perte de certification et disqualification en appels d’offres majeurs', 'Cadre international certifiable définissant les exigences pour établir une politique environnementale, maîtriser ses impacts et progresser en continu.', 'Entreprises industrielles, Tertiaires, Services, BTP', 'Audit de certification initial et audits de suivi annuels par un organisme accrédité.', 'recommande_marches'),
('iso_50001', 'ISO 50001 — Système de Management de l’Énergie', 'Norme internationale ISO 50001:2018', 'international', 'Management Environnemental', 'International / Énergie', 'Perte de l’exemption d’audit énergétique réglementaire obligatoire', 'Norme fixant les exigences pour élaborer une politique énergétique, fixer des objectifs de consommation et mesurer la performance énergétique.', 'Grands consommateurs d''énergie, Usines, Bâtiments tertiaires', 'Revue énergétique, indicateurs de performance (IPÉ) et plan de comptage.', 'recommande_marches'),

-- C. RSE & Gouvernance
('iso_26000', 'ISO 26000 — Responsabilité Sociétale des Organisations', 'Lignes directrices internationales ISO 26000:2010', 'international', 'RSE & Gouvernance', 'International / RSE', 'Évaluation de maturité RSE faible lors des audits donneurs d’ordres (EcoVadis)', 'Référentiel universel non certifiable articulé autour de 7 questions centrales (Gouvernance, Droits de l’homme, Relations de travail, Environnement, Loyauté des pratiques, Consommateurs, Développement local).', 'Toutes organisations publiques et privées', 'Auto-évaluation ou labellisation tierce partie (Engagé RSE AFNOR, Label Lucie).', 'recommande_marches'),
('gri_standards', 'GRI Standards — Global Reporting Initiative', 'Normes GRI Universelles et Sectorielles', 'international', 'RSE & Gouvernance', 'International / Reporting', 'Non-reconnaissance internationale du rapport de développement durable', 'Cadre mondial le plus utilisé pour le reporting volontaire de durabilité et d’impact sur l’économie, l’environnement et la société.', 'Multinationales, ETI, Grandes entreprises', 'Déclaration de conformité aux standards GRI avec tableau d''index des contenus.', 'recommande_marches'),


-- 🧨 IV. TEXTES SECTORIELS (POUR LE SCANNER D'OPPORTUNITÉS COMMERCIALES)
-- Construction / BTP
('sec_btp_re2020', 'RE2020 & Seuils Carbone BTP Dégressifs', 'Réglementation Bâtiment 2020 / CCH', 'sectoriel', 'Construction & BTP', 'Secteur / BTP', 'Refus de conformité de livraison d’ouvrage et pénalités de retard', 'La RE2020 impose une trajectoire de décarbonation de la construction avec abaissement des seuils d’émissions par m² en 2025, 2028 et 2031.', 'BTP, Promoteurs, Fabricants de matériaux', 'Obligation de FDES pour chaque matériau posé.', 'obligatoire_direct'),
('sec_btp_inies', 'Base INIES & Données Environnementales du Bâtiment', 'Arrêté ministériel & Programme INIES', 'sectoriel', 'Construction & BTP', 'Secteur / BTP', 'Pénalisation lourde des chantiers avec les valeurs par défaut DED', 'Base nationale de référence sur les déclarations environnementales et sanitaires des produits et équipements de la construction.', 'Fabricants de composants de bâtiment', 'Déclaration obligatoire des FDES / PEP certifiées.', 'obligatoire_direct'),

-- Industrie
('sec_ind_ets_macf', 'Paquet Industrie Décarbonée (EU ETS & MACF)', 'Règlements UE 2023/956 & Directive ETS révisée', 'sectoriel', 'Industrie & Matériaux', 'Secteur / Industrie', 'Taxe carbone de 80 à 100 € par tonne de CO2 excédentaire', 'Fin progressive des quotas gratuits européens et taxation carbone aux frontières pour protéger l’industrie verte européenne.', 'Sidérurgie, Chimie, Ciment, Métallurgie, Verre', 'Plan d’investissement de décarbonation industrielle.', 'obligatoire_direct'),
('sec_ind_espr_rohs', 'Conformité Matérielle ESPR, RoHS & DEEE', 'Règlements ESPR 2024 & Directives RoHS/DEEE', 'sectoriel', 'Industrie & Matériaux', 'Secteur / Équipements', 'Interdiction de mise sur le marché et destruction des stocks non conformes', 'Exigences d’écoconception, traçabilité des substances critiques et recyclabilité des équipements industriels.', 'Biens industriels, Électronique, Machinerie', 'Fiche de données de sécurité et passeport produit.', 'obligatoire_direct'),

-- Agroalimentaire
('sec_agro_inco_agec', 'Réglementation Allégations Agroalimentaires (INCO & AGEC)', 'Règlement (UE) 1169/2011 (INCO) & Loi AGEC', 'sectoriel', 'Agroalimentaire & Distribution', 'Secteur / Agroalimentaire', 'Saisie DGCCRF, rappels de lots et amendes pour étiquetage trompeur', 'Encadrement strict des mentions écologiques et d’origine sur les emballages alimentaires : interdiction des mentions absolues sans label bio ou HVE.', 'Agroalimentaire, Boissons, Maraîchage, Élevage', 'Preuves d''origine certifiée et étiquetage conforme Info-Tri.', 'obligatoire_direct'),

-- Tourisme / Hôtellerie
('sec_tour_cleverte_ecolabel', 'Écolabel Européen & Label Clé Verte Hébergements', 'Règlement (CE) 66/2010 & Critères Green Key', 'sectoriel', 'Tourisme & Hôtellerie', 'Secteur / Tourisme', 'Interdiction d’utiliser les termes "Hôtel écologique" sans label certifié', 'Normes d’éco-labellisation des hébergements touristiques, restaurants et campings (eau, énergie, déchets, achats responsables).', 'Hôtels, Résidences, Campings, Gîtes, Restaurants', 'Audit sur site selon les 67 critères obligatoires de gestion durable.', 'recommande_marches'),
('sec_tour_affichage_env', 'Affichage Environnemental Touristique (Score Impact)', 'Article L. 541-9-11 du Code de l’environnement', 'sectoriel', 'Tourisme & Hôtellerie', 'Secteur / Tourisme', 'Non-conformité de la communication client en ligne', 'Expérimentation et déploiement du score écologique officiel des nuitées hôtelières (note A à E basée sur les émissions par nuit).', 'Hôtellerie et hébergements marchands', 'Calcul du score environnemental par nuitée selon la méthode ADEME.', 'recommande_marches');

-- Aucun jeu de données d’exemple n’est inséré automatiquement.
-- La base V1.1 démarre vierge : les entreprises, audits et allégations
-- sont créés uniquement par les scans et les actions réelles des utilisateurs.
