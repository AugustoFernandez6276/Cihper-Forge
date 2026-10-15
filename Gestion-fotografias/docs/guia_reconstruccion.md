# Guía Paso a Paso para la Reconstrucción de la Infraestructura

> **Convenciones de esta guía:** los bloques marcados `LOCAL` describen desarrollo/testing/evaluación UTU. Los bloques marcados `PRODUCCIÓN` describen preparación para entorno real. Los bloques `COMPARTIDO` aplican a ambos. Todo valor no confirmado figura como `PENDIENTE DE DEFINICIÓN`.
>
> **Referencia técnica:** el detalle de `Dockerfile`, `docker-compose.yml`, esquema SQL y respaldos vive en `infraestructura.md`. Esta guía contiene pasos, no duplica ese detalle.
>
> **Alcance:** Parte 1 describe hechos comprobados en el repositorio. Parte 2 describe prerrequisitos y brechas verificadas; no es una guía de despliegue ejecutable hasta que se resuelvan las decisiones pendientes (ver § Parte 2.6 e `infraestructura.md §6`).

---

# Parte 1 — Reconstrucción y ejecución local [LOCAL]

Propósito original conservado: desplegar, inicializar y reconstruir desde cero el entorno completo de **Cipher_Forge** en máquina host (Windows, Linux o macOS) con fines de desarrollo o evaluación tribunalicia de UTU.

## 1. Requisitos previos [LOCAL]

1. **Docker Desktop:** versión 24.0 o superior, motor activo y soporte para Docker Compose v2.
2. **Git:** para clonar el repositorio y gestionar versiones.
3. **Servidor HTTP para el frontend:** VS Code Live Server (puerto `5501` según `.vscode/settings.json`). No usar doble clic sobre los HTML: todas las páginas usan `<script type="module">` (p. ej. `frontend-fotografo/index.html:62`, `frontend-cliente/index.html:62`) y `file://` bloquea los módulos ES.
4. **Puertos locales libres:**
   * **8080:** Apache / Backend API (`backend/docker-compose.yml:10`, `8080:80`).
   * **3306:** MySQL (`backend/docker-compose.yml:85`, `3306:3306`).
   * **8025:** interfaz web Mailpit (buzón de desarrollo).
   * **1025:** entrada SMTP usada por la app (`backend/docker-compose.yml:77-78`).
   *(Detén XAMPP, WampServer o MySQL locales para evitar conflictos).*

## 2. Puesta en marcha local [LOCAL]

### Paso 1: Obtener el código fuente

```bash
git clone https://github.com/NicolasNegretto7/Cipher_Forge.git
cd Cipher_Forge/Gestion-fotografias/backend
```

### Paso 2: Validar archivos de configuración

En `Gestion-fotografias/backend` deben existir:

* `Dockerfile` — receta del contenedor `php:8.2-apache` + FFmpeg + GD + `pdo_mysql` + `zip` (ver `infraestructura.md §2`).
* `docker-compose.yml` — orquestador de 4 servicios (ver `infraestructura.md §3`).
* `database/schema.sql` — DDL de las 11 tablas iniciales (ver `infraestructura.md §4.1`).
* `database/migration.sql` — migración idempotente aplicada en cada arranque por `docker-entrypoint.sh:33-38`.
* `php.ini` — inyectado como `99-cipher-forge.ini` (`Dockerfile:26`).
* `docker-entrypoint.sh` — crea `uploads/originals,previews,standard` y aplica la migración.

### Paso 3: Construcción e inicio de contenedores

```bash
docker compose up --build -d
```

