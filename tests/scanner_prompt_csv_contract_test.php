<?php
declare(strict_types=1);

function scanner_prompt_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$admin = (string)file_get_contents(__DIR__ . '/../api/admin/index.php');
$engine = (string)file_get_contents(__DIR__ . '/../api/includes/ai_engine.php');
$export = (string)file_get_contents(__DIR__ . '/../api/admin/export.php');
$promptKeys = [
    'aiKeywordInstructions',
    'aiAnalysisInstructions',
    'aiEvidenceInstructions',
    'aiRiskInstructions',
    'aiCommercialInstructions',
    'aiLegalFramework',
    'aiCorrectiveActions',
    'aiUserPrompt',
    'aiClaimsDictionary',
    'customBannedKeywords'
];

scanner_prompt_assert(
    str_contains($admin, "['missions', 'legal', 'texts', 'scanner']"),
    'the Scanner tab exposes the shared CSV import action'
);
scanner_prompt_assert(
    str_contains($admin, "'Clé métier (serviceKey)'")
        && str_contains($admin, "missions: ['ID', 'Clé métier (serviceKey)'")
        && str_contains($admin, "JSON.stringify(lines[0]) !== JSON.stringify(expectedHeaders)"),
    'CSV imports reject stale or mismatched column layouts'
);
scanner_prompt_assert(
    str_contains($admin, "if (p.length !== 2)")
        && str_contains($admin, "Chaque ligne Scanner doit contenir exactement une clé et une consigne."),
    'Scanner CSV imports reject rows with extra or missing columns'
);
scanner_prompt_assert(
    str_contains($export, "['text_key', 'title', 'content'], ';', '\"', ''")
        && str_contains($admin, "texts: ['text_key', 'title', 'content']"),
    'text exports use the same semicolon-delimited schema as imports'
);
scanner_prompt_assert(
    str_contains($admin, "String(value ?? '').replace(/\"/g, '\"\"')")
        && str_contains($admin, "else if (!quoted && (char === '\\n' || char === '\\r'))"),
    'CSV exports quote multiline cells and imports preserve quoted newlines'
);
scanner_prompt_assert(str_contains($admin, "tab === 'scanner'"), 'Scanner CSV import and export handlers are available');
scanner_prompt_assert(str_contains($admin, "syncState('scanVars', appState.scanVars)"), 'imported Scanner prompts are saved to application state');

foreach ($promptKeys as $key) {
    scanner_prompt_assert(str_contains($admin, "'{$key}'"), "Scanner CSV supports {$key}");
    scanner_prompt_assert(str_contains($engine, "'{$key}'"), "the AI engine consumes {$key}");
}

foreach ([
    'ia_prompt' => 'aiKeywordInstructions',
    'ia_scope' => 'aiAnalysisInstructions',
    'ia_evidence' => 'aiEvidenceInstructions',
    'ia_risk' => 'aiRiskInstructions',
    'ia_commercial' => 'aiCommercialInstructions',
    'ia_compliance' => 'aiLegalFramework',
    'ia_corrections' => 'aiCorrectiveActions',
    'ia_user_prompt' => 'aiUserPrompt',
    'ia_dictionary' => 'aiClaimsDictionary',
    'ia_banned' => 'customBannedKeywords'
] as $inputId => $key) {
    scanner_prompt_assert(
        str_contains($admin, "scanVars.{$key} = document.getElementById('{$inputId}').value"),
        "saving the Scanner tab persists {$key}"
    );
}

scanner_prompt_assert(str_contains($engine, '$state    = load_app_state();'), 'the AI engine reads the current saved application state for each run');
scanner_prompt_assert(
    str_contains($engine, 'CONSIGNE SPÉCIFIQUE À CET AUDIT') && str_contains($engine, '$baseUserPrompt . "\\n\\nCONSIGNE SPÉCIFIQUE À CET AUDIT :\\n" . $customPrompt'),
    'request-specific prompts supplement rather than replace the configured user prompt'
);

echo "OK: Scanner prompt CSV import/export and AI engine contract\n";
