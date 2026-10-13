import {
    detalleColeccion,
    actualizarColeccion,
    eliminarColeccion,
} from "../../services/colecciones/coleccionesService.js";
import {
    listarMultimedia,
    subirMultimedia,
    eliminarMultimedia,
} from "../../services/multimedia/multimediaService.js";
import { enviarBorradoKeepalive } from "../../services/http.js";
import { requiereSesion } from "../comun/sesion.js";
import { cargarVista, liberarVistas } from "../multimedia/multimedia.js";
import { actualizarAlmacenamiento, formatearAlmacenamiento } from "../comun/almacenamiento.js";
import { consultarCuota } from "../../services/fotografo/fotografoService.js";
import { configurarTags } from "./tags.js";
import { configurarQr } from "./qr.js";

const usuario = requiereSesion("fotografo");
const coleccionId = Number(new URLSearchParams(location.search).get("coleccionId"));
const galeria = document.getElementById("galeria");
const mensaje = document.getElementById("estadoVacio");
const selectorArchivos = document.getElementById("selectorArchivos");
const zonaCarga = document.getElementById("zonaCarga");
const barraAcciones = document.getElementById("barraAcciones");
const botonPublicar = document.getElementById("botonPublicar");
const menuVisibilidad = document.getElementById("menuVisibilidad");
const botonEliminar = document.getElementById("eliminarSeleccionados");
const botonSeleccionar = document.getElementById("seleccionarTodas");
const visor = document.getElementById("visor");
const contenidoVisor = document.getElementById("visorContenido");
const LIMITE_IMAGEN_BYTES = 30 * 1024 * 1024;
const LIMITE_VIDEO_BYTES = 800 * 1024 * 1024;

let archivos = [];
let seleccionados = [];
let ocupado = false;
// Borrado automático de la colección recién creada (nueva=1) que el fotógrafo
// abandona sin subir nada. Solo se arma tras cargar bien la colección vacía;
// cualquier acción real (subir, publicar, generar QR) lo desarma.
let coleccionVacia = false;
let subioArchivos = false;

function eliminarColeccionVacia(evento) {
    // persisted = la página queda en caché y puede volver (botón "atrás"):
    // en ese caso no se borra nada.
    if (evento && evento.persisted) return;
    if (!coleccionVacia || subioArchivos) return;
    coleccionVacia = false;
    // keepalive: la petición sobrevive al cierre/navegación de la pestaña.
    enviarBorradoKeepalive("/colecciones/" + coleccionId, "DELETE");
}

function indicarTrabajo(enCurso) {
    ocupado = enCurso;
    selectorArchivos.disabled = enCurso;
    zonaCarga.disabled = enCurso;
    botonPublicar.disabled = enCurso;
    botonEliminar.disabled = enCurso;
    botonSeleccionar.disabled = enCurso;
    for (const boton of menuVisibilidad.querySelectorAll("button")) boton.disabled = enCurso;
}

function mostrarVisibilidad(tipo) {
    document.getElementById("estadoVisibilidad").textContent =
        tipo === "publica" ? "Visible en colecciones públicas" : "Colección privada";
}

function actualizarSeleccion() {
    for (const tarjeta of galeria.querySelectorAll(".TarjetaMedia")) {
        const seleccionada = seleccionados.includes(Number(tarjeta.dataset.id));
        tarjeta.classList.toggle("Seleccionada", seleccionada);
        tarjeta.querySelector("input").checked = seleccionada;
    }
    botonSeleccionar.textContent =
        seleccionados.length === archivos.length
            ? "Quitar selección"
            : "Seleccionar todos los archivos";
}

function crearTarjeta(archivo) {
    const tarjeta = document.createElement("article");
    tarjeta.className = "TarjetaMedia";
    tarjeta.dataset.id = archivo.id_multimedia;
    const vista = document.createElement(archivo.tipo === "video" ? "video" : "img");
    vista.className = "VistaMiniatura";
    vista.setAttribute("aria-label", archivo.titulo || "Abrir archivo");
    vista.tabIndex = 0;
    vista.addEventListener("click", async function () {
        await abrirVisor(archivo);
    });
    vista.addEventListener("keydown", async function (evento) {
        if (evento.key === "Enter") await abrirVisor(archivo);
    });
    tarjeta.appendChild(vista);
    const selector = document.createElement("input");
    selector.type = "checkbox";
    selector.className = "SelectorArchivo";
    selector.setAttribute("aria-label", "Seleccionar " + (archivo.titulo || "archivo"));
    selector.addEventListener("change", function () {
        if (ocupado) {
            actualizarSeleccion();
            return;
        }
        const id = Number(archivo.id_multimedia);
        if (selector.checked) seleccionados.push(id);
        else
            seleccionados = seleccionados.filter(function (seleccionado) {
                return seleccionado !== id;
            });
        actualizarSeleccion();
    });
    tarjeta.appendChild(selector);
    galeria.appendChild(tarjeta);
    return vista;
}

