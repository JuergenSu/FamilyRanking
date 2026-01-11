<?php
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$surveys = [];
$deleteError = '';
$deleteSuccess = false;
$currentName = isset($_COOKIE['fr_name']) ? trim($_COOKIE['fr_name']) : '';
$showSurveyList = $currentName !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    setcookie('fr_name', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'samesite' => 'Lax'
    ]);
    if (!headers_sent()) {
        header('Location: index.php');
        exit;
    }
    $currentName = '';
    $showSurveyList = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_name'])) {
    $loginName = trim($_POST['login_name']);
    if ($loginName !== '') {
        setcookie('fr_name', $loginName, [
            'expires' => time() + 31536000,
            'path' => '/',
            'samesite' => 'Lax'
        ]);
        if (!headers_sent()) {
            header('Location: index.php');
            exit;
        }
        $currentName = $loginName;
        $showSurveyList = true;
    }
}

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
            if (!headers_sent()) {
                header('Location: index.php');
                exit;
            }
            $deleteSuccess = true;
        } else {
            $deleteError = 'Umfrage konnte nicht geloescht werden.';
        }
    }
}

if ($showSurveyList && is_dir($baseDir)) {
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
            $hasVoted = isset($data['votes']) && is_array($data['votes']) && array_key_exists($currentName, $data['votes']);
            if ($creator !== $currentName && !$hasVoted) {
                continue;
            }
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
        <form method="post" class="actions" style="margin-top: 8px;">
          <button class="share-button" type="submit" name="logout" value="1">Logout</button>
        </form>
      <?php endif; ?>
      <p>Starte eine neue Abstimmung oder nimm an einer bestehenden Umfrage teil.</p>
    </div>
  </header>

  <main class="page">
    <?php if ($currentName === '') : ?>
      <section class="card">
        <h2>Login</h2>
        <p>Bitte gib deinen Namen ein, um deine Umfragen zu sehen.</p>
        <form method="post">
          <label>
            Name
            <input type="text" name="login_name" placeholder="Dein Name" required>
          </label>
          <div class="actions">
            <button type="submit">Weiter</button>
          </div>
        </form>
      </section>
    <?php endif; ?>
    <?php if ($currentName !== '') : ?>
      <section class="card">
        <h2>Neue Abstimmung erzeugen</h2>
        <p>Erstelle eine neue Umfrage mit bis zu 10 Begriffen.</p>
        <div class="actions">
          <a class="share-button" href="create.php">Neue Abstimmung erzeugen</a>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($currentName !== '') : ?>
      <section class="card">
        <h2>Vorhandene Umfragen</h2>
        <?php if ($deleteSuccess) : ?>
          <div class="result">Umfrage geloescht.</div>
        <?php endif; ?>
        <?php if ($deleteError !== '') : ?>
          <div class="empty"><?php echo htmlspecialchars($deleteError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($showSurveyList && count($surveys) === 0) : ?>
          <div class="empty">Noch keine Umfragen vorhanden.</div>
        <?php elseif ($showSurveyList) : ?>
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
    <?php endif; ?>
  </main>
</body>
</html>
