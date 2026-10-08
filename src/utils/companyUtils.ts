import { GreenwashingAudit } from '../types.js';

/**
 * Cleanly formats a company name from raw input or domain string.
 * Strips URLs, domain TLDs (e.g. .fr, .com), replaces hyphens with spaces,
 * and capitalizes words nicely if no official name is present.
 */
export function cleanCompanyName(rawName?: string, domain?: string): string {
  let name = (rawName || '').trim();

  // If missing or looks like a URL/domain
  if (!name || name.includes('http://') || name.includes('https://') || name.includes('www.') || name.includes('.')) {
    const src = name || domain || '';
    name = src
      .replace(/^https?:\/\//i, '')
      .replace(/^www\./i, '')
      .split('/')[0]
      .split('?')[0];

    // Strip common domain TLDs (.fr, .com, .org, .net, .eu, .co.uk, .io, etc.)
    name = name.replace(/\.(fr|com|org|net|eu|co\.uk|io|be|ch|de|es|it|gouv\.fr)$/i, '');

    // Replace hyphens and underscores with spaces
    name = name.replace(/[-_]/g, ' ');

    // Capitalize words
    name = name
      .split(' ')
      .filter(Boolean)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
      .join(' ');
  }

  // Handle common uppercase acronyms or clean up HTML titles like "Company - Home"
  if (name.includes(' - ')) {
    name = name.split(' - ')[0].trim();
  }
  if (name.includes(' | ')) {
    name = name.split(' | ')[0].trim();
  }

  return name || 'Entreprise Auditée';
}

/**
 * Returns a standardized domain key (e.g. "locacuisines.fr") for grouping scans.
 */
export function getCompanyDomainKey(audit: { cleanUrl?: string; url?: string; domain?: string }): string {
  const src = audit.cleanUrl || audit.url || audit.domain || '';
  return src
    .toLowerCase()
    .replace(/^https?:\/\//i, '')
    .replace(/^www\./i, '')
    .split('/')[0]
    .split('?')[0]
    .trim();
}

// Stage hierarchy for picking the most advanced deal status when grouping
const STAGE_RANK: Record<string, number> = {
  signe: 6,
  devis_envoye: 5,
  rdv_fixe: 4,
  relance: 3,
  contacte: 2,
  nouveau: 1,
};

export type CompanyAuditRecord = GreenwashingAudit & {
  totalCompanyScansCount?: number;
};

/**
 * Groups audits by company (domain key) and returns EXACTLY ONE representative audit per company.
 * Requirement: "Compter un scan par entreprise dans le pipeline meme si plusieurs scans faits"
 * The representative audit is chosen based on highest pipeline progression stage, then newest date.
 */
export function getUniqueCompanyAudits(audits: GreenwashingAudit[]): CompanyAuditRecord[] {
  const map = new Map<string, GreenwashingAudit>();
  const scanCounts = new Map<string, number>();
  const companyScores = new Map<string, number[]>();
  const allAuditsPerCompany = new Map<string, GreenwashingAudit[]>();

  audits.forEach((audit) => {
    const key = getCompanyDomainKey(audit) || audit.companyName.toLowerCase().trim();
    if (!key) return;

    scanCounts.set(key, (scanCounts.get(key) || 0) + 1);

    const list = allAuditsPerCompany.get(key) || [];
    list.push(audit);
    allAuditsPerCompany.set(key, list);

    // Track all valid non-null scores
    const s = audit.isUnnotable || audit.riskScore === null ? null : (audit.riskScore ?? audit.score_global ?? null);
    if (s !== null && s !== undefined && typeof s === 'number') {
      const arr = companyScores.get(key) || [];
      arr.push(s);
      companyScores.set(key, arr);
    }

    const existing = map.get(key);
    if (!existing) {
      map.set(key, audit);
    } else {
      const existingRank = STAGE_RANK[existing.prospectStatus || 'nouveau'] || 1;
      const currentRank = STAGE_RANK[audit.prospectStatus || 'nouveau'] || 1;

      if (currentRank > existingRank) {
        map.set(key, audit);
      } else if (currentRank === existingRank) {
        const existingDate = new Date(existing.analyzedAt || 0).getTime();
        const currentDate = new Date(audit.analyzedAt || 0).getTime();
        if (currentDate > existingDate) {
          map.set(key, audit);
        }
      }
    }
  });

  return Array.from(map.entries()).map(([key, audit]) => {
    const scores = companyScores.get(key) || [];
    // RÈGLE MÉTIER : "Le scan le plus favorable = note de l'entreprise"
    const bestScore = scores.length > 0 ? Math.max(...scores) : (audit.riskScore ?? audit.score_global ?? null);
    const bestRiskLevel = bestScore !== null 
      ? (bestScore >= 70 ? 'faible' : bestScore >= 40 ? 'modere' : 'critique') 
      : audit.riskLevel;

    // Pick best available contact across all scans of that company if current is empty
    const companyScans = allAuditsPerCompany.get(key) || [audit];
    const bestEmail = audit.prospectEmail || audit.leadContact?.email || 
      companyScans.find(a => a.prospectEmail || a.leadContact?.email)?.prospectEmail ||
      companyScans.find(a => a.leadContact?.email)?.leadContact?.email ||
      audit.companyContacts?.[0]?.email ||
      `contact@${key}`;

    const bestPhone = audit.prospectPhone || audit.leadContact?.phone ||
      companyScans.find(a => a.prospectPhone || a.leadContact?.phone)?.prospectPhone ||
      companyScans.find(a => a.leadContact?.phone)?.leadContact?.phone ||
      audit.companyContacts?.[0]?.phone ||
      '04 73 00 00 00';

    const bestName = audit.prospectName || audit.leadContact?.name ||
      companyScans.find(a => a.prospectName || a.leadContact?.name)?.prospectName ||
      companyScans.find(a => a.leadContact?.name)?.leadContact?.name ||
      audit.companyContacts?.[0]?.name ||
      'Direction / Responsable RSE';

    return {
      ...audit,
      riskScore: bestScore,
      score_global: bestScore,
      riskLevel: bestRiskLevel,
      prospectEmail: bestEmail,
      prospectPhone: bestPhone,
      prospectName: bestName,
      leadContact: audit.leadContact || {
        name: bestName,
        email: bestEmail,
        phone: bestPhone,
        role: audit.leadContact?.role || 'Direction & RSE'
      },
      companyName: cleanCompanyName(audit.companyName, audit.cleanUrl || audit.url),
      totalCompanyScansCount: scanCounts.get(key) || 1,
    };
  });
}
