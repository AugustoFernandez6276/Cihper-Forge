import { requiereSesion } from "../comun/sesion.js";
import { actualizarPerfil } from "../../services/fotografo/fotografoService.js";
import { guardarUsuario } from "../../services/sesion/sesionService.js";

const usuarioGuardado = requiereSesion("fotografo");
const mensaje = document.getElementById("mensajePerfil");
const campoNombre = document.getElementById("nombreCompleto");
const campoTelefono = document.getElementById("telefono");

function mostrarDatos() {
    campoNombre.value = (usuarioGuardado.nombre_completo || "").replace(
        /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g,
        ""
    );
    campoTelefono.value = (usuarioGuardado.telefono || "").replace(/[^0-9]/g, "");
}

campoNombre.addEventListener("input", function () {
    campoNombre.value = campoNombre.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, "");
});

campoTelefono.addEventListener("input", function () {
    campoTelefono.value = campoTelefono.value.replace(/[^0-9]/g, "");
});

function mostrarMensaje(texto, esError = false) {
    mensaje.textContent = texto;
    mensaje.className = esError ? "mensaje-perfil error" : "mensaje-perfil";
}

async function guardarCambios(datos) {
    const respuesta = await actualizarPerfil(datos);
    if (respuesta.nombre_completo !== undefined) {
        usuarioGuardado.nombre_completo = respuesta.nombre_completo;
    }
    if (respuesta.telefono !== undefined) {
        usuarioGuardado.telefono = respuesta.telefono;
    }
    guardarUsuario(usuarioGuardado);
    mostrarDatos();
    mostrarMensaje("Dato guardado correctamente.");
}

async function enviarFormulario(evento, datos) {
    evento.preventDefault();
    const boton = evento.currentTarget.querySelector("button");
    boton.disabled = true;
    mostrarMensaje("Guardando...");

    try {
        await guardarCambios(datos);
    } catch (error) {
        mostrarMensaje(error.message, true);
        if (error.status === 401) location.href = "login.html";
    } finally {
        boton.disabled = false;
    }
}

if (usuarioGuardado) {
    mostrarDatos();
    document.getElementById("formNombre").addEventListener("submit", async function (evento) {
        const datos = {
            nombre_completo: document.getElementById("nombreCompleto").value.trim(),
        };
        await enviarFormulario(evento, datos);
    });
    document.getElementById("formTelefono").addEventListener("submit", async function (evento) {
        const datos = { telefono: document.getElementById("telefono").value.trim() };
        await enviarFormulario(evento, datos);
    });
}
