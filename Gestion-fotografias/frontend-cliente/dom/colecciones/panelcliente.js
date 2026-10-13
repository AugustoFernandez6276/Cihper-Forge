import {
    listarColeccionesPublicas,
    listarHashtags,
} from "../../services/colecciones/coleccionesService.js";
import { cargarPortada, liberarVistas } from "../multimedia/multimedia.js";
import { cargarFavoritos, crearBotonFavorito } from "../favoritos/favoritos.js";
import { requiereSesion } from "../comun/sesion.js";
import { crearTarjetaColeccion } from "./tarjetas.js";
import "../comun/auth.js";

const usuario = requiereSesion("cliente");
const galeria = document.getElementById("galeriaPublica");
const estadoVacio = document.getElementById("estadoVacio");
const mensajeCarga = document.getElementById("mensajeCarga");
const mensajeError = document.getElementById("mensajeError");
const campoTag = document.getElementById("tagBusqueda");
const listaTags = document.getElementById("tagsBusqueda");
const cajaTags = document.getElementById("buscadorTags");
const zonaBuscadorTags = document.getElementById("zonaBuscadorTags");
let colecciones = [];
let tagsSeleccionados = [];

function normalizarTag(tag) {
    return tag.trim().toLowerCase().replace(/^#/, "");
}

// Cuantos de los tags seleccionados tiene la colección. Sirve para ordenar
// por relevancia; no se usa para ocultar resultados.
function puntuacionTags(coleccion) {
    const tags = (coleccion.hashtags || []).map(normalizarTag);
    return tagsSeleccionados.filter(function (tag) {
        return tags.includes(tag);
    }).length;
}

function abrirCajaTags() {
    cajaTags.hidden = false;
}

function cerrarCajaTags() {
    cajaTags.hidden = true;
}

async function mostrarColecciones() {
    liberarVistas(galeria);
    galeria.replaceChildren();
    const conArchivos = colecciones.filter(function (coleccion) {
        return (Number(coleccion.total_archivos) || 0) > 0;
    });
    const ordenadas = conArchivos.slice().sort(function (a, b) {
        return puntuacionTags(b) - puntuacionTags(a);
    });
    estadoVacio.hidden = ordenadas.length > 0;
    // Insertar todas las tarjetas de golpe: con columnas CSS, añadirlas una a
    // una (esperando cada portada) reequilibraba el mosaico y las tarjetas ya
    // colocadas cambiaban de sitio. Las portadas se cargan en paralelo al final.
    const cargasPortada = [];
    for (const coleccion of ordenadas) {
        const tarjeta = crearTarjetaColeccion(coleccion);
        tarjeta.appendChild(crearBotonFavorito(coleccion.id));
        const etiquetas = document.createElement("div");
        etiquetas.className = "ListaTagsColeccion";
        for (const tag of coleccion.hashtags || []) {
            const boton = document.createElement("button");
            boton.type = "button";
            boton.className = "ChipTag";
            boton.textContent = "#" + tag;
            boton.addEventListener("click", async function () {
                await agregarTag(tag);
            });
            etiquetas.appendChild(boton);
        }
        tarjeta.appendChild(etiquetas);
        galeria.appendChild(tarjeta);
        cargasPortada.push(
            cargarPortada(tarjeta.querySelector(".CubiertaTarjeta"), coleccion),
        );
    }
    await Promise.all(cargasPortada);
}

function mostrarTags() {
    listaTags.replaceChildren();
    for (const tag of tagsSeleccionados) {
        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "EtiquetaTag";
        boton.textContent = "#" + tag + " ×";
        boton.addEventListener("click", async function () {
            tagsSeleccionados = tagsSeleccionados.filter(function (actual) {
                return actual !== tag;
            });
            mostrarTags();
            await mostrarColecciones();
        });
        listaTags.appendChild(boton);
    }
}

async function agregarTag(valor) {
    const tag = normalizarTag(valor);
    if (!tag || tagsSeleccionados.includes(tag)) return;
    tagsSeleccionados.push(tag);
    campoTag.value = "";
    mostrarTags();
    await mostrarColecciones();
}

async function cargarSugerencias() {
    try {
        const hashtags = await listarHashtags();
        for (const hashtag of hashtags) {
            const opcion = document.createElement("option");
            opcion.value = "#" + hashtag.nombre_hashtags;
            document.getElementById("sugerenciasHashtags").appendChild(opcion);
        }
    } catch (error) {
        // El buscador también acepta etiquetas escritas a mano.
    }
}

async function iniciar() {
    try {
        colecciones = await listarColeccionesPublicas();
        await cargarFavoritos();
        const tag = new URLSearchParams(location.search).get("hashtag");
        if (tag) {
            tagsSeleccionados.push(normalizarTag(tag));
            abrirCajaTags();
        }
        mostrarTags();
        await mostrarColecciones();
        await cargarSugerencias();
    } catch (error) {
        mensajeError.textContent = error.message;
        mensajeError.hidden = false;
    } finally {
        mensajeCarga.hidden = true;
    }
}

campoTag.addEventListener("focus", abrirCajaTags);
campoTag.addEventListener("keydown", async function (evento) {
    if (evento.key === "Enter") {
        evento.preventDefault();
        await agregarTag(campoTag.value);
    }
});
zonaBuscadorTags.addEventListener("click", function (evento) {
    evento.stopPropagation();
});
document.addEventListener("click", cerrarCajaTags);
document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") cerrarCajaTags();
});
if (usuario) await iniciar();
