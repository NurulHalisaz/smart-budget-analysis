// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Element selections
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    // Sidebar Toggle Logic for Mobile Devices
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
        });
    }

    // Auto-close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991.98) {
            // Check if click happened outside the sidebar and not on the toggle button
            if (sidebar && !sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Initialize Bootstrap tooltips globally if any exist
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // --- UI/UX Polishing ---

    // 1. Auto-dismiss Alerts after 4 seconds
    const autoDismissAlerts = document.querySelectorAll('.auto-dismiss');
    if (autoDismissAlerts.length > 0) {
        setTimeout(() => {
            autoDismissAlerts.forEach(alert => {
                // Check if alert is still in the DOM and hasn't been closed manually
                if (document.body.contains(alert)) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 4000);
    }

    // 2. Add Loading Spinner to buttons on form submit to prevent double-click
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Find the submit button(s) within this form
            const submitButtons = this.querySelectorAll('button[type="submit"], .btn-loading');
            
            // Wait a tiny fraction so HTML5 validation can trigger first
            setTimeout(() => {
                // If the form is valid, show loading state
                if (this.checkValidity()) {
                    submitButtons.forEach(btn => {
                        btn.classList.add('btn-is-loading');
                    });
                }
            }, 10);
        });
    });
});