> **¿Qué ocurre internamente? [LOCAL — lógica compartida, valores solo-local]**
> 1. Descarga `mysql:8.0`, base `php:8.2-apache` y `axllent/mailpit`.
> 2. Compila `ffmpeg`, `libpng`, `libjpeg`, `libfreetype`, `libzip` y extensiones `gd`, `pdo_mysql`, `zip` (`backend/Dockerfile:5-18`).
> 3. Crea la red puente interna.
> 4. Inicializa `cipher_forge_db` y monta `database/schema.sql` en `/docker-entrypoint-initdb.d/` (solo primera vez del volumen).
> 5. Inicializa `cipher_forge_app` (`DB_HOST=db`, `SMTP_HOST=mailpit`, `FRONTEND_URL=http://localhost:5501/Gestion-fotografias` según `docker-compose.yml:15-40`).
> 6. Inicializa `cipher_forge_worker` (`php cron-backup.php --loop`, `BACKUP_INTERVAL_SEG=86400`, `PURGE_INTERVAL_SEG=3600`).
> 7. Inicializa `cipher_forge_mail` (Mailpit) para interceptar correos de verificación (CF-02 / RF18).
> 8. En cada arranque, `docker-entrypoint.sh` espera MySQL con `mysql --skip-ssl` y aplica `database/migration.sql`.

### Paso 4: Comprobación del estado

```bash
docker compose ps
```

*Salida esperada:*

```text
NAME                    IMAGE                   COMMAND                  SERVICE   STATUS   PORTS
cipher_forge_app        cipher-forge:dev        "docker-php-entrypoi…"   app       Up       0.0.0.0:8080->80/tcp
cipher_forge_db         mysql:8.0               "docker-entrypoint.s…"   db        Up       0.0.0.0:3306->3306/tcp
cipher_forge_worker     cipher-forge:dev        "php cron-backup.php …"  worker    Up
cipher_forge_mail       axllent/mailpit:latest  "/usr/local/bin/mail…"   mailpit   Up       0.0.0.0:8025->8025/tcp, 0.0.0.0:1025->1025/tcp
```

### Paso 5: Verificación de la base de datos

```bash
docker exec -it cipher_forge_db mysql -u cipher_user -p -e "USE cipher_forge; SHOW TABLES;"
```
*(El cliente pedirá la clave por prompt; no se incluye en claro. Credencial de desarrollo en `docker-compose.yml:87-90`, no usar en producción).*

*Las 11 tablas:* `acceso_colecciones`, `backups`, `clientes`, `coleccion_hashtags`, `colecciones`, `favoritos`, `fotografos`, `hashtags`, `multimedia`, `qr_tokens`, `usuarios` (`backend/database/schema.sql:3-87`).

> **Nota de trazabilidad:** tabla `usuarios` como padre de `fotografos` y `clientes` (decisión en `01-requerimientos.md Decisiones técnicas del equipo`; ERD en `02-modelado.md §2.1`).

### Paso 6: Prueba de conectividad con la API

> **Nota:** endpoint solo técnico de diagnóstico, fuera del alcance funcional. Sin RF asociado en `01-requerimientos.md`.

```bash
curl http://localhost:8080/api/ping
```

*Respuesta esperada (HTTP 200, `src/controllers/HomeController.php:13-20` vía `src/Core/Response.php:11-18`):*

```json
{
  "ok": true,
  "mensaje": "Conexión exitosa con el backend",
  "datos": {
    "servidor": "Cipher_Forge Backend API",
    "estado": "activo",
    "hora": "2026-09-07 12:00:00"
  }
}
```

> **Corrección respecto a versión anterior:** el body `{status,message,timestamp}` no corresponde al código. El formato real es `{ok,mensaje,datos}`.

## 3. Uso de los portales frontend [LOCAL]

Servir la carpeta `Cipher_Forge` con Live Server en `http://localhost:5501` y abrir por HTTP, nunca con doble clic. Usar `localhost` (no `127.0.0.1`) para que `SameSite=Strict` envíe la cookie de sesión (ver `backend/docker-compose.yml:20-24`, `src/helpers/CookieAuth.php:48-62`). Divergencia conocida: el README raíz indica `127.0.0.1:5500`, desactualizado frente a compose y `.vscode/settings.json`.

