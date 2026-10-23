import { requiereSesion } from "../comun/sesion.js";
import { actualizarPerfil } from "../../services/fotografo/fotografoService.js";
import { guardarUsuario } from "../../services/sesion/sesionService.js";

const usuarioGuardado = requiereSesion("fotografo");
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

function mostrarToast(texto, tipo) {
    const contenedor = document.getElementById("toasts");
    if (!contenedor) return;
    const toast = document.createElement("div");
    toast.className = "Toast " + (tipo || "Exito");
    toast.textContent = texto;
    contenedor.appendChild(toast);
    setTimeout(function () {
        toast.classList.add("Saliendo");
        const quitar = function () {
            toast.remove();
        };
        toast.addEventListener("animationend", quitar, { once: true });
        setTimeout(quitar, 400);
    }, 3000);
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
}

async function enviarFormulario(evento, datos) {
    evento.preventDefault();
    const boton = evento.currentTarget.querySelector("button");
    if (!boton) return;
    boton.disabled = true;
    const vigilante = setTimeout(function () {
        boton.disabled = false;
        mostrarToast("El servidor tardó demasiado. Intenta de nuevo.", "Error");
    }, 15000);

    try {
        await guardarCambios(datos);
        mostrarToast("Dato guardado correctamente.", "Exito");
    } catch (error) {
        mostrarToast(error.message, "Error");
        if (error.status === 401) location.href = "login.html";
    } finally {
        clearTimeout(vigilante);
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