async function cargarArchivos() {
    archivos = await listarMultimedia(coleccionId);
    liberarVistas(galeria);
    galeria.replaceChildren();
    seleccionados = [];
    mensaje.hidden = archivos.length > 0;
    mensaje.textContent = "Aún no hay archivos. Sube un JPG o MP4 para comenzar.";
    barraAcciones.classList.toggle("Visible", archivos.length > 0);
    for (const archivo of archivos) {
        const vista = crearTarjeta(archivo);
        await cargarVista(vista, archivo);
    }
    actualizarSeleccion();
}

function validarTamanoArchivo(archivo) {
    const esVideo = String(archivo.type).startsWith("video") || archivo.name.toLowerCase().endsWith(".mp4");
    const limite = esVideo ? LIMITE_VIDEO_BYTES : LIMITE_IMAGEN_BYTES;
    const limiteMB = esVideo ? 800 : 30;
    if (archivo.size > limite) {
        return archivo.name + ": supera el límite de " + limiteMB + " MB.";
    }
    return null;
}

async function subirArchivos() {
    if (ocupado || !selectorArchivos.files.length) return;
    indicarTrabajo(true);
    const errores = [];
    let espacioDisponible = 0;
    try {
        const cuota = await consultarCuota();
        espacioDisponible = Number(cuota.espacio_disponible_bytes) || 0;
    } catch (error) {
        errores.push("No se pudo consultar el espacio disponible. Se intentará subir igualmente.");
        espacioDisponible = Infinity;
    }
    const archivosValidos = [];
    for (const archivo of selectorArchivos.files) {
        const errorTamano = validarTamanoArchivo(archivo);
        if (errorTamano) {
            errores.push(errorTamano);
            continue;
        }
        if (espacioDisponible !== Infinity && archivo.size > espacioDisponible) {
            errores.push(archivo.name + ": no queda espacio disponible (" + formatearAlmacenamiento(espacioDisponible) + " libres).");
            continue;
        }
        archivosValidos.push(archivo);
    }
    for (const archivo of archivosValidos) {
        try {
            const resultado = await subirMultimedia(coleccionId, [archivo], {
                titulo: archivo.name.slice(0, 60),
            });
            subioArchivos = true;
            if (resultado.excedentes && resultado.excedentes.length > 0) {
                errores.push(archivo.name + ": no queda espacio disponible.");
            } else if (espacioDisponible !== Infinity) {
                espacioDisponible = Math.max(0, espacioDisponible - archivo.size);
            }
        } catch (error) {
            errores.push(archivo.name + ": " + error.message);
        }
    }
    try {
        await cargarArchivos();
        await actualizarAlmacenamiento();
    } catch (error) {
        errores.push("No se pudo actualizar la galería: " + error.message);
    } finally {
        selectorArchivos.value = "";
        indicarTrabajo(false);
    }
    if (errores.length) alert(errores.join("\n"));
}

async function eliminarSeleccionados() {
    if (ocupado || !seleccionados.length) return;
    if (!confirm("¿Eliminar los archivos seleccionados?")) return;
    indicarTrabajo(true);
    const errores = [];
    for (const id of seleccionados) {
        try {
            await eliminarMultimedia(id);
        } catch (error) {
            errores.push(error.message);
        }
    }
    try {
        await cargarArchivos();
        await actualizarAlmacenamiento();
    } catch (error) {
        errores.push(error.message);
    } finally {
        indicarTrabajo(false);
    }
    if (errores.length) alert(errores.join("\n"));
}

