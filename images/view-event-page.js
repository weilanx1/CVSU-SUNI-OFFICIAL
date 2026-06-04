// --- 1. STATE MANAGER ---
function updateTicketUI(state) {
    const container = document.getElementById('ticket-ui');
    if (!container) return;

    // Profile Template
    const profile = `
        <div class="ticket-profile">
            <img src="images/sid.png" style="width: 70px; height: 70px; border-radius: 50%;">
            <div>
                <h3 style="margin:0;">Xeed Love L. Magtira</h3>
                <p style="margin:0; font-size: 12px; color: #666;">xeedlove.magtira@cvsu.edu.ph</p>
            </div>
        </div>`;

    let bodyContent = '';

    switch(state) {
        case 'initial':
            // Halimbawa sa 'initial' case:
bodyContent = `
    ${profile}
    <div class="content-wrapper">
        <h4 style="margin: 0;">Approval required</h4>
        <p style="margin: 5px 0;">Welcome, Xeed! Please register to join the event.</p>
        <button id="openReq" style="width: 100%; margin-top: 10px;">Request to Join</button>
    </div>
`;
            break;

        case 'pending':
            bodyContent = `
                ${profile}
                <div class="content-wrapper">
                <h4 style="color: #4c9b5d; margin: 10px 0;">Pending Approval 🔄</h4>
                <p>Welcome, Xeed! We will let you know if your registration is approved.</p>
                <p style="font-size: 13px; margin-top: 10px;">
                    No longer to attend? Notify the host by 
                    <a href="#" id="cancelLink" style="color: red; text-decoration: underline;">cancelling your registration.</a>
                </p>
                </div>
            `;
            break;

        case 'cancel-confirm':
            bodyContent = `
                <div class="cancel-confirm-box" style="text-align:center;">
                    <h3 style="margin-top:0;">Cancel Registration ❌</h3>
                    <p>Click Confirm to cancel your registration. We'll let the host notified about your cancellation.</p>
                    <div class="cancel-actions" style="display: flex; gap: 10px; margin-top: 15px;">
                        <button id="doCancel" class="btn-confirm" style="flex:1; padding: 10px;">Confirm</button>
                        <button id="backToPending" class="btn-dismiss" style="flex:1; padding: 10px;">Dismiss</button>
                    </div>
                </div>
            `;
            break;

        case 'cancelled-by-user':
            bodyContent = `
                ${profile}
                <h4 style="color: #666; margin: 15px 0;">Registration Cancelled 🚫</h4>
                <p>You have successfully cancelled your registration request.</p>
                <button id="reRegister" style="width: 100%; margin-top: 10px; padding: 10px;">Register Again</button>
            `;
            break;
      case 'approved':
    bodyContent = `
        <div class="content-wrapper" style="width: 100%; display: flex; flex-direction: column; padding-top: 20px;">
            <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                <img src="images/sid.png" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover;">
                <div style="display: flex; flex-direction: column; margin-top: 10px;">
                    <h4 style="margin: 0; font-size: 18px; color: #2e7d32; display: flex; align-items: center; gap: 5px;">
                        REQUEST APPROVED <span style="font-size: 20px;">✔</span>
                    </h4>
                     <p style="margin: 5px 0; font-size: 14px; font-weight: bold;">You're on the list. See you there!</p>
                    <p style="margin: 0; font-size: 12px; color: #777;">Save this ticket. Your unique QR code is required for entry.</p>
                </div>
            </div>
            
            <div style="background: #ffff; padding: 15px; border: 1px solid #eee; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; margin-top: auto; box-shadow: 5px 5px 10px 2px rgba(0, 0, 0, 0.3); gap: 15px">
                <div style="width: 80px; flex: 1; height: 40px; display: flex; align-items: center;">
                    <img src="images/logo.png" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                
                <button id="viewTicketBtn" style="padding: 10px 20px; background: #77b800; flex: 1.5; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    VIEW TICKET
                </button>
            </div>
        </div>
    `;
    break;
        case 'declined':
            bodyContent = `
                ${profile}
                <h4 style="color: #ff4d4d; margin: 10px 0;">Request Declined ❌</h4>
                <p style="margin-bottom: 15px;">Sorry, the host declined your request. But don't worry, there are plenty more events waiting for you!</p>
                <a href="events.html" style="font-weight: bold; color: #222;">[Browse Other Events]</a>
            `;
            break;
    }

    // Pag-render ng Body content lamang
    container.innerHTML = bodyContent;
    // ... nasa loob ng updateTicketUI function, pagkatapos ng container.innerHTML = bodyContent;

    if (state === 'initial') {
        // ... existing codes ...
    } else if (state === 'approved') {
        // DITO: Ito ang nagbubukas ng ticketModal
        document.getElementById("viewTicketBtn").addEventListener("click", () => {
            document.getElementById("ticketModal").classList.add("open");
        });
    }
    // --- RE-ATTACH EVENT LISTENERS ---
    if (state === 'initial') {
        document.getElementById("openReq").addEventListener("click", () => document.getElementById("req").classList.add("open"));
    } else if (state === 'pending') {
        document.getElementById('cancelLink').addEventListener('click', (e) => { e.preventDefault(); updateTicketUI('cancel-confirm'); });
    } else if (state === 'cancel-confirm') {
        document.getElementById('doCancel').addEventListener('click', () => updateTicketUI('cancelled-by-user'));
        document.getElementById('backToPending').addEventListener('click', () => updateTicketUI('pending'));
    } else if (state === 'cancelled-by-user') {
        document.getElementById('reRegister').addEventListener('click', () => updateTicketUI('initial'));
    }
}

