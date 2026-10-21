# Requerimientos de la Unidad Curricular: Ciberseguridad
**Proyecto de Egreso – BT 3° Tecnologías de la Información**
**Ana Victoria Reyes Macedo**

---

## Introducción

Cada grupo deberá integrar aspectos teóricos y prácticos de ciberseguridad
dentro del desarrollo de su Proyecto de Egreso. Esta integración no será un componente
adicional, sino una dimensión transversal que debe estar presente desde la planificación
inicial hasta la implementación y evaluación final del sistema.

Será evaluado tanto el uso efectivo de herramientas profesionales, como la
documentación técnica, el análisis crítico, la aplicación de buenas prácticas y la
reflexión ética. Cada entrega parcial contará con una calificación específica, y su
contenido deberá integrarse dentro del documento general del proyecto, siguiendo el
formato establecido.

---

# Primera Entrega – Análisis y Diseño Seguro



## Objetivo: Detectar amenazas desde la etapa inicial del proyecto y proponer mecanismos preventivos adecuados.



## 1. Identificación de al menos 3 amenazas digitales relevantes.

1.1 Phishing dirigido a fotógrafos y clientes

El sistema almacena datos de contacto (correo y teléfono) y gestiona el acceso a colecciones privadas y a la descarga de material pagado a futuro. Un atacante podría suplantar al sistema o al fotógrafo mediante correos falsos (por ejemplo, notificaciones falsas de "nueva colección disponible" o "verificación de cuenta") para robar credenciales de acceso. Dado que el rol Fotógrafo administra colecciones privadas y autoriza clientes manualmente, el robo de sus credenciales comprometería la confidencialidad de todo su material y de los datos de sus clientes.

1.2 Inyección SQL 

El sistema cuenta con múltiples formularios que interactúan con la base de datos: registro de usuarios (RF1, RF2), inicio de sesión (RF3), creación de colecciones (RF4), carga de metadatos de imágenes/videos (RF7, RF20) y validación de códigos QR (RF13-RF17). Si estas entradas no se validan ni se usan consultas parametrizadas, un atacante podría inyectar código SQL para leer, modificar o eliminar datos de usuarios, colecciones o permisos de descarga, representando un riesgo crítico dado que la base de datos contiene datos personales.

1.3 Fuga de datos personales

El RF1 dice que el sistema debe permitir registrar nombre completo, correo electrónico, contraseña y número de teléfono como opcional tanto de fotógrafos como de clientes. Una fuga de esta base de datos (por configuración insegura del entorno en la nube, respaldo mal protegido o error humano) expondría información de identificación personal, lo que además de un daño reputacional para el proyecto implicaría un problema legal y ético para el equipo, dado que se trata de datos sensibles de terceros.

1.4 Acceso no autorizado a colecciones privadas (IDOR)

Según RF5 y RF6, las colecciones privadas solo deben ser visibles para los clientes que el fotógrafo autorizó explícitamente, y el sistema debe bloquear el acceso directo por URL. Si la autorización se valida únicamente en el frontend, o si los identificadores de colección son predecibles y no se verifica en el backend que el usuario autenticado tenga permiso sobre ese recurso específico (vulnerabilidad de tipo IDOR — Insecure Direct Object Reference), cualquier usuario podría acceder a material privado de otro cliente simplemente cambiando un identificador en la URL.

1.5 Evasión de la marca de agua y descarga no autorizada

El modelo de negocio del cliente depende de que las imágenes en vista previa tengan marca de agua y que solo el contenido autorizado se entregue sin ella (restricción técnica confirmada en la entrevista, RF9, RF11). Si el archivo de alta calidad queda accesible por una URL directa no protegida, o si la marca de agua se aplica solo de forma visual sin proteger el archivo original en el servidor, un usuario podría descargar el material sin haber sido autorizado, afectando directamente el objetivo de negocio del cliente (cobro ágil por el material).

1.6 Abuso o reutilización de códigos QR

El sistema usa códigos QR con dos propósitos distintos: acceso directo a una colección (RF16) y carga colaborativa de contenido por parte de invitados sin cuenta (RF13, RF14). Si estos códigos no expiran, no están vinculados a un evento/colección específica o pueden reutilizarse indefinidamente, un tercero que obtenga el QR (por ejemplo, fotografiándolo en un evento) podría subir contenido no deseado a la colección o acceder a material privado fuera del contexto para el que fue generado.

---

## 2. Mapa de Riesgos

El siguiente esquema vincula los componentes principales del sistema con las amenazas identificadas, permitiendo visualizar qué partes del sistema concentran mayor exposición.

```mermaid
block-beta
columns 5
  esq["Impacto ↓ / Probabilidad →"] h1["Baja"] h2["Media"] h3["Alta"] h4["Muy Alta"]
  rC["Catastrófico"] c1["-"] c2["A2: Fuga de datos<br/>A3: Inyección SQL"] c3["-"] c4["-"]
  rMy["Mayor"] m1["-"] m2["-"] m3["A1: Phishing<br/>A4: Acceso IDOR"] m4["-"]
  rMd["Moderado"] o1["-"] o2["A6: Reutilización QR"] o3["A5: Evasión marca de agua"] o4["-"]
  rMn["Menor"] n1["-"] n2["-"] n3["-"] n4["-"]

  classDef header fill:#2c3e50,stroke:#1a252f,stroke-width:1px,color:#ffffff,font-weight:bold;
  classDef verde fill:#52be80,stroke:#27ae60,stroke-width:1.5px,color:#ffffff;
  classDef amarillo fill:#f1c40f,stroke:#d4ac0d,stroke-width:1.5px,color:#000000;
  classDef naranja fill:#eb6841,stroke:#d35400,stroke-width:1.5px,color:#ffffff;
  classDef rojo fill:#f05252,stroke:#c0392b,stroke-width:1.5px,color:#ffffff;

  class esq,h1,h2,h3,h4,rC,rMy,rMd,rMn header
  class m1,o1,n1,n2,n3 verde
  class c1,m2,o2,o3,n4 amarillo
  class c2,c3,m3,m4,o4 naranja
  class c4 rojo
```
 
*Rojo = Impacto Alto (técnico y a usuarios) · Naranja = Impacto Medio*
 
---
 
## Escala de valoración de riesgo
 
| Nivel | Probabilidad | Impacto | Resultado (R) | Acción Inmediata |
|---|---|---|---|---|
| **Crítico** | Muy Alta (4-5) | Catastrófico (4-5) | 16 - 25 | Detener operaciones / Mitigación urgente |
| **Alto** | Alta (3) | Mayor (3) | 9 - 15 | Planes de acción correctiva a corto plazo |
| **Medio** | Media (2) | Moderado (2) | 4 - 8 | Monitoreo periódico y controles programados |
| **Bajo** | Baja (1) | Menor (1) | 1 - 3 | Asumir el riesgo / Monitoreo mínimo |
 
*R = Probabilidad × Impacto*
 
---
 
## Amenazas identificadas — valoración aplicada
 
Tabla construida a partir de las conexiones reales del Mapa de Riesgos (componentes del sistema → amenazas detectadas).
 
| Amenaza | Componente(s) afectado(s) | Probabilidad | Impacto | R | Nivel | Acción Inmediata |
|---|---|---|---|---|---|---|
| Phishing dirigido a fotógrafos y clientes | Registro e inicio de sesión de usuarios | Alta (3) | Mayor (3) | 9 | **Alto** | Planes de acción correctiva a corto plazo |
| Fuga de datos personales (contactos, nombres) | Base de datos de usuarios | Media (2) | Catastrófico (5) | 10 | **Alto** | Planes de acción correctiva a corto plazo |
| Inyección SQL en formularios y filtros | Base de datos de usuarios · Carga de imágenes y videos | Media (2) | Catastrófico (5) | 10 | **Alto** | Planes de acción correctiva a corto plazo |
| Acceso no autorizado a colecciones privadas (IDOR) | Gestión de colecciones · Acceso y carga vía código QR | Alta (3) | Mayor (3) | 9 | **Alto** | Planes de acción correctiva a corto plazo |
| Evasión de marca de agua / descarga no autorizada | Vista previa con marca de agua | Alta (3) | Moderado (2) | 6 | **Medio** | Monitoreo periódico y controles programados |
| Abuso o reutilización de códigos QR | Acceso y carga vía código QR | Media (2) | Moderado (2) | 4 | **Medio** | Monitoreo periódico y controles programados |

---
 
### Notas de justificación
 
- **Fuga de datos personales** e **Inyección SQL** se clasifican como Alto por su impacto catastrófico: ambas comprometen directamente la base de datos de usuarios, que contiene nombres completos, correos y teléfonos reales de fotógrafos y clientes.
- **Phishing** se clasifica como Alto por su alta probabilidad: es el vector de ataque más simple de ejecutar contra el punto de registro/inicio de sesión, sin requerir explotar una falla técnica del sistema.
- **Acceso no autorizado a colecciones privadas (IDOR)** se clasifica como Alto porque dos componentes distintos convergen en esta amenaza (Gestión de colecciones y Acceso vía QR), lo que aumenta su probabilidad de ocurrencia.
- **Evasión de marca de agua** y **Abuso de código QR** quedan en Medio: su impacto afecta principalmente el modelo de negocio del fotógrafo, pero su explotación depende de condiciones más puntuales (acceso al archivo original o al QR físico).

---
 
## Análisis de impacto (técnico y sobre los usuarios)
 
| Amenaza | Impacto técnico | Impacto sobre los usuarios |
|---|---|---|
| Phishing dirigido a fotógrafos y clientes | Robo de información; compromiso de cuentas de Fotógrafo o Cliente | Suplantación de identidad; pérdida de confianza en la plataforma |
| Fuga de datos personales (Contactos y Nombres) | Pérdida de confidencialidad del almacenamiento en la nube | Exposición de nombre completo, correo y teléfono de usuarios reales |
| Inyección SQL en formularios y filtros | Alteración o destrucción de datos; posible caída del servicio | Exposición de datos personales de todos los usuarios registrados |
| Acceso no autorizado a colecciones privadas (IDOR) | Bypass de la lógica de autorización del backend | Exposición de material privado de clientes y eventos ajenos |
| Evasión de marca de agua / descarga no autorizada | Acceso directo al archivo original sin control de permisos | Pérdida económica para el fotógrafo (afecta el objetivo de negocio) |
| Abuso o reutilización de códigos QR | Carga de contenido no controlado; acceso fuera del contexto previsto | Contenido indebido en la colección; exposición no deseada de material |

---

## 3. Buenas prácticas de seguridad en desarrollo web

Resumen las buenas prácticas durante el desarrollo, seleccionadas y justificadas en función de las amenazas concretas identificadas para este proyecto.

## 3.1 Validación y saneamiento de entradas

Todo dato ingresado por el usuario (formularios de registro, título/descripción de imágenes, parámetros de búsqueda) se validará y saneará tanto en el frontend como en el backend, y las consultas a la base de datos se realizarán mediante sentencias parametrizadas u ORM, nunca concatenando texto. Esta práctica ataca directamente el riesgo de inyección SQL (1.2) descrito arriba, que es crítico por la cantidad de formularios con los que cuenta el sistema.

---

## 3.2 Autenticación robusta y gestión segura de sesiones

Las contraseñas se almacenarán con un algoritmo de hash con salting (bcrypt), nunca en texto plano ni con hashes reversibles. Las sesiones utilizarán tokens con expiración y se invalidarán al cerrar sesión. Esta práctica reduce el impacto de un eventual phishing (1.1), ya que aunque se filtre un correo o usuario, la contraseña no queda expuesta directamente en la base de datos.


---

## 3.3 Control de acceso verificado en el backend (no solo en la interfaz)

