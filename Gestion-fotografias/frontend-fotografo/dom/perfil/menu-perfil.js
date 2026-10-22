import { obtenerUsuario } from "../../services/sesion/sesionService.js";
import { cerrarSesion } from "../comun/sesion.js";

function renderPerfil() {
    const usuario = obtenerUsuario() || {};
    const nombre = usuario.nombre_completo || "Fotógrafo";
    const inicial = nombre.charAt(0).toUpperCase();

    document.querySelectorAll(".AvatarPerfil").forEach(function (elemento) {
        elemento.textContent = inicial;
    });
}

document.querySelectorAll(".CerrarSesion").forEach(function (boton) {
    boton.addEventListener("click", cerrarSesion);
});

renderPerfil();

window.addEventListener("pageshow", function () {
    renderPerfil();
});

window.addEventListener("storage", function (evento) {
    if (evento.key === "fotografo-usuario") {
        renderPerfil();
    }
});
