document.addEventListener("DOMContentLoaded", () => {
    const btnToggle = document.getElementById("btn-toggle-chat");
    const btnCerrar = document.getElementById("btn-cerrar-chat");
    const panelChats = document.getElementById("panel-chats");

    if (btnToggle && panelChats) {
        btnToggle.addEventListener("click", () => {
            panelChats.classList.toggle("activo");
        });
    }

    if (btnCerrar && panelChats) {
        btnCerrar.addEventListener("click", () => {
            panelChats.classList.remove("activo");
        });
    }
});