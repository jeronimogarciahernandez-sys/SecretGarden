document.addEventListener("DOMContentLoaded", () => {
    // 1. MODO OSCURO CON PERSISTENCIA
    const themeBtn = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme');

    if (themeBtn) {
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeBtn.textContent = '☀️ Modo Claro';
        }

        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            
            let theme = 'light';
            if (document.body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeBtn.textContent = '☀️ Modo Claro';
            } else {
                themeBtn.textContent = '🌙 Modo Oscuro';
            }
            
            localStorage.setItem('theme', theme);
        });
    }
});
