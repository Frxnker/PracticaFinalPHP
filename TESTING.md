# Plan de pruebas – Tablón de anuncios

Pruebas manuales de las funcionalidades principales: registro, login, publicaciones, comentarios y seguridad.

- **Entorno:** Docker (PHP 8.3 + Apache, MySQL 8.0), navegador en `http://localhost:8080`
- **Fecha de ejecución:** _rellenar_
- **Usuarios de prueba:**
  - **Usuario A:** `ana@test.com` / `ana` / `123456`
  - **Usuario B:** `berto@test.com` / `berto` / `123456`

---

## 1. Prompt utilizado

El prompt de ejemplo del enunciado es demasiado genérico: la IA no conoce la aplicación, así que inventa funcionalidades que no existen y no prueba las reglas importantes (autorización, CSRF, validaciones). Este es el prompt mejorado que he usado:

> Actúa como tester de QA. Tengo una aplicación web en PHP 8.3 + MySQL (MVC, sin framework) que es un tablón de anuncios con estas funcionalidades y reglas:
>
> - **Registro** con email (válido y único), username (único, máx. 50 caracteres) y contraseña (mín. 6 caracteres, guardada con `password_hash`).
> - **Login** por email y contraseña; guarda `user_id` y `email` en la sesión. **Logout** destruye la sesión.
> - **Publicaciones** con título (obligatorio, máx. 150), categoría (obligatoria, máx. 50) y contenido (obligatorio). Cualquiera puede verlas; solo un usuario logueado puede crear; solo el autor puede editar o borrar (si no, 403). Hay un buscador por título o categoría con `LIKE`.
> - **Comentarios** en el detalle de cada publicación; solo usuarios logueados; el `user_id` sale de la sesión.
> - **Seguridad:** sentencias preparadas, `htmlspecialchars` en las vistas y token CSRF en todos los formularios POST (si falta o no coincide, 403).
> - Las rutas son `index.php?action=...` (`register`, `login`, `logout`, `index`, `show`, `create`, `edit`, `destroy`, `comment`) y el id va en `&id=`.
>
> Genera unos 30 casos de prueba agrupados por funcionalidad. Incluye casos normales, casos límite (valores en el límite exacto, vacíos, muy largos, ids inexistentes) y casos de error o ataque (manipular formularios con las herramientas de desarrollador, cambiar ids en la URL, XSS, inyección SQL, CSRF). Para cada caso indica: un id, qué se prueba, los pasos exactos para reproducirlo en el navegador y el resultado esperado concreto (mensaje, redirección o código HTTP). Formato: una tabla Markdown por funcionalidad, con columnas para el resultado obtenido y si pasa, que dejarás vacías.

Después de generarlos, he revisado los casos y he ajustado los resultados esperados a los mensajes reales de la aplicación.

---

## 2. Registro

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| R1 | Registro correcto | `?action=register` → rellenar Usuario A → Registrarse | Redirige al login. En phpMyAdmin aparece el usuario con `password` empezando por `$2y$` | | |
| R2 | Email repetido | Registrar otro usuario con `ana@test.com` | Mensaje *"Ese email o nombre de usuario ya está registrado."* y no se crea | | |
| R3 | Email repetido en mayúsculas | Registrar con `ANA@test.com` y otro username | Mismo mensaje que R2 (MySQL compara sin distinguir mayúsculas) | | |
| R4 | Username repetido | Registrar con otro email y username `ana` | Mismo mensaje que R2 | | |
| R5 | Contraseña de 5 caracteres | Registrar con contraseña `12345` | Mensaje *"La contraseña debe tener al menos 6 caracteres."* | | |
| R6 | Contraseña de 6 caracteres (límite) | Registrar con contraseña `123456` | Registro correcto | | |
| R7 | Email no válido (saltando el HTML) | F12 → cambiar `type="email"` a `type="text"` → email `hola` | Mensaje *"Email no válido."* | | |
| R8 | Username de 51 caracteres | F12 si hace falta → username de 51 caracteres | Mensaje de error de validación, sin error fatal | | |

## 3. Login y logout

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| L1 | Login correcto | `?action=login` → datos del Usuario A | Redirige al listado; la cabecera muestra `ana@test.com`, "Publicar" y "Cerrar sesión" | | |
| L2 | Contraseña incorrecta | Email del Usuario A con contraseña `000000` | *"Email o contraseña incorrectos."* | | |
| L3 | Email que no existe | `noexiste@test.com` / `123456` | El mismo mensaje que L2 (no revela qué emails existen) | | |
| L4 | Logout | Con sesión iniciada → "Cerrar sesión" | Vuelve al listado y la cabecera muestra "Iniciar sesión" y "Registrarse" | | |
| L5 | Volver atrás tras logout | Tras L4, pulsar Atrás en el navegador hasta el formulario de publicar y enviarlo | No se publica nada: 403 por CSRF o redirección al login | | |

