export function obtenerToken() {
    return localStorage.getItem("cliente-token");
}

export function obtenerUsuario() {
    try {
        return JSON.parse(localStorage.getItem("cliente-usuario") || "null");
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
    localStorage.setItem("cliente-usuario", JSON.stringify(usuario));
}

export function limpiarSesion() {
    localStorage.removeItem("cliente-token");
    localStorage.removeItem("cliente-usuario");
    localStorage.removeItem("cliente-cuota-almacenamiento");
}

export function haySesion() {
    return Boolean(obtenerUsuario());
}
