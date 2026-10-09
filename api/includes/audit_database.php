<?php
declare(strict_types=1);

/**
 * Persiste un audit validé dans les tables consultées par le cockpit commercial.
 * La sauvegarde JSON existante reste la source de secours si MySQL est indisponible.
 */
function gh_persist_commercial_audit(array $audit, array $enterpriseData = []): bool
{
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        return false;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );

        $url = trim((string)($audit['url'] ?? ''));
        $domain = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
        $domain = preg_replace('/^www\./i', '', $domain);
        if ($url === '' || $domain === '') {
            return false;
        }

        $companyName = trim((string)($audit['companyName'] ?? ''));
        if ($companyName === '') {
            $companyName = ucfirst(explode('.', $domain)[0]);
        }
        $sector = (string)($enterpriseData['entreprise_secteur'] ?? $audit['entreprise_secteur'] ?? '');
        $headcount = (string)($enterpriseData['entreprise_salaries'] ?? $audit['entreprise_salaries'] ?? '');
        $turnover = (string)($enterpriseData['entreprise_ca'] ?? $audit['entreprise_ca'] ?? '');

        $findCompany = $pdo->prepare('SELECT id FROM cl_companies WHERE domain = ? ORDER BY id ASC LIMIT 1');
        $findCompany->execute([$domain]);
        $companyId = $findCompany->fetchColumn();

        if ($companyId === false) {
            $insertCompany = $pdo->prepare(
                'INSERT INTO cl_companies (name, domain, sector, headcount, turnover) VALUES (?, ?, ?, ?, ?)'
            );
            $insertCompany->execute([$companyName, $domain, $sector ?: null, $headcount ?: null, $turnover ?: null]);
            $companyId = (int)$pdo->lastInsertId();
        } else {
            $updateCompany = $pdo->prepare(
                'UPDATE cl_companies SET name = ?, sector = COALESCE(NULLIF(?, \'\'), sector), headcount = COALESCE(NULLIF(?, \'\'), headcount), turnover = COALESCE(NULLIF(?, \'\'), turnover) WHERE id = ?'
            );
            $updateCompany->execute([$companyName, $sector, $headcount, $turnover, (int)$companyId]);
        }

        $analyzedAt = (string)($audit['analyzedAt'] ?? date('c'));
        $timestamp = strtotime($analyzedAt);
        $mysqlDate = $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
        $score = is_numeric($audit['riskScore'] ?? null) ? (float)$audit['riskScore'] : null;
        $riskLevel = (string)($audit['riskLevel'] ?? '');
        $assignedTo = trim((string)($audit['assigned_to'] ?? ''));

        $insertAudit = $pdo->prepare(
            'INSERT INTO cl_audits (company_id, url, score, riskLevel, headcount, turnover, analyzedAt, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertAudit->execute([
            (int)$companyId,
            $url,
            $score,
            $riskLevel ?: null,
            $headcount ?: null,
            $turnover ?: null,
            $mysqlDate,
            $assignedTo ?: null,
        ]);

        return true;
    } catch (Throwable $e) {
        // Ne pas exposer la configuration SQL ni bloquer le scan public si MySQL échoue.
        error_log('GreenHumble: audit commercial non persisté en base (' . get_class($e) . ').');
        return false;
    }
}
