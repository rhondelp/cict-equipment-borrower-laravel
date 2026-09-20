// Shared list and dialog behaviour for the admin and borrower screens.
//
// This replaced DataTables. What the old plugin actually earned its 90KB for,
// on tables of eight rows, was a search box — the rest was "Show 10 entries",
// pagination under a single page of data, and a sort arrow on every column
// including the ones nobody sorts by. Those are gone; the search survives, as
// the three functions below.
//
//   initListFilters()   search + filter chips + a live "showing N of M"
//   initRemoveDialogs() the destructive confirm, filled from the row's data
//   initModals()        open/close, backdrop, Escape, focus restore
//
// Everything is delegated from document, so markup rendered after load (or
// swapped in by a redirect) keeps working without re-initialising.

/* ------------------------------------------------------------------ lists */

function rowMatches(row, term, chip) {
  const haystack = (row.getAttribute('data-search') || '').toLowerCase();
  const chips = (row.getAttribute('data-chip') || '').split(/\s+/);
  const matchesTerm = !term || haystack.includes(term);
  const matchesChip = !chip || chip === 'all' || chips.includes(chip);
  return matchesTerm && matchesChip;
}

function applyFilter(list) {
  const term = (list.querySelector('[data-list-search]')?.value || '').trim().toLowerCase();
  const chip = list.getAttribute('data-active-chip') || 'all';
  const rows = list.querySelectorAll('[data-list-row]');

  let shown = 0;
  rows.forEach((row) => {
    const visible = rowMatches(row, term, chip);
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

export function initListFilters() {
  document.querySelectorAll('[data-list]').forEach((list) => applyFilter(list));

  document.addEventListener('input', (event) => {
    const search = event.target.closest('[data-list-search]');
    if (!search) return;
    const list = search.closest('[data-list]');
    if (list) applyFilter(list);
  });

  document.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-list-chip]');
    if (!chip) return;
    const list = chip.closest('[data-list]');
    if (list) setChip(list, chip.getAttribute('data-list-chip'));
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
