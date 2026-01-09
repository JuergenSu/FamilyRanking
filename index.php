<?php
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$surveys = [];

if (is_dir($baseDir)) {
    $files = glob($baseDir . DIRECTORY_SEPARATOR . 'survey_*.json');
    if ($files) {
        foreach ($files as $file) {
            $raw = file_get_contents($file);
            $data = json_decode($raw, true);
            if (!is_array($data) || !isset($data['id'])) {
                continue;
            }
            $surveys[] = [
                'id' => $data['id'],
                'title' => isset($data['title']) ? $data['title'] : 'Unbenannte Umfrage',
                'created_at' => isset($data['created_at']) ? $data['created_at'] : '',
                'participant_count' => isset($data['votes']) && is_array($data['votes']) ? count($data['votes']) : 0
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
      <?php if (count($surveys) === 0) : ?>
        <div class="empty">Noch keine Umfragen vorhanden.</div>
      <?php else : ?>
        <div class="survey-list">
          <?php foreach ($surveys as $survey) : ?>
            <div class="survey-item">
              <div>
                <div class="survey-title"><?php echo htmlspecialchars($survey['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="survey-meta">
                  <?php if ($survey['created_at'] !== '') : ?>
                    Erstellt: <?php echo htmlspecialchars($survey['created_at'], ENT_QUOTES, 'UTF-8'); ?> ·
                  <?php endif; ?>
                  Teilnehmer: <?php echo (int) $survey['participant_count']; ?>
                </div>
              </div>
              <a class="share-button" href="vote.php?survey=<?php echo htmlspecialchars($survey['id'], ENT_QUOTES, 'UTF-8'); ?>">Teilnehmen</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
