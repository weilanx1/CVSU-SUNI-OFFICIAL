document.addEventListener("DOMContentLoaded", () => {
    // Kunin ang mga HTML Elements ng Modal Window Interface
    const modal = document.getElementById('statusModal');
    const closeModalBtn = document.querySelector('.modal-close-btn');
    const statusSelect = document.getElementById('statusSelect');

    // ==========================================
    // A. DIRECT BUTTON ACTION HANDLERS (Approve / Decline)
    // ==========================================
    
    // Handler para sa APPROVE button
    document.querySelectorAll('.btn-approve').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Pinipigilan nitong bumukas ang modal window box
            
            const row = btn.closest('.guest-row-item');
            const actionsCell = row.querySelector('.guest-actions-cell');
            
            // 1. Palitan ang laman ng actions cell: Alisin ang buttons at ipalit ang "Going" badge at timestamp
            actionsCell.innerHTML = `
                <span class="status-badge badge-going">Going</span>
                <span class="status-timestamp">Just now</span>
                <button class="btn-more-options"><i class="fa-solid fa-ellipsis"></i></button>
            `;
            
            // Re-attach listener para sa bagong ellipsis button na kalalagay lang
            actionsCell.querySelector('.btn-more-options').addEventListener('click', (optEvent) => {
                optEvent.stopPropagation();
                console.log("Binuksan ang more options");
            });

            console.log("Inaprubahan ang guest:", row.querySelector('.guest-name').innerText);
            
            // KUNG GUMAGAMIT KA NG PHP/AJAX, DITO MO ISASAK SAK ANG IYONG FETCH:
            // Example: updateStatusInDatabase(guestId, 'going');
        });
    });

    // Handler para sa DECLINE button
    document.querySelectorAll('.btn-decline').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Pinipigilan nitong bumukas ang modal window box
            
            const row = btn.closest('.guest-row-item');
            console.log("Tinanggihan ang guest:", row.querySelector('.guest-name').innerText);
            
            // Smooth removal animation o pwedeng itago agad ang row
            row.style.transition = "all 0.3s ease";
            row.style.opacity = "0";
            row.style.transform = "translateX(50px)";
            
            setTimeout(() => {
                row.remove();
            }, 300);

            // KUNG GUMAGAMIT KA NG PHP/AJAX, DITO MO ISASAK SAK ANG IYONG FETCH:
            // Example: updateStatusInDatabase(guestId, 'declined');
        });
    });

    // Handler para sa MORE OPTIONS (ellipsis) button na default na nandoon na
    document.querySelectorAll('.btn-more-options').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            console.log("Binuksan ang more options context menu");
        });
    });


    // ==========================================
    // B. ROW CLICK HANDLER (Para sa Modal Popup)
    // ==========================================
    document.querySelectorAll('.guest-row-item').forEach(row => {
        row.addEventListener('click', () => {
            // Dahil sa stopPropagation sa itaas, gagana lang ito pag mismong row body ang pinindot
            
            const name = row.querySelector('.guest-name').innerText;
            const email = row.querySelector('.guest-email').innerText;
            const avatarSrc = row.getAttribute('data-avatar') || row.querySelector('.guest-avatar').src;

            // Isulat ang nakuhang text nodes sa loob ng modal
            document.getElementById('modalName').innerText = name;
            document.getElementById('modalEmail').innerText = email;
            document.getElementById('modalAvatar').src = avatarSrc;

            // AUTOMATIC STATUS DETECTION SYSTEM: I-match ang current badge sa select dropdown
            if (row.querySelector('.badge-going')) {
                statusSelect.value = 'going';
            } else {
                statusSelect.value = 'pending'; 
            }

            // Ipakita ang modal frame window interface
            modal.classList.add('show-modal');
        });
    });

    // Event link handler para isara ang modal sa (X) click
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('show-modal');
        });
    }

    // Isara ang modal kapag pinindot ang labas o ang madilim na background area
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('show-modal');
        }
    });
});