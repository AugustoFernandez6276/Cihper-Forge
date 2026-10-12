import {
    haySesion,
    obtenerUsuario,
    limpiarSesion,
} from "../../services/sesion/sesionService.js";
import { enviarJson } from "../../services/http.js";

const LOGIN_URL = new URL("../../index.html", import.meta.url).href;

export function requiereSesion(rol) {
    const usuario = obtenerUsuario();
    if (!haySesion() || (rol && usuario.rol !== rol)) {
        limpiarSesion();
        location.href = LOGIN_URL;
        return null;
    }
    return usuario;
}

export async function cerrarSesion() {
    // La sesión real vive en la cookie HttpOnly: el backend debe borrarla.
    // Si la petición falla, igualmente se limpia la sesión local.
    try {
        await enviarJson("/auth/logout", "POST");
    } catch (error) {
        // El cierre local continúa aunque el servidor no responda.
    }
    limpiarSesion();
    location.href = LOGIN_URL;
}
