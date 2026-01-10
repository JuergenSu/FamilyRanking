<?php
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$surveys = [];
$deleteError = '';
$currentName = isset($_COOKIE['fr_name']) ? trim($_COOKIE['fr_name']) : '';

function clean_id($id) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
}

function format_date_only($value) {
    if ($value === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($value);
        return $dt->format('d.m.Y');
    } catch (Exception $e) {
        return $value;
    }
}

function load_survey_data($path) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_survey'])) {
    $deleteId = clean_id($_POST['delete_survey']);
    if ($deleteId !== '' && is_dir($baseDir)) {
        $deletePath = $baseDir . DIRECTORY_SEPARATOR . 'survey_' . $deleteId . '.json';
        $deleteData = load_survey_data($deletePath);
        $creator = is_array($deleteData) && isset($deleteData['creator']) ? trim($deleteData['creator']) : '';
        if ($deleteData && $currentName !== '' && $creator !== '' && $creator === $currentName) {
            unlink($deletePath);
            header('Location: index.php');
            exit;
        }
    }
    $deleteError = 'Umfrage konnte nicht geloescht werden.';
}

if (is_dir($baseDir)) {
    $files = glob($baseDir . DIRECTORY_SEPARATOR . 'survey_*.json');
    if ($files) {
        foreach ($files as $file) {
            $raw = file_get_contents($file);
            $data = json_decode($raw, true);
            if (!is_array($data) || !isset($data['id'])) {
                continue;
            }
            $createdAt = isset($data['created_at']) ? $data['created_at'] : '';
            $creator = isset($data['creator']) ? trim($data['creator']) : '';
            $surveys[] = [
                'id' => $data['id'],
                'title' => isset($data['title']) ? $data['title'] : 'Unbenannte Umfrage',
                'creator' => $creator,
                'created_at' => $createdAt,
                'created_date' => format_date_only($createdAt),
                'participant_count' => isset($data['votes']) && is_array($data['votes']) ? count($data['votes']) : 0,
                'can_delete' => $currentName !== '' && $creator !== '' && $creator === $currentName
            ];
        }
    }
}

usort($surveys, function ($a, $b) {
    return strcmp($b['created_at'], $a['created_at']);
});
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Familien Ranking</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="hero">
    <div class="hero-inner">
      <div class="brand">Familien Ranking</div>
      <h1>Uebersicht der Umfragen</h1>
      <?php if ($currentName !== '') : ?>
        <div class="pill">Angemeldet als <?php echo htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <p>Starte eine neue Abstimmung oder nimm an einer bestehenden Umfrage teil.</p>
    </div>
  </header>

  <main class="page">
    <section class="card">
      <h2>Neue Abstimmung erzeugen</h2>
      <p>Erstelle eine neue Umfrage mit bis zu 10 Begriffen.</p>
      <div class="actions">
        <a class="share-button" href="create.php">Neue Abstimmung erzeugen</a>
      </div>
    </section>

    <section class="card">
      <h2>Vorhandene Umfragen</h2>
      <?php if ($deleteError !== '') : ?>
        <div class="empty"><?php echo htmlspecialchars($deleteError, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if (count($surveys) === 0) : ?>
        <div class="empty">Noch keine Umfragen vorhanden.</div>
      <?php else : ?>
        <div class="survey-list">
          <?php foreach ($surveys as $survey) : ?>
            <div class="survey-item">
              <div class="survey-content">
                <div class="survey-title"><?php echo htmlspecialchars($survey['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="survey-meta">
                  <?php if ($survey['created_date'] !== '') : ?>
                    <div>Erstellt: <?php echo htmlspecialchars($survey['created_date'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php endif; ?>
                  <div>Teilnehmer: <?php echo (int) $survey['participant_count']; ?></div>
                </div>
              </div>
              <a class="share-button" href="vote.php?survey=<?php echo htmlspecialchars($survey['id'], ENT_QUOTES, 'UTF-8'); ?>">Teilnehmen</a>
              <?php if ($survey['can_delete']) : ?>
                <form class="delete-form" method="post" onsubmit="return confirm('Diese Umfrage wirklich loeschen?');">
                  <input type="hidden" name="delete_survey" value="<?php echo htmlspecialchars($survey['id'], ENT_QUOTES, 'UTF-8'); ?>">
                  <button class="delete-button" type="submit" aria-label="Umfrage loeschen">x</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
