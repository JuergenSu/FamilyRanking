const createForm = document.querySelector('#create-form');
const createResult = document.querySelector('#create-result');
const shareLink = document.querySelector('#share-link');
const shareButton = document.querySelector('#share-button');
const surveyCard = document.querySelector('#survey-card');
const surveyTitle = document.querySelector('#survey-title');
const surveyMeta = document.querySelector('#survey-meta');
const surveyLink = document.querySelector('#survey-link');
const surveyShareButton = document.querySelector('#survey-share-button');
const rankList = document.querySelector('#rank-list');
const voteForm = document.querySelector('#vote-form');
const nameInput = document.querySelector('#vote-form input[name="name"]');
const resultsBox = document.querySelector('#results');
const overallResults = document.querySelector('#overall-results');
const participantResults = document.querySelector('#participant-results');
const page = document.querySelector('.page');
const surveyId = page ? page.dataset.surveyId : '';
let existingNames = [];
let draggingRow = null;
let draggingPointerId = null;

const getCookie = (key) => {
  if (!document.cookie) {
    return '';
  }
  const parts = document.cookie.split(';').map((entry) => entry.trim());
  const match = parts.find((entry) => entry.startsWith(`${key}=`));
  if (!match) {
    return '';
  }
  return decodeURIComponent(match.slice(key.length + 1));
};

const setCookie = (key, value, days) => {
  const date = new Date();
  date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
  document.cookie = `${key}=${encodeURIComponent(value)};expires=${date.toUTCString()};path=/;SameSite=Lax`;
};

const applyStoredName = () => {
  const stored = getCookie('fr_name');
  if (stored && nameInput) {
    nameInput.value = stored;
    nameInput.readOnly = true;
    nameInput.classList.add('locked');
  }
  return stored;
};

const applyStoredVote = (storedName, participants) => {
  if (!storedName || !Array.isArray(participants)) {
    return false;
  }
  const participant = participants.find((entry) => entry.name === storedName);
  if (!participant || !Array.isArray(participant.order)) {
    return false;
  }
  renderRanking(participant.order);
  return true;
};

const getTargetLink = (button) => {
  if (!button) {
    return '';
  }
  const targetId = button.dataset.linkTarget;
  const linkEl = targetId ? document.getElementById(targetId) : null;
  return linkEl ? linkEl.textContent.trim() : '';
};

const updateShareButtonLabel = (button) => {
  if (!button) {
    return;
  }
  const link = getTargetLink(button);
  button.disabled = link.length === 0;
  if (navigator.share) {
    button.textContent = 'Teilen';
  } else {
    button.textContent = 'Kopieren';
  }
};

const handleShareClick = async (button) => {
  if (!button) {
    return;
  }
  const link = getTargetLink(button);
  if (!link) {
    return;
  }

  if (navigator.share) {
    try {
      await navigator.share({ title: 'Familien Ranking', url: link });
      return;
    } catch (err) {
      // fall through to copy
    }
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    await navigator.clipboard.writeText(link);
  } else {
    const temp = document.createElement('textarea');
    temp.value = link;
    document.body.append(temp);
    temp.select();
    document.execCommand('copy');
    temp.remove();
  }
  button.textContent = 'Kopiert';
  setTimeout(() => updateShareButtonLabel(button), 1500);
};

if (shareButton) {
  updateShareButtonLabel(shareButton);
  shareButton.addEventListener('click', () => handleShareClick(shareButton));
}

if (surveyShareButton) {
  updateShareButtonLabel(surveyShareButton);
  surveyShareButton.addEventListener('click', () => handleShareClick(surveyShareButton));
}

const buildLink = (id) => {
  const base = `${window.location.origin}${window.location.pathname}`;
  return `${base.replace(/create\.php$/, 'vote.php')}?survey=${id}`;
};

const apiRequest = async (action, data, method = 'POST') => {
  const body = new URLSearchParams({ action, ...data });
  const response = await fetch(`api.php${method === 'GET' ? `?${body.toString()}` : ''}`, {
    method,
    headers: method === 'POST' ? { 'Content-Type': 'application/x-www-form-urlencoded' } : undefined,
    body: method === 'POST' ? body.toString() : undefined
  });
  const json = await response.json();
  if (!json.ok) {
    throw new Error(json.error || 'Unbekannter Fehler');
  }
  return json;
};

const renderRanking = (items) => {
  if (!rankList) {
    return;
  }
  rankList.innerHTML = '';
  items.forEach((item, index) => {
    const row = document.createElement('div');
    row.className = 'rank-row';
    row.dataset.item = item;

    const handle = document.createElement('button');
    handle.type = 'button';
    handle.className = 'drag-handle';
    handle.setAttribute('aria-label', 'Ziehen zum Umsortieren');
    handle.textContent = '::';

    const label = document.createElement('div');
    label.className = 'rank-label';
    label.textContent = item;

    const controls = document.createElement('div');
    controls.className = 'rank-controls';

    const up = document.createElement('button');
    up.type = 'button';
    up.textContent = '▲';
    up.disabled = index === 0;
    up.addEventListener('click', () => moveItem(index, index - 1));

    const down = document.createElement('button');
    down.type = 'button';
    down.textContent = '▼';
    down.disabled = index === items.length - 1;
    down.addEventListener('click', () => moveItem(index, index + 1));

    controls.append(up, down);
    row.append(handle, label, controls);
    rankList.append(row);
  });
  setupDragAndDrop();
};

const getCurrentOrder = () => Array.from(rankList.querySelectorAll('.rank-row')).map((row) => row.dataset.item);

const moveItem = (from, to) => {
  const current = getCurrentOrder();
  const item = current.splice(from, 1)[0];
  current.splice(to, 0, item);
  renderRanking(current);
};

