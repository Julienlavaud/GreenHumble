import { GreenwashingAudit } from '../types.js';

/**
 * Safely parses French currency/budget strings into a clean numeric value in Euros.
 * Examples:
 * - "8 500 € HT (Reste à charge ~4 500 € après subvention)" => 8500
 * - "5 000 € à 9 000 € (Finançable à 50% via Diag Décarbon’Action)" => 7000 (average of range)
 * - "6 000 € HT" => 6000
 * - "12 500 €" => 12500
 */
export const parseBudgetValue = (str: string | undefined | null): number | null => {
  if (!str || typeof str !== 'string') return null;

  // 1. First attempt: strip parenthetical notes like "(Reste à charge ~4 500 € ...)"
  let cleaned = str.replace(/\([^)]*\)/g, ' ').trim();

  // If stripping parentheses left no string or no digits, revert to full string
  if (!cleaned || !/\d/.test(cleaned)) {
    cleaned = str;
  }

  // 2. Remove percentage tokens like "50%", "80 %"
  cleaned = cleaned.replace(/\d+\s*%/g, ' ');

  // 3. Normalize non-breaking spaces
  cleaned = cleaned.replace(/[\u00a0\u202f]/g, ' ');

  // 4. Combine thousands separators formatted like "8 500", "12 000", "5.000", "12.500"
  cleaned = cleaned.replace(/(\b\d{1,3})[\s\.](\d{3}\b)/g, '$1$2');

  // 5. Extract all numbers from the cleaned text
  const matches = cleaned.match(/\b\d+\b/g);
  if (!matches || matches.length === 0) return null;

  // 6. Convert numbers and filter out realistic budget bounds for commercial ESG missions (500 € to 250 000 €)
  const numbers = matches
    .map((m) => parseInt(m, 10))
    .filter((n) => !isNaN(n) && n >= 500 && n <= 250000);

  if (numbers.length === 0) return null;

  // 7. If range is provided e.g. "5 000 € à 8 000 €", calculate average
  if (numbers.length >= 2) {
    const sum = numbers.reduce((acc, num) => acc + num, 0);
    return Math.round(sum / numbers.length);
  }

  return numbers[0];
};

/**
 * Calculates the realistic deal budget for a given audit.
 */
export const getDealBudget = (audit: GreenwashingAudit): number => {
  // 1. Direct pricing estimate from recommended proposal
  const proposalPrice = audit.commercialProspectingKit?.climatLocalProposal?.pricingEstimate;
  if (proposalPrice) {
    const parsedProposal = parseBudgetValue(proposalPrice);
    if (parsedProposal) return parsedProposal;
  }

  // 2. Business opportunities primary or average
  if (Array.isArray(audit.businessOpportunities) && audit.businessOpportunities.length > 0) {
    const oppBudgetStr = audit.businessOpportunities[0]?.potentialBudget;
    if (oppBudgetStr) {
      const parsedOpp = parseBudgetValue(oppBudgetStr);
      if (parsedOpp) return parsedOpp;
    }
  }

  // 3. Fallback based on risk level if no explicit pricing is parsed
  if (audit.riskLevel === 'critique') return 5500;
  if (audit.riskLevel === 'eleve') return 4200;
  return 3500;
};
