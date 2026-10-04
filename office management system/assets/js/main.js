/**
 * Office Management System - Client Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Digital Clock
    const liveClockEl = document.getElementById('live-clock');
    if (liveClockEl) {
        const updateClock = () => {
            const now = new Date();
            liveClockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 2. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !sidebarToggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 3. Modal Dialog Triggers
    const modalOpenTriggers = document.querySelectorAll('[data-open-modal]');
    modalOpenTriggers.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-open-modal');
            const targetModal = document.getElementById(targetId);
            if (targetModal) {
                targetModal.classList.add('open');
            }
        });
    });

    const modalCloseTriggers = document.querySelectorAll('[data-close-modal]');
    modalCloseTriggers.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('open');
            }
        });
    });

    // Close modal when clicking on overlay background
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('open');
            }
        });
    });

    // 4. Quick Fill Demo Credentials (for Login page)
    const fillBtns = document.querySelectorAll('.fill-demo-btn');
    fillBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = btn.getAttribute('data-email');
                passInput.value = btn.getAttribute('data-pass');
            }
        });
    });
});