## 4. Publicaciones

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| P1 | Crear sin sesión | Sin sesión → `?action=create` | Redirige al login con *"Tienes que iniciar sesión para publicar."* | | |
| P2 | Crear correctamente | Como Usuario A → Publicar → título, categoría y contenido | Va al detalle con *"Publicación creada."*; en la BD el `user_id` es el de A | | |
| P3 | Título vacío (saltando el HTML) | F12 → quitar `required` del título → enviar con título vacío | *"El título es obligatorio."* y el formulario conserva lo escrito | | |
| P4 | Título de 150 caracteres (límite) | Título de exactamente 150 caracteres | Se crea correctamente | | |
| P5 | Título de 151 caracteres | F12 → quitar `maxlength` → título de 151 caracteres | *"El título no puede superar 150 caracteres."* | | |
| P6 | Categoría de 51 caracteres | F12 → quitar `maxlength` → categoría de 51 caracteres | Mensaje de error de validación, sin error fatal | | |
| P7 | Ver un post inexistente | `?action=show&id=9999` | Página *"404 – No encontrado"* | | |
| P8 | Id no numérico | `?action=show&id=abc` | Página 404, sin errores de PHP | | |
| P9 | Editar post propio | Como A → abrir su post → Editar → cambiar título | *"Publicación actualizada."* y se ve el título nuevo | | |
| P10 | Editar post ajeno por URL | Como B → `?action=edit&id=<id de un post de A>` | *"403 – Prohibido"* | | |
| P11 | Botones en post ajeno | Como B → abrir un post de A | No aparecen los botones Editar ni Borrar | | |
| P12 | Borrar post ajeno manipulando el formulario | Como A, abrir un post propio; con F12 cambiar el `action` del formulario de Borrar al id de un post de B | *"403 – Prohibido"* y el post de B sigue existiendo | | |
| P13 | Borrar por GET | Como autor → `?action=destroy&id=<id propio>` en la barra de direcciones | No se borra; se muestra el listado | | |
| P14 | Borrar post propio | Como autor → Borrar → Aceptar | *"Publicación eliminada."* y desaparece del listado | | |

## 5. Búsqueda

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| B1 | Buscar por título | Buscar una palabra del título de un post | Sale solo ese post (y los que coincidan) | | |
| B2 | Buscar por categoría | Buscar una categoría existente | Salen los posts de esa categoría | | |
| B3 | Sin resultados | Buscar `xyzxyz` | *"No hay publicaciones que coincidan con la búsqueda."* | | |
| B4 | Mayúsculas | Buscar el título en MAYÚSCULAS | Mismos resultados que en minúsculas | | |
| B5 | Inyección SQL | Buscar `' OR 1=1 -- ` | No hay error SQL; se busca como texto literal | | |

## 6. Comentarios

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| C1 | Sin sesión | Abrir un post sin sesión | Se ven los comentarios; en lugar del formulario aparece *"Inicia sesión para comentar."* | | |
| C2 | Comentar correctamente | Como B → comentar en un post de A | *"Comentario publicado."*; aparece con el nombre `berto` y la fecha | | |
| C3 | Orden | Escribir 3 comentarios seguidos | Aparecen del más antiguo al más nuevo | | |
| C4 | Comentario solo con espacios | F12 → quitar `required` → enviar solo espacios | *"El comentario no puede estar vacío."* y no se guarda | | |
| C5 | Suplantar a otro usuario | F12 → añadir `<input name="user_id" value="1">` al formulario → comentar | En la BD el `user_id` es el del usuario logueado, no 1 | | |
| C6 | Comentar en post inexistente | F12 → cambiar el `action` a `?action=comment&id=9999` | Página 404 y no se guarda nada | | |
| C7 | Borrado en cascada | Borrar un post que tenga comentarios | Se borra sin error y sus comentarios desaparecen de la tabla `comments` | | |

## 7. Seguridad (XSS y CSRF)

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| S1 | XSS en el título | Crear un post con título `<script>alert(1)</script>` | Se ve el texto tal cual; no salta ninguna alerta ni en el listado ni en el detalle | | |
| S2 | XSS en un comentario | Comentar `<b>hola</b>` | Se ve `<b>hola</b>` como texto, no en negrita | | |
| S3 | Token CSRF presente | F12 → inspeccionar cualquier formulario POST | Hay un `<input type="hidden" name="csrf_token">` de 64 caracteres, igual en todos | | |
| S4 | Token manipulado | F12 → cambiar una letra del token del formulario de comentar → enviar | *"403 – Petición no válida"* y no se guarda | | |
| S5 | Sin token | F12 → borrar el campo del token del formulario de Borrar → enviar | 403 y el post sigue existiendo | | |
| S6 | Token nuevo por sesión | Anotar el token → logout → login → mirar el token | El token es distinto | | |
| S7 | Archivos privados | Abrir `http://localhost:8080/config/config.php` y `http://localhost:8080/src/` | 404: Apache solo sirve `public/` | | |

---

## 8. Resumen de resultados

| Total | Pasan | Fallan |
|---|---|---|
| 46 | _rellenar_ | _rellenar_ |

### Fallos encontrados y corrección

_Por cada caso que falle: qué ocurrió, por qué y cómo se ha corregido (o si queda pendiente)._

| ID | Problema | Causa | Corrección |
|---|---|---|---|
| | | | |