async function publicarColeccion(tipo) {
    if (ocupado) return;
    indicarTrabajo(true);
    try {
        await actualizarColeccion(coleccionId, { tipo_visibilidad: tipo });
        mostrarVisibilidad(tipo);
        menuVisibilidad.hidden = true;
        botonPublicar.setAttribute("aria-expanded", "false");
        // El fotógrafo actuó sobre la colección: ya no es un abandono.
        coleccionVacia = false;
    } catch (error) {
        alert(error.message);
    } finally {
        indicarTrabajo(false);
    }
}

function cerrarVisor() {
    visor.classList.remove("Abierto");
    liberarVistas(contenidoVisor);
    contenidoVisor.replaceChildren();
}

async function abrirVisor(archivo) {
    cerrarVisor();
    const medio = document.createElement(archivo.tipo === "video" ? "video" : "img");
    if (archivo.tipo === "video") medio.controls = true;
    else medio.alt = archivo.titulo || "Fotografía";
    contenidoVisor.appendChild(medio);
    visor.classList.add("Abierto");
    await cargarVista(medio, archivo);
}

async function iniciar() {
    indicarTrabajo(true);
    if (!usuario) return;
    if (!Number.isInteger(coleccionId) || coleccionId <= 0) {
        mensaje.textContent = "La colección no es válida. Vuelve al panel.";
        return;
    }
    const parametros = new URLSearchParams(location.search);
    const esNueva = parametros.get("nueva") === "1";
    try {
        const coleccion = await detalleColeccion(coleccionId);
        document.getElementById("nombreColeccion").textContent = coleccion.titulo;
        document.getElementById("descripcionColeccionTexto").textContent =
            coleccion.descripcion || "";
        mostrarVisibilidad(coleccion.tipo_visibilidad);
        configurarQr(coleccionId);
        await configurarTags(coleccion);
        await cargarArchivos();
        await actualizarAlmacenamiento();
        // Colección recién creada y aún vacía: se lista para borrar si se abandona.
        coleccionVacia = esNueva && archivos.length === 0;
        indicarTrabajo(false);
    } catch (error) {
        mensaje.hidden = false;
        mensaje.textContent = error.message;
    }
}

zonaCarga.addEventListener("click", function () {
    selectorArchivos.click();
});
selectorArchivos.addEventListener("change", subirArchivos);
botonEliminar.addEventListener("click", eliminarSeleccionados);
botonSeleccionar.addEventListener("click", function () {
    seleccionados =
        seleccionados.length === archivos.length
            ? []
            : archivos.map(function (archivo) {
                  return Number(archivo.id_multimedia);
              });
    actualizarSeleccion();
});
botonPublicar.addEventListener("click", function () {
    menuVisibilidad.hidden = !menuVisibilidad.hidden;
    botonPublicar.setAttribute("aria-expanded", String(!menuVisibilidad.hidden));
});
for (const boton of menuVisibilidad.querySelectorAll("button")) {
    boton.addEventListener("click", async function () {
        await publicarColeccion(boton.dataset.visibilidad);
    });
}
document.getElementById("cerrarVisor").addEventListener("click", cerrarVisor);
visor.addEventListener("click", function (evento) {
    if (evento.target === visor) cerrarVisor();
});
document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") cerrarVisor();
});
// Generar un QR implica planes para la colección: no es un abandono.
for (const idQr of ["botonQrColaborativo", "botonQrAcceso"]) {
    document.getElementById(idQr).addEventListener("click", function () {
        coleccionVacia = false;
    });
}
// Añadir un tag también es una acción deliberada sobre la colección.
document.getElementById("tagInput").addEventListener("keydown", function (evento) {
    if (evento.key === "Enter") coleccionVacia = false;
});
// Salir por el enlace del panel: se borra aquí (página aún viva) y luego se navega.
document.querySelector(".EnlaceVolver").addEventListener("click", async function (evento) {
    if (!coleccionVacia || subioArchivos) return;
    evento.preventDefault();
    // currentTarget se anula tras un await: se guarda el destino antes.
    const destino = evento.currentTarget.href;
    coleccionVacia = false;
    try {
        await eliminarColeccion(coleccionId);
    } catch (error) {
        // Si el borrado falla, la colección queda en el panel para gestionarla a mano.
    }
    location.href = destino;
});
// Cerrar pestaña o navegar fuera: keepalive para que el DELETE complete igual.
window.addEventListener("pagehide", eliminarColeccionVacia);
await iniciar();
