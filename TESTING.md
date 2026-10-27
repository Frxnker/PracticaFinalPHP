# Plan de pruebas – Tablón de anuncios

Pruebas manuales de las funcionalidades principales: registro, login, publicaciones, comentarios y seguridad.

- **Entorno:** Docker (PHP 8.3 + Apache, MySQL 8.0), navegador en `http://localhost:8080`
- **Fecha de ejecución:** 27/10/2026
- **Cómo se han ejecutado:** con ayuda de IA (Claude), que automatizó cada caso en un navegador Chromium siguiendo exactamente los pasos de la tabla, incluidas las manipulaciones con las herramientas de desarrollador (quitar `required`/`maxlength`, cambiar el `action`, editar o borrar el token). Se ejecutaron sobre una copia del proyecto con la misma configuración que el `docker-compose.yml`: PHP 8.3 + Apache con la raíz en `public/`, MySQL 8.0 con la base de datos creada desde `sql/init.sql` y `composer dump-autoload` para generar `vendor/`. Los resultados de la BD se comprobaron con consultas SQL.
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
| R1 | Registro correcto | `?action=register` → rellenar Usuario A → Registrarse | Redirige al login. En phpMyAdmin aparece el usuario con `password` empezando por `$2y$` | Redirige a `?action=login`; en la BD el password es un hash de 60 caracteres que empieza por `$2y$10$` | ✅ |
| R2 | Email repetido | Registrar otro usuario con `ana@test.com` | Mensaje *"Ese email o nombre de usuario ya está registrado."* y no se crea | Muestra *"Ese email o nombre de usuario ya está registrado."*; no se crea el usuario | ✅ |
| R3 | Email repetido en mayúsculas | Registrar con `ANA@test.com` y otro username | Mismo mensaje que R2 (MySQL compara sin distinguir mayúsculas) | Muestra el mismo mensaje que R2; no se crea el usuario | ✅ |
| R4 | Username repetido | Registrar con otro email y username `ana` | Mismo mensaje que R2 | Muestra el mismo mensaje que R2; no se crea el usuario | ✅ |
| R5 | Contraseña de 5 caracteres | Registrar con contraseña `12345` | Mensaje *"La contraseña debe tener al menos 6 caracteres."* | Muestra *"La contraseña debe tener al menos 6 caracteres."* | ✅ |
| R6 | Contraseña de 6 caracteres (límite) | Registrar con contraseña `123456` | Registro correcto | Registro correcto; redirige al login | ✅ |
| R7 | Email no válido (saltando el HTML) | F12 → cambiar `type="email"` a `type="text"` → email `hola` | Mensaje *"Email no válido."* | Muestra *"Email no válido."* | ✅ |
| R8 | Username de 51 caracteres | F12 si hace falta → username de 51 caracteres | Mensaje de error de validación, sin error fatal | Sale `Fatal error: Uncaught PDOException: SQLSTATE[22001] ... Data too long for column 'username'` y no se crea el usuario | ❌ |

## 3. Login y logout

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| L1 | Login correcto | `?action=login` → datos del Usuario A | Redirige al listado; la cabecera muestra `ana@test.com`, "Publicar" y "Cerrar sesión" | Redirige al listado; la cabecera muestra `ana@test.com`, "Publicar" y "Cerrar sesión" | ✅ |
| L2 | Contraseña incorrecta | Email del Usuario A con contraseña `000000` | *"Email o contraseña incorrectos."* | Muestra *"Email o contraseña incorrectos."* | ✅ |
| L3 | Email que no existe | `noexiste@test.com` / `123456` | El mismo mensaje que L2 (no revela qué emails existen) | Muestra exactamente el mismo mensaje que L2 | ✅ |
| L4 | Logout | Con sesión iniciada → "Cerrar sesión" | Vuelve al listado y la cabecera muestra "Iniciar sesión" y "Registrarse" | Vuelve al listado; la cabecera muestra "Iniciar sesión" y "Registrarse" | ✅ |
| L5 | Volver atrás tras logout | Tras L4, pulsar Atrás en el navegador hasta el formulario de publicar y enviarlo | No se publica nada: 403 por CSRF o redirección al login | Redirige al login con *"Tienes que iniciar sesión para publicar."*; no se publica nada | ✅ |

