<?php
// Simple JSON storage backend for surveys and votes.

header('Content-Type: application/json; charset=utf-8');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';

function respond($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_id($id) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
}

function survey_path($baseDir, $id) {
    return $baseDir . DIRECTORY_SEPARATOR . 'survey_' . $id . '.json';
}

function load_survey($baseDir, $id) {
    $path = survey_path($baseDir, $id);
    if (!file_exists($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

function save_survey($baseDir, $survey) {
    $path = survey_path($baseDir, $survey['id']);
    $json = json_encode($survey, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($path, $json, LOCK_EX);
}

function normalize_items($items) {
    $out = [];
    foreach ($items as $item) {
        $item = trim($item);
        if ($item === '') {
            continue;
        }
        if (!in_array($item, $out, true)) {
            $out[] = $item;
        }
    }
    return $out;
}

function compute_points($items, $order) {
    $points = [];
    $n = count($items);
    for ($i = 0; $i < $n; $i++) {
        $points[$order[$i]] = $n - $i;
    }
    return $points;
}

function compute_results($survey) {
    $items = $survey['items'];
    $totals = [];
    foreach ($items as $item) {
        $totals[$item] = 0;
    }

    $participants = [];
    foreach ($survey['votes'] as $name => $vote) {
        $participants[] = [
            'name' => $name,
            'order' => $vote['order'],
            'points' => $vote['points']
        ];
        foreach ($vote['points'] as $item => $score) {
            if (!isset($totals[$item])) {
                $totals[$item] = 0;
            }
            $totals[$item] += $score;
        }
    }

    $count = count($survey['votes']);
    $overall = [];
    foreach ($totals as $item => $sum) {
        $avg = $count > 0 ? $sum / $count : 0;
        $overall[] = [
            'item' => $item,
            'total' => $sum,
            'average' => round($avg, 2)
        ];
    }

    usort($overall, function ($a, $b) {
        if ($a['total'] === $b['total']) {
            return $a['item'] <=> $b['item'];
        }
        return $b['total'] <=> $a['total'];
    });

    return [
        'participants' => $participants,
        'overall' => $overall,
        'participant_count' => $count
    ];
}

if (!is_dir($baseDir)) {
    respond(['ok' => false, 'error' => 'Data directory missing.'], 500);
}

if ($action === 'create') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $creator = isset($_POST['creator']) ? trim($_POST['creator']) : '';
    $itemsRaw = isset($_POST['items']) ? $_POST['items'] : '[]';
    $items = json_decode($itemsRaw, true);
    $cookieName = isset($_COOKIE['fr_name']) ? trim($_COOKIE['fr_name']) : '';

    if (!is_array($items)) {
        respond(['ok' => false, 'error' => 'Items invalid.'], 400);
    }

    $items = normalize_items($items);

    if ($title === '') {
        $title = 'Neue Umfrage';
    }

    if ($creator === '') {
        respond(['ok' => false, 'error' => 'Bitte einen Ersteller angeben.'], 400);
    }

    if ($cookieName !== '' && $cookieName !== $creator) {
        respond(['ok' => false, 'error' => 'Der Name ist in diesem Browser bereits gespeichert.'], 400);
    }

    if (count($items) < 1 || count($items) > 10) {
        respond(['ok' => false, 'error' => 'Bitte 1 bis 10 Begriffe angeben.'], 400);
    }

    $id = '';
    for ($i = 0; $i < 5; $i++) {
        $candidate = bin2hex(random_bytes(4));
        $path = survey_path($baseDir, $candidate);
        if (!file_exists($path)) {
            $id = $candidate;
            break;
        }
    }

    if ($id === '') {
        respond(['ok' => false, 'error' => 'Konnte keine ID erzeugen.'], 500);
    }

    $survey = [
        'id' => $id,
        'title' => $title,
        'creator' => $creator,
        'items' => $items,
        'created_at' => gmdate('c'),
        'votes' => []
    ];

    save_survey($baseDir, $survey);

    if ($cookieName === '') {
        setcookie('fr_name', $creator, [
            'expires' => time() + 31536000,
            'path' => '/',
            'samesite' => 'Lax'
        ]);
    }

    $link = 'vote.php?survey=' . $id;
    respond(['ok' => true, 'id' => $id, 'link' => $link]);
}

if ($action === 'get') {
    $id = isset($_GET['survey']) ? clean_id($_GET['survey']) : '';
    if ($id === '') {
        respond(['ok' => false, 'error' => 'Survey missing.'], 400);
    }

    $survey = load_survey($baseDir, $id);
    if ($survey === null) {
        respond(['ok' => false, 'error' => 'Umfrage nicht gefunden.'], 404);
    }

    $results = compute_results($survey);
    respond(['ok' => true, 'survey' => $survey, 'results' => $results]);
}

if ($action === 'vote') {
    $id = isset($_POST['survey']) ? clean_id($_POST['survey']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $orderRaw = isset($_POST['order']) ? $_POST['order'] : '[]';
    $order = json_decode($orderRaw, true);
    $cookieName = isset($_COOKIE['fr_name']) ? trim($_COOKIE['fr_name']) : '';

    if ($id === '' || $name === '' || !is_array($order)) {
        respond(['ok' => false, 'error' => 'Ungültige Daten.'], 400);
    }

    if ($cookieName !== '' && $cookieName !== $name) {
        respond(['ok' => false, 'error' => 'Der Name ist in diesem Browser bereits gespeichert.'], 400);
    }

    $survey = load_survey($baseDir, $id);
    if ($survey === null) {
        respond(['ok' => false, 'error' => 'Umfrage nicht gefunden.'], 404);
    }

    if ($cookieName === '' && isset($survey['votes'][$name])) {
        respond(['ok' => false, 'error' => 'Dieser Name hat bereits abgestimmt.'], 400);
    }

    $items = $survey['items'];
    if (count($order) !== count($items)) {
        respond(['ok' => false, 'error' => 'Reihenfolge passt nicht zu den Begriffen.'], 400);
    }

    $normalized = normalize_items($order);
    if (count($normalized) !== count($items)) {
        respond(['ok' => false, 'error' => 'Reihenfolge enthält ungültige Einträge.'], 400);
    }

    foreach ($items as $item) {
        if (!in_array($item, $normalized, true)) {
            respond(['ok' => false, 'error' => 'Reihenfolge enthält unbekannte Begriffe.'], 400);
        }
    }

    $points = compute_points($items, $normalized);
    $survey['votes'][$name] = [
        'order' => $normalized,
        'points' => $points,
        'updated_at' => gmdate('c')
    ];

    save_survey($baseDir, $survey);

    if ($cookieName === '') {
        setcookie('fr_name', $name, [
            'expires' => time() + 31536000,
            'path' => '/',
            'samesite' => 'Lax'
        ]);
    }

    $results = compute_results($survey);
    respond(['ok' => true, 'survey' => $survey, 'results' => $results]);
}

respond(['ok' => false, 'error' => 'Unknown action.'], 400);
