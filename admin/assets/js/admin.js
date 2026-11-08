/**
 * ShopEase Admin Dashboard JavaScript
 * Vanilla JavaScript logic for interactivity, sidebar toggle, image preview & delete modal.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.querySelector('.sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    let overlay = document.querySelector('.sidebar-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function toggleSidebar() {
        if (adminSidebar) {
            adminSidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            if (sidebarToggleBtn) {
                const isExpanded = adminSidebar.classList.contains('show');
                sidebarToggleBtn.setAttribute('aria-expanded', isExpanded);
            }
        }
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }

    overlay.addEventListener('click', function () {
        if (adminSidebar && adminSidebar.classList.contains('show')) {
            adminSidebar.classList.remove('show');
            overlay.classList.remove('show');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // 2. Profile Dropdown Toggle
    const adminProfile = document.querySelector('.admin-profile');
    if (adminProfile) {
        adminProfile.addEventListener('click', function (e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });

        document.addEventListener('click', function () {
            adminProfile.classList.remove('active');
        });
    }

    // 3. Live Image Upload Preview
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            // Find closest form-group or container
            const container = input.closest('.form-group') || input.parentElement;
            let previewBox = container.querySelector('.image-preview-container');

            if (!previewBox) {
                previewBox = document.createElement('div');
                previewBox.className = 'image-preview-container';
                container.appendChild(previewBox);
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                previewBox.innerHTML = '<img src="' + event.target.result + '" alt="Image Preview">';
            };
            reader.readAsDataURL(file);
        });
    });

    // 4. Modal Delete Confirmation
    let confirmDeleteAction = null;
    const modalHtml = `
        <div class="modal-backdrop" id="deleteModal">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="modal-icon-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title" id="modalTitle">Confirm Deletion</h3>
                <p class="modal-text">Are you sure you want to delete this record? This action cannot be undone.</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="cancelDeleteBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    function openDeleteModal(action) {
        confirmDeleteAction = action;
        if (deleteModal) {
            deleteModal.classList.add('show');
        }
    }

    function closeDeleteModal() {
        confirmDeleteAction = null;
        if (deleteModal) {
            deleteModal.classList.remove('show');
        }
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (e) {
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            const action = confirmDeleteAction;
            closeDeleteModal();
            if (action) action();
        });
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.matches('form[data-confirm-delete]')) {
            e.preventDefault();
            openDeleteModal(() => form.submit());
        }
    });

    // Keep the confirmation dialog for existing delete links.
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('a.action-delete, a[href*="delete.php"]');
        if (deleteBtn && !deleteBtn.hasAttribute('data-no-modal')) {
            e.preventDefault();
            const href = deleteBtn.getAttribute('href');
            if (href && href !== '#') {
                openDeleteModal(() => { window.location.href = href; });
            }
        }
    });
});
