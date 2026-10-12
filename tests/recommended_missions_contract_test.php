<?php
declare(strict_types=1);

function recommended_missions_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$analysis = (string)file_get_contents(__DIR__ . '/../api/analyse.php');
$script = (string)file_get_contents(__DIR__ . '/../api/assets/js/analyse.js');
$start = strpos($analysis, '<?php if ($currentTab === \'missio\'): ?>');
$end = strpos($analysis, '<!-- ONGLET 4 : CONTACTS', $start ?: 0);
$missionSection = $start !== false && $end !== false ? substr($analysis, $start, $end - $start) : '';

recommended_missions_assert($missionSection !== '', 'the Missions tab section is available');
recommended_missions_assert(
    strpos($missionSection, 'if ($recommended) {') !== false
        && strpos($missionSection, '$missions[] = $mission;') !== false,
    'only scan-recommended active catalog missions are rendered'
);
recommended_missions_assert(strpos($missionSection, 'Toutes (') === false, 'the full-catalogue filter is removed');
recommended_missions_assert(
    strpos($missionSection, 'Aucune mission du catalogue ne correspond') !== false
        && strpos($missionSection, 'Voici le catalogue complet') === false,
    'no recommendations produce an empty-state message instead of the full catalogue'
);
recommended_missions_assert(strpos($script, 'mission-filter-btn') === false, 'the removed filter has no remaining JavaScript handler');

echo "OK: Missions tab only displays recommended catalog offers\n";