const setupDragAndDrop = () => {
  if (!rankList) {
    return;
  }
  if (rankList.dataset.dragSetup === 'true') {
    return;
  }
  rankList.dataset.dragSetup = 'true';

  const clearDrag = () => {
    if (draggingRow) {
      draggingRow.classList.remove('dragging');
    }
    draggingRow = null;
    draggingPointerId = null;
  };

  rankList.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('.drag-handle');
    if (!handle) {
      return;
    }
    const row = handle.closest('.rank-row');
    if (!row) {
      return;
    }
    event.preventDefault();
    draggingRow = row;
    draggingPointerId = event.pointerId;
    row.classList.add('dragging');
    row.setPointerCapture(draggingPointerId);
  });

  rankList.addEventListener('pointermove', (event) => {
    if (!draggingRow || draggingPointerId !== event.pointerId) {
      return;
    }
    event.preventDefault();
    const target = document.elementFromPoint(event.clientX, event.clientY);
    const targetRow = target ? target.closest('.rank-row') : null;
    if (targetRow && targetRow !== draggingRow && rankList.contains(targetRow)) {
      const allRows = Array.from(rankList.querySelectorAll('.rank-row'));
      const draggingIndex = allRows.indexOf(draggingRow);
      const targetIndex = allRows.indexOf(targetRow);
      if (draggingIndex < targetIndex) {
        rankList.insertBefore(draggingRow, targetRow.nextSibling);
      } else {
        rankList.insertBefore(draggingRow, targetRow);
      }
    }
  }, { passive: false });

  rankList.addEventListener('pointerup', () => {
    clearDrag();
  });

  rankList.addEventListener('pointercancel', () => {
    clearDrag();
  });
};

const renderResults = (results) => {
  if (!resultsBox) {
    return;
  }
  resultsBox.hidden = false;
  overallResults.innerHTML = '';
  participantResults.innerHTML = '';

  const overallList = document.createElement('ol');
  overallList.className = 'result-list';
  results.overall.forEach((entry, index) => {
    const li = document.createElement('li');
    li.innerHTML = `
      <div class="result-main">
        <span class="place">${index + 1}.</span>
        <strong>${entry.item}</strong>
      </div>
      <div class="result-meta">${entry.total} Punkte (Durchschnitt ${entry.average})</div>
    `;
    overallList.append(li);
  });
  overallResults.append(overallList);

  if (results.participants.length === 0) {
    participantResults.innerHTML = '<div class="empty">Noch keine Rankings gespeichert.</div>';
    return;
  }

  results.participants.forEach((participant) => {
    const block = document.createElement('div');
    block.className = 'participant-block';

    const title = document.createElement('div');
    title.className = 'participant-name';
    title.textContent = participant.name;

    const list = document.createElement('ol');
    list.className = 'participant-list';
    participant.order.forEach((item) => {
      const li = document.createElement('li');
      li.textContent = item;
      list.append(li);
    });

    block.append(title, list);
    participantResults.append(block);
  });
};

const loadSurvey = async (id) => {
  const data = await apiRequest('get', { survey: id }, 'GET');
  if (surveyCard) {
    surveyCard.hidden = false;
  }
  surveyTitle.textContent = data.survey.title;
  surveyMeta.textContent = `${data.survey.items.length} Begriffe · ${data.results.participant_count} Teilnehmer`;
  const link = buildLink(data.survey.id);
  surveyLink.textContent = link;
  updateShareButtonLabel(surveyShareButton);
  renderRanking(data.survey.items);
  renderResults(data.results);
  existingNames = data.results.participants.map((participant) => participant.name);
  const stored = applyStoredName();
  applyStoredVote(stored, data.results.participants);
};

if (createForm) {
  createForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(createForm);
    const title = formData.get('title').toString();
    const items = formData
      .get('items')
      .toString()
      .split('\n')
      .map((entry) => entry.trim())
      .filter((entry) => entry.length > 0);

    if (items.length === 0) {
      alert('Bitte mindestens einen Begriff eingeben.');
      return;
    }
    if (items.length > 10) {
      alert('Bitte maximal 10 Begriffe eingeben.');
      return;
    }

    try {
      const data = await apiRequest('create', { title, items: JSON.stringify(items) });
      const link = buildLink(data.id);
      if (createResult) {
        createResult.hidden = false;
      }
      if (shareLink) {
        shareLink.textContent = link;
      }
      updateShareButtonLabel(shareButton);
    } catch (err) {
      alert(err.message);
    }
  });
}

if (voteForm) {
  voteForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(voteForm);
    const name = formData.get('name').toString().trim();
    if (!name) {
      alert('Bitte einen Namen eingeben.');
      return;
    }

    const stored = getCookie('fr_name');
    if (stored && stored !== name) {
      alert('Der Name ist in diesem Browser bereits gespeichert.');
      return;
    }
    if (!stored && existingNames.includes(name)) {
      alert('Dieser Name hat bereits abgestimmt.');
      return;
    }

    const order = getCurrentOrder();

    try {
      const data = await apiRequest('vote', {
        survey: surveyId,
        name,
        order: JSON.stringify(order)
      });
      surveyMeta.textContent = `${data.survey.items.length} Begriffe · ${data.results.participant_count} Teilnehmer`;
      renderResults(data.results);
      existingNames = data.results.participants.map((participant) => participant.name);
      if (!stored) {
        setCookie('fr_name', name, 365);
        applyStoredName();
      }
    } catch (err) {
      alert(err.message);
    }
  });
}

if (surveyId) {
  loadSurvey(surveyId).catch((err) => {
    if (surveyCard) {
      surveyCard.hidden = true;
    }
    alert(err.message);
  });
}
