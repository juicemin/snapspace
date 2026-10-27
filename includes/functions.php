<?php

function escape(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function imageUrl(?string $path): string
{
    $fallback =
        '/snapspace/assets/images/image-placeholder.png';

    $path = trim($path ?? '');

    if ($path === '') {
        return $fallback;
    }

    if (
        str_starts_with($path, 'http://') ||
        str_starts_with($path, 'https://')
    ) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);

    return '/snapspace/' . ltrim($path, '/');
}