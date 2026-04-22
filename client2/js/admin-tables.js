/* 
Name: Asadel Ali
Date: March 24, 2026
Description: JavaScript for the admin tables (attendance, members, events). 
Handles toggling between read-only rows and inline edit forms, ensuring only one edit form is open at a time.
*/

/**
 * Toggles a record row between its read-only view and its inline edit form.
 * Any other currently open edit rows are closed first.
 *
 * @param {number|string} id - The record ID suffix used in the row's HTML id attribute
 *                             e.g. id=5 targets #view-5 and #edit-5
 */
function toggleEdit(id) {
    const viewRow = document.getElementById('view-' + id);
    const editRow = document.getElementById('edit-' + id);

    // close any other open edit rows before opening this one
    document.querySelectorAll('tr[id^="edit-"]').forEach(row => {
        if (row.id !== 'edit-' + id && row.style.display !== 'none') {
            row.style.display = 'none';
            const matchingView = document.getElementById(row.id.replace('edit-', 'view-'));
            if (matchingView) matchingView.style.display = '';
        }
    });

    // toggle this row
    if (editRow.style.display === 'none' || editRow.style.display === '') {
        viewRow.style.display = 'none';
        editRow.style.display = '';
    } else {
        viewRow.style.display = '';
        editRow.style.display = 'none';
    }
}
