
        const themeBtn = document.getElementById('theme-toggle');
        const currentTheme = localStorage.getItem('theme');

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


// Control del menú desplegable
const texto = document.getElementById('miTexto');
const opciones = document.getElementById('misOpciones');
const contenedor = document.querySelector('.menu-container');

if (texto && opciones && contenedor) {
    texto.addEventListener('mouseenter', () => {
        opciones.style.setProperty('display', 'block', 'important');
    });

    contenedor.addEventListener('mouseleave', () => {
        opciones.style.setProperty('display', 'none', 'important');
    });
}
