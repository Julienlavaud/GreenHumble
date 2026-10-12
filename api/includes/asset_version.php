<?php
declare(strict_types=1);

function gh_asset_version(string $relativePath): string
{
    $apiRoot = realpath(dirname(__DIR__));
    $assetPath = $apiRoot === false
        ? false
        : realpath($apiRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR));
    $assetRoot = $apiRoot === false ? false : realpath($apiRoot . DIRECTORY_SEPARATOR . 'assets');

    if ($assetPath === false || $assetRoot === false ||
        !str_starts_with($assetPath, $assetRoot . DIRECTORY_SEPARATOR) ||
        !is_file($assetPath)) {
        return '1';
    }

    $contentHash = hash_file('sha256', $assetPath);
    if ($contentHash === false) return '1';

    return $contentHash;
}
