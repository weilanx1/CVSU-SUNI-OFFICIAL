// manage-event-guest.js
// ─────────────────────────────────────────────────────────────────────────────

// ── AJAX helper ───────────────────────────────────────────────────────────────
async function postAction(payload) {
  const fd = new FormData();
  Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
  const res = await fetch('manage-events-guest.php?event_id=' + EVENT_ID, {
    method: 'POST',
    body: fd
  });
  return res.json();
}

// ── Badge label/class map ─────────────────────────────────────────────────────
const BADGE_MAP = {
  going:       ['Going',     'badge-going'],
  not_going:   ['Not Going', 'badge-not-going'],
  cancelled:   ['Cancelled', 'badge-cancelled'],
  approved:    ['Approved',  'badge-approved'],
  rejected:    ['Declined',  'badge-declined'],
  waitlisted:  ['Waitlist',  'badge-waitlist'],
  pending:     ['Pending',   'badge-pending'],
  unconfirmed: ['Approved',  'badge-approved'],
};

// ── Statuses where attendance_confirmation is meaningless ─────────────────────
// If a guest is rejected/pending/waitlisted, their attend value should never
// drive the badge or filter — the approval status takes priority.
const ATTEND_IGNORED_FOR = ['rejected', 'pending', 'waitlisted'];

// ── Derive the correct filter bucket for a row ────────────────────────────────
function deriveFilter(status, attend) {
  // Attendance only counts if the guest is actually approved
  if (!ATTEND_IGNORED_FOR.includes(status)) {
    if (attend === 'going')     return 'going';
    if (attend === 'not_going') return 'not_going';
    if (attend === 'cancelled') return 'cancelled';
  }
  if (status === 'approved')   return 'approved';
  if (status === 'rejected')   return 'rejected';
  if (status === 'waitlisted') return 'waitlisted';
  return 'pending';
}

// ── Derive the correct badge for a row ────────────────────────────────────────
function deriveBadge(status, attend) {
  // Same rule: attendance badge only applies to approved guests
  if (!ATTEND_IGNORED_FOR.includes(status) && attend && attend !== 'unconfirmed') {
    return BADGE_MAP[attend] || BADGE_MAP.pending;
  }
  return BADGE_MAP[status] || BADGE_MAP.pending;
}

// ── Sanitise attend value before saving / after status change ─────────────────
// If the new approval status makes attendance meaningless, reset it to unconfirmed.
// This keeps the DB and the UI consistent.
function sanitiseAttend(status, attend) {
  if (ATTEND_IGNORED_FOR.includes(status)) return 'unconfirmed';
  return attend;
}

// ── Attach the row-click listener to a single row ─────────────────────────────
// Called both on page load (for PHP-rendered rows) and after rebuildCell
// (for dynamically rebuilt rows) so the listener is never lost.
function attachRowListener(row) {
  // Remove any previous listener by replacing with a clone, then re-attach
  const fresh = row.cloneNode(true);
  row.replaceWith(fresh);
  fresh.addEventListener('click', () => openStatusModal(fresh));
  // Re-attach stopPropagation on action buttons inside the rebuilt row
  fresh.querySelectorAll('.btn-approve, .btn-decline, .btn-more-options').forEach(btn => {
    btn.addEventListener('click', (e) => e.stopPropagation());
  });
  return fresh; // return so callers can update their reference
}

// ── Rebuild a guest row's actions cell after any status change ────────────────
function rebuildCell(row, status, rawAttend) {
  // Always sanitise attend so rejected/pending guests can never stay as "going"
  const attend = sanitiseAttend(status, rawAttend);

  const cell = row.querySelector('.guest-actions-cell');

  const [label, badgeClass] = deriveBadge(status, attend);

  const badge = document.createElement('span');
  badge.className   = 'status-badge ' + badgeClass;
  badge.textContent = label;

  const ts = document.createElement('span');
  ts.className   = 'status-timestamp';
  ts.textContent = 'Just now';

  const moreBtn = document.createElement('button');
  moreBtn.className = 'btn-more-options';
  moreBtn.innerHTML = '<i class="fa-solid fa-ellipsis"></i>';
  moreBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    openStatusModal(moreBtn);
  });

  cell.innerHTML = '';
  cell.appendChild(badge);
  cell.appendChild(ts);
  cell.appendChild(moreBtn);

  // Keep data attributes in sync — use the sanitised attend value
  row.dataset.status = status;
  row.dataset.attend = attend;
  row.dataset.filter = deriveFilter(status, attend);
}