1. **Portal Fotógrafo:** `http://localhost:5501/Gestion-fotografias/frontend-fotografo/index.html` (registro rol fotógrafo, login, colecciones, subida JPG/MP4, QR, moderación).
2. **Portal Cliente:** `http://localhost:5501/Gestion-fotografias/frontend-cliente/index.html` (explorar públicas, invitación privada con canje, descargas Buena/Alta sin marca de agua).

Detalles verificados:

* Backend base en ambos frontends: `frontend-*/services/config.js:2` → `http://localhost:8080`. Si cambias host/puerto del backend, debes editar esos dos archivos (no hay mecanismo por entorno).
* URLs de QR codifican `FRONTEND_URL` (`src/controllers/ColaborativoController.php:354-424`). Valor efectivo local: `http://localhost:5501/Gestion-fotografias` (`docker-compose.yml:24`). El valor `http://127.0.0.1:5500/...` citado en versiones anteriores de `infraestructura.md` está desactualizado.
* Correo de verificación: abrir `http://localhost:8025` (Mailpit). Gmail/proveedores externos fuera de alcance (`backend/EMAIL_CONFIG.md`).
* Límites reales: imagen JPG ≤30 MB, video MP4 ≤800 MB (`src/validators/MultimediaValidator.php:15-17`); transporte PHP `900M/1G` (`backend/php.ini:10-13`).

## 4. Diagnóstico y mantenimiento local [LOCAL]

```bash
# Logs backend Apache/PHP
docker compose logs -f app

# Logs MySQL
docker compose logs -f db

# Shell backend
docker exec -it cipher_forge_app bash

# Shell BD
docker exec -it cipher_forge_db bash

# Pausar conservando volúmenes
docker compose stop

# Reanudar
docker compose start
```

## 5. Reconstrucción limpia desde cero [LOCAL]

```bash
# 1. Detener y destruir volúmenes (borra BD y uploads persistidos)
docker compose down -v

# 2. Reconstruir y reiniciar
docker compose up --build -d
```

## 6. Configuración efectiva local y advertencias [LOCAL]

| Variable / archivo | Valor local efectivo | Fuente |
|---|---|---|
| `DB_HOST/DB_NAME/DB_USER/DB_PASS/DB_PORT` | `db` / `cipher_forge` / `cipher_user` / (ver `docker-compose.yml:15-19,60-64`) / `3306` — credenciales de desarrollo, no usar en producción | `docker-compose.yml:15-19,60-64` |
| `FRONTEND_URL` | `http://localhost:5501/Gestion-fotografias` | `docker-compose.yml:24` |
| `SMTP_HOST/SMTP_PORT/MAIL_FROM/MAIL_FROM_NAME` | `mailpit` / `1025` / `no-reply@cipherforge.local` / `Cipher Forge` | `docker-compose.yml:28-31` |
| `COOKIE_NAME/COOKIE_SECURE/COOKIE_SAMESITE` | `cipher_forge_token` / `false` (HTTP) / `Strict` | `docker-compose.yml:35-37`, `src/helpers/CookieAuth.php:32-62` |
| `BACKUP_INTERVAL_SEG/PURGE_INTERVAL_SEG` | `86400` / `3600` | `docker-compose.yml:65-66` |
| `SISTEMA_ALLOWED_IPS` | No definida → regla por defecto no-pública | `docker-compose.yml:40`, `src/Core/Config.php:97-105` |
| `JWT_SECRET` | No definida → autogenerada en `uploads/.jwt_secret` | `src/Core/Config.php:17-37` |