## 4. Publicaciones

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| P1 | Crear sin sesión | Sin sesión → `?action=create` | Redirige al login con *"Tienes que iniciar sesión para publicar."* | Redirige al login y muestra *"Tienes que iniciar sesión para publicar."* | ✅ |
| P2 | Crear correctamente | Como Usuario A → Publicar → título, categoría y contenido | Va al detalle con *"Publicación creada."*; en la BD el `user_id` es el de A | Va a `?action=show&id=1` con *"Publicación creada."*; en la BD `user_id` = 1 (id de Ana) | ✅ |
| P3 | Título vacío (saltando el HTML) | F12 → quitar `required` del título → enviar con título vacío | *"El título es obligatorio."* y el formulario conserva lo escrito | Muestra *"El título es obligatorio."* y el formulario conserva la categoría y el contenido | ✅ |
| P4 | Título de 150 caracteres (límite) | Título de exactamente 150 caracteres | Se crea correctamente | Se crea correctamente; en la BD el título mide 150 caracteres | ✅ |
| P5 | Título de 151 caracteres | F12 → quitar `maxlength` → título de 151 caracteres | *"El título no puede superar 150 caracteres."* | Muestra *"El título no puede superar 150 caracteres."* y no se guarda | ✅ |
| P6 | Categoría de 51 caracteres | F12 → quitar `maxlength` → categoría de 51 caracteres | Mensaje de error de validación, sin error fatal | Sale `Fatal error: Uncaught PDOException: SQLSTATE[22001] ... Data too long for column 'category'` y no se guarda el post | ❌ |
| P7 | Ver un post inexistente | `?action=show&id=9999` | Página *"404 – No encontrado"* | Muestra *"404 – No encontrado"* (código HTTP 404) | ✅ |
| P8 | Id no numérico | `?action=show&id=abc` | Página 404, sin errores de PHP | Muestra la página 404 (código HTTP 404), sin avisos de PHP | ✅ |
| P9 | Editar post propio | Como A → abrir su post → Editar → cambiar título | *"Publicación actualizada."* y se ve el título nuevo | Muestra *"Publicación actualizada."* y el título nuevo "Vendo bicicleta de montaña" | ✅ |
| P10 | Editar post ajeno por URL | Como B → `?action=edit&id=<id de un post de A>` | *"403 – Prohibido"* | Muestra *"403 – Prohibido. No tienes permiso para hacer esto."* (código HTTP 403) | ✅ |
| P11 | Botones en post ajeno | Como B → abrir un post de A | No aparecen los botones Editar ni Borrar | No aparecen los botones Editar ni Borrar | ✅ |
| P12 | Borrar post ajeno manipulando el formulario | Como A, abrir un post propio; con F12 cambiar el `action` del formulario de Borrar al id de un post de B | *"403 – Prohibido"* y el post de B sigue existiendo | Muestra *"403 – Prohibido"* y el post de Berto sigue en la BD | ✅ |
| P13 | Borrar por GET | Como autor → `?action=destroy&id=<id propio>` en la barra de direcciones | No se borra; se muestra el listado | Muestra el listado de publicaciones; el post no se borra | ✅ |
| P14 | Borrar post propio | Como autor → Borrar → Aceptar | *"Publicación eliminada."* y desaparece del listado | Pide confirmación, vuelve al listado con *"Publicación eliminada."* y el post ya no aparece | ✅ |

## 5. Búsqueda

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| B1 | Buscar por título | Buscar una palabra del título de un post | Sale solo ese post (y los que coincidan) | Buscando `bicicleta` sale solo "Vendo bicicleta de montaña" | ✅ |
| B2 | Buscar por categoría | Buscar una categoría existente | Salen los posts de esa categoría | Buscando `Alquiler` sale "Busco piso en Almería" | ✅ |
| B3 | Sin resultados | Buscar `xyzxyz` | *"No hay publicaciones que coincidan con la búsqueda."* | Muestra *"No hay publicaciones que coincidan con la búsqueda."* | ✅ |
| B4 | Mayúsculas | Buscar el título en MAYÚSCULAS | Mismos resultados que en minúsculas | Buscando `BICICLETA` sale "Vendo bicicleta de montaña", igual que en minúsculas | ✅ |
| B5 | Inyección SQL | Buscar `' OR 1=1 -- ` | No hay error SQL; se busca como texto literal | No hay error SQL; muestra *"No hay publicaciones que coincidan con la búsqueda."* (se busca como texto) | ✅ |