// --- 2. MODAL LOGIC ---
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("req");
    const closeBtn = document.querySelector(".modal-close");
    if (closeBtn) closeBtn.addEventListener("click", () => modal.classList.remove("open"));
    if (modal) modal.addEventListener("click", (e) => { if(e.target === modal) modal.classList.remove("open"); });
});
// --- 3. FORM SUBMISSION LOGIC ---
const regForm = document.getElementById("regForm");
if (regForm) {
    regForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = {
            firstName: document.getElementById("firstName").value,
            lastName: document.getElementById("lastName").value,
            studentId: document.getElementById("studentID").value,
            department: document.getElementById("selectedDept").textContent
        };
        fetch('process_registration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(() => {
            alert("Registration Request Sent!");
            const modal = document.getElementById("req");
            if (modal) modal.classList.remove("open");
            regForm.reset();
            updateTicketUI('pending'); 
        })
        .catch(error => console.error('Error:', error));
    });
}

// --- 4. DROPDOWN LOGIC ---
document.querySelectorAll(".custom-dropdown .dropdown-header").forEach(header => {
    header.addEventListener("click", () => {
        const parent = header.parentElement;
        document.querySelectorAll(".custom-dropdown").forEach(d => { if(d !== parent) d.classList.remove("open"); });
        parent.classList.toggle("open");
    });
});
function updateDropdown(spanId, value) {
    document.getElementById(spanId).textContent = value;
    document.querySelectorAll(".custom-dropdown").forEach(d => d.classList.remove("open"));
}
// --- 5. INITIAL LOAD ---
document.addEventListener('DOMContentLoaded', () => {
    updateTicketUI('initial');
});
// Ilagay ito sa dulo ng iyong JS file para sa mga MODAL
document.addEventListener("DOMContentLoaded", () => {
    // Para sa Registration Modal
    const reqModal = document.getElementById("req");
    const closeReq = document.querySelector("#req .modal-close");
    if (closeReq) closeReq.addEventListener("click", () => reqModal.classList.remove("open"));

    // DITO: Para sa Ticket Modal (Hydrofest)
    const ticketModal = document.getElementById("ticketModal");
    const closeTicket = document.querySelector("#ticketModal .modal-close");
    if (closeTicket) {
        closeTicket.addEventListener("click", () => ticketModal.classList.remove("open"));
    }
});