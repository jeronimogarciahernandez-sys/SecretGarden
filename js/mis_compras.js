document.addEventListener("DOMContentLoaded", () => {
    // Permitir hacer clic en toda la tarjeta del chat para redirigir directamente
    const itemsChat = document.querySelectorAll(".item-chat-comprador");

    itemsChat.forEach(item => {
        const botonChat = item.querySelector(".btn-ir-chat");

        if (botonChat) {
            item.style.cursor = "pointer";
            item.addEventListener("click", (e) => {
                if (e.target !== botonChat && !botonChat.contains(e.target)) {
                    window.location.href = botonChat.getAttribute("href");
                }
            });
        }
    });
});