> **Advertencias locales verificadas (no aptas para producción):**
> * `backend/.env` existe pero el código no lo carga (solo `getenv()`; sin dotenv). La configuración efectiva es `environment:` de compose. `backend/.env.example` además está incompleto (omite `SMTP_*` y `APP_ENV/APP_DEBUG`) y en todo caso es inerte sin dotenv.
> * Credenciales de desarrollo versionadas en compose (ver `docker-compose.yml:15-19,87-90`), no usar en producción.
> * `backend/database/migration.sql:50` contiene `DELETE FROM favoritos;` y se ejecuta en **cada arranque**. Cada reinicio vacía `favoritos`. BLOQUEANTE conocido para producción (ver `infraestructura.md §4.1` y `§6`).
> * `backend/database/migration.sql:50` contiene `DELETE FROM favoritos;` y se ejecuta en **cada arranque**. Cada reinicio vacía `favoritos`. BLOQUEANTE conocido para producción (ver `infraestructura.md §4.1` y `§6`).
> * Tres volcados `backend/backups/backup_cipher_forge_*.sql` están versionados en git pese a `backend/.gitignore:11` (`/backups/`). No subir respaldos con datos al repo.

---

# Parte 2 — Preparación y reconstrucción en producción [PRODUCCIÓN]

> **Estado:** escenario solicitado por el cliente, no implementado. Los requisitos vigentes declaran hosting/dominio en producción como excluido (`01-requerimientos.md §10.5`, `project_charter.md Alcance excluido`, restricción UTU). Esta parte define prerrequisitos, brechas y trabajo necesario. No sustituye una decisión de alcance. Todo lo no decidido figura como `PENDIENTE DE DEFINICIÓN`. Detalle técnico en `infraestructura.md §5-§6`.

## 2.1 Prerrequisitos [PRODUCCIÓN]

* Dominio, DNS y terminación TLS delante de Apache. Sin HTTPS no hay producción: el contenedor expone HTTP (`8080:80`) y `COOKIE_SECURE=false` es solo-local.
* Secretos fuera de imagen y repo: `JWT_SECRET`, `DB_*`, `SMTP_*`, `SISTEMA_ALLOWED_IPS`. Valores actuales de compose son de desarrollo.
* MySQL sin puerto público; persistencia gestionada con snapshots; respaldo off-site y restauración probada (hoy solo 3 copias locales en `backend/backups/`, sin restore documentado).
* Frontend estático con origen fijo; `API_URL` y `FRONTEND_URL` como parámetros de despliegue (hoy hardcodeadas en `frontend-*/services/config.js:2`).
* Proveedor SMTP real con TLS. `src/helpers/Mailer.php:91-146` solo habla SMTP plano; Mailpit no es transporte de producción. `PENDIENTE DE DEFINICIÓN`: proveedor, host, puerto, credenciales.
* Correcciones BLOQUEANTES previas (ver `infraestructura.md §6`): migración sin `DELETE`, CORS con lista cerrada (`public/index.php:80`), sin fuga de `$e->getMessage()` (`public/index.php:102`), sin JWT en `?token=` (`AuthMiddleware.php:87-90`), throttling en auth, validación de longitudes, paginación.

## 2.2 Configuración por entorno [COMPARTIDO → PRODUCCIÓN]

| Clave | Local (hecho) | Producción (requerido) |
|---|---|---|
| `FRONTEND_URL` | `http://localhost:5501/Gestion-fotografias` | `PENDIENTE DE DEFINICIÓN` (origen HTTPS fijo) |
| `API_URL` frontends | `http://localhost:8080` en código | `PENDIENTE DE DEFINICIÓN` (parámetro de despliegue, no edición manual) |
| `COOKIE_SECURE` | `false` | `true` (exige HTTPS) |
| `JWT_SECRET` | Ausente (autogenerado) | `PENDIENTE DE DEFINICIÓN` (obligatorio, secreto gestionado) |
| `SISTEMA_ALLOWED_IPS` | Ausente (regla no-pública) | `PENDIENTE DE DEFINICIÓN` (CIDRs explícitos; tras proxy exigir `mod_remoteip` o auth) |
| SMTP | Mailpit `mailpit:1025` | `PENDIENTE DE DEFINICIÓN` (proveedor TLS; requiere cambio en `Mailer.php`) |

## 2.3 Preparación del servidor y despliegue [PRODUCCIÓN]

