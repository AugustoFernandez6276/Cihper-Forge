import { API_URL } from "./config.js";
import { obtenerToken, limpiarSesion } from "./sesion/sesionService.js";

function cabeceras(conCuerpo) {
    const headers = {};
    if (conCuerpo) headers["Content-Type"] = "application/json";
    const token = obtenerToken();
    if (token) headers.Authorization = "Bearer " + token;
    return headers;
}

async function parsear(respuesta) {
    if (respuesta.status === 401) limpiarSesion();
    let json;
    try {
        json = await respuesta.json();
    } catch (error) {
        throw new Error("El servidor no devolvió una respuesta válida.");
    }
    if (!respuesta.ok) {
        let mensaje = json.mensaje || "No se pudo completar la solicitud.";
        for (const detalle of json.errores || []) {
            if (typeof detalle === "string") mensaje += "\n" + detalle;
            else mensaje += "\n" + (detalle.motivo || "Archivo inválido.");
        }
        const error = new Error(mensaje);
        error.status = respuesta.status;
        error.errores = json.errores || [];
        throw error;
    }
    return json.datos;
}

export async function enviarJson(ruta, metodo, cuerpo) {
    const opciones = {
        method: metodo,
        headers: cabeceras(cuerpo !== undefined),
        credentials: 'include',
    };
    if (cuerpo !== undefined) opciones.body = JSON.stringify(cuerpo);
    const respuesta = await fetch(API_URL + ruta, opciones);
    return await parsear(respuesta);
}

// Petición "fire-and-forget" para usar al abandonar la página (pagehide).
// keepalive permite que el navegador la complete aunque el documento ya no exista.
// No parsea respuesta ni lanza excepciones: si falla, no hay nada que hacer.
export function enviarBorradoKeepalive(ruta, metodo) {
    fetch(API_URL + ruta, {
        method: metodo || "DELETE",
        headers: cabeceras(false),
        credentials: 'include',
        keepalive: true,
    }).catch(function () {});
}

export async function enviarMultipart(ruta, metodo, formulario, conAuth) {
    const opciones = {
        method: metodo,
        headers: {},
        body: formulario,
        credentials: 'include',
    };
    if (conAuth) opciones.headers = cabeceras(false);
    const respuesta = await fetch(API_URL + ruta, opciones);
    return await parsear(respuesta);
}

export function adjuntarArchivos(formulario, archivos) {
    for (const archivo of archivos) formulario.append("archivos[]", archivo);
}

// Devuelve el archivo y sus cabeceras. No crea imágenes ni enlaces HTML.
export async function obtenerArchivo(ruta) {
    const respuesta = await fetch(API_URL + ruta, { headers: cabeceras(false), credentials: 'include' });
    if (!respuesta.ok) await parsear(respuesta);
    const blob = await respuesta.blob();
    return {
        blob: blob,
        disposicion: respuesta.headers.get("Content-Disposition") || "",
        tipo: respuesta.headers.get("Content-Type") || blob.type,
    };
}
