document.addEventListener("DOMContentLoaded", () => {
    // --- MODO OSCURO CON PERSISTENCIA ---
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

    const chatBox = document.getElementById("chatBox");
    const params = new URLSearchParams(window.location.search);
    const idArticulo = params.get("articulo");
    const idCompradorParam = params.get("comprador");
    
    const idComprador = (idCompradorParam && idCompradorParam !== 'null') ? idCompradorParam : '';

    let primerCarga = true;

    // --- 1. CARGAR MENSAJES VÍA FETCH ---
    async function cargarMensajes() {
        if (!idArticulo || !chatBox) return;

        try {
            const timestamp = Date.now();
            const res = await fetch(`obtener_mensajes.php?articulo=${idArticulo}&comprador=${idComprador}&_t=${timestamp}`);
            
            if (!res.ok) return;

            const data = await res.json();

            if (data.error) {
                console.warn("Respuesta servidor:", data.error);
                return;
            }

            renderizarMensajes(data.mensajes, data.rol_actual);
        } catch (error) {
            console.error("Error cargando mensajes por AJAX:", error);
        }
    }

    // --- 2. RENDERIZAR MENSAJES EN EL DOM ---
    function renderizarMensajes(mensajes, rolActual) {
        const estaAlFinal = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;

        chatBox.innerHTML = ""; 

        mensajes.forEach(m => {
            const esMio = (m.remitente_tipo === rolActual);
            const msgDiv = document.createElement("div");
            msgDiv.className = `msg ${esMio ? 'msg-enviado' : 'msg-recibido'}`;

            let contenidoHtml = `<div>${escaparHTML(m.contenido)}</div>`;

            // Tarjeta de Oferta
            if (parseInt(m.es_oferta) === 1) {
                const total = parseFloat(m.oferta_precio).toFixed(2);
                contenidoHtml += `
                    <div class="card-oferta">
                        <strong>Propuesta de Compra</strong><br>
                        <span>Cantidad: ${m.oferta_cantidad}</span><br>
                        <span>Total: $${total}</span><br>
                        <span>Estado: <strong>${m.oferta_estado.charAt(0).toUpperCase() + m.oferta_estado.slice(1)}</strong></span>
                `;

                if (m.oferta_estado === 'pendiente' && rolActual === 'comprador') {
                    contenidoHtml += `
                        <form class="form-ajax-oferta" method="POST" action="chat.php">
                            <input type="hidden" name="accion" value="aceptar_oferta">
                            <input type="hidden" name="id_mensaje" value="${m.id_mensaje}">
                            <input type="hidden" name="id_articulo" value="${idArticulo}">
                            <input type="hidden" name="id_comprador" value="${idComprador}">
                            <button type="submit" class="btn-aceptar">✓ Aceptar y Confirmar Compra</button>
                        </form>
                    `;
                }
                contenidoHtml += `</div>`;
            }

            // Formateo de Hora
            let hora = "";
            if (m.fecha_envio) {
                const fechaFix = m.fecha_envio.replace(/-/g, "/");
                const fechaObj = new Date(fechaFix);
                hora = isNaN(fechaObj.getTime()) 
                    ? m.fecha_envio.substring(11, 16) 
                    : fechaObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            contenidoHtml += `<small>${hora}</small>`;
            msgDiv.innerHTML = contenidoHtml;
            chatBox.appendChild(msgDiv);
        });

        if (primerCarga || estaAlFinal) {
            chatBox.scrollTop = chatBox.scrollHeight;
            primerCarga = false;
        }
    }

    // --- 3. PROCESAR FORMULARIOS ---
    async function procesarEnvioFormulario(form) {
        const formData = new FormData(form);

        try {
            const res = await fetch('chat.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.ok) {
                const inputTexto = form.querySelector('input[name="mensaje_texto"]');
                if (inputTexto) inputTexto.value = '';

                const inputCant = form.querySelector('input[name="oferta_cantidad"]');
                const inputPrecio = form.querySelector('input[name="oferta_precio"]');
                if (inputCant) inputCant.value = '';
                if (inputPrecio) inputPrecio.value = '';

                await cargarMensajes();
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        } catch (error) {
            console.error("Error enviando mensaje:", error);
        }
    }

    // Interceptor global de envíos
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const accion = form.querySelector('input[name="accion"]')?.value;

        if (accion === 'vaciar_chat') return;

        e.preventDefault();
        procesarEnvioFormulario(form);
    });

    function escaparHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    // --- 4. INICIALIZAR Y POLLING ---
    cargarMensajes();
    setInterval(cargarMensajes, 2000);
});