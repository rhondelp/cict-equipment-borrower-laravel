// Shared list and dialog behaviour for the admin and borrower screens.
//
// This replaced DataTables. What the old plugin actually earned its 90KB for,
// on tables of eight rows, was a search box — the rest was "Show 10 entries",
// pagination under a single page of data, and a sort arrow on every column
// including the ones nobody sorts by. Those are gone; the search survives, as
// the three functions below.
//
//   initListFilters()   search + filter chips + one sort control + "showing N of M"
//   initRemoveDialogs() the destructive confirm, filled from the row's data
//   initModals()        open/close, backdrop, Escape, focus restore
//
// Everything is delegated from document, so markup rendered after load (or
// swapped in by a redirect) keeps working without re-initialising.

/* ------------------------------------------------------------------ lists */

function rowMatches(row, term, chip, from, to) {
  const haystack = (row.getAttribute('data-search') || '').toLowerCase();
  const chips = (row.getAttribute('data-chip') || '').split(/\s+/);
  const matchesTerm = !term || haystack.includes(term);
  const matchesChip = !chip || chip === 'all' || chips.includes(chip);

  // ISO dates compare correctly as strings, which is the whole reason the rows
  // carry `data-date` in that format rather than something human-readable.
  const date = row.getAttribute('data-date') || '';
  const matchesFrom = !from || (date && date >= from);
  const matchesTo = !to || (date && date <= to);

  return matchesTerm && matchesChip && matchesFrom && matchesTo;
}

function applyFilter(list) {
  const term = (list.querySelector('[data-list-search]')?.value || '').trim().toLowerCase();
  const chip = list.getAttribute('data-active-chip') || 'all';
  const from = list.querySelector('[data-list-from]')?.value || '';
  const to = list.querySelector('[data-list-to]')?.value || '';
  const rows = list.querySelectorAll('[data-list-row]');

  let shown = 0;
  rows.forEach((row) => {
    const visible = rowMatches(row, term, chip, from, to);
    row.hidden = !visible;
    if (visible) shown += 1;
  });

  const empty = list.querySelector('[data-list-empty]');
  if (empty) empty.hidden = shown > 0;

  // Group headings disappear with their last visible row rather than leaving
  // a date header floating above nothing.
  list.querySelectorAll('[data-list-group]').forEach((group) => {
    const visibleInGroup = group.querySelectorAll('[data-list-row]:not([hidden])').length;
    group.hidden = visibleInGroup === 0;
  });

  const counter = list.querySelector('[data-list-count]');
  if (counter) {
    const total = parseInt(counter.getAttribute('data-total') || String(rows.length), 10);
    const noun = counter.getAttribute('data-noun') || 'rows';
    counter.textContent = shown === total
      ? `Showing all ${total} ${noun}`
      : `Showing ${shown} of ${total} ${noun}`;
  }
}

/**
 * One sort control per list, in place of a sort arrow on every column header.
 *
 * Rows carry `data-sort-<key>` attributes and the <select>'s options name the
 * key plus how to read it (`data-type="number"`, `data-dir="desc"`). Sorting
 * reorders the DOM rather than re-querying, so it composes with the filter
 * chips and the search box instead of fighting them.
 */
function applySort(list) {
  const select = list.querySelector('[data-list-sort]');
  const container = list.querySelector('[data-list-rows]');
  if (!select || !container) return;

  const option = select.selectedOptions[0];
  const key = select.value;
  if (!key) return;

  const direction = option && option.getAttribute('data-dir') === 'desc' ? -1 : 1;
  const numeric = option && option.getAttribute('data-type') === 'number';

  const rows = Array.from(container.querySelectorAll(':scope > [data-list-row]'));
  rows.sort((a, b) => {
    const left = a.getAttribute('data-sort-' + key) || '';
    const right = b.getAttribute('data-sort-' + key) || '';
    const compared = numeric
      ? (parseFloat(left) || 0) - (parseFloat(right) || 0)
      : left.localeCompare(right, undefined, { sensitivity: 'base', numeric: true });
    return compared * direction;
  });

  rows.forEach((row) => container.appendChild(row));
}

