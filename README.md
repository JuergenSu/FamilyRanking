# Familien Ranking

Familien Ranking ist eine kleine PHP-Webapp, mit der ihr gemeinsam Begriffe sammelt und per Ranking bewertet. Eine Person erstellt eine Umfrage mit bis zu 10 Begriffen, teilt den Link, und alle Teilnehmer sortieren die Begriffe. Das System vergibt automatisch Punkte und zeigt eine Gesamtwertung sowie die Einzelstimmen.

## Installation

Voraussetzungen:
- PHP (inkl. JSON-Unterstuetzung)
- Schreibrechte auf dem Ordner `data`

Schritte:
1. Ins Projektverzeichnis wechseln.
2. Lokalen PHP-Server starten:
   ```bash
   php -S localhost:8000 -t .
   ```
3. Im Browser oeffnen: `http://localhost:8000`

Hinweise:
- Umfragen und Stimmen werden als JSON-Dateien in `data/` gespeichert.
- Es gibt keine Datenbank oder Benutzerverwaltung; das Tool ist fuer kleine, private Runden gedacht.
