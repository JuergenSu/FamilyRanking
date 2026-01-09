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
      <h1>Gemeinsam Begriffe sammeln und fair ranken.</h1>
      <p>Erstelle eine Umfrage mit bis zu 10 Begriffen, teile den Link, und sammle Rankings mit automatischer Punktewertung.</p>
    </div>
  </header>

  <main class="page">
    <section class="card" id="create-card">
      <h2>Neue Umfrage starten</h2>
      <p>Gib einen Titel und bis zu 10 Begriffe (eine Zeile pro Begriff) ein.</p>
      <form id="create-form">
        <label>
          Titel
          <input type="text" name="title" placeholder="Zum Beispiel: Lieblingssnacks">
        </label>
        <label>
          Begriffe
          <textarea name="items" rows="8" placeholder="kinderschokolade\nyogurethe\nmilchschokolade"></textarea>
        </label>
        <div class="actions">
          <button type="submit">Link erzeugen</button>
        </div>
      </form>
      <div class="result" id="create-result" hidden>
        <div class="result-title">Fertig! Diesen Link teilen:</div>
        <div class="share-row">
          <div class="result-link" id="share-link"></div>
          <button class="share-button" type="button" id="share-button" data-link-target="share-link" disabled>Kopieren</button>
        </div>
      </div>
    </section>
  </main>

  <script src="app.js"></script>
</body>
</html>