function setChip(list, value) {
  list.setAttribute('data-active-chip', value);
  list.querySelectorAll('[data-list-chip]').forEach((button) => {
    const on = button.getAttribute('data-list-chip') === value;
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
    button.classList.toggle('bg-primary-50', on);
    button.classList.toggle('border-primary-300', on);
    button.classList.toggle('text-primary-700', on);
    button.classList.toggle('bg-white', !on);
    button.classList.toggle('border-neutral-300', !on);
    button.classList.toggle('text-neutral-700', !on);
  });
  applyFilter(list);
}

// A chip control usually sits inside its list. The summary tiles above the
// table do not, so they name their list with `data-list-target`.
function listFor(control) {
  const target = control.getAttribute('data-list-target');
  return target ? document.querySelector(target) : control.closest('[data-list]');
}

export function initListFilters() {
  // A link can arrive pre-filtered: /admin/transaction?filter=overdue. This is
  // what makes a dashboard queue entry land on the rows it was counting rather
  // than on the whole screen, leaving the reader to re-find them.
  const wanted = new URLSearchParams(window.location.search).get('filter');

  document.querySelectorAll('[data-list]').forEach((list) => {
    const known = list.querySelector('[data-list-chip="' + (wanted || '') + '"]');
    if (wanted && known) {
      setChip(list, wanted);
    }
    applySort(list);
    applyFilter(list);
  });

  document.addEventListener('input', (event) => {
    const search = event.target.closest('[data-list-search]');
    if (!search) return;
    const list = search.closest('[data-list]');
    if (list) applyFilter(list);
  });

  document.addEventListener('change', (event) => {
    const sort = event.target.closest('[data-list-sort]');
    if (sort) {
      const list = sort.closest('[data-list]');
      if (list) {
        applySort(list);
        applyFilter(list);
      }
      return;
    }

    const range = event.target.closest('[data-list-from], [data-list-to]');
    if (range) {
      const list = range.closest('[data-list]');
      if (list) applyFilter(list);
    }
  });

  document.addEventListener('click', (event) => {
    const clear = event.target.closest('[data-list-range-clear]');
    if (!clear) return;
    const list = clear.closest('[data-list]');
    if (!list) return;
    list.querySelectorAll('[data-list-from], [data-list-to]').forEach((input) => { input.value = ''; });
    applyFilter(list);
  });

  document.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-list-chip]');
    if (!chip) return;
    const list = listFor(chip);
    if (!list) return;

    setChip(list, chip.getAttribute('data-list-chip'));

    // A tile fired from above the table scrolls its result into view, or the
    // filter looks like it did nothing.
    if (chip.hasAttribute('data-list-target')) {
      list.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}

/* ----------------------------------------------------------------- modals */

let lastFocused = null;

export function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return null;
  lastFocused = document.activeElement;
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  // The first real control, not the close button: a dialog that opens with
  // focus on its dismiss is a dialog that reads as an error.
  const target = modal.querySelector('[data-autofocus]') || modal.querySelector('input, select, textarea, button');
  if (target) setTimeout(() => target.focus(), 30);
  return modal;
}

export function closeModal(id) {
  const modal = typeof id === 'string' ? document.getElementById(id) : id;
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  if (lastFocused && document.contains(lastFocused)) lastFocused.focus();
}

function topMostOpenModal() {
  const open = Array.from(document.querySelectorAll('[data-modal]')).filter((m) => !m.classList.contains('hidden'));
  return open.length ? open[open.length - 1] : null;
}

export function initModals() {
  document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');
    if (opener) {
      event.preventDefault();
      openModal(opener.getAttribute('data-modal-open'));
      return;
    }

    const closer = event.target.closest('[data-modal-close]');
    if (closer) {
      event.preventDefault();
      closeModal(closer.getAttribute('data-modal-close') || closer.closest('[data-modal]'));
      return;
    }

    // Backdrop: only when the click started on the overlay itself.
    const modal = event.target.closest('[data-modal]');
    if (modal && event.target === modal) closeModal(modal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = topMostOpenModal();
    if (modal) closeModal(modal);
  });
}

/* --------------------------------------------------------- remove dialogs */