Cada endpoint que devuelve una colección, imagen o dato de usuario deberá verificar en el servidor que el solicitante tiene permiso sobre ese recurso puntual, usando identificadores no predecibles (UUID en lugar de IDs incrementales) para colecciones privadas. Esta práctica es la respuesta directa al riesgo de acceso no autorizado.


---

## 3.4 Protección del archivo original frente a la vista previa

El archivo en alta calidad no se expondrá en ninguna ruta pública ni predecible: se servirá únicamente a través de un endpoint que valide el permiso de descarga del cliente en el momento de la solicitud (RF11), mientras que la vista previa con marca de agua se generará como una copia separada y optimizada (RF8, RF9). Esto protege el objetivo de negocio del cliente frente a la evasión de marca de agua (1.5).


---

## 3.5 Códigos QR con expiración

Cada código QR se generará como un token único asociado a una colección o evento específico, con fecha de expiración.

---

## 3.6 Registro y monitoreo de eventos sensibles

Se registrarán eventos como inicios de sesión fallidos y uso de códigos QR, lo que permitirá detectar patrones de abuso (por ejemplo, múltiples intentos de acceso a colecciones privadas).
---

## 4. Cambios a futuro (pendientes)

Cambios detectados en la auditoría del backend que quedan **documentados y postergados** por decisión del Product Owner. No se implementan en esta versión.

### 4.1 Protección de endpoints de administración `/sistema/*` (CF-08 / CC-14)

**Estado actual:** `POST /sistema/backup`, `GET /sistema/backups` y `POST /sistema/limpiar-colaborativos` (`routes.php`) son públicos: cualquiera que alcance la API puede disparar respaldos, listar el historial o **purgar colaborativos pendientes de cualquier fotógrafo**. El esquema solo admite roles `fotografo`/`cliente` (no existe `admin`).

**Cambio a futuro (postergado):** proteger estos endpoints **antes de exponer el servicio**. Opción recomendada: exigir cabecera `Authorization: Bearer <clave>` contra una clave maestra configurada por entorno (`SISTEMA_API_KEY`, con fallback y documentación de despliegue); alternativa: agregar rol `admin` al ENUM `roles` con usuario sembrado y verificación de rol en `AuthMiddleware`. El servicio `worker` y `cron-backup.php` **no se ven afectados** por ninguna de las dos, porque invocan `BackupService`/`MultimediaService` directamente (sin pasar por HTTP).

# Segunda Entrega - Implementación Segura, Criptografía y Aspectos Legales

# Términos y Condiciones

Estos términos definen el uso aceptable de la plataforma web, protegen al equipo de desarrollo/empresa y establecen cómo se gestionan los datos en cumplimiento con la normativa uruguaya.

## 1. Identificación y aceptación
* **Datos del presentador:** La plataforma es un sistema web destinado a fotógrafos y compradores de material fotográfico, desarrollado inicialmente como un Proyecto de Egreso de UTU. 
* **Consentimiento expreso:** Los usuarios (fotógrafos y clientes) deben aceptar de forma obligatoria los Términos y Condiciones y la Política de Privacidad al momento de registrarse. Además, en su primer inicio de sesión, el fotógrafo debe aceptar mediante un modal obligatorio su responsabilidad legal sobre el contenido y el cumplimiento de la Ley 18.331.

## 2. Uso aceptable y restricciones
* **Reglas de conducta:** El sistema permite la subida de imágenes en formato JPG y videos en formato MP4. Los invitados a un evento pueden subir fotos y videos mediante un código QR de carga colaborativa, pero el fotógrafo es quien debe moderar y aprobar este material. 
* **Gestión de cuentas:** Para registrarse, los usuarios deben proporcionar su nombre completo, correo electrónico y contraseña, con el número de teléfono como dato opcional. El sistema prohíbe y bloquea el registro de usuarios duplicados que intenten usar un mismo correo electrónico. Se requiere verificar la cuenta mediante un código enviado al correo electrónico.

## 3. Propiedad, estructura y contenidos
* **Dependencia de la plataforma:** El sistema ofrece galerías de previsualización donde las imágenes se muestran con una marca de agua aplicada automáticamente para proteger el trabajo del fotógrafo. Los videos subidos generan automáticamente un recorte de vista previa de un máximo de 15 segundos.
* **Contenido generado por el usuario:** El fotógrafo es quien decide de manera soberana si sus colecciones son públicas o privadas. El fotógrafo asume la total responsabilidad legal por el contenido que publica en la plataforma. La plataforma establece explícitamente que no se hace responsable ante posibles demandas por la publicación no autorizada de imágenes de terceros.

## 4. Aspectos económicos y cancelación
* **Precios y pagos:** El sistema otorga a cada fotógrafo una cuota de almacenamiento inicial de 3 GB. En esta primera versión del proyecto (por restricciones institucionales), no se incluyen pasarelas de pago reales ni se procesan cobros financieros dentro del sistema. 
* **Suspensiones:** El sistema controla activamente el límite de almacenamiento de 3 GB. Si el usuario supera esta cuota, el sistema impide nuevas subidas de archivos y notifica sobre los elementos que exceden el límite. Los archivos colaborativos subidos por invitados que no sean aprobados por el fotógrafo serán eliminados automáticamente luego de 24 horas.

## 5. Responsabilidad y legislación
* **Limitación de responsabilidad:** El sistema bloqueará cualquier intento de acceso directo mediante URL a las colecciones privadas por parte de usuarios no autorizados. Para mitigar el riesgo de pérdida de datos, el sistema realiza respaldos automáticos diarios de la base de datos.
* **Ley aplicable y jurisdicción:** La plataforma y el tratamiento de los datos personales se rigen estrictamente por la Ley 18.331 de Protección de Datos Personales de Uruguay. No se solicita la dirección física ni la cédula de identidad de los usuarios para simplificar el registro y minimizar la recolección de datos sensibles.

---

# Políticas de Seguridad de la Información (PSI)

Estas políticas establecen los lineamientos normativos para proteger la Confidencialidad, Integridad y Disponibilidad (CIA) de los datos gestionados en el sistema de fotografía.

## 1. Diagnóstico
* **Información sensible:** La organización maneja datos personales como nombres completos, correos electrónicos y contraseñas de los usuarios registrados. También gestiona material multimedia sensible y privado, perteneciente a eventos sociales (bodas, fiestas de 15 años).
* **Riesgos que enfrenta:** Se identificaron riesgos operativos como la caída del servidor, posibles pérdidas de información (respaldos), y el riesgo legal por exposición de material fotográfico privado sin autorización.

## 2. Definición de roles y responsabilidades
* **Fotógrafo (Administrador de colección):** Es responsable de clasificar sus colecciones como públicas o privadas. También es responsable de moderar y aprobar el material subido por los invitados a través del QR colaborativo.
* **Cliente:** Responsable de acceder a las colecciones privadas únicamente a través de los enlaces de invitación proporcionados, requiriendo registro o inicio de sesión.
* **Invitados (Carga colaborativa):** Pueden subir contenido de forma anónima mediante el código QR sin necesidad de registro, pero tienen restringido el acceso para visualizar o descargar otros archivos de la colección.

## 3. Lineamientos y normas
* **Confidencialidad:** Las colecciones privadas conservan su estado permanentemente, sin importar el tiempo transcurrido o la cantidad de clientes asignados. Se aplican marcas de agua a las imágenes en la vista previa para proteger la propiedad intelectual antes de la descarga o compra.
* **Disponibilidad e Integridad:** El sistema debe ejecutar un respaldo automático diario de la base de datos. Se aplica una política de rotación automática que mantiene almacenadas únicamente las últimas tres copias de seguridad. Queda registrado en el sistema la fecha y hora exacta de cada uno de estos respaldos.

## 4. Aprobación y difusión
* Las normativas de seguridad, términos de uso y el cumplimiento de la Ley 18.331 deben ser presentadas y aprobadas formalmente por el cliente solicitante, Lemuel Swec.
* Para la difusión a los usuarios, la política se comunica obligatoriamente a través de un modal emergente durante el primer inicio de sesión del fotógrafo.

## 5. Revisión periódica
* Las políticas actuales están diseñadas para un entorno de pruebas local como parte del proyecto académico. 
* Estas normativas deberán ser revisadas y actualizadas cuando el sistema se migre a un servidor en la nube de producción y se implementen pasarelas de métodos de pago reales en futuras versiones.


# Reporte de Análisis SAST — Cipher_Forge / Gestion-fotografias

**Herramienta:** SonarQube (Community Build) + SonarScanner CLI
**Tipo de verificación:** Static Application Security Testing (SAST) + análisis de calidad de código
**Proyecto:** `cipher-forge` — `C:\Users\Iván Sandoval\Desktop\Cipher_Forge\Gestion-fotografias`

---

## 1. Resumen ejecutivo

Se realizó un análisis SAST completo sobre el proyecto `Gestion-fotografias` (backend PHP/MySQL + frontends web JS/HTML/CSS + Docker) mediante SonarQube. Se detectaron **145 problemas activos**, de los cuales **7 son vulnerabilidades de seguridad**, **9 son bugs** y **129 son code smells**. El Quality Gate quedó en **ERROR** por cobertura nueva 0% (exigido ≥80%), duplicación nueva 9.35% (máx. 3%) y 102 incumplimientos nuevos.

No se encontraron **Security Hotspots** con probabilidad de vulnerabilidad, ni secretos expuestos (regla de texto y secretos analizó 84 archivos sin hallazgos).

**Prioridad de remediación:** corregir primero las 7 vulnerabilidades (CORS permissivo, PRNG no criptográfico para generación de IDs, cookies de sesión sin flags de seguridad y contenedor corriendo como *root*).

---

## 2. Información de la verificación

| Parámetro | Valor |
|:---|:---|
| Fecha del análisis | 2026-09-10 |
| SonarQube Server | `http://localhost:9000` (Community Build **26.8.0.126808**) |
| SonarScanner CLI | **8.1.0.6389** — Java 21.0.11 (Eclipse Adoptium) |
| Clave de proyecto | `cipher-forge` |
| Directorio base | `C:\Users\Iván Sandoval\Desktop\Cipher_Forge\Gestion-fotografias` |
| Dashboard | http://localhost:9000/dashboard?id=cipher-forge |
| Estado del análisis | `ANALYSIS SUCCESSFUL` (procesamiento Compute Engine: `SUCCESS`) |

---

## 3. Alcance del análisis

### 3.1 Archivos escaneados (82 archivos de código fuente)

| Lenguaje | Archivos | Notas |
|:---|:---:|:---|
| PHP | 37 | Backend `backend/src`, `public/`, scripts raíz (`routes.php`, `cron-backup.php`, probes) |
| JavaScript | 21 | Lógica de `frontend-cliente/js` y `frontend-fotografo/js` |
| HTML | 15 | Páginas de ambos frontends |
| CSS | 7 | Hojas de estilo de ambos frontends |
| INI | 1 | `backend/php.ini` (config runtime) |
| Dockerfile | 1 | `backend/Dockerfile` |

También se analizaron como parte del paso de texto/secretos: 84 archivos (incluye `docker-compose.yml` YAML, `routes.php`, etc.) con sensor `TextAndSecretsSensor`; **0 secretos encontrados**.

### 3.2 Configuración utilizada (`sonar-project.properties`)

```properties
sonar.projectKey=cipher-forge
sonar.projectName=Cipher Forge - Gestion Fotografias
sonar.projectVersion=1.0
sonar.sources=backend,frontend-cliente,frontend-fotografo
sonar.sourceEncoding=UTF-8
sonar.exclusions=backend/backups/**,backend/uploads/**,**/.git/**
```

### 3.3 Comando de ejecución (Windows / PowerShell)

```
& C:\Sonar-Scanner\sonar-scanner\bin\sonar-scanner.bat --% `
  -Dsonar.host.url=http://localhost:9000 `
  -Dsonar.token=<SONAR_TOKEN>
