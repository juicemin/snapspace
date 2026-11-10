<?php

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$query = trim($_GET['q'] ?? '');

$type = trim($_GET['type'] ?? 'all');

$allowedTypes = [
    'all',
    'creator',
    'album',
    'photo'
];

if (!in_array($type, $allowedTypes, true)) {
    echo json_encode([]);
    exit;
}

if (
    $query === '' ||
    strlen($query) > 100
) {
    echo json_encode([]);
    exit;
}

$prefix = $query . '%';
$suggestions = [];

try {
    /*
     * Creator suggestions.
     */
    if ($type === 'all' || $type === 'creator') { //Creator query and foreach
    $creatorStatement = $pdo->prepare(
        'SELECT idcreator, name
         FROM creator
         WHERE name LIKE :keyword
         ORDER BY name ASC
         LIMIT 8'
    );

    $creatorStatement->execute([
        'keyword' => $prefix
    ]);

    foreach ($creatorStatement->fetchAll() as $creator) {
        $suggestions[] = [
            'label' => $creator['name'],
            'type' => 'Creator',
            'url' =>
                '/snapspace/creator-details.php?id=' .
                (int) $creator['idcreator']
        ];
    }
    }
    /*
     * Album suggestions.
     */
    if ($type === 'all' || $type === 'album') { //Album query and foreach
    $albumStatement = $pdo->prepare(
        'SELECT idalbum, title
         FROM album
         WHERE title LIKE :keyword
         ORDER BY title ASC
         LIMIT 8'
    );

    $albumStatement->execute([
        'keyword' => $prefix
    ]);

    foreach ($albumStatement->fetchAll() as $album) {
        $suggestions[] = [
            'label' => $album['title'],
            'type' => 'Album',
            'url' =>
                '/snapspace/album-details.php?id=' .
                (int) $album['idalbum']
        ];
    }
    }
    /*
     * Photo suggestions.
     */
    if ($type === 'all' || $type === 'photo') { //Photo query and foreach
    $photoStatement = $pdo->prepare(
        'SELECT idphoto, title
         FROM photo
         WHERE title LIKE :keyword
         ORDER BY title ASC
         LIMIT 8'
    );

    $photoStatement->execute([
        'keyword' => $prefix
    ]);

    foreach ($photoStatement->fetchAll() as $photo) {
        $suggestions[] = [
            'label' => $photo['title'],
            'type' => 'Photo',
            'url' =>
                '/snapspace/photo-details.php?id=' .
                (int) $photo['idphoto']
        ];
    }
    }

    echo json_encode(
        array_slice($suggestions, 0, 8),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([]);
}