/**
 * Fills the shared remove dialog from the trigger's data attributes, so every
 * destructive confirm states the same three things: what is about to happen,
 * the numbers it happens to, and the safe thing to do instead.
 *
 * A trigger carrying data-blocked hides the hard delete entirely and prints
 * the reason in its place — the server refuses those anyway, and a button that
 * always fails is worse than no button.
 */
function fillRemoveDialog(dialog, data) {
  const setText = (selector, value) => {
    const el = dialog.querySelector(selector);
    if (el) el.textContent = value || '';
  };

  setText('[data-remove-title]', data.title);
  setText('[data-remove-body]', data.body);

  ['a', 'b', 'c'].forEach((key) => {
    const row = dialog.querySelector(`[data-fact="${key}"]`);
    if (!row) return;
    const value = data[`fact${key.toUpperCase()}`];
    row.hidden = value === undefined || value === null || value === '';
    const valueEl = row.querySelector('[data-fact-value]');
    if (valueEl) {
      valueEl.textContent = value || '';
      const alert = data[`fact${key.toUpperCase()}Alert`] === '1';
      valueEl.classList.toggle('text-danger-700', alert);
      valueEl.classList.toggle('text-neutral-900', !alert);
    }
  });

  const blocked = (data.blocked || '').trim();
  const deleteForm = dialog.querySelector('[data-remove-delete-form]');
  const blockedNote = dialog.querySelector('[data-remove-blocked]');

  if (deleteForm) {
    deleteForm.hidden = blocked !== '';
    if (data.deleteUrl) deleteForm.action = data.deleteUrl;
  }
  if (blockedNote) {
    blockedNote.hidden = blocked === '';
    blockedNote.textContent = blocked;
  }

  const safeForm = dialog.querySelector('[data-remove-safe-form]');
  if (safeForm && data.safeUrl) safeForm.action = data.safeUrl;

  if (data.safeLabel) setText('[data-remove-safe-label]', data.safeLabel);
  if (data.safeIcon) {
    const icon = dialog.querySelector('[data-remove-safe-icon]');
    if (icon) icon.className = `fas ${data.safeIcon} text-base`;
  }

  // Reason fields start clean on every open, and the submit follows the rule
  // the server enforces rather than letting an empty box reach it.
  safeForm?.querySelectorAll('textarea, input[type="text"]').forEach((field) => {
    field.value = '';
  });
  syncSafeForm(dialog);
}

/**
 * Keeps the safe action's submit disabled — with the reason stated next to it —
 * until a required reason has actually been typed.
 */
function syncSafeForm(dialog) {
  const form = dialog.querySelector('[data-remove-safe-form]');
  if (!form) return;
  const required = form.querySelector('[data-requires-reason]');
  const submit = form.querySelector('[type="submit"]');
  const hint = dialog.querySelector('[data-remove-safe-hint]');
  if (!required || !submit) return;

  const min = parseInt(required.getAttribute('minlength') || '5', 10);
  const value = required.value.trim();
  const ok = value.length >= min;

  submit.disabled = !ok;
  submit.classList.toggle('opacity-50', !ok);
  submit.classList.toggle('cursor-not-allowed', !ok);
  if (hint) {
    hint.textContent = ok
      ? hint.getAttribute('data-ready') || ''
      : hint.getAttribute('data-blocked') || `Write at least ${min} characters first`;
    hint.classList.toggle('text-danger-700', !ok);
    hint.classList.toggle('text-neutral-600', ok);
  }
}

export function initRemoveDialogs() {
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-remove-trigger]');
    if (!trigger) return;
    event.preventDefault();

    const dialog = document.getElementById(trigger.getAttribute('data-dialog') || 'remove-dialog');
    if (!dialog) return;

    fillRemoveDialog(dialog, { ...trigger.dataset });
    openModal(dialog.id);
  });

  document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-requires-reason]');
    if (!field) return;
    const dialog = field.closest('[data-modal]');
    if (dialog) syncSafeForm(dialog);
  });
}

/* ------------------------------------------------------------------- boot */

function boot() {
  initListFilters();
  initModals();
  initRemoveDialogs();
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
}

if (typeof window !== 'undefined') {
  window.appUI = { openModal, closeModal, applyListFilters: initListFilters };
}