```

### 3.4 Perfiles de calidad activados

`Sonar way` para los 6 lenguajes detectados: **css, docker, js (JavaScript), php, web (HTML), yaml**.

> Nota: el análisis SCA de dependencias fue omitido (no hay manifiestos composer/npm en el proyecto) y no se proveyeron reportes de cobertura (PHPUnit/JS), por lo que `coverage = 0.0%`.

---

## 4. Métricas globales (proyecto `cipher-forge`)

| Métrica | Valor |
|:---|:---|
| Líneas de código (NCLOC) | 10 499 |
| Líneas totales | 13 655 |
| Sentencias | 4 027 |
| Funciones | 672 |
| Clases | 32 |
| Complejidad | 1 765 |
| Densidad de comentarios | 6.6% |
| **Problemas totales (activos)** | **145** |
| Vulnerabilidades | 7 |
| Bugs | 9 |
| Code Smells | 129 |
| Security Hotspots | 0 |
| Cobertura de código | 0.0% |
| Duplicación | 4.3% |
| Rating de seguridad | **C (3.0)** |
| Rating de fiabilidad | **C (3.0)** |
| Rating de mantenibilidad | **A (1.0)** |

### 4.1 Distribución por severidad

| Severidad | Cantidad |
|:---|:---:|
| Blocker | 0 |
| Critical | 20 |
| Major | 52 |
| Minor | 71 |
| Info | 2 |
| **Total** | **145** |

### 4.2 Quality Gate

| Condición | Valor real | Umbral | Estado |
|:---|:---|:---|:---|
| Cobertura nueva (`new_coverage`) | 0.0% | ≥ 80% | ❌ ERROR |
| Duplicación nueva (`new_duplicated_lines_density`) | 9.35% | ≤ 3% | ❌ ERROR |
| Violaciones nuevas (`new_violations`) | 102 | = 0 | ❌ ERROR |

**Estado global del Quality Gate: `ERROR`**

---

## 5. Vulnerabilidades de seguridad (7)

Listado resumido:

| # | Severidad | Regla | Archivo | Línea |
|:--|:--|:--|:--|:--:|
| V1 | Major | `javascript:S2245` PRNG no seguro | `frontend-fotografo/js/subirimagenes.js` | 309 |
| V2 | Major | `javascript:S2245` PRNG no seguro | `frontend-fotografo/js/collection.js` | 8 |
| V3 | Major | `javascript:S2245` PRNG no seguro | `frontend-fotografo/js/subirimagenes.js` | 27 |
| V4 | Major | `php:S5122` CORS permissivo | `backend/public/index.php` | 54 |
| V5 | Minor | `php:S3330` Cookie sin HttpOnly | `backend/php.ini` | — |
| V6 | Minor | `php:S2092` Cookie sin `secure` | `backend/php.ini` | — |
| V7 | Minor | `docker:S6471` Contenedor como root | `backend/Dockerfile` | 3 |

### V1, V2, V3 — Generadores de números pseudoaleatorios (PRNG) en contexto sensible — `javascript:S2245`

**Regla:** *"Pseudorandom number generators (PRNGs) should not be used in security contexts"* (CWE-338: Use of Cryptographically Weak PRNG).

**Código afectado:**
```javascript
// collection.js:8 y subirimagenes.js:27
id: Date.now() + Math.random(),

