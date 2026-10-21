# Implementaciones futuras y observaciones pendientes

Este documento registra hallazgos, riesgos y oportunidades de mejora identificados durante la revisión de código del frontend, que no requieren acción inmediata pero deben considerarse en futuras iteraciones.

---

## 1. Protección de colecciones privadas depende exclusivamente del backend

**Fecha de identificación:** 2026-09-14
**Requisitos relacionados:** RF5, RF6
**Severidad:** Baja (observación de arquitectura)

### Contexto

- **RF5** establece que las colecciones privadas deben conservar su privacidad de forma permanente, sin cambiar su estado por el tiempo ni por la cantidad de usuarios vinculados.
- **RF6** establece que el sistema debe bloquear cualquier intento de acceso directo mediante URL a colecciones privadas por parte de usuarios no autorizados.

### Observación

El frontend gestiona el acceso a colecciones privadas a través de un sistema de invitaciones (tokens). Sin embargo, **no implementa ninguna barrera de protección adicional** más allá de no mostrar las colecciones privadas en la lista pública de colecciones.

La protección real contra acceso directo por URL depende enteramente del backend: cada endpoint que sirve contenido de una colección privada debe verificar que el usuario solicitante tenga una invitación válida o sea el propietario de la colección.

### Riesgo

Si el backend tiene un bug de autorización (por ejemplo, un endpoint que no verifica permisos antes de devolver contenido), el frontend no tiene protección compensatoria. Un usuario podría acceder a contenido privado manipulando la URL directamente.

### Recomendación para futuras iteraciones

Considerar agregar validación client-side como capa defensiva adicional:
1. Al cargar una colección, verificar la respuesta del backend para detectar errores de autorización (403) y mostrar un mensaje claro.
2. En la página de visualización de colección, si la colección es privada y el usuario no tiene invitación válida, redirigir antes de cargar el contenido.
3. Implementar una página de "acceso denegado" consistente en lugar de depender exclusivamente de los mensajes de error del backend.

Estas son mejoras de UX y defensa en profundidad, no reemplazan la validación server-side que es la protección principal.

### Archivos relevantes

- `frontend-cliente/dom/colecciones/tuscoleccionescliente.js` (flujo de invitaciones)
- `frontend-cliente/services/invitaciones/invitacionesService.js` (validación y canje del token)
- `frontend-cliente/dom/colecciones/coleccion.js` (visualización de colecciones)
- `frontend-cliente/dom/multimedia/multimedia.js` (carga de contenido con sesión)

---

## 2. Validación de formato de archivo por magic bytes en el frontend

**Fecha de identificación:** 2026-09-14
**Requisitos relacionados:** RF7, RF25
**Severidad:** Baja (mejora de UX)

### Contexto

- **RF7** establece que el sistema debe permitir subir imágenes en formato JPG y videos MP4.
- **RF25** establece que los videos subidos deben ser clips o recortes con un límite máximo por video original.

### Observación

El frontend del fotógrafo (`frontend-fotografo/dom/colecciones/subirimagenes.js`) valida el **tamaño** de cada archivo (`validarTamanoArchivo`), pero **no verifica el contenido real** antes de enviarlo al backend. El `input` de subida solo restringe por extensión (`.jpg,.jpeg,.mp4`) y el navegador reporta el MIME a partir de esa extensión, por lo que no hay una comprobación de los **magic bytes** (los primeros bytes de un archivo) que confirme que el contenido real es JPEG o MP4.

**¿Qué son los magic bytes?** Son los primeros bytes de un archivo que identifican su formato real. Por ejemplo:
- Todo archivo JPEG real empieza con los bytes `FF D8 FF`
- Todo archivo MP4 real contiene la cadena `ftyp` en sus primeros 8 bytes

En consecuencia, el archivo se envía al backend sin comprobar su contenido real más allá del `accept` del input y del MIME reportado por el navegador.

### Ejemplo del problema

Un usuario podría renombrar un archivo `.png` o `.webp` a `.jpg`. El frontend lo aceptaría y lo enviaría al backend. Si el backend rechaza el archivo, el usuario recibiría un error sin entender por qué. Si el backend lo acepta, podría haber problemas de procesamiento (marca de agua, vista previa, etc.).

### Recomendación para futuras iteraciones

Agregar validación de magic bytes en el frontend antes de subir archivos. Como referencia sigue existiendo la validación de MIME que el backend sí aplica (`backend/src/validators/MultimediaValidator.php`). Esto mejoraría la experiencia de usuario al detectar archivos con extensión incorrecta antes de iniciar la subida.

### Archivos relevantes

- `frontend-fotografo/dom/colecciones/subirimagenes.js` (validación actual: solo tamaño, no magic bytes)
- `backend/src/validators/MultimediaValidator.php` (validación real de MIME en el servidor)

---

*Este documento se actualizará a medida que se identifiquen nuevas observaciones pendientes.

## 3. Mailpit solo para entorno de desarrollo

**Fecha de identificación:** 2026-10-21
**Referencia:** `Gestion-fotografias/docs/infraestructura.md` §5.6, §6 B6

### Contexto

El servicio Mailpit (`axllent/mailpit:latest`) está configurado y funciona exclusivamente en el entorno local de desarrollo (`docker-compose.yml`). Cumple el requisito RF18 de envío de códigos de verificación por correo durante el desarrollo y pruebas del proyecto UTU.

### Estado actual

- **Local (desarrollo):** Mailpit está activo en puertos `8025` (UI) y `1025` (SMTP). Permite capturar y revisar los correos de verificación enviados por la aplicación durante el desarrollo.
- **Producción:** No está implementado. Para un entorno de producción se requiere contratar un servicio de correo externo (SendGrid, Mailgun, Amazon SES, etc.) o integrar una API de envío de correos. El uso continuado de Mailpit en producción no está recomendado ni soportado.

### Recomendación

- Mantener Mailpit como parte del stack de desarrollo local (`docker-compose.yml`).
- Para despliegues reales, configurar credenciales y endpoints SMTP apropiados a través de variables de entorno (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`) y proveedor de servicio externo.
- Documentar en la guía de despliegue la transición de Mailpit a un servicio de producción.

### Archivos relevantes

- `Gestion-fotografias/backend/docker-compose.yml` (configuración actual de Mailpit)
- `Gestion-fotografias/docs/infraestructura.md` (notas de producción)
- `Gestion-fotografias/backend/.env` (plantilla de variables de entorno)*
