// view-event-page.js
// The ticket UI states (initial, pending, approved, rejected, waitlisted) are
// rendered server-side by PHP. This file only handles interactive behaviour:
// dropdowns, modal open/close, attendance updates, and cancel flow.
// Do NOT call updateTicketUI() on page load — PHP already rendered the right state.

// ── Department dropdown ───────────────────────────────────────────────────────
function toggleDeptDropdown() {
    document.getElementById('deptDropdown').classList.toggle('open');
}
function updateDropdown(code) {
    document.getElementById('selectedDept').textContent = code;
    document.getElementById('deptDropdown').classList.remove('open');
}

// ── Registration modal ────────────────────────────────────────────────────────
function openRegisterModal() {
    document.getElementById('req').classList.add('open');
}

// ── Ticket modal ──────────────────────────────────────────────────────────────
function openTicketModal()  { document.getElementById('ticketModal').classList.add('open'); }
function closeTicketModal() { document.getElementById('ticketModal').classList.remove('open'); }

// ── Cancel state (injected into ticket-state-content) ────────────────────────
function showCancelState() {
    const container = document.getElementById('ticket-state-content');
    container.innerHTML = `
        <div class="ticket-body-layout text-center">
            <div class="ticket-cancel-headline">
                Cancel Registration <i class="fa-solid fa-circle-xmark text-red-icon"></i>
            </div>
            <div class="ticket-cancel-body-msg">
                Click Confirm to cancel your registration. We'll let the host notified about your cancellation.
            </div>
            <div class="ticket-cancel-buttons-wrapper">
                <button class="btn-confirm-cancel" onclick="confirmCancellation()">Confirm</button>
                <button class="btn-dismiss-cancel" onclick="window.location.reload()">Dismiss</button>
            </div>
        </div>
    `;
}

function confirmCancellation() {
    postAction({ ajax_action: 'cancel_registration' }).then(r => {
        if (r.ok) {
            window.location.reload();
        } else {
            alert('Error: ' + (r.msg || 'Could not cancel registration.'));
        }
    });
}

// ── Attendance update (inside ticket modal) ───────────────────────────────────
function setAttendance(value) {
    postAction({ ajax_action: 'update_attendance', attendance: value }).then(r => {
        if (!r.ok) { alert('Error updating attendance.'); return; }
        const badge = document.getElementById('ticket-modal-attend-badge');
        if (badge) {
            badge.className = 'ticket-status-badge-inline ' + (value === 'going' ? 'badge-going' : 'badge-not-going');
            badge.textContent = value === 'going' ? '✔ Going' : '✖ Not Going';
        }
        const mGoing    = document.getElementById('modal-btn-going');
        const mNotGoing = document.getElementById('modal-btn-not-going');
        if (mGoing)    mGoing.classList.toggle('active', value === 'going');
        if (mNotGoing) mNotGoing.classList.toggle('active', value === 'not_going');
    });
}

// ── DOM ready ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Registration modal close
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const reqModal      = document.getElementById('req');
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', () => reqModal.classList.remove('open'));
    if (reqModal)      reqModal.addEventListener('click', (e) => { if (e.target === reqModal) reqModal.classList.remove('open'); });

    // Ticket modal close (backdrop click)
    const ticketModal = document.getElementById('ticketModal');
    if (ticketModal) ticketModal.addEventListener('click', (e) => { if (e.target === ticketModal) closeTicketModal(); });

    // Registration form submission
    const regForm = document.getElementById('regForm');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            e.preventDefault();
            reqModal.classList.remove('open');

            postAction({ ajax_action: 'register' }).then(r => {
                if (!r.ok) { alert('Error: ' + (r.msg || 'Unknown error')); return; }
                // Reload so PHP renders the correct new state (pending / approved / waitlisted)
                window.location.reload();
            }).catch(() => alert('Network error. Please try again.'));
        });
    }

});