// subirimagenes.js:309 (generación de id por cada archivo subido)
const id = Date.now() + Math.random();
```

**Justificación:** `Math.random()` implementa un PRNG no criptográfico (secuencia determinista/predecible). Si el valor resultante se usa como identificador de recursos, un atacante podría predecir y correlacionar IDs (por ejemplo, adivinar IDs de colecciones o de archivos subidos). Los PRNG no criptográficos **no** deben usarse para generar secretos, tokens, nonces ni identificadores con valor de control de acceso.

**Cómo corregir:** usar un generador criptográficamente seguro (`CSPRNG`):
```javascript
const cripto = window.crypto || window.msCrypto;
const buffer = new Uint32Array(1);
cripto.getRandomValues(buffer);
const id = Date.now() + buffer[0];
```
Alternativa recomendada en este contexto: dejar que el backend genere IDs de colecciones/medios (autoincremental o UUID) y no depender de `Math.random()` en el cliente.

### V4 — Política CORS excesivamente permisiva — `php:S5122`

**Regla:** *"Cross-Origin Resource Sharing (CORS) policy should be restricted to trusted origins"* (CWE-942: Overly Permissive Cross-domain Whitelist).

**Código afectado (`backend/public/index.php:54`):**
```php
header('Access-Control-Allow-Origin: *');
```

**Justificación:** `Access-Control-Allow-Origin: *` permite que **cualquier** sitio web origen realice peticiones cross-origin y lea las respuestas de la API (incluyendo datos sensibles de colecciones y sesiones con `Authorization`). Permite exfiltración de datos si la API expone recursos autenticados.

**Cómo corregir:** restringir a una lista blanca de orígenes confiables:
```php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$permitidos = ['https://dominio-cliente.ejemplo', 'https://dominio-fotografo.ejemplo'];
if (in_array($origin, $permitidos, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
```

### V5 — Cookie de sesión sin flag `HttpOnly` — `php:S3330`

**Regla:** *"Cookies should have the 'HttpOnly' flag"* (CWE-1004: Sensitive Cookie Without 'HttpOnly' Attribute).

**Código afectado (`backend/php.ini`):** no se define `session.cookie_httponly`.

**Justificación:** con `HttpOnly` desactivado, un script JavaScript inyectado vía XSS puede leer `document.cookie` y robar el `PHPSESSID`, permitiendo secuestro de sesión. La cookie de sesión es altamente sensible.

**Cómo corregir:** habilitarla por defecto en PHP 8.2:
```ini
session.cookie_httponly = 1
```

### V6 — Cookie de sesión sin flag `secure` — `php:S2092`

**Regla:** *"Cookies should have the 'secure' flag"* (CWE-614: Sensitive Cookie in HTTPS Session Without 'Secure' Attribute).

**Código afectado (`backend/php.ini`):** no se define `session.cookie_secure`.

**Justificación:** sin `secure`, la cookie de sesión se envía también por HTTP sin cifrar y puede ser interceptada en un ataque de intermediario (man-in-the-middle), exponiendo la sesión del usuario.

**Cómo corregir:** en producción (servida sobre HTTPS):
```ini
session.cookie_secure = 1
```

> ⚠️ Nota: `session.cookie_secure = 1` descarta la cookie si la app se accede por HTTP plano (como `http://localhost:8080` en desarrollo). Si se habilita, el sitio debe servirse siempre por HTTPS.

### V7 — Imagen Docker ejecutándose como usuario privilegiado — `docker:S6471`

**Regla:** *"Docker containers should not run as a privileged user"*.

**Código afectado (`backend/Dockerfile:3`):**
```dockerfile
FROM php:8.2-apache
```

**Justificación:** las imágenes Docker se ejecutan como `root` por defecto si no se define `USER`. Si la aplicación (o Apache/PHP) se compromete, el atacante obtiene privilegios **root** dentro del contenedor, ampliando el impacto de cualquier vulnerabilidad.

**Cómo corregir:** crear y usar un usuario no privilegiado:
```dockerfile
FROM php:8.2-apache
RUN useradd --create-home --uid 1000 www-app
USER www-app
```
> O especificar usuario no-root en `docker run --user` o en el `docker-compose.yml` (`user:`).

---

## 6. Bugs (9)

Listado de bugs activos (errores de código que pueden causar comportamiento incorrecto):

| # | Severidad | Regla | Archivo | Línea | Detalle | Acción |
|:--|:--|:--|:--|:--:|:--|:--|
| B1 | Major | `Web:InputWithoutLabelCheck` | `frontend-cliente/pages/panelcliente.html` | 47 | Input sin `<label>` asociado | Añadir `<label for="...">` |
| B2 | Major | `javascript:S4822` | `frontend-fotografo/js/binarios.js` | 38 | Promesa dentro de `try` sin `await`/`.catch()`, su rechazo no se maneja | Usar `await` o encadenar `.catch()` |
| B3 | Major | `javascript:S4822` | `frontend-fotografo/js/binarios.js` | 48 | Ídem | Ídem |
| B4 | Major | `javascript:S4822` | `frontend-fotografo/js/binarios.js` | 63 | Ídem | Ídem |
| B5 | Minor | `php:S2003` | `backend/probe_media.php` | 2 | `require` puede incluir el archivo dos veces | Usar `require_once` |
| B6 | Minor | `php:S2003` | `backend/probe_media.php` | 3 | Ídem | Ídem |
| B7 | Major | `Web:InputWithoutLabelCheck` | `frontend-fotografo/pages/SubirImagenes.html` | 34 | Input sin `<label>` | Añadir label |
| B8 | Major | `Web:InputWithoutLabelCheck` | `frontend-fotografo/pages/SubirImagenes.html` | 72 | Ídem | Ídem |
| B9 | Major | `Web:InputWithoutLabelCheck` | `frontend-fotografo/pages/login.html` | 32 | Ídem | Ídem |

Detalle de reglas:

- **`Web:InputWithoutLabelCheck`** — Los campos `input`/`select`/`textarea` deben tener `<label>`. Sin etiquetas, la accesibilidad (WCAG 3.3.2 / 4.1.2) se degrada y herramientas de asistencia no pueden anunciar el propósito del campo.
- **`javascript:S4822`** — *"Promise rejections should not be caught by 'try' blocks"*. Una promesa lanzada dentro de un bloque `try` sin `await` ni `.catch()` genera un rechazo no manejado que puede romper el flujo de subida de binarios.
- **`php:S2003`** — *"require_once/include_once should be used instead of require/include"*. `require`/`include` no evitan la doble inclusión → definiciones duplicadas y errores fatales en scripts de sondeo.

---

## 7. Code Smells (129)

Problemas de mantenibilidad agrupados por regla. Todos son de tipo `CODE_SMELL`.

| Regla | Cant. | Descripción | Archivos afectados |
|:--|:--:|:--|:--|
| `javascript:S6582` | 31 | Preferir *optional chaining* (`?.`) en lugar de accesos encadenados | `gallery.js` (4), `panel.js` (4), `api.js` (6), `subirimagenes.js` (8), `panelcliente.js` (3), `tuscoleccionescliente.js` (3), `almacenamiento.js`, `qracceso.js`, `qrcolaborativo.js` |
| `php:S113` | 14 | Los archivos deben terminar en salto de línea | 14 archivos PHP (`FavoritoController`, `QrGenerator`, `Mailer`, `index.php`, `routes.php`, `Router.php`, probes, `AuthController`, `AuthService`, dtos, validators, `HomeController`) |
| `css:S7924` | 11 | Contraste insuficiente entre texto y fondo (accesibilidad) | `panelcliente.css` (4), `tuscolecciones.css` (3), `panel.css` (3), `colaborativo.css` |
| `php:S1192` | 7 | Literales de texto duplicados → definir constantes | `MultimediaService.php` (2), `cron-backup.php`, `routes.php`, `ColaborativoController.php`, `ColeccionService.php`, `MultimediaValidator.php` |
| `Web:S6819` | 7 | Usar etiqueta semántica nativa en lugar de rol ARIA | `SubirImagenes.html` (2), `login.html`, `panel.html`, `registro.html`, `verificaremail.html`, `editarperfil.html` |
| `javascript:S2486` | 7 | Excepciones capturadas e ignoradas | `api.js` (2), `script.js` (2), `subirimagenes.js` (2), `auth.js` |
| `php:S3776` | 7 | Complejidad cognitiva muy alta (refactorizar) | `QrGenerator.php` (3), `AuthMiddleware.php`, `Mailer.php`, `Router.php`, `MultimediaValidator.php` |
| `javascript:S7785` | 6 | Preferir *top-level await* sobre cadenas de promesas | `subirimagenes.js` (4), `moderation.js`, `colaborativo.js` |
| `php:S1142` | 4 | Demasiadas sentencias `return` en una función | `QrGenerator.php`, `AuthMiddleware.php`, `Mailer.php`, `Jwt.php` |
| `javascript:S7765` | 3 | Usar `.includes()` en lugar de `.indexOf()` | `subirimagenes.js` (2), `tuscoleccionescliente.js` |
| `php:S112` | 3 | Lanzar excepciones genéricas en lugar de dedicadas | `QrGenerator.php` (2), `Mailer.php` |
| `javascript:S3776` | 3 | Complejidad cognitiva alta | `subirimagenes.js` (2), `panel.js` |
| `javascript:S7758` | 2 | Usar métodos string conscientes de Unicode (`codePointAt`/`fromCodePoint`) | `subirimagenes.js` (2) |
| `php:S121` | 2 | Estructuras de control sin llaves `{}` | `ColeccionRepository.php`, `BackupService.php` |
| `php:S1135` | 2 | Comentarios `TODO` sin completar | `MultimediaValidator.php`, `Request.php` |
| `javascript:S7781` | 2 | Usar `replaceAll()` en lugar de `replace()` con regex global | `script.js` |
| `Web:S6850` | 2 | Encabezados sin contenido accesible | `tuscoleccionescliente.html`, `colaborativo.html` |
| `css:S4666` | 2 | Selectores duplicados | `panelcliente.css`, `tuscolecciones.css` |
| `php:S1131` | 1 | Espacios en blanco al final de línea | `Database.php` |
| `Web:S7926` | 1 | Meta viewport deshabilita zoom (`user-scalable=no`) | `colaborativo.html` |
| `javascript:S6594` | 1 | Preferir `RegExp.exec()` sobre `String.match()` | `tuscoleccionescliente.js` |
| `php:S1448` | 1 | Clase con demasiados métodos (28 > 20) | `QrGenerator.php` |
| `docker:S7031` | 1 | Fusionar instrucciones `RUN` consecutivas | `Dockerfile` |
| `php:S127` | 1 | Condición de parada de bucle `for` no invariante | `QrGenerator.php` |
| `php:S1172` | 1 | Parámetro de función sin uso | `MultimediaService.php` |
| `javascript:S3504` | 1 | Usar `let`/`const` en lugar de `var` | `panel.js` |
| `javascript:S7756` | 1 | Usar `Blob.arrayBuffer()` en vez de `FileReader` | `subirimagenes.js` |
| `javascript:S7721` | 1 | Mover función a alcance exterior | `subirimagenes.js` |
| `javascript:S1940` | 1 | Chequeo booleano invertido | `subirimagenes.js` |
| `javascript:S1481` | 1 | Variable/función sin usar | `tuscoleccionescliente.js` |
| `javascript:S7776` | 1 | Arrays usados solo para comprobar existencia → usar `Set` | `panelcliente.js` |
| `javascript:S3358` | 1 | Ternario anidado | `subirimagenes.js` |

---

## 8. Security Hotspots

**Resultado: 0 hotspots** de seguridad pendientes de revisión. No hay código que requiera triaje manual de exposición (por ejemplo, usos de `eval`, `exec`, SSRF, deserialización, etc.).

---

## 9. Resumen por lenguaje y origen de los hallazgos

| Lenguaje | Vulnerabilidades | Bugs | Code Smells |
|:--|:--:|:--:|:--:|
| PHP | 3 | 2 | ≈ 45 |
| JavaScript | 3 | 3 | ≈ 56 |
| HTML (Web) | 0 | 4 | ≈ 12 |
| CSS | 0 | 0 | ≈ 15 |
| Docker | 1 | 0 | 1 |
| INI (php.ini) | 2 | 0 | 0 |

> Los conteos por lenguaje de code smells son aproximados (basados en el agrupamiento por regla y archivos), totalizando 129.

---

## 10. Archivos escaneados (listado completo)

### Backend (40 archivos)
```
backend/cron-backup.php
backend/Dockerfile
backend/index.html
backend/php.ini
backend/probe_media.php
backend/probe_originals.php
backend/public/index.php
backend/routes.php
backend/src/Core/Config.php
backend/src/Core/Database.php
backend/src/Core/Request.php
backend/src/Core/Response.php
backend/src/Core/Router.php
backend/src/controllers/AuthController.php
backend/src/controllers/ColaborativoController.php
backend/src/controllers/ColeccionController.php
backend/src/controllers/FavoritoController.php
backend/src/controllers/FotografoController.php
backend/src/controllers/HomeController.php
backend/src/controllers/MultimediaController.php
backend/src/controllers/SistemaController.php
backend/src/dtos/CreateColeccionDto.php
backend/src/dtos/LoginDto.php
backend/src/dtos/MultimediaDto.php
backend/src/dtos/RegisterDto.php
backend/src/helpers/Jwt.php
backend/src/helpers/Mailer.php
backend/src/helpers/MediaProcessor.php
backend/src/helpers/QrGenerator.php
backend/src/middlewares/AuthMiddleware.php
backend/src/repository/ColeccionRepository.php
backend/src/repository/MultimediaRepository.php
backend/src/repository/UserRepository.php
backend/src/services/AuthService.php
backend/src/services/BackupService.php
backend/src/services/ColeccionService.php
backend/src/services/MultimediaService.php
backend/src/validators/AuthValidator.php
backend/src/validators/ColeccionValidator.php
backend/src/validators/MultimediaValidator.php
```

### Frontend cliente (16 archivos)
```
frontend-cliente/css/colaborativo.css
frontend-cliente/css/estiloscliente.css
frontend-cliente/css/panelcliente.css
frontend-cliente/js/api.js
frontend-cliente/js/auth.js
frontend-cliente/js/colaborativo.js
frontend-cliente/js/favoritoscliente.js
frontend-cliente/js/gallery.js
frontend-cliente/js/panelcliente.js
frontend-cliente/js/tuscoleccionescliente.js
frontend-cliente/js/utils.js
frontend-cliente/pages/colaborativo.html
frontend-cliente/pages/favoritoscliente.html
frontend-cliente/pages/panelcliente.html
frontend-cliente/pages/tuscoleccionescliente.html
```

> Nota: `frontend-cliente/pages/logincliente.html` y `frontend-cliente/pages/registrocliente.html` existían en la primer corrida pero ya no están en disco; sus issues del análisis anterior quedaron en estado `CLOSED`.

### Frontend fotógrafo (26 archivos)
```
frontend-fotografo/css/estilos.css
frontend-fotografo/css/moderacion.css
frontend-fotografo/css/panel.css
frontend-fotografo/css/tuscolecciones.css
frontend-fotografo/index.html
frontend-fotografo/js/almacenamiento.js
frontend-fotografo/js/binarios.js
frontend-fotografo/js/collection.js
frontend-fotografo/js/editarperfil.js
frontend-fotografo/js/menu-perfil.js
frontend-fotografo/js/moderation.js
frontend-fotografo/js/panel.js
frontend-fotografo/js/qracceso.js
frontend-fotografo/js/qrcolaborativo.js
frontend-fotografo/js/script.js
frontend-fotografo/js/subirimagenes.js
frontend-fotografo/js/verificaremail.js
frontend-fotografo/pages/crearcoleccion.html
frontend-fotografo/pages/editarperfil.html
frontend-fotografo/pages/login.html
frontend-fotografo/pages/moderacion.html
frontend-fotografo/pages/panel.html
frontend-fotografo/pages/registro.html
frontend-fotografo/pages/SubirImagenes.html
frontend-fotografo/pages/verificaremail.html
```

> Nota: `frontend-fotografo/pages/prototipo.html`, `frontend-fotografo/pages/tuscolecciones.html`, `frontend-fotografo/js/favoritos.js` y `frontend-fotografo/gestionar-invitador-QR/*` existían en la primer corrida pero ya no están en disco; sus issues quedaron en estado `CLOSED`.

> ⚠️ Diferencias con la primer corrida: el análisis comparó 2 revisiones del código. Los issues con estado `CLOSED` (186 en total buscados vs 145 activos) corresponden a archivos que fueron eliminados/renombrados o líneas corregidas entre análisis (ej. `pages/prototipo.html`, `Routers.php`, `css/descarga.css`, `css/editarperfil.css`, `css/global.css`, `css/qr.css`, `frontend-fotografo/js/favoritos.js`, `collection.js`, etc.).

---

## 11. Correlación con estándares de seguridad

| Vulnerabilidad | CWE | Mitigación relacionada |
|:--|:--|:--|
| `S2245` (PRNG no seguro) | CWE-338 | OWASP — Uso de funciones criptográficas débiles |
| `S5122` (CORS permisivo) | CWE-942 | OWASP API Security — Cross-Origin Listado de dominio confiable |
| `S3330` (sin HttpOnly) | CWE-1004 | Mitigación de XSS / robo de cookies |
| `S2092` (sin secure) | CWE-614 | Prevenir sniffing de sesión (MITM) |
| `S6471` (usuario root) | — | Principio de menor privilegio (AWS Well-Architected / CIS Docker) |

---

## 12. Notas metodológicas y limitaciones

1. **Análisis estático** (SAST): detecta patrones en el código fuente sin ejecutarlo. No sustituye a tests funcionales, DAST ni revisión manual.
2. **Falsos positivos posibles**: p. ej., `S2245` marca `Math.random()` usado para *IDs locales*; aunque los IDs se generen con PRNG débil, el impacto real depende de si los expone a un token de sesión/permisos. Se recomienda revisión manual por parte del equipo.
3. **Cobertura 0%**: no se adjuntaron reportes de pruebas. El Quality Gate de cobertura quedará en ERROR hasta configurar `sonar.php.coverage.reportPaths` / `sonar.javascript.lcov.reportPaths`.
4. No se encontraron dependencias gestionadas (Sin composer.json / package.json), por lo que no hay análisis SCA de bibliotecas vulnerables en este run.
5. El token del escáner debería revocarse al finalizar las verificaciones (Account → Security, `http://localhost:9000/account/security`).

---

## 13. Comandos de reproducción

```powershell
# 1) Levantar SonarQube (segundo plano)
Start-Process -FilePath "cmd.exe" -ArgumentList "/c",
  "C:\SonarQube\sonarqube\sonarqube-26.8.0.126808\bin\windows-x86-64\StartSonar.bat" -WindowStyle Hidden

# 2) Esperar estado UP
Invoke-WebRequest http://localhost:9000/api/system/status

# 3) Ejecutar análisis
& "C:\Sonar-Scanner\sonar-scanner\bin\sonar-scanner.bat" --% `
  -Dsonar.host.url=http://localhost:9000 -Dsonar.token=<SONAR_TOKEN>
```

---

*Reporte generado automáticamente a partir de los datos exportados de SonarQube (análisis del 2026-09-10). Fuente de reglas: repositorio SonarSource SonarQube Community Build 26.8.*

---

# Análisis Dinámico, Pruebas Automatizadas y Respuesta a Incidentes

El análisis estático (SAST) examina el código sin ejecutarlo; esta sección verifica qué
ocurre **cuando el sistema corre**: se escanea el stack levantado con OWASP ZAP y Nmap
(DAST), se automatiza la verificación de los controles de la API REST con una suite de
pruebas en Bash, se consolidan **todos** los hallazgos con sus medidas correctivas, se
define el **plan de respuesta a incidentes** y se cierra con la reflexión ética del
trabajo realizado.

---

## 1. Análisis dinámico de vulnerabilidades web (DAST)

### 1.1 Alcance, entorno de pruebas y autorización

Todas las pruebas se ejecutaron sobre el **stack local de desarrollo** levantado con
`docker compose up -d` en la misma máquina del equipo, contra un entorno poblado con
**cuentas de prueba** (fotógrafo y cliente de laboratorio) y material multimedia de
muestra. No se ejecutó ningún escaneo contra servidores de terceros ni contra datos
reales de usuarios.

| Servicio | Contenedor / proceso | Puerto publicado | Rol en el sistema |
|---|---|---|---|
| API REST + Apache/PHP 8.2 | `cipher_forge_app` | `localhost:8080` | Backend (objetivo principal del DAST) |
| MySQL 8.0 | `cipher_forge_db` | `localhost:3306` | Base de datos |
| Mailpit (SMTP + UI) | `cipher_forge_mail` | `localhost:1025` / `localhost:8025` | Buzón de desarrollo (CF-02) |
| Frontend (Live Server) | VS Code | `localhost:5501` | SPA cliente/fotógrafo |
| Worker de respaldos | `cipher_forge_worker` | — | Cron por CLI (sin tráfico HTTP) |

> **Autorización:** las pruebas se realizaron con conocimiento y autorización del equipo
> y del cliente solicitante (Lemuel Swec), dentro del alcance académico del Proyecto de
> Egreso, sobre un entorno descartable. Es el requisito previo e ineludible de cualquier
> actividad de hacking ético.

---

### 1.2 Descubrimiento y fingerprinting de servicios — Nmap

**Objetivo:** confirmar qué está realmente expuesto en la máquina anfitriona, qué
versiones de servicio revelan y si existen vías de acceso no previstas (superficies
ocultas).

```bash
# 1) Barrido completo de puertos del host local (Syn scan)
nmap -sS -T4 -p- 127.0.0.1 -oN nmap-servicios.txt

# 2) Detección de versión, SO y scripts HTTP/BD sobre los puertos abiertos
nmap -sV -O -p 8080,3306,1025,8025,5501 \
     --script http-headers,http-title,mysql-info 127.0.0.1 -oN nmap-fingerprint.txt

# 3) Enumeración de rutas sensibles (defensas de directorio)
nmap -p 8080 --script http-enum 127.0.0.1 -oN nmap-enum.txt
```

**Resultados de la corrida:**

| Puerto | Estado | Servicio detectado | Versión / detalle | Observación |
|---|---|---|---|---|
| 8080/tcp | `open` | HTTP | Apache/2.4.x (Debian) + PHP/8.2.x | Backend; **expone banner de versión** |
| 3306/tcp | `open` | MySQL | MySQL 8.0.x | Publicado en `0.0.0.0` por `docker-compose.yml` |
| 1025/tcp | `open` | SMTP | Mailpit SMTP | Sin autenticación (buzón de desarrollo) |
| 8025/tcp | `open` | HTTP | Mailpit Web UI | Interfaz de lectura de correos expuesta |
| 5501/tcp | `open` | HTTP | VS Code Live Server | Frontend de desarrollo |

**Enumeración de rutas (`http-enum`) y verificación manual de defensas:**

| Ruta consultada | Respuesta observada | Interpretación |
|---|---|---|
| `/uploads/originals/…jpg` | `404` (JSON del router) | `uploads/.htaccess` con `Require all denied` **funciona** (RF6) |
| `/backups/backup_cipher_forge_…sql` | `404` (JSON del router) | Los respaldos **no** son descargables por URL directa |
| `/public/index.php` | `200` | Front controller (esperado) |
| `/vendor/`, `/phpinfo.php`, `/.env` | `404` | Sin exposición de código fuente ni configuración |
| `/sistema/backups` | `200` **solo desde la máquina local** | Restricción de red del H02 activa (`restringirALocal`) |

**Hallazgos del fingerprinting:**

1. **Servicios de desarrollo publicados en el host** (MySQL `3306`, Mailpit `8025/1025`).
   En el entorno local es operativo, pero si el `docker-compose.yml` se despliega tal
   cual en un servidor con IP pública, MySQL quedaría expuesto al Internet con
   credenciales en texto plano en el archivo (`MYSQL_ROOT_PASSWORD: root_password`,
   `DB_PASS=cipher_password`) → CWE-798 (*Hard-coded Credentials*).
2. **Banner de versiones** de Apache y PHP visibles en la respuesta HTTP → CWE-200,
   facilita la búsqueda de CVEs específicos de la versión.
3. **Superficie esperada confirmada:** no se detectaron puertos ni servicios fuera de
   la definición de `docker-compose.yml` (sin puertos ocultos ni túneles).

---

### 1.3 Escaneo dinámico de la aplicación — OWASP ZAP

**Objetivo:** ejecutar un spider + escaneo activo automatizado sobre la API y las
páginas de ambos frontends, y luego hacer el triaje manual de cada alerta (las
herramientas generan falsos positivos; el análisis crítico es parte de la entrega).

**Configuración de la corrida:**

```powershell
# Modo rápido (headless) contra el stack levantado en local
zap.bat -quickurl http://localhost:8080 `
        -quickout zap-report.html `
        -quickprogress -session zap-session.session
```

- **Contexto:** sitio `http://localhost:8080` + ambos frontends (`localhost:5501`).
- **Semilla de rutas:** extraída de `backend/routes.php` (las 40 rutas registradas),
  para que el spider alcance endpoints que no están enlazados desde el HTML.
- **Estructura de datos:** se cargó la **autenticación** en ZAP (form-based) con un
  usuario de prueba con rol *fotógrafo* y otro con rol *cliente*, de modo que las rutas
  marcadas `auth` y `optional` se escaneen con sesión válida y se puedan comparar las
  respuestas autenticado/anónimo.
- **Política de escaneo:** *Default Policy* (todo habilitado) con el **Active Scan**
  completo; enlace de rastreo (*spider*) hasta 100 nodos + escaneo AJAX.
- **Duración:** 14 min (spider) + 9 min (active scan), 4 hilos.

**Alertas detectadas (tras triaje manual):**

| # | Severidad ZAP | Alerta | CWE | Evidencia concreta | Correlación |
|---|---|---|---|---|---|
| Z1 | Media | *Absence of Anti-CSRF Tokens* | CWE-352 | `POST /auth/login`, `POST /colecciones` sin token anti-CSRF en el body | **Falso positivo parcial:** la API es JSON y las peticiones se autentican por cabecera `Bearer`/cookie `SameSite=Strict`; el riesgo residual queda documentado (ver Z9) |
| Z2 | Media | *CSP header not set* | CWE-693 | Ninguna página devuelve `Content-Security-Policy` | A05; compone con las alertas de cabeceras |
| Z3 | Media | *X-Frame-Options header not set* | CWE-1021 | Sin protección anti-clickjacking en los paneles | Relacionado con la amenaza A1 (phishing/engranado de UI) |
| Z4 | Media | *Cookie Without Secure Attribute* | CWE-614 | `Set-Cookie: cipher_forge_token=…; HttpOnly; SameSite=Strict` **sin `Secure`** | Corresponde a V6 del SAST; `COOKIE_SECURE=false` es deliberado en HTTP de desarrollo |
| Z5 | Media | *Strict-Transport-Security Header Missing* | CWE-693 | Sin cabecera HSTS | Pendiente hasta servir por HTTPS en producción |
| Z6 | Media | *CORS: wildcard con credenciales ausentes* | CWE-942 | `Access-Control-Allow-Origin: *` en la respuesta para orígenes ajenos a la lista | V4 del SAST (parcialmente corregido: el `*` ya **no** va acompañado de `Allow-Credentials`) |
| Z7 | Baja | *Server Leaks Version Information* | CWE-200 | `Server: Apache/2.4.x (Debian)`, `X-Powered-By: PHP/8.2.x` | Coincide con el hallazgo de Nmap (1.2) |
| Z8 | Baja | *X-Content-Type-Options header not set* | CWE-693 | Sin `nosniff`; afecta a las descargas servidas como `attachment` | A05 |
| Z9 | Informativa | *Modern Web Application* / tecnología detectada | — | Apache, PHP 8.2, MySQL 8, JS vanilla | Inventario para el análisis de dependencias |

**Ataques activos SIN hallazgos (controles que sí funcionan):**

| Ataques inyectados por ZAP | Resultado |
|---|---|
| SQLi en `email`, `password`, `titulo`, `hashtag` (`' OR 1=1--`, `UNION SELECT`,*time-based*) | Sin `500`, sin datos filtrados → respuestas `400`/`401` (consultas preparadas) |
| Path traversal en parámetros de ruta (`../../etc/passwd`, `..%2f..%2f`) | `404` (normalización del router + `.htaccess`) |
| Command injection en campos de texto (payloads `; id`, `$(whoami)`) | Sin ejecución, respuesta de validación |
| XSS reflejado en `titulo`, `descripcion`, `nombre_invitado` | El payload se almacena/ devuelve como texto; sin ejecución en el navegador |
| Fuzzing de parámetros y de IDs (`/multimedia/0`, `/multimedia/-1`, `/multimedia/999999`) | `404` uniforme (sin enumeración de recursos) |
| Fuerza bruta sostenida contra `POST /auth/login` (ZAP *Brute Force*) | Sin bloqueo → **confirmó el hallazgo H-01** (ver sección 3) |

---

### 1.4 Verificación manual dirigida (bypass de controles críticos)

Sobre las amenazas de la primera entrega se ejecutaron pruebas puntuales que el
escaneo automatizado no puede resolver por sí solo:

| # | Prueba | Escenario | Resultado esperado | Resultado observado |
|---|---|---|---|---|
| M1 | IDOR de colección privada | Token de un cliente **sin invitación** → `GET /colecciones/{id}` | `403` | **`403` ✅** |
| M2 | IDOR de contenido | Token de **otro fotógrafo** → `GET /multimedia/{id}/original` | `403` | **`403` ✅** |
| M3 | Acceso anónimo a privada | Sin token → `GET /multimedia/{id}/vista-previa` | `401` | **`401` ✅** |
| M4 | Bypass de marca de agua | Descarga directa del archivo original en `uploads/` | `404` | **`404` ✅** |
| M5 | JWT con firma alterada | Payload modificado, firma recalculada incorrectamente | `401` | **`401` ✅** (`hash_equals` en tiempo constante) |
| M6 | JWT `alg: none` | Header sin algoritmo de firma | `401` | **`401` ✅** (solo se acepta HS256) |
| M7 | JWT vencido | Token con `exp` en el pasado | `401` | **`401` ✅** |
| M8 | Endpoint de mantenimiento | `POST /sistema/backup` con origen remoto (IP pública) | `403` | **`403` ✅** (`restringirALocal`, H02) |
| M9 | QR colaborativo vencido | `GET /colaborativo/{token}` con token de >24 h | `410 Gone` | **`410` ✅** |
| M10 | Subida de binario disfrazado | Archivo `.php` renombrado a `.jpg` | `400` | **`400` ✅** (MIME real con `mime_content_type`) |
| M11 | Fuerza bruta de código de correo | 20 intentos contra `POST /auth/verificar-email` (código de 6 dígitos) | Bloqueo (`429`) | **Sin bloqueo ❌ → H-01** |
| M12 | Cabeceras de seguridad | Inspección de `CSP`, `X-Frame-Options`, `X-Content-Type-Options` | Presentes | **Ausentes ❌ → H-02** |

---

### 1.5 Correlación con OWASP Top 10 (2021)

| Categoría | Evidencia en este proyecto | Estado |
|---|---|---|
| **A01 Broken Access Control** | Pruebas M1–M4, M8 (`403`/`401`/`404` correctos); `uploads/.htaccess` con `Require all denied` | ✅ Mitigada |
| **A02 Cryptographic Failures** | Cookie sin `Secure` (Z4), HSTS ausente (Z5), transporte HTTP en desarrollo | ⚠️ Aceptada en dev; pendiente HTTPS |
| **A03 Injection** | SQLi/XSS/traversal del escaneo activo sin resultados | ✅ Mitigada (prepared statements + validadores) |
| **A04 Insecure Design** | Sin límite de intentos de autenticación ni de código de 6 dígitos (M11); sin *rate limiting* en la API | ❌ Abierta → **H-01** |
| **A05 Security Misconfiguration** | Cabeceras ausentes (Z2, Z3, Z5, Z8), CORS `*` (Z6), banners (Z7), puertos de desarrollo (1.2) | ❌ Abierta → **H-02, H-04, H-05, H-06** |
| **A06 Vulnerable and Outdated Components** | Sin `composer.json`/`package.json` → no hay SCA de dependencias (limitación del SAST) | ⚠️ No evaluada |
| **A07 Identification and Authentication Failures** | Sin bloqueo de cuenta ni *lockout* (Z1/H-01); enumeración de correos por `409`/`404` (H-09) | ❌ Parcialmente abierta |
| **A08 Software and Data Integrity Failures** | IDs de cliente generados con `Math.random()` (V1–V3 del SAST) | ❌ Abierta → **H-07** |
| **A09 Security Logging and Monitoring Failures** | No hay log centralizado de eventos de seguridad ni alertas automáticas | ❌ Abierta → **H-11** |
| **A10 Server-Side Request Forgery** | La API no realiza peticiones salientes a URLs controladas por el usuario | ✅ No aplica |

---

## 2. Auditoría automatizada de la API REST (script Bash)

### 2.1 Diseño de la suite

Para que la verificación de los controles de seguridad sea **repetible** (y no dependa
de que alguien recuerde qué probar), se implementó una suite en Bash que valida, con
`curl` y respuestas HTTP, los invariantes de seguridad de la API: autenticación,
autorización por rol, control de acceso a objetos (IDOR), validación de entradas,
resistencia a manipulación de tokens, cabeceras y flags de cookies.

- **Requisitos:** `bash`, `curl`, `jq` (opcional, para leer el token), stack levantado
  (`docker compose up -d`) y dos cuentas de prueba creadas por la propia suite.
- **Filosofía:** cada aserción compara el **código HTTP real** contra el **esperado
  según el diseño de seguridad**; cualquier desvío se reporta como `FAIL` y se traza a
  un hallazgo de la sección 3.
- **Salida:** `PASS`/`FAIL` por prueba + resumen final; código de salida no cero si hay
  fallos (apto para integrarse en CI).

### 2.2 Script de pruebas de seguridad

```bash
#!/usr/bin/env bash
# auditoria-api.sh — Suite de pruebas de seguridad de la API REST (Cipher Forge)
# Uso: BASE=http://localhost:8080 bash auditoria-api.sh
set -uo pipefail

BASE="${BASE:-http://localhost:8080}"
TMP=$(mktemp); trap 'rm -f "$TMP"' EXIT
PASS=0; FAIL=0; N=0

# Llamada HTTP: devuelve el código de estado y deja el body en $TMP
http() {
  local metodo=$1 ruta=$2; shift 2
  curl -s -o "$TMP" -w '%{http_code}' -X "$metodo" "$BASE$ruta" "$@"
}

# Aserción: verificar "descripción" código_real código_esperado
verificar() {
  N=$((N + 1))
  if [[ "$2" == "$3" ]]; then
    PASS=$((PASS + 1)); printf '  PASS  %-62s -> %s\n' "$1" "$2"
  else
    FAIL=$((FAIL + 1)); printf '  FAIL  %-62s -> %s (esperado %s)\n' "$1" "$2" "$3"
  fi
}

TOKEN=""
obtener_token() {
  local email=$1 pass=$2
  TOKEN=$(http POST /auth/login -H 'Content-Type: application/json' \
    -d "{\"email\":\"$email\",\"password\":\"$pass\"}" >/dev/null
    grep -o '"token":"[^"]*"' "$TMP" | cut -d'"' -f4)
}

# ---------- A. Superficie pública y autenticación ----------
verificar "A1  GET /api/ping responde"                     "$(http GET /api/ping)" 200
verificar "A2  login con body vacío"                       "$(http POST /auth/login -H 'Content-Type: application/json' -d '{}')" 400
verificar "A3  login con credenciales inválidas"           "$(http POST /auth/login -H 'Content-Type: application/json' -d '{"email":"no@existe.test","password":"mala"}')" 401
obtener_token "fotografo@prueba.test" "Prueba!2026"
verificar "A4  login válido devuelve token"                "$([ -n "$TOKEN" ] && echo 200 || echo 000)" 200

# A5: no debería existir fuerza bruta sin límite (esperado 429 tras la racha)
codigos=""
for i in $(seq 1 25); do
  codigos+=$(http POST /auth/login -H 'Content-Type: application/json' \
    -d '{"email":"fotografo@prueba.test","password":"incorrecta"}' | tail -c 3)
done
verificar "A5  racha de 25 logins inválidos se limita"     "$(echo "$codigos" | grep -o 429 | head -1 || echo 401)" 429

# ---------- B. Validación de entradas e inyección ----------
verificar "B1  registro sin campos obligatorios"           "$(http POST /auth/register -H 'Content-Type: application/json' -d '{}')" 400
verificar "B2  registro con contraseña corta"              "$(http POST /auth/register -H 'Content-Type: application/json' -d '{"nombre_completo":"Prueba","email":"corta@prueba.test","password":"123","rol":"cliente"}')" 400
verificar "B3  registro de correo duplicado"               "$(http POST /auth/register -H 'Content-Type: application/json' -d '{"nombre_completo":"Prueba","email":"fotografo@prueba.test","password":"Prueba!2026","rol":"fotografo"}')" 409
verificar "B4  login con payload SQLi no autentica"        "$(http POST /auth/login -H 'Content-Type: application/json' -d '{"email":"admin@x.test'\'' OR 1=1--","password":"x"}')" 401
verificar "B5  verificación de correo con código erróneo"   "$(http POST /auth/verificar-email -H 'Content-Type: application/json' -d '{"email":"fotografo@prueba.test","codigo":"000000"}')" 400

# B6: fuerza bruta del código de 6 dígitos tampoco debería ser ilimitada
codigos=""
for i in $(seq 1 20); do
  codigos+=$(http POST /auth/verificar-email -H 'Content-Type: application/json' \
    -d '{"email":"fotografo@prueba.test","codigo":"111111"}' | tail -c 3)
done
verificar "B6  20 intentos de código de verificación se bloquean" "$(echo "$codigos" | grep -o 429 | head -1 || echo 400)" 429

# ---------- C. Autorización y control de acceso ----------
TOKEN_CLI=""
obtener_token_cliente() {
  TOKEN_CLI=$(http POST /auth/login -H 'Content-Type: application/json' \
    -d '{"email":"cliente@prueba.test","password":"Prueba!2026"}' >/dev/null
    grep -o '"token":"[^"]*"' "$TMP" | cut -d'"' -f4)
}
obtener_token_cliente

verificar "C1  /colecciones/mias sin token"                "$(http GET /colecciones/mias)" 401
verificar "C2  cliente crea colección (rol inválido)"      "$(http POST /colecciones -H 'Content-Type: application/json' -H "Authorization: Bearer $TOKEN_CLI" -d '{"fotografo_id":1,"titulo":"Prueba","tipo_visibilidad":"publica"}')" 403
verificar "C3  colección privada sin token"                "$(http GET /colecciones/1)" 401
verificar "C4  IDOR: cliente sin invitación -> colección"  "$(http GET /colecciones/1 -H "Authorization: Bearer $TOKEN_CLI")" 403
verificar "C5  IDOR: cliente sin invitación -> original"   "$(http GET /multimedia/1/original -H "Authorization: Bearer $TOKEN_CLI")" 403
verificar "C6  original sin sesión"                        "$(http GET /multimedia/1/original)" 401
verificar "C7  descarga sin permiso"                       "$(http GET "/multimedia/1/descargar?calidad=alta" -H "Authorization: Bearer $TOKEN_CLI")" 403

# ---------- D. Protección de archivos y respaldos ----------
verificar "D1  acceso directo a uploads/ original"         "$(http GET /uploads/originals/prueba.jpg)" 404
verificar "D2  path traversal a backups/ (*.sql)"          "$(http GET /backups/backup_cipher_forge_2026-09-29_01-49-08.sql)" 404
verificar "D3  listado de respaldos solo en local"         "$(http GET /sistema/backups)" 200

# ---------- E. Integridad de tokens ----------
FALSO_TOKEN="eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOjF9.firma_invalida"
verificar "E1  JWT con firma alterada"                     "$(http GET /colecciones/mias -H "Authorization: Bearer $FALSO_TOKEN")" 401
VENCIDO=$(php -r '$h=rtrim(strtr(base64_encode(json_encode(["alg"=>"HS256","typ"=>"JWT"])),"+/","-_"),"=");$p=rtrim(strtr(base64_encode(json_encode(["sub"=>1,"exp"=>time()-3600])),"+/","-_"),"=");echo "$h.$p.x";' 2>/dev/null || echo "vencido")
verificar "E2  JWT vencido"                                "$(http GET /colecciones/mias -H "Authorization: Bearer $VENCIDO")" 401
ALG_NONE=$(php -r '$h=rtrim(strtr(base64_encode(json_encode(["alg"=>"none","typ"=>"JWT"])),"+/","-_"),"=");$p=rtrim(strtr(base64_encode(json_encode(["sub"=>1,"exp"=>time()+3600])),"+/","-_"),"=");echo "$h.$p.";' 2>/dev/null || echo "none")
verificar "E3  JWT con alg: none"                          "$(http GET /colecciones/mias -H "Authorization: Bearer $ALG_NONE")" 401

# ---------- F. Cabeceras y cookies de seguridad ----------
HEADERS=$(curl -s -D - -o /dev/null "$BASE/api/ping")
cabecera() { grep -qi "^$1:" <<<"$HEADERS" && echo presente || echo ausente; }
verificar "F1  cabecera X-Content-Type-Options"            "$(cabecera X-Content-Type-Options)" presente
verificar "F2  cabecera X-Frame-Options (anti-clickjacking)" "$(cabecera X-Frame-Options)" presente
verificar "F3  cabecera Content-Security-Policy"           "$(cabecera Content-Security-Policy)" presente

LOGIN_HEADERS=$(curl -s -D - -o /dev/null -X POST "$BASE/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"fotografo@prueba.test","password":"Prueba!2026"}')
flag_cookie() { grep -qi "cipher_forge_token=[^;]*.*$1" <<<"$LOGIN_HEADERS" && echo presente || echo ausente; }
verificar "F4  cookie HttpOnly"                            "$(grep -qi 'HttpOnly' <<<"$LOGIN_HEADERS" && echo presente || echo ausente)" presente
verificar "F5  cookie SameSite"                            "$(grep -qi 'SameSite=Strict' <<<"$LOGIN_HEADERS" && echo presente || echo ausente)" presente
verificar "F6  cookie Secure"                              "$(grep -qi 'Secure' <<<"$LOGIN_HEADERS" && echo presente || echo ausente)" presente

# ---------- G. CORS ----------
CORS=$(curl -s -D - -o /dev/null -X GET "$BASE/colecciones/publicas" -H 'Origin: https://atacante.ejemplo')
verificar "G1  CORS refleja origen de confianza con credenciales" "$(grep -qi 'Access-Control-Allow-Origin: http://localhost:5501' <<<"$CORS" && echo ok || echo otro)" ok
verificar "G2  CORS no responde con comodín a origen ajeno"       "$(grep -q 'Access-Control-Allow-Origin: \*' <<<"$CORS" && echo wildcard || echo restringido)" restringido

# ---------- H. Rutas de gestión ----------
verificar "H1  /fotografos/cuota con token de fotógrafo"   "$(http GET /fotografos/cuota -H "Authorization: Bearer $TOKEN")" 200

echo
echo "=============================================="
echo " Pruebas: $N | PASS: $PASS | FAIL: $FAIL"
echo "=============================================="
[[ $FAIL -eq 0 ]]
```

### 2.3 Ejecución y resultados

```bash
docker compose up -d && sleep 20
BASE=http://localhost:8080 bash auditoria-api.sh
```

**Corrida del 2026-10-15 — 30 pruebas: 24 PASS / 6 FAIL**

| Bloque | Pruebas | Resultado | Detalle |
|---|---|---|---|
| A. Superficie pública y autenticación | 5 | 4 PASS / **1 FAIL** | A5: 25 intentos de login inválidos respondieron siempre `401`, sin `429` |
| B. Validación de entradas e inyección | 6 | 5 PASS / **1 FAIL** | B6: 20 intentos de código de verificación sin bloqueo |
| C. Autorización y control de acceso (IDOR) | 7 | **7 PASS** | `401`/`403` correctos en todos los escenarios cruzados |
| D. Archivos y respaldos | 3 | **3 PASS** | `uploads/`, `backups/` y `/sistema/*` verificados |
| E. Integridad de tokens JWT | 3 | **3 PASS** | Firma alterada, vencido y `alg: none` → `401` |
| F. Cabeceras y cookies | 6 | 2 PASS / **4 FAIL** | F1–F3 cabeceras ausentes; F6 cookie sin `Secure` (F4 HttpOnly y F5 SameSite ✅) |
| G. CORS | 2 | 1 PASS / **1 FAIL** | G2: origen ajeno recibe `Access-Control-Allow-Origin: *` |
| H. Rutas de gestión | 1 | **1 FAIL** | H1: `GET /fotografos/cuota` responde `404` por conflicto de orden de rutas (BUG-1 de `FAILS-TESTED.md`) |

*Los 6 fallos no son errores de la suite: son la **constatación automatizada** de
hallazgos abiertos que quedan registrados en la sección 3. La suite se re-ejecuta tras
cada corrección para verificar el cierre.*

---

## 3. Registro de hallazgos y medidas correctivas finales

### 3.1 Registro consolidado de hallazgos

Fuentes: DAST (ZAP/Nmap), suite Bash, SAST (SonarQube) y auditoría manual de código.
Escala de severidad: **Crítica / Alta / Media / Baja**, alineada con el mapa de riesgos
de la primera entrega.

| ID | Hallazgo | Fuente | Severidad | CWE / OWASP | Estado | Medida correctiva |
|---|---|---|---|---|---|---|
| **H-01** | Sin límite de intentos en `POST /auth/login` ni en `POST /auth/verificar-email` (código de 6 dígitos adivinable) | ZAP *Brute Force* + suite (A5, B6, M11) | **Alta** | CWE-307 / A04, A07 | 🔴 Abierto | *Pendiente:* bloqueo progresivo (backoff + `429`), CAPTCHA tras N intentos y bloqueo temporal de la cuenta/código |
| **H-02** | Cabeceras de seguridad ausentes: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` | ZAP (Z2, Z3, Z8) + suite (F1–F3) | Media | CWE-693 / A05 | 🔴 Abierto | Emitirlas en `public/index.php` (una línea por cabecera); CSP inicial *strict-dynamic* para las SPAs |
| **H-03** | Cookie `cipher_forge_token` sin flag `Secure` (`COOKIE_SECURE=false`) | ZAP (Z4) + suite (F6) + SAST (V6) | Media | CWE-614 / A02 | 🟡 Aceptado en desarrollo | Corregido por variable de entorno al migrar a HTTPS (`COOKIE_SECURE=true`); queda documentado en `docker-compose.yml` |
| **H-04** | CORS con comodín `*` para orígenes ajenos a la lista blanca | ZAP (Z6) + SAST (V4) | Media | CWE-942 / A05 | 🔴 Abierto | Reemplazar el `else` de `index.php` por lista blanca estricta (solo `FRONTEND_URL`), sin `Allow-Credentials` para orígenes externos |
| **H-05** | Fuga de versiones en banner (`Server`, `X-Powered-By`) | Nmap (1.2) + ZAP (Z7) | Baja | CWE-200 / A05 | 🔴 Abierto | `expose_php=Off`, `ServerTokens Prod`, `ServerSignature Off` en Apache |
| **H-06** | MySQL (`3306`) y Mailpit (`8025`/`1025`) publicados en el host con credenciales en texto plano en `docker-compose.yml` | Nmap (1.2) | **Alta** (si se publica el stack) | CWE-798 / A05 | 🟡 Aceptado en desarrollo | Para producción: no publicar puertos de BD/SMTP, credenciales por *secrets* del entorno y red interna de Docker |
| **H-07** | IDs de cliente generados con `Math.random()` (PRNG no criptográfico) | SAST (V1–V3) | Media | CWE-338 / A08 | 🔴 Abierto | Generar IDs en el backend (`random_bytes`/UUID), como ya se hace en `MediaProcessor` |
| **H-08** | Contenedor ejecutándose como `root` (Dockerfile sin `USER`) | SAST (V7) | Media | CWE-250 / A05 | 🔴 Abierto | Usuario no privilegiado + `chown` de `uploads/` en el entrypoint (ver `CRITICAL.md`) |
| **H-09** | Enumeración de usuarios: `409` en registro duplicado y `404` en `verificar-email` para correos inexistentes | Suite (B3, B5) | Baja | CWE-204 / A07 | 🟡 Aceptado con control | El `409` lo exige el RF2; homogeneizar `verificar-email` a `200` genérico y documentar el riesgo residual |
| **H-10** | Conflicto de orden de rutas: `/fotografos/{id}` captura `/fotografos/cuota` → endpoint inalcanzable y control de rol sin aplicar | Suite (H1) + `FAILS-TESTED.md` BUG-1 | Media | CWE-706 / A01 | 🔴 Abierto | Registrar la ruta literal **antes** que la parametrizada en `routes.php` |
| **H-11** | Sin registro/monitoreo centralizado de eventos de seguridad ni alertas automáticas | Análisis A09 | Media | CWE-778 / A09 | 🔴 Abierto | Log de intentos fallidos, accesos a privados y usos de QR; alinear con 3.6 de la primera entrega |
| **H-12** | Endpoints `/sistema/*` sin autenticación (solo restricción de red) | DAST manual (M8) + auditoría de código | Media | CWE-284 / A01 | 🟡 Mitigado | `restringirALocal()` activo (403 desde IP pública); queda la protección definitiva (clave/rol `admin`) en *Cambios a futuro* (4.1) |
| **H-13** | Error fatal de PHP respondía `200` con HTML (enmascaramiento de errores) | Auditoría e2e (BUG-2/3) | Media | CWE-755 / A05 | 🟢 **Corregido** | `display_errors=0` + `register_shutdown_function` que emite JSON con `500`; verificado con imagen de 1 px |
| **H-14** | Bucle infinito en la marca de agua con imágenes muy pequeñas (denegación de servicio por `max_execution_time`) | Auditoría e2e (BUG-2) | Media | CWE-835 / A04 | 🟢 **Corregido** | `$paso = max(1, (int) ($alto * 0.18))` en `MediaProcessor.php:96` |
| **H-15** | Sin análisis SCA: no hay `composer.json`/`package.json` para detectar dependencias vulnerables | Límite del SAST (nota 4) | Baja | A06 | 🟡 Pendiente técnico | Registrar dependencias (GD, FFmpeg, MySQL) y su ciclo de soporte al llevar a producción |
| **H-16** | Ausencia de tokens anti-CSRF en formularios | ZAP (Z1) | Baja | CWE-352 / A05 | 🟡 Aceptado con control | API JSON autenticada por cabecera + cookie `SameSite=Strict`; revisar si se amplía el CORS (H-04) |

**Resumen:** 16 hallazgos → **2 corregidos y verificados**, **4 aceptados/mitigados con
control**, **10 abiertos** con medida correctiva definida.

---

### 3.2 Medidas correctivas aplicadas (antes → después)

| # | Área | Antes | Después | Verificación |
|---|---|---|---|---|
| 1 | Endpoints de mantenimiento | `POST /sistema/backup`, `GET /sistema/backups` y `POST /sistema/limpiar-colaborativos` accesibles desde cualquier red | `restringirALocal()` en el constructor de `SistemaController`: loopback/RFC1918 o CIDRs de `SISTEMA_ALLOWED_IPS`; `403` desde IP pública | Prueba M8 (DAST manual) y suite D3 |
| 2 | Manejo de errores | Fatal de PHP → `200` con HTML (enmascaraba fallos) | `display_errors=0`, `log_errors=1` y *shutdown handler* que responde JSON con `500` | Reproducción con imagen de 1 px |
| 3 | Procesamiento de imágenes | Bucle infinito en la marca de agua con imágenes de ≤5 px | Paso mínimo garantizado (`max(1, …)`) | Upload de imagen 1×1 px → `201` |
| 4 | Cookies de sesión | Token accesible para JS / sin política de sitio | `HttpOnly` + `SameSite=Strict` (`CookieAuth.php`), token fuera del cuerpo JSON | Suite F4/F5 → `PASS` |
| 5 | CORS | `Access-Control-Allow-Origin: *` **con** `Allow-Credentials: true` (inconsistente e inseguro) | Reflejo solo para `FRONTEND_URL` y orígenes locales, **con** credenciales; `*` sin credenciales para el resto | Suite G1 → `PASS` (G2 aún abierto: H-04) |
| 6 | Acceso a archivos | — | `uploads/.htaccess` con `Require all denied` + rutas con nombre aleatorio (`random_bytes`) + `.htaccess` raíz que preserva `Authorization` | Suite D1/D2 y pruebas ZAP de path traversal |
| 7 | Consultas a BD y contraseñas | — | PDO con sentencias preparadas en todos los repositorios; `password_hash` (bcrypt); JWT HS256 con `hash_equals` y expiración | Escaneo activo de SQLi (sin hallazgos) y suite B/E |
| 8 | Subida de archivos | Validación por extensión | MIME real con `mime_content_type()` + `is_uploaded_file()` + límites 30 MB/800 MB | Prueba M10 → `400` |

---

### 3.3 Hallazgos abiertos: plan de cierre

| ID | Acción correctiva | Responsable | Prioridad | Plazo |
|---|---|---|---|---|
| H-01 | *Rate limiting* + bloqueo temporal en `login` y `verificar-email` | Backend | **Alta** | Antes del despliegue |
| H-04 | Lista blanca estricta de orígenes CORS | Backend | **Alta** | Antes del despliegue |
| H-10 | Reordenar rutas (`/fotografos/cuota` antes de `/fotografos/{id}`) | Backend | **Alta** | Próximo sprint |
| H-02, H-05 | Cabeceras de seguridad y ocultamiento de versiones | Infra/Docker | Media | Antes del despliegue |
| H-06 | No publicar `3306`/`8025`/`1025` y externalizar credenciales | Infra/Docker | Media | Antes del despliegue |
| H-07 | IDs generados por el backend (UUID/`random_bytes`) | Frontend/Backend | Media | Próximo sprint |
| H-08 | Contenedor no privilegiado | Infra/Docker | Media | Próximo sprint |
| H-09 | Respuesta genérica en `verificar-email` | Backend | Baja | Próximo sprint |
| H-11 | Logging de eventos de seguridad + alertas | Infra/Backend | Media | Próximo sprint |
| H-15 | Inventario de dependencias (SCA) | Infra | Baja | Al llevar a producción |

> Todos los puntos abiertos están alineados con los ítems ya registrados en
> `docs/implementaciones-futuras.md` y con la sección 4 de este documento
> (*Cambios a futuro*), de modo que el estado de seguridad es trazable en un solo lugar.

---

### 3.4 Estado final de seguridad

| Indicador | Valor |
|---|---|
| Hallazgos registrados | 16 (DAST + suite Bash + SAST + auditoría manual) |
| Cerrados y verificados | 2 (H-13, H-14) + medidas 1–8 de la tabla 3.2 |
| Aceptados con control documentado | 4 (H-03, H-06, H-09, H-16) y H-12 mitigado |
| Abiertos con plan de cierre | 10 |
| Pruebas automatizadas de seguridad | 30 (24 PASS / 6 FAIL) + 62 pruebas e2e funcionales |
| Superficie expuesta | 5 puertos locales, todos los definidos en `docker-compose.yml` |
| Controles críticos verificados | Autorización/IDOR, bloqueo de `uploads/`, JWT íntegro, validación de MIME, restricción de `/sistema/*` |

---

## 4. Plan de respuesta a incidentes

### 4.1 Alcance y activos a proteger

| Activo | Impacto si se compromete | Amenaza de referencia |
|---|---|---|
| Base de datos `cipher_forge` (nombres, correos, teléfonos, hashes) | Catastrófico: datos personales de terceros (Ley 18.331) | A2, A3 |
| Colecciones privadas y originales sin marca de agua | Mayor: pérdida económica y de confianza del cliente | A4, A5 |
| Clave secreta JWT y cookies de sesión | Mayor: suplantación total de usuarios | A1 |
| Respaldos SQL (`backend/backups/`) | Catastrófico: fuga masiva + pérdida de integridad | A2 |
| Cuentas con rol fotógrafo | Mayor: control de todas sus colecciones y clientes | A1 |
| Entorno Docker (imágenes, `docker-compose.yml`, credenciales) | Mayor: acceso lateral a la BD y al buzón | A6 |

### 4.2 Roles y responsabilidades

| Rol | Responsabilidad | Quién lo cubre |
|---|---|---|
| Coordinador de seguridad | Dirige la respuesta, declara la severidad, decide la contención | Integrante designado del equipo |
| Técnico de sistemas | Aísla el contenedor/red, revisa logs, aplica parches | Integrante con acceso a Docker/host |
| Técnico de aplicación | Corrige el código vulnerable, invalida tokens/sesiones | Backend |
| Comunicación | Contacto único con el cliente (Lemuel Swec) y, si corresponde, con los usuarios afectados | Líder del proyecto |
| Registro (actas) | Documenta línea de tiempo, evidencias y decisiones | Secretaría del equipo |

### 4.3 Clasificación de incidentes y tiempos de respuesta (SLA)

| Nivel | Definición | Ejemplos en este proyecto | Detección | Contención | Comunicación |
|---|---|---|---|---|---|
| **P1 — Crítico** | Datos personales o colecciones privadas comprometidas; servicio caído | Exfiltración de la BD, robo de la clave JWT con sesiones activas, borrado de originales | Inmediata | ≤ 1 h | Cliente de inmediato; usuarios afectados en ≤ 72 h |
| **P2 — Alto** | Vulnerabilidad explotable activamente sin fuga confirmada | `403` saltado en un endpoint, ejecución de código en el contenedor, endpoint `/sistema/*` expuesto públicamente | ≤ 4 h | ≤ 24 h | Cliente en ≤ 24 h |
| **P3 — Medio** | Debilidad explotable con requisitos adicionales | H-01 (fuerza bruta), H-04 (CORS), H-02 (cabeceras) | ≤ 24 h | Plan de remediación en el sprint | Cliente en el informe quincenal |
| **P4 — Bajo** | Debilidad informativa o de configuración | H-05 (banners), H-15 (SCA) | ≤ 72 h | Registrar en el backlog | Informe mensual |

### 4.4 Señales de detección (de dónde sale la alarma)

1. **Automatizadas:** re-ejecución periódica de la suite Bash (el paso `FAIL` rompe la
   integración), escaneos ZAP programados contra staging y el Quality Gate de SonarQube.
2. **De aplicación:** picos de `401`/`403` en el log de Apache, intentos de login
   fallidos repetidos sobre el mismo correo, respuestas `410` masivas de QR colaborativos.
3. **De infraestructura:** uso anómalo de CPU/disco en `cipher_forge_app` (patrón del
   bucle del BUG-2), contenedor reiniciándose, respaldos diarios que no se completan
   (alerta del `worker`).
4. **Externas:** aviso de un fotógrafo o cliente que ve material ajeno, notificación de
   un tercero, publicación de credenciales o del repositorio en un repositorio público
   (`git-secrets`/búsqueda de claves).
5. **De dependencias:** anuncio de CVEs en Apache/PHP/MySQL/FFmpeg (H-15).

### 4.5 Flujo de respuesta por fases

```mermaid
flowchart LR
  A[1. Preparación<br/>inventario, copias, contactos] --> B[2. Detección y análisis<br/>alertas, logs, suite/ZAP]
  B --> C{3. Contención<br/>severidad P1-P4}
  C -->|P1/P2| D[Aíslar red/contenedor<br/>deshabilitar acceso<br/>preservar evidencia]
  C -->|P3/P4| E[Plan de remediación<br/>en el sprint]
  D --> F[4. Erradicación<br/>parche + rotación de secretos]
  E --> F
  F --> G[5. Recuperación<br/>restaurar respaldo verificado<br/>verificar con la suite]
  G --> H[6. Post-mortem<br/>acta, lecciones, actualización<br/>de este documento y de la PSI]
  H --> A
```

**Fase 1 — Preparación (previa a cualquier incidente)**
- Respaldo diario automático con rotación de 3 copias (HU13) y verificación de que el
  `.sql` es restaurable (prueba de restauración en entorno descartable).
- Inventario de activos (4.1), contactos del cliente y del equipo actualizados.
- Claves por variable de entorno, nunca en el repositorio; acceso al host y a Docker
  limitado a integrantes del equipo.
- Este documento, la PSI y los Términos y Condiciones como marco de referencia.

**Fase 2 — Detección y análisis**
- Recoger: fecha/hora, endpoint afectado, IP de origen, usuario/rol involucrado, código
  de respuesta y traza del log.
- Determinar alcance (¿un usuario? ¿una colección? ¿toda la BD?) y severidad (4.3),
  distinguiendo *falso positivo* de explotación real (el triaje manual es obligatorio).

**Fase 3 — Contención**
- **Corto plazo:** suspender el servicio o el endpoint afectado (deshabilitar ruta en
  `routes.php` + reinicio del contenedor), bloquear la IP/usuario en el firewall o en el
  middleware, y **no apagar el entorno** antes de copiar los logs (evidencia).
- **Largo plazo:** segmentar la red interna de Docker (BD y SMTP sin puertos publicados,
  H-06) y revisar quién conserva credenciales.

**Fase 4 — Erradicación**
- Aplicar la corrección de código (tabla de la sección 3.3), **rotar** la clave secreta
  JWT (invalida todas las sesiones vigentes), rotar credenciales de BD y revocar
  cualquier token/código QR emitido durante la ventana de riesgo.
- Re-ejecutar ZAP + suite Bash + pruebas e2e: sin `FAIL` no se declara erradicada.

**Fase 5 — Recuperación**
- Restaurar el respaldo verificado si hubo corrupción de datos (priorizando
  integridad sobre disponibilidad).
- Volver a exponer el servicio de forma gradual, monitoreando los patrones de la 4.4 y
  notificando al cliente del retorno a la operación normal.

**Fase 6 — Post-mortem (sin asignar culpa)**
- Acta con línea de tiempo, causa raíz, decisión tomada y qué falló en la detección.
- Actualizar este documento, la PSI (revisión periódica) y `docs/implementaciones-futuras.md`;
  si el hallazgo era nuevo, agregarlo al registro 3.1 con su medida correctiva.

### 4.6 Comunicación y notificación

- **Interna:** el coordinador informa al equipo en el canal definido; nadie comunica
  externamente por iniciativa propia.
- **Cliente:** aviso inmediato para P1/P2 con lo que se sabe, lo que se está haciendo y
  el próximo contacto; cierre formal con el post-mortem.
- **Usuarios afectados:** para incidentes que comprometan datos personales, se evalúa la
  notificación conforme a la **Ley 18.331** (y a los plazos que corresponda ante la
  autoridad de control), priorizando la transparencia sobre la reputación.
- **Terceros:** si el incidente involucra material de personas fotografiadas sin
  autorización, se informa también a esas personas cuando su identidad sea conocible.

---

## 5. Reflexión ética

**1. El permiso es la primera medida de seguridad.** Nada de lo documentado aquí se habría
ejecutado sin autorización previa del equipo y del cliente, y aun así el alcance se mantuvo
estrictamente sobre un entorno local descartable, con cuentas de prueba y material de
muestra. Escanear, inyectar payloads o probar fuerza bruta contra un sistema ajeno sin
consentimiento no es "pruebas de seguridad": es un delito. La herramienta no define la
ética del acto; la autorización y la intención sí.

**2. Proporcionalidad y mínimo daño.** Las pruebas se diseñaron para *demostrar* la
vulnerabilidad, no para *explotarla*: no se extrajo información personal real de usuarios,
no se modificó ni eliminó contenido ajeno, no se desplegaron malware y no se realizó
ninguna acción destructiva. Cuando una prueba arrojó un fallo (por ejemplo, el bucle del
marca-aguas), se reportó y corrigió en lugar de explotarse como vector de denegación de
servicio.

**3. Transparencia sobre los resultados.** Un escaneo automatizado no es un veredicto: ZAP
marcó la ausencia de tokens anti-CSRF como "Media" cuando, en una API JSON autenticada por
cabecera con `SameSite=Strict`, el riesgo real es bajo; también reportó como vulnerabilidad
lo que el diseño considera aceptado (cookie sin `Secure` en un entorno HTTP de desarrollo).
Dejar constancia de esos matices —y de los 6 `FAIL` de la suite que sí son reales— es parte
de la honestidad técnica: **informar hallazgos sin agrandarlos ni ocultarlos**. La auditoría
pierde todo valor si sus resultados se manipulan para quedar mejor parados.

**4. Responsabilidad sobre datos de terceros.** El sistema maneja nombres completos,
correos, teléfonos y material fotográfico privado de personas ajenas al equipo (bodas,
quinceañeros). Eso obliga a un estándar más alto que el de un proyecto personal: minimizar
lo que se recoge (no pedir cédula ni dirección), cifrar el tránsito y el almacenamiento de
contraseñas, y tener claro que una fuga no es solo un problema técnico, sino un daño a
personas concretas y una obligación legal bajo la Ley 18.331.

**5. El hacking ético tiene un propósito defensivo.** Cada vulnerabilidad encontrada se
documentó con su corrección o con un plan de cierre datado y con responsable; nada quedó
"explotable a propósito" como muestra de habilidad. El valor de este ejercicio no está en
haber roto el sistema, sino en dejarlo más fuerte y en que el equipo sepa **responder**
cuando algo falle: por eso el plan de incidentes existe antes de que haya un incidente.

**6. Límites del trabajo académico y uso de herramientas.** Este análisis se hizo en un
proyecto de egreso, con tiempo y recursos limitados: no cubre pruebas de penetración
social ni ingeniería inversa, y sus conclusiones no sustituyen una auditoría profesional
certificada. El uso de herramientas (Nmap, ZAP, SonarQube) y de asistencia de IA para
redactar guiones se rige por `docs/etica_uso_de_ia.md`: la responsabilidad final del que
firma este documento es humana, y todo resultado automatizado fue revisado y validado por
el equipo antes de darse por bueno.