## 6. Comentarios

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| C1 | Sin sesión | Abrir un post sin sesión | Se ven los comentarios; en lugar del formulario aparece *"Inicia sesión para comentar."* | Se ve la sección de comentarios y, en lugar del formulario, *"Inicia sesión para comentar."* | ✅ |
| C2 | Comentar correctamente | Como B → comentar en un post de A | *"Comentario publicado."*; aparece con el nombre `berto` y la fecha | Muestra *"Comentario publicado."* y el comentario aparece como "berto · 2026-10-27 17:20:59 ¿Sigue disponible?" | ✅ |
| C3 | Orden | Escribir 3 comentarios seguidos | Aparecen del más antiguo al más nuevo | Los 3 comentarios aparecen en orden: Comentario 1, 2, 3 (del más antiguo al más nuevo) | ✅ |
| C4 | Comentario solo con espacios | F12 → quitar `required` → enviar solo espacios | *"El comentario no puede estar vacío."* y no se guarda | Muestra *"El comentario no puede estar vacío."* y no se guarda nada | ✅ |
| C5 | Suplantar a otro usuario | F12 → añadir `<input name="user_id" value="1">` al formulario → comentar | En la BD el `user_id` es el del usuario logueado, no 1 | Se guarda con `user_id` = 2 (Berto, el usuario logueado), no con el 1 inyectado | ✅ |
| C6 | Comentar en post inexistente | F12 → cambiar el `action` a `?action=comment&id=9999` | Página 404 y no se guarda nada | Muestra *"404 – No encontrado"* y no se guarda nada | ✅ |
| C7 | Borrado en cascada | Borrar un post que tenga comentarios | Se borra sin error y sus comentarios desaparecen de la tabla `comments` | El post se borra sin error y sus comentarios pasan de 2 a 0 en la tabla `comments` | ✅ |

## 7. Seguridad (XSS y CSRF)

| ID | Qué se prueba | Pasos | Resultado esperado | Resultado obtenido | ¿Pasa? |
|---|---|---|---|---|---|
| S1 | XSS en el título | Crear un post con título `<script>alert(1)</script>` | Se ve el texto tal cual; no salta ninguna alerta ni en el listado ni en el detalle | El título se ve como texto `<script>alert(1)</script>` en el listado y en el detalle; no salta ninguna alerta | ✅ |
| S2 | XSS en un comentario | Comentar `<b>hola</b>` | Se ve `<b>hola</b>` como texto, no en negrita | Se ve `<b>hola</b>` como texto; en el HTML aparece escapado como `&lt;b&gt;hola&lt;/b&gt;` | ✅ |
| S3 | Token CSRF presente | F12 → inspeccionar cualquier formulario POST | Hay un `<input type="hidden" name="csrf_token">` de 64 caracteres, igual en todos | Hay un `csrf_token` oculto en cada formulario POST (3 revisados), de 64 caracteres y con el mismo valor en todos | ✅ |
| S4 | Token manipulado | F12 → cambiar una letra del token del formulario de comentar → enviar | *"403 – Petición no válida"* y no se guarda | Muestra *"403 – Petición no válida"* y el comentario no se guarda | ✅ |
| S5 | Sin token | F12 → borrar el campo del token del formulario de Borrar → enviar | 403 y el post sigue existiendo | Muestra *"403 – Petición no válida"* y el post sigue existiendo | ✅ |
| S6 | Token nuevo por sesión | Anotar el token → logout → login → mirar el token | El token es distinto | Antes: `9671601a…`; después de logout y login: `24160d05…`. Son distintos | ✅ |
| S7 | Archivos privados | Abrir `http://localhost:8080/config/config.php` y `http://localhost:8080/src/` | 404: Apache solo sirve `public/` | `/config/config.php` → 404 y `/src/` → 404 (Not Found) | ✅ |

---

## 8. Resumen de resultados

| Total | Pasan | Fallan |
|---|---|---|
| 46 | 44 | 2 |

### Fallos encontrados y corrección

_Por cada caso que falle: qué ocurrió, por qué y cómo se ha corregido (o si queda pendiente)._

| ID | Problema | Causa | Corrección |
|---|---|---|---|
| R8 | Con un username de 51 caracteres la página muestra un *Fatal error* (`SQLSTATE[22001] Data too long for column 'username'`) en vez de un mensaje de validación | `register()` no comprueba la longitud del username; la columna es `VARCHAR(50)` y MySQL (modo estricto) rechaza el dato. La excepción solo se captura para el código `23000` (duplicado), así que esta se relanza | Pendiente. Añadir en `register()`: `if (mb_strlen($username) > 50) $errors[] = 'El nombre de usuario no puede superar 50 caracteres.';` |
| P6 | Con una categoría de 51 caracteres la página muestra un *Fatal error* (`Data too long for column 'category'`) | `validate()` de `PostController` comprueba la longitud del título pero no la de la categoría (`VARCHAR(50)`). En condiciones normales el `maxlength` del HTML lo impide, pero se salta con F12 | Pendiente. Añadir en `validate()`: `if (mb_strlen($data['category']) > 50) $errors[] = 'La categoría no puede superar 50 caracteres.';` |

En ambos casos el dato **no** llega a guardarse (la base de datos lo rechaza), así que no hay datos corruptos: el fallo es que el usuario ve un error de PHP en lugar de un mensaje claro. La lección es que la validación del HTML (`maxlength`, `required`) es solo comodidad y siempre hay que repetirla en el servidor.
