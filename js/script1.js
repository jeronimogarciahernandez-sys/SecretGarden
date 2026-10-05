const container = document.getElementById('bubbles-container');

// Configuración de la animación
const BUBBLE_COUNT = 25; // Cantidad de burbujas en pantalla

function createBubble() {
  const bubble = document.createElement('div');
  bubble.classList.add('bubble');

  // Tamaño aleatorio entre 20px y 90px
  const size = Math.random() * 70 + 20;
  bubble.style.width = `${size}px`;
  bubble.style.height = `${size}px`;

  // Posición horizontal aleatoria (0% a 100%)
  bubble.style.left = `${Math.random() * 100}%`;

  // Duración de la animación aleatoria (entre 7s y 16s)
  const duration = Math.random() * 9 + 7;
  bubble.style.animationDuration = `${duration}s`;

  // Retraso aleatorio para que no salgan todas al mismo tiempo
  const delay = Math.random() * 8;
  bubble.style.animationDelay = `${delay}s`;

  // Eliminar la burbuja cuando termina su animación y crear una nueva
  setTimeout(() => {
    bubble.remove();
    createBubble();
  }, (duration + delay) * 1000);

  container.appendChild(bubble);
}

// Inicializar las burbujas al cargar la página
for (let i = 0; i < BUBBLE_COUNT; i++) {
  createBubble();
}

fetch('../php/login.php', {
    method: 'POST', // o 'GET'
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ dato: 'valor' })
})
.then(response => response.json())
.then(data => {
    console.log('Respuesta del PHP:', data);
})
.catch(error => {
    console.error('Error:', error);
});

function viewPassword() {
    let password = document.querySelector('#password');   
    if (password.type == "password") {
        password.type = "text";
    } else {
        password.type = "password";
    } 

}