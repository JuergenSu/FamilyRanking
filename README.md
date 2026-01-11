# Familien Ranking

Familien Ranking is a lightweight PHP web app for collecting items and ranking them together. One person creates a survey with up to 10 items, shares the link, and everyone orders the items. The app assigns points automatically and shows the overall ranking.

## Features

- Login prompt on the index page when no name cookie is present.
- Create surveys with a title, creator name, up to 10 items, and an optional toggle to show/hide participant details.
- Shareable voting link for each survey.
- Drag-and-drop and button controls for ranking items.
- Overall results plus optional participant detail view (controlled per survey).
- Creator-only delete with confirmation.
- Index page shows only surveys created by or voted on by the current user.
- Data is stored as JSON files in `data/`.

## Installation

Requirements:
- PHP (with JSON support)
- Write access to the `data` folder

Steps:
1. Change into the project directory.
2. Start a local PHP server:
   ```bash
   php -S localhost:8000 -t .
   ```
3. Open in the browser: `http://localhost:8000`

Notes:
- Surveys and votes are stored as JSON files in `data/`.
- This is a simple, private tool without a full user system or database.
