<?php
declare(strict_types=1);

/**
 * Persiste un audit validé et rattache les domaines à une fiche société canonique.
 * Le SIREN confirmé est prioritaire; la sauvegarde JSON reste le secours MySQL.
 */
function gh_persist_commercial_audit(array $audit, array $enterpriseData = []): bool
{
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        return false;
    }

    $pdo = null;
    $lockName = null;
    $lockAcquired = false;
    try {
        $pdo = get_db_connection();

        $url = trim((string)($audit['url'] ?? ''));
        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
        $domain = preg_replace('/^www\./i', '', $host);
        if ($url === '' || $domain === '') return false;

        $companyName = trim((string)($audit['companyName'] ?? ''));
        if ($companyName === '') $companyName = ucfirst(explode('.', $domain)[0]);
        $companyName = function_exists('mb_substr') ? mb_substr($companyName, 0, 200) : substr($companyName, 0, 200);
        $siteUrl = strtolower((string)(parse_url($url, PHP_URL_SCHEME) ?: 'https')) . '://' . $host;
        $sector = (string)($enterpriseData['entreprise_secteur'] ?? $audit['entreprise_secteur'] ?? '');
        $headcount = (string)($enterpriseData['entreprise_salaries'] ?? $audit['entreprise_salaries'] ?? '');
        $turnover = (string)($enterpriseData['entreprise_ca'] ?? $audit['entreprise_ca'] ?? '');
        $candidateSiren = $enterpriseData['company_siren'] ?? ($audit['companyRegistry']['siren'] ?? '');
        $siren = preg_match('/^\d{9}$/', (string)$candidateSiren) ? (string)$candidateSiren : null;

        // Sérialiser les créations concurrentes d'une même société quand le SIREN est connu.
        if ($siren !== null) {
            $lockName = 'gh_siren_' . $siren;
            $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
            $lockStmt->execute([$lockName]);
            $lockAcquired = (int)$lockStmt->fetchColumn() === 1;
            if (!$lockAcquired) throw new RuntimeException('SIREN lock unavailable');
        }

        $pdo->beginTransaction();

        $domainCompany = null;
        $domainStmt = $pdo->prepare('SELECT c.id, c.siren FROM cl_company_domains d JOIN cl_companies c ON c.id = d.company_id WHERE d.domain = ? LIMIT 1');
        $domainStmt->execute([$domain]);
        $domainCompany = $domainStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$domainCompany) {
            $legacyStmt = $pdo->prepare('SELECT id, siren FROM cl_companies WHERE domain = ? OR clean_url = ? ORDER BY id ASC LIMIT 1');
            $legacyStmt->execute([$domain, $domain]);
            $domainCompany = $legacyStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $sirenCompany = null;
        if ($siren !== null) {
            $sirenStmt = $pdo->prepare('SELECT id, siren FROM cl_companies WHERE siren = ? ORDER BY id ASC LIMIT 1');
            $sirenStmt->execute([$siren]);
            $sirenCompany = $sirenStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $companyId = null;
        $duplicateCompanyId = null;
        if ($sirenCompany) {
            $companyId = (int)$sirenCompany['id'];
            if ($domainCompany && (int)$domainCompany['id'] !== $companyId) {
                $existingDomainSiren = trim((string)($domainCompany['siren'] ?? ''));
                if ($existingDomainSiren === '' || $existingDomainSiren === $siren) {
                    $duplicateCompanyId = (int)$domainCompany['id'];
                }
            }
        } elseif ($domainCompany) {
            $existingDomainSiren = trim((string)($domainCompany['siren'] ?? ''));
            if ($siren === null || $existingDomainSiren === '' || $existingDomainSiren === $siren) {
                $companyId = (int)$domainCompany['id'];
            }
            // Un SIREN différent indique un changement de titulaire ou un rapprochement antérieur erroné:
            // conserver les deux fiches et ne fusionner aucun historique.
        }

        if ($companyId === null) {
            $insertCompany = $pdo->prepare(
                'INSERT INTO cl_companies (name, siren, domain, website, clean_url, sector, headcount, turnover) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertCompany->execute([$companyName, $siren, $domain, $url ?: $siteUrl, $domain, $sector ?: null, $headcount ?: null, $turnover ?: null]);
            $companyId = (int)$pdo->lastInsertId();
        } else {
            $updateCompany = $pdo->prepare(
                'UPDATE cl_companies SET name = ?, siren = COALESCE(NULLIF(?, \'\'), siren), sector = COALESCE(NULLIF(?, \'\'), sector), headcount = COALESCE(NULLIF(?, \'\'), headcount), turnover = COALESCE(NULLIF(?, \'\'), turnover) WHERE id = ?'
            );
            $updateCompany->execute([$companyName, $siren, $sector, $headcount, $turnover, $companyId]);
        }

        // Rebrancher tous les historiques existants du même SIREN et l'éventuelle fiche de domaine.
        $duplicateCompanyIds = [];
        if ($siren !== null) {
            $duplicatesStmt = $pdo->prepare('SELECT id FROM cl_companies WHERE siren = ? AND id <> ?');
            $duplicatesStmt->execute([$siren, $companyId]);
            $duplicateCompanyIds = array_map('intval', $duplicatesStmt->fetchAll(PDO::FETCH_COLUMN));
        }
        if ($duplicateCompanyId !== null && $duplicateCompanyId !== $companyId) {
            $duplicateCompanyIds[] = $duplicateCompanyId;
        }
        $duplicateCompanyIds = array_values(array_unique(array_filter($duplicateCompanyIds, static function ($id) use ($companyId) {
            return (int)$id > 0 && (int)$id !== (int)$companyId;
        })));

        $relatedTables = ['cl_audits', 'cl_business_opportunities', 'cl_prospecting_kits', 'cl_commercial_checklist', 'cl_company_contacts'];
        $tableExists = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
        foreach ($duplicateCompanyIds as $duplicateId) {
            $duplicateRowStmt = $pdo->prepare('SELECT domain, website, clean_url FROM cl_companies WHERE id = ? LIMIT 1');
            $duplicateRowStmt->execute([$duplicateId]);
            $duplicateRow = $duplicateRowStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $mergeMetadata = $pdo->prepare(
                'UPDATE cl_companies canonical JOIN cl_companies duplicate ON duplicate.id = ? SET
                    canonical.sector = COALESCE(NULLIF(canonical.sector, \'\'), NULLIF(duplicate.sector, \'\')),
                    canonical.headcount = COALESCE(NULLIF(canonical.headcount, \'\'), NULLIF(duplicate.headcount, \'\')),
                    canonical.turnover = COALESCE(NULLIF(canonical.turnover, \'\'), NULLIF(duplicate.turnover, \'\')) WHERE canonical.id = ?'
            );
            $mergeMetadata->execute([$duplicateId, $companyId]);
            foreach ([$duplicateRow['domain'] ?? '', $duplicateRow['website'] ?? '', $duplicateRow['clean_url'] ?? ''] as $oldDomainValue) {
                $oldDomainHost = parse_url((string)$oldDomainValue, PHP_URL_HOST) ?: (string)$oldDomainValue;
                $oldDomain = strtolower(preg_replace('/^www\\./i', '', trim($oldDomainHost)));
                if ($oldDomain !== '') {
                    $mapOldDomain = $pdo->prepare('INSERT INTO cl_company_domains (domain, company_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE company_id = VALUES(company_id)');
                    $mapOldDomain->execute([$oldDomain, $companyId]);
                }
            }
            $mapExistingAliases = $pdo->prepare('UPDATE cl_company_domains SET company_id = ? WHERE company_id = ?');
            $mapExistingAliases->execute([$companyId, $duplicateId]);

            foreach ($relatedTables as $table) {
                $tableExists->execute([$table]);
                if ($tableExists->fetchColumn()) {
                    $move = $pdo->prepare("UPDATE `{$table}` SET company_id = ? WHERE company_id = ?");
                    $move->execute([$companyId, $duplicateId]);
                }
            }
        }

        // Un domaine pointe vers une seule fiche active. L'URL exacte reste stockée sur chaque audit.
        $mapDomain = $pdo->prepare('INSERT INTO cl_company_domains (domain, company_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE company_id = VALUES(company_id)');
        $mapDomain->execute([$domain, $companyId]);
        $primaryDomain = $pdo->prepare('UPDATE cl_companies SET domain = COALESCE(NULLIF(domain, \'\'), ?), website = COALESCE(NULLIF(website, \'\'), ?), clean_url = COALESCE(NULLIF(clean_url, \'\'), ?) WHERE id = ?');
        $primaryDomain->execute([$domain, $url ?: $siteUrl, $domain, $companyId]);

        $analyzedAt = (string)($audit['analyzedAt'] ?? date('c'));
        $timestamp = strtotime($analyzedAt);
        $mysqlDate = $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
        $score = is_numeric($audit['riskScore'] ?? null) ? (float)$audit['riskScore'] : null;
        $riskLevel = (string)($audit['riskLevel'] ?? '');
        $assignedTo = trim((string)($audit['assigned_to'] ?? ''));

        $rawResponse = json_encode($audit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $insertAudit = $pdo->prepare(
            'INSERT INTO cl_audits (company_id, url, clean_url, score, riskLevel, headcount, turnover, summary, financial_impact, raw_response, source, analyzedAt, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertAudit->execute([
            $companyId, $url, $domain, $score, $riskLevel ?: 'modere',
            $headcount ?: null, $turnover ?: null,
            is_string($audit['summary'] ?? null) ? $audit['summary'] : null,
            is_string($audit['financialImpact'] ?? null) ? $audit['financialImpact'] : null,
            is_string($rawResponse) ? $rawResponse : null, 'scan_public', $mysqlDate, $assignedTo ?: null,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        error_log('GreenHumble: audit commercial non persisté en base (' . get_class($e) . ').');
        return false;
    } finally {
        if ($lockAcquired && $pdo instanceof PDO && $lockName !== null) {
            try {
                $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (Throwable $e) {
                error_log('GreenHumble: impossible de libérer le verrou SIREN.');
            }
        }
    }
}
