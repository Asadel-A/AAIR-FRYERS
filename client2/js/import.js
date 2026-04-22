/*
Name: Asadel Ali & Aneek
Date: March 22, 2026
Description: JavaScript for the attendance import page. 
Handles the delete confirmation modal and AJAX delete requests.

*/ 

let pendingDeleteId = null;

/**
 * Opens the delete warning modal and fills it with details about the import.
 *
 * @param {number} importId      - The import_log.id of the import to delete
 * @param {string} filename      - Original filename shown in the warning
 * @param {number} insertedCount - Number of attendance records that will be removed
 */
function confirmDelete(importId, filename, insertedCount) {
    pendingDeleteId = importId;
    document.getElementById('warnFilename').textContent = filename;
    document.getElementById('warnCounts').textContent =
        `This import added ${insertedCount} attendance records.`;
    document.getElementById('warnBackdrop').classList.add('open');
}

/**
 * Closes the delete warning modal and clears the pending ID.
 */
function closeWarn() {
    document.getElementById('warnBackdrop').classList.remove('open');
    pendingDeleteId = null;
}

document.getElementById('warnBackdrop').addEventListener('click', function (e) {
    if (e.target === this) closeWarn();
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!pendingDeleteId) return;

    const btn = this;
    btn.disabled    = true;
    btn.textContent = 'Deleting…';

    const formData = new FormData();
    formData.append('action',    'delete_import');
    formData.append('import_id', pendingDeleteId);

    fetch('import_attendance.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`import-row-${pendingDeleteId}`);
                if (row) {
                    row.style.transition = 'opacity 0.4s, transform 0.4s';
                    row.style.opacity    = '0';
                    row.style.transform  = 'translateX(20px)';
                    setTimeout(() => row.remove(), 400);
                }
                closeWarn();
                showToast(`"${data.filename}" deleted successfully.`, 'success');
            } else {
                showToast('Delete failed: ' + data.error, 'error');
                btn.disabled    = false;
                btn.textContent = 'Yes, Delete Everything';
            }
        })
        .catch(() => {
            showToast('Network error — please try again.', 'error');
            btn.disabled    = false;
            btn.textContent = 'Yes, Delete Everything';
        });
});


/**
 * Briefly shows a dismissing toast message at the bottom of the screen.
 *
 * @param {string} msg  - The message to display
 * @param {string} type - 'success' or 'error', controls the colour scheme
 */
function showToast(msg, type) {
    const isSuccess = type === 'success';

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        border-radius: 4px;
        font-size: 0.82rem;
        z-index: 2000;
        background: ${isSuccess ? '#1e3a2f' : '#3a1e1e'};
        border-left: 4px solid ${isSuccess ? '#2ecc71' : '#e74c3c'};
        color: ${isSuccess ? '#a8f0c6' : '#f0a8a8'};
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        transition: opacity 0.4s;
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);

    // auto-dismiss after 3.5 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}
