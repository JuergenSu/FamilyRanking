<?php
$surveyId = '';
if (isset($_GET['survey'])) {
    $surveyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['survey']);
} elseif (isset($_GET['s'])) {
    $surveyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['s']);
}

$surveyTitle = 'Familien Ranking';
if ($surveyId !== '') {
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'survey_' . $surveyId . '.json';
    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['title']) && trim($data['title']) !== '') {
            $surveyTitle = trim($data['title']);
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($surveyTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="hero">
    <div class="hero-inner">
      <div class="brand">Familien Ranking</div>
      <h1>Ranking abgeben</h1>
      <p>Sortiere die Begriffe und speichere dein Ranking. Danach siehst du die Gesamtergebnisse.</p>
    </div>
  </header>

  <main class="page" data-survey-id="<?php echo htmlspecialchars($surveyId, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="actions">
      <a class="share-button" href="index.php">Zurueck zur Uebersicht</a>
    </div>
    <section class="card" id="survey-card" hidden>
      <div class="survey-header">
        <div>
          <div class="pill">Umfrage</div>
          <h2 id="survey-title"></h2>
          <p id="survey-meta"></p>
        </div>
        <div class="share-box">
          <div class="share-label">Link teilen</div>
          <div class="share-row">
            <div class="result-link" id="survey-link"></div>
            <button class="share-button" type="button" id="survey-share-button" data-link-target="survey-link" disabled>Kopieren</button>
          </div>
        </div>
      </div>

      <form id="vote-form">
        <label>
          Dein Name
          <input type="text" name="name" placeholder="Dein Name">
        </label>
        <div class="rank-list" id="rank-list"></div>
        <div class="hint">Reihenfolge mit den Pfeilen oder per Drag & Drop aendern. Oben bekommt die meisten Punkte.</div>
        <div class="actions">
          <button type="submit">Ranking speichern</button>
        </div>
      </form>

      <div class="results" id="results" hidden>
        <h3>Ergebnisse</h3>
        <div class="results-grid">
          <div>
            <h4>Gesamtranking</h4>
            <div id="overall-results"></div>
          </div>
          <div>
            <h4>Teilnehmer</h4>
            <div id="participant-results"></div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="app.js"></script>
</body>
</html>