// ── Approve ───────────────────────────────────────────────────────────────────
function handleApprove(btn) {
  btn.disabled = true;
  const row   = btn.closest('.guest-row-item');
  const regId = row.dataset.regId;

  postAction({ ajax_action: 'approve', registration_id: regId })
    .then(r => {
      if (!r.ok) { alert('Error: ' + (r.msg || 'Unknown error')); btn.disabled = false; return; }
      rebuildCell(row, 'approved', 'unconfirmed');
      updateGlanceCounts();
      applyCurrentFilter();
    })
    .catch(() => { alert('Network error. Please try again.'); btn.disabled = false; });
}

// ── Decline ───────────────────────────────────────────────────────────────────
function handleDecline(btn) {
  btn.disabled = true;
  const row   = btn.closest('.guest-row-item');
  const regId = row.dataset.regId;

  postAction({ ajax_action: 'decline', registration_id: regId })
    .then(r => {
      if (!r.ok) { alert('Error: ' + (r.msg || 'Unknown error')); btn.disabled = false; return; }
      // Pass 'unconfirmed' — the PHP decline handler now also resets attendance
      rebuildCell(row, 'rejected', 'unconfirmed');
      updateGlanceCounts();
      applyCurrentFilter();
    })
    .catch(() => { alert('Network error. Please try again.'); btn.disabled = false; });
}

// ── Status modal — open ───────────────────────────────────────────────────────
function openStatusModal(el) {
  const row = el.classList.contains('guest-row-item')
    ? el
    : el.closest('.guest-row-item');

  if (!row) return;

  const status = row.dataset.status || 'pending';
  const attend = row.dataset.attend || 'unconfirmed';

  document.getElementById('modalRegId').value       = row.dataset.regId;
  document.getElementById('modalAvatar').src        = row.dataset.avatar   || 'images/person3.png';
  document.getElementById('modalName').textContent  = row.dataset.fullname || '—';
  document.getElementById('modalEmail').textContent = row.querySelector('.guest-email').textContent;
  document.getElementById('statusSelect').value     = status;
  // Only show attendance value if it's meaningful for this status
  document.getElementById('attendSelect').value     = ATTEND_IGNORED_FOR.includes(status)
    ? 'unconfirmed'
    : attend;

  document.getElementById('statusModal').classList.add('show-modal');
}

// ── Status modal — close ──────────────────────────────────────────────────────
function closeStatusModal() {
  document.getElementById('statusModal').classList.remove('show-modal');
}

