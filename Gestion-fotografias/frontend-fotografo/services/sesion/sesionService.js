export function obtenerToken() {
    return localStorage.getItem("fotografo-token");
}

export function obtenerUsuario() {
    try {
        return JSON.parse(localStorage.getItem("fotografo-usuario") || "null");
    } catch (error) {
        return null;
    }
}

// El token ya no se almacena en localStorage: viaja en cookie HttpOnly
// y el navegador lo envía automáticamente (invisible a JavaScript).
export function guardarSesion(usuario) {
    delete usuario.token;
    guardarUsuario(usuario);
}

export function guardarUsuario(usuario) {
    localStorage.setItem("fotografo-usuario", JSON.stringify(usuario));
}

export function limpiarSesion() {
    localStorage.removeItem("fotografo-token");
    localStorage.removeItem("fotografo-usuario");
    localStorage.removeItem("fotografo-cuota-almacenamiento");
}

export function haySesion() {
    return Boolean(obtenerUsuario());
}
