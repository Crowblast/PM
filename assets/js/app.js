// assets/js/app.js

const App = {
    init: () => {
        App.loadTheme();
        App.setupListeners();
    },

    loadTheme: () => {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    },

    toggleTheme: () => {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    },

    setupListeners: () => {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', App.toggleTheme);
        }

        // Mobile Sidebar
        const menuBtn = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');

        if (menuBtn && sidebar && overlay) {
            menuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Custom Item Form
        const customForm = document.getElementById('custom-item-form');
        if (customForm) {
            customForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const desc = document.getElementById('custom-desc').value;
                const price = parseFloat(document.getElementById('custom-price').value);
                const currency = document.getElementById('custom-currency').value;

                if (desc && price > 0) {
                    POS.addCustomItem(desc, price, currency);
                    document.getElementById('custom-item-form').reset();
                    document.getElementById('custom-item-modal').style.display = 'none';
                }
            });
        }
    },

    formatCurrency: (amount, currency = 'ARS') => {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
};

document.addEventListener('DOMContentLoaded', App.init);