// ── Sync attendSelect when statusSelect changes inside modal ──────────────────
// If the admin switches approval to rejected/pending, grey out / reset attendance
function syncAttendDropdown() {
  const status      = document.getElementById('statusSelect').value;
  const attendSel   = document.getElementById('attendSelect');
  const shouldReset = ATTEND_IGNORED_FOR.includes(status);

  attendSel.disabled = shouldReset;
  if (shouldReset) attendSel.value = 'unconfirmed';
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Attach row-click listeners to all PHP-rendered rows
  document.querySelectorAll('.guest-row-item').forEach(row => {
    row.addEventListener('click', () => openStatusModal(row));
  });

  // Sync attend dropdown when status changes in modal
  document.getElementById('statusSelect').addEventListener('change', syncAttendDropdown);

  // Modal close
  document.getElementById('modalCloseBtn').addEventListener('click', closeStatusModal);
  window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('statusModal')) closeStatusModal();
  });

  // ── UPDATE STATUS button ───────────────────────────────────────────────────
  document.getElementById('updateStatusBtn').addEventListener('click', () => {
    const regId     = document.getElementById('modalRegId').value;
    const newStatus = document.getElementById('statusSelect').value;
    // Always sanitise on the way out — if status makes attendance irrelevant, send unconfirmed
    const newAttend = sanitiseAttend(
      newStatus,
      document.getElementById('attendSelect').value
    );

    if (!regId) {
      alert('No guest selected. Please close the modal and try again.');
      return;
    }

    const btn = document.getElementById('updateStatusBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';

    postAction({
      ajax_action:     'update_status',
      registration_id: regId,
      new_status:      newStatus,
      new_attend:      newAttend
    })
      .then(r => {
        if (!r.ok) { alert('Error: ' + (r.msg || 'Unknown error')); return; }
        const row = document.querySelector(`.guest-row-item[data-reg-id="${regId}"]`);
        if (!row) { location.reload(); return; }
        rebuildCell(row, newStatus, newAttend);
        closeStatusModal();
        updateGlanceCounts();
        applyCurrentFilter();
      })
      .catch(() => alert('Network error. Please try again.'))
      .finally(() => {
        btn.disabled    = false;
        btn.textContent = 'Update Status';
      });
  });

  // ── Filter tabs ────────────────────────────────────────────────────────────
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilter(btn.dataset.filter);
    });
  });

  // ── Glance widget shortcuts ────────────────────────────────────────────────
  document.querySelectorAll('.glance-widget-btn[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      const f = btn.dataset.filter;
      if (f === 'checkin') return;
      const target = document.querySelector(`.filter-btn[data-filter="${f}"]`);
      if (target) target.click();
      document.querySelector('.guest-list-section').scrollIntoView({ behavior: 'smooth' });
    });
  });

  // ── Live search ────────────────────────────────────────────────────────────
  document.getElementById('guestSearchInput').addEventListener('input', () => applyCurrentFilter());

});

// ── Filter logic ──────────────────────────────────────────────────────────────
function applyFilter(filter) {
  const q = document.getElementById('guestSearchInput').value.toLowerCase().trim();
  document.querySelectorAll('.guest-row-item').forEach(row => {
    const inFilter = filter === 'all' || row.dataset.filter === filter;
    const inSearch = !q || row.dataset.name.includes(q) || row.dataset.email.includes(q);
    row.style.display = (inFilter && inSearch) ? '' : 'none';
  });
}

function applyCurrentFilter() {
  const active = document.querySelector('.filter-btn.active');
  applyFilter(active ? active.dataset.filter : 'all');
}

// ── Glance counter refresh ────────────────────────────────────────────────────
function updateGlanceCounts() {
  const rows = document.querySelectorAll('.guest-row-item');
  let approved = 0, going = 0, pending = 0;
  const total = rows.length;

  rows.forEach(r => {
    const status = r.dataset.status;
    const attend = r.dataset.attend;
    if (status === 'approved') approved++;
    if (status === 'pending')  pending++;
    // Only count "going" if the guest is approved — prevents rejected+going ghost counts
    if (status === 'approved' && attend === 'going') going++;
  });

  document.getElementById('glanceApproved').textContent = approved;
  document.getElementById('glanceRegistered').innerHTML =
    '<i class="fa-solid fa-circle status-dot-black"></i> ' + total + ' Registered';
  document.getElementById('glanceGoing').innerHTML =
    '<i class="fa-solid fa-circle status-dot-green"></i> ' + going + ' Going';
  document.getElementById('glancePending').innerHTML =
    '<i class="fa-solid fa-circle status-dot-yellow"></i> ' + pending + ' Pending';
  document.getElementById('pendingBadge').textContent = pending;

  const pct = (typeof CAP !== 'undefined' && CAP > 0)
    ? Math.min(100, Math.round((approved / CAP) * 100))
    : 0;
  document.getElementById('glanceBar').style.width = pct + '%';
}