Sin comandos inventados. Procedimiento ejecutable `PENDIENTE DE DEFINICIÓN` hasta resolver P1-P10. Condiciones verificadas que el procedimiento deberá cumplir:

1. Sin bind mount `.:/var/www/html` en producción; imagen inmutable construida desde `Dockerfile`.
2. `restart` y healthcheck definidos para `app` (hoy `app` sin `restart`; `worker: unless-stopped`, `mailpit/db: always`). `/api/ping` hoy no chequea BD.
3. Migración versionada con bloqueo o job único; eliminar `DELETE FROM favoritos` antes de desplegar.
4. `AllowOverride` debe permitir `.htaccess` de `uploads/` y `backups/` (`Require all denied`), o migrar la denegación a configuración del servidor.
5. `JWT_SECRET` compartido si hay réplicas; volumen `uploads_data` compartido o almacenamiento `PENDIENTE DE DEFINICIÓN`.

## 2.4 Comprobaciones posteriores [PRODUCCIÓN]

Reutilizar las comprobaciones de Parte 1 adaptadas al origen HTTPS real: `SHOW TABLES` (11 tablas), `GET /api/ping` con formato `{ok,mensaje,datos}`, `GET /fotografos/cuota` (fotógrafo 200 / cliente 403 / anónimo 401, `routes.php:32-34`), subida JPG/MP4 dentro de límites, QR con `FRONTEND_URL` de producción, correo real `PENDIENTE DE DEFINICIÓN`, restauración de respaldo `PENDIENTE DE DEFINICIÓN`.

## 2.5 Operación y mantenimiento [PRODUCCIÓN]

* Respaldos: `worker` (`cron-backup.php --loop`) + `POST /sistema/backup` restringido por `SistemaController::restringirALocal` + `SISTEMA_ALLOWED_IPS`. Tras proxy, validar `REMOTE_ADDR` o exigir auth. Detalle en `infraestructura.md §4.2, §5.8`.
* Purga colaborativa 24h: `MultimediaService::purgarExpirados` + `POST /sistema/limpiar-colaborativos`.
* Logs/monitorización: hoy solo `docker compose logs`. Sin métricas ni alertas. `PENDIENTE DE DEFINICIÓN`.
* Reversión: `PENDIENTE DE DEFINICIÓN` (snapshot DB + volumen uploads + versión de imagen).

## 2.6 Decisiones pendientes y advertencias [PRODUCCIÓN]

Ver `infraestructura.md §6` (P1-P10): alcance, proveedor/entorno, dominios/mismo-site (`SameSite=Strict` exige mismo site), SMTP, volumen/retención, off-site/RPO-RTO, privacidad del directorio (`GET /fotografos` expone email/teléfono), secretos/TLS obligatorios, operador, modelo `/sistema/*`, criterios de aceptación. Ninguna tratada como aprobada.

---

## Trazabilidad

* Requisitos: `01-requerimientos.md §9-§12` (RF/RNF, EP4/EP7), restricción UTU y alcance excluido §10.5.
* Arquitectura/datos: `02-modelado.md §1-§2`, `infraestructura.md §1-§4`.
* Seguridad/privacidad: `03-seguridad.md` (STRIDE, Términos, PSI; §4.1 desactualizado sobre `/sistema/*`), Ley 18.331.
* Cambios: `08_control_de_cambios.md` CC-11 (worker), CC-14 (postergado `/sistema/*`), CC-16 (php.ini), CC-27 (30 MB); `09_control_de_cambios_tecnicos.md` CCT-11/CCT-16.
* Hallazgos: `backend/important.md` H01/H02/H09 resueltos, H03/H04/H10 con estado desactualizado; `backend/SISTEMA_LOCAL.md`; `backend/EMAIL_CONFIG.md`; `backend/Done.md`; `backend/FAILS-TESTED.md`.

> Estado documental: refleja el código inspeccionado por lectura directa; sin ejecución del stack Docker ni pruebas propias.
