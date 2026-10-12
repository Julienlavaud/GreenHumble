<?php
/**
 * GET /api/export/structure
 * Export Souverain de la Structure / Schémas de données
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(false);

echo json_encode([
    'success' => true,
    'exported_at' => date('c'),
    'schemas' => [
        'scans' => [
            'type' => 'array',
            'items' => [
                'required' => ['id', 'url', 'type_scan', 'group_id', 'project_id', 'date_scan', 'resultat_ia', 'statut', 'utilisateur'],
                'properties' => [
                    'id' => 'string',
                    'url' => 'string',
                    'type_scan' => 'enum(diagnostic|pipeline|widget)',
                    'group_id' => 'string',
                    'project_id' => 'string|null',
                    'date_scan' => 'ISO8601 string',
                    'resultat_ia' => 'object { score: number, recommendations: string[], raw: { model: string } }',
                    'statut' => 'string',
                    'utilisateur' => 'string'
                ]
            ]
        ],
        'dossiers' => [
            'type' => 'array',
            'items' => [
                'required' => ['id', 'nom', 'domaine', 'date_creation', 'scans'],
                'properties' => [
                    'id' => 'string',
                    'nom' => 'string',
                    'domaine' => 'string',
                    'date_creation' => 'ISO8601 string',
                    'scans' => 'array of scan_id'
                ]
            ]
        ],
        'listes' => [
            'type' => 'array',
            'items' => [
                'required' => ['id', 'nom', 'description', 'items'],
                'properties' => [
                    'id' => 'string',
                    'nom' => 'string',
                    'description' => 'string',
                    'items' => 'array of scan_id'
                ]
            ]
        ],
        'scanner_groups' => [
            'type' => 'array',
            'items' => [
                'required' => ['id', 'nom', 'description'],
                'properties' => [
                    'id' => 'string',
                    'nom' => 'string',
                    'description' => 'string',
                    'is_default' => 'boolean',
                    'protected' => 'boolean'
                ]
            ]
        ],
        'user_style' => [
            'type' => 'object',
            'properties' => [
                'bgMain' => 'color string (#121212)',
                'cardBg' => 'color string (#1E1E1E)',
                'textColor' => 'color string (#E0E0E0)',
                'headingColor' => 'color string (#FFFFFF)',
                'accentColor' => 'color string (#2A7AE2)'
            ]
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
