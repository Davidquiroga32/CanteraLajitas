# Plan de Desarrollo — Cantera Lajitas S.A.S.

> Sistema de control de ventas de materiales por m³: **remisiones**, compradores, cartera (créditos y anticipos), inventario, equipo y reportes.
> Stack: **PHP 8.5 + Laravel 13 + MySQL 8.4 + Blade/Breeze (Alpine.js) + Tailwind CSS**. Sin Livewire ni Inertia.
> Despliegue objetivo: **VPS Hostinger** (Ubuntu + Nginx + PHP-FPM + MySQL + HTTPS con Let's Encrypt).

Fuentes de este plan:
- Boceto `C:\Proyectos\Bocetos San Jose\cantera-lajitas.html` (estructura, pantallas y componentes).
- Documento "LISTA DE COSAS A CAMBIAR Y AGREGAR.docx" (reglas de negocio, remisión, cartera, productos, logo y colores). **Si el boceto y el documento chocan, manda el documento.**
- Referencias guardadas en `docs/referencias/`: `logo-cantera-lajitas.png` y `remision-fisica-actual.png` (foto del talonario actual).

---

## 1. Cambios respecto al boceto (según el documento)

| # | Cambio | Impacto |
|---|---|---|
| 1 | **"Factura" pasa a llamarse "Remisión"** en todo el sistema (menús, PDF, tablas, mensajes). | Modelo `Remision`, prefijo de número, textos. |
| 2 | La remisión se imprime en **3 copias** en una sola impresión: 1 para el vendedor y 2 para el cliente. En el sistema queda guardada sin firma. | Vista de impresión con 3 copias (salto de página entre copias). |
| 3 | **Todos los campos de la remisión son obligatorios.** Se **quita el teléfono**. Si el destino no aplica se escribe **N/A**. | Validación servidor + cliente. |
| 4 | Si falta algún campo al pulsar "Generar remisión", se muestra un **aviso grande y claro** con la lista de lo que falta (usuarios mayores, poco afines a la tecnología). | Componente de alerta grande, foco y scroll al campo. |
| 5 | **El vendedor no se elige**: es el usuario que inició sesión, de forma automática. | Se elimina el select de vendedor en Nueva remisión. |
| 6 | Campos nuevos: **conductor**, **máquina que carga el material** y **destino del material**. | Columnas nuevas en `remisiones`. |
| 7 | **5 formas de pago**: Efectivo, Transferencia, Crédito, Anticipo y Donación. | Enum + reglas contables. |
| 8 | **Módulo de Cartera** (personas/empresas que deben) y **Anticipos** (saldo a favor). | Módulo nuevo + movimientos. |
| 9 | Firmas físicas: bajo cada firma va el nombre y documento. **Cliente**: nombre/razón social + NIT o CC. **Vendedor**: nombre + cédula. | Los usuarios necesitan campo `cedula`. |
| 10 | Encabezado legal como el talonario actual (ver sección 2.3). | Datos de empresa configurables. |
| 11 | **Inventario**: se pueden crear productos nuevos. Catálogo real con 7 productos y precios (sección 3.2). | CRUD de materiales (solo admin). |
| 12 | Reportes **por cliente** y **de todos los clientes que deben**. | Reportes de cartera. |
| 13 | Exportar a **Excel y PDF** las ventas de los últimos 15 días o del rango que elijan. | Preset "15 días" + exportaciones. |
| 14 | Se usa el **logo real** y sus **colores** como paleta principal. | Nueva paleta (sección 2.1). |

Lo que se conserva del boceto: estructura de pantallas, barra lateral, tarjetas KPI, tablas, drawer de compradores, gráfico de 30 días, vista previa en vivo de la remisión, tipografías y componentes.

---

## 2. Identidad visual

### 2.1 Paleta (tomada del logo)

| Token | Valor | Uso |
|---|---|---|
| `navy` | `#162342` | Color principal: barra lateral, títulos, botones primarios |
| `navy-700` | `#1f2f57` | Hover / ítem activo en barra lateral |
| `navy-50` | `#e6e9f1` | Fondos suaves, etiquetas |
| `orange` | `#DA7D15` | Acento: CTA "Nueva remisión", indicador activo, KPIs |
| `orange-600` | `#c06c0f` | Hover del acento |
| `orange-50` | `#fbeedd` | Etiquetas suaves |
| `brown` | `#955515` | Acento secundario (montaña del logo), gráficos |
| `slate` | `#55534B` | Texto "SAS" del logo; textos secundarios |

Neutros del boceto que se mantienen (fondo cálido tipo piedra): `bg #ece8de`, `card #faf8f2`, `line #dbd5c6`, `line2 #c8c1ae`, `ink #1f1d19`, `muted #7a7466`.
Estados: éxito `#3f5c3a` (verde del boceto), peligro `#a8553a` (clay). Se usan solo para estados (pagado, deuda, stock bajo), no como color de marca.

### 2.2 Tipografía y formato
- **Fraunces** → títulos · **Public Sans** → cuerpo · **JetBrains Mono** → números, remisiones, documentos.
- Base de texto un poco más grande que el boceto (15–16 px) y botones/campos más altos, pensando en usuarios mayores.
- Moneda: `$ 1.234.567` (es-CO, sin decimales). Cantidades con separador de miles. Zona horaria `America/Bogota`.

### 2.3 Remisión (documento impreso)
Basada en el talonario actual (`docs/referencias/remision-fisica-actual.png`):

- Logo + "REMISIÓN DE COMPRA — MINA LA VACA" + **NIT 900.897.394-0**.
- Caja de control: **Código LAJ-FT-10 · Versión 03 · Aprobado 05-ENE-2026**.
- Línea legal: **"Contrato de Concesión PLACA IGU-10411 · Agencia Nacional de Minería / Lic. Ambiental Res. No. 205 Corporación CDA"**.
- Número de remisión consecutivo que **empieza en 000001** (6 dígitos; número inicial configurable).
- Papel **tamaño carta**.
- Campos: fecha y hora, cliente + NIT/CC, conductor, placa, materiales (tipo, cantidad m³, valor unitario, valor total), forma de pago, máquina que carga, destino (o N/A), observaciones.
- Firmas: **Quien despacha** (nombre + cédula del vendedor) y **Cliente** (nombre/razón social + NIT/CC).
- Pie: "Calle 27 # 23-49 Barrio San Jorge 1 / Cel 317 370 3814 – 315 319 9366".
- Impresión: 3 copias rotuladas ("Copia vendedor", "Copia cliente", "Copia cliente").

Todos los datos de empresa (NIT, placa del contrato, código/versión del formato, dirección, teléfonos, número inicial) se guardan en una tabla `configuracion` editable por el administrador, para no tocar código cuando cambie la versión del formato.

---

## 3. Modelo de datos

### 3.1 Tablas

```
users                 (login + equipo)
 ├─ name, cedula, email, password, cargo, role{admin,vendedor}, activo
 └─ 1:N remisiones (como vendedor)

compradores
 ├─ nombre, tipo_documento{CC,NIT}, documento (único), tipo{empresa,persona}, ciudad, activo
 ├─ saldo_credito (deuda, calculado/cacheado), saldo_anticipo (a favor, calculado/cacheado)
 └─ 1:N remisiones, 1:N movimientos_cartera

materiales            (inventario)
 ├─ nombre, precio_m3 (entero COP), stock_m3, stock_minimo_m3, color, activo
 └─ 1:N remision_lineas, 1:N cambios_precio

remisiones
 ├─ numero (único, consecutivo), fecha_hora, comprador_id, vendedor_id(user)
 ├─ conductor, placa, maquina, destino, forma_pago{efectivo,transferencia,credito,anticipo,donacion}
 ├─ observaciones, total, estado{emitida,anulada}, anulada_por, motivo_anulacion
 └─ 1:N remision_lineas

remision_lineas
 └─ remision_id, material_id, cantidad_m3 (decimal 8,2), precio_m3, subtotal

movimientos_cartera   (créditos, abonos y anticipos del comprador)
 └─ comprador_id, tipo{cargo_credito,abono_credito,anticipo,consumo_anticipo,ajuste},
    valor, remision_id (nullable), medio{efectivo,transferencia}, nota, user_id, fecha

configuracion         (clave/valor de datos de empresa y formato de remisión)
auditoria             (user_id, accion, modelo, modelo_id, antes/después JSON, ip, fecha)
```

### 3.2 Catálogo inicial real (precio por m³, COP)

| Producto | Precio |
|---|---|
| Recebo morado | $ 70.000 |
| Recebo zarandeado | $ 50.000 |
| Recebo gris | $ 50.000 |
| Relleno | $ 25.000 |
| Arena de río | $ 85.000 |
| Gravilla de río | $ 90.000 |
| Piedra rajón | $ 120.000 |

### 3.3 Reglas de negocio
- **Precio histórico**: `remision_lineas.precio_m3` guarda el precio del momento; cambiar un precio no altera remisiones pasadas. Cada cambio de precio queda en auditoría.
- **Solo el administrador** crea/edita materiales y precios. El vendedor usa el precio del catálogo (no lo edita en la remisión).
- **Número de remisión**: consecutivo único desde 1, generado dentro de una transacción con bloqueo (sin saltos ni duplicados).
- **Remisiones no se borran**: se **anulan** (solo admin, con motivo). Una anulación revierte stock y movimientos de cartera.
- **Stock**: se descuenta al emitir la remisión y se repone al anularla. **No se vende sin stock**: un material con stock en cero no aparece para vender y no se puede pedir más de lo que hay en patio, hasta que el administrador actualice el stock.
- **Formas de pago y cartera**:
  - *Efectivo / Transferencia*: venta pagada, no genera movimiento de cartera.
  - *Crédito*: genera un cargo; la deuda del comprador se acumula (sin intereses). Los **abonos** se registran en Cartera y la reducen.
  - *Anticipo*: el comprador entrega dinero por adelantado (movimiento `anticipo`); cada remisión con esta forma de pago descuenta de su saldo a favor. **Si el saldo no alcanza, la diferencia pasa a crédito** y el comprador queda como deudor. Sin saldo de anticipo no se puede elegir esta forma de pago.
  - *Donación*: se registra con total, pero no suma a ingresos ni a cartera (aparece aparte en reportes).
- **Vendedor**: solo ve sus remisiones; administrador ve todo.

---

## 4. Plan por fases

Cada fase termina con: código en el repo, migraciones/seeders corriendo, pruebas en verde y revisión contigo antes de seguir.

### Fase 0 — Arranque y limpieza  ✅
- [x] Proyecto Laravel 13 + Breeze (Blade/Alpine) en `C:\Proyectos\CanteraLajitas`.
- [x] Repo conectado: `github.com/Davidquiroga32/CanteraLajitas` (rama `main`).
- [x] Plan corregido según el documento de cambios (este archivo) y referencias en `docs/referencias/`.
- [x] Laravel Boost instalado (guías y skills para el agente en `CLAUDE.md`, `AGENTS.md`, `.claude/`, `.agents/`).
- [x] **Base de datos local: SQLite** (`database/database.sqlite`). Producción usará MySQL 8; `docker-compose.yml` queda por si se instala Docker más adelante.
- [x] Zona horaria `America/Bogota` (`APP_TIMEZONE`), locale `es`.
- [x] Quitados los workflows y el `CHANGELOG.md` de la plantilla de Laravel; workflow propio `.github/workflows/tests.yml` (PHP 8.5, rama `main`).
- [x] Quitado `@tailwindcss/vite` (v4), que chocaba con `tailwindcss` v3 (aprobado).
- [x] Symfony actualizado a 8.1.12: `composer audit` sin avisos de seguridad (aprobado).

### Fase 1 — Base de datos, modelos y seeders  ✅
- [x] Migraciones de la sección 3.1 (`users` + `cedula`, `cargo`, `role`, `activo`; `compradores`, `materiales`, `remisiones`, `remision_lineas`, `movimientos_cartera`, `configuracion`, `auditoria`).
- [x] Enums en `app/Enums`: `Rol`, `FormaPago`, `TipoDocumento`, `EstadoRemision`, `CuentaCartera`, `TipoMovimiento`.
- [x] Modelos con relaciones, casts y scopes (`visiblesPara`, `emitidas`, `conSaldos`, `buscar`, `activos`).
- [x] `App\Services\RemisionService` (emitir/anular en transacción: consecutivo, precio de catálogo, stock, cartera, auditoría) y `App\Services\CarteraService` (abonos y anticipos).
- [x] Seeders: configuración de la remisión, 7 productos reales, 4 usuarios demo, 10 compradores demo y ~360 remisiones de 82 días con las 5 formas de pago, anticipos y abonos (solo fuera de producción).
- [x] Pruebas de servicios (`tests/Feature/Services`): totales, consecutivo desde 000001, precio histórico, stock (sin venta sin existencias), cartera (diferencia del anticipo a crédito), anulación y auditoría.

**Entregable:** `php artisan migrate:fresh --seed` funciona y deja datos realistas. Usuarios demo: `admin@canteralajitas.test`, `sebastian@…`, `yuliana@…`, `gilberto@…` (contraseña `password`, solo desarrollo).

Otras reglas: abonos y anticipos se reciben solo en efectivo o transferencia.

### Fase 2 — Layout, autenticación y dashboard  ✅
> Hecho: paleta del logo en Tailwind, estilos del boceto en `resources/css/app.css`, logo en variantes clara/oscura + favicon (`public/images`), layout con barra lateral y menú móvil, componentes (`x-encabezado`, `x-kpi`, `x-icono`, `x-tag-pago`, `x-grafico-lineas`, `x-alerta-grande`, `x-panel-lateral`), login y perfil en español, registro público y auto-borrado de cuenta eliminados, bloqueo de usuarios inactivos (también con sesión abierta), `Gate` `admin`, mensajes de validación en español (`lang/es`), dashboard con `DashboardService`. Módulos de fases siguientes muestran "en construcción".
- Configurar Tailwind con la paleta del logo y las 3 tipografías; pasar los estilos del boceto a `resources/css/app.css` como componentes.
- Logo en `public/images/` (barra lateral, login, remisión, favicon).
- Layout Blade `layouts/app.blade.php` con barra lateral (CTA "Nueva remisión", menú según rol, usuario y cerrar sesión) y componentes Blade: `card`, `kpi`, `btn`, `field`, `tag`, `modal`, `drawer`, `toast`, `alerta-grande`.
- Reemplazar las vistas de Breeze: login con el diseño del boceto (sin selector de rol), perfil.
- **Desactivar registro público**: solo el administrador crea usuarios.
- Roles: middleware/`Gate` `admin`; políticas para remisiones (vendedor solo las suyas).
- **Dashboard**: ventas de hoy, del mes vs. mes anterior a la fecha, m³ despachados, ticket promedio, gráfico 30 días (SVG generado en servidor + tooltip con Alpine), material más vendido, últimas remisiones, mejores compradores, y para admin: total en cartera y anticipos.

**Entregable:** login y panel con la identidad real de la empresa.

### Fase 3 — Materiales (inventario) y compradores  ✅
> Hecho: `MaterialController` (catálogo, crear/editar y ajuste de stock por entrada o conteo físico, solo admin, con auditoría) y `CompradorController` (búsqueda, crear para todos, editar solo admin, panel lateral con historial, deuda y anticipo vía `?ver=`). El vendedor ve en el historial solo sus remisiones. Ciudad y documento obligatorios; documento único.
- **Materiales**: tarjetas con precio y barra de stock; admin crea productos, edita precio y ajusta stock (con auditoría).
- **Compradores**: listado con búsqueda, crear/editar (CC o NIT, sin teléfono obligatorio), drawer con historial de remisiones, deuda y saldo de anticipo; botón "Vender a este comprador".

### Fase 4 — Nueva remisión (núcleo)  ✅
> Hecho: `RemisionController` (crear, emitir, ver, imprimir, PDF, búsqueda de compradores), `EmitirRemisionRequest` con mensajes claros, `RemisionPolicy` (vendedor solo las suyas), formulario Alpine (`resources/js/remision.js`) con 4 pasos, vista previa en vivo, aviso grande de lo que falta y barra de total fija; documento con encabezado legal, casillas de las 5 formas de pago y firmas; impresión y PDF (`barryvdh/laravel-dompdf`) en tamaño carta, **una copia por hoja** (copia vendedor + 2 copias cliente).
- Paso 1 comprador: búsqueda con autocompletado (nombre o documento); si existe, se autorrellenan sus datos; si no, se crea al emitir.
- Paso 2 materiales: varias líneas (material, cantidad m³, precio de catálogo, subtotal).
- Paso 3 despacho y pago: conductor, placa, máquina que carga, destino (botón rápido "N/A"), forma de pago (5 opciones grandes). Con *Anticipo* se muestra el saldo disponible; con *Crédito*, la deuda actual.
- Vendedor = usuario en sesión (solo se muestra).
- Vista previa de la remisión en vivo (Alpine).
- Validación completa en servidor (`FormRequest`) y en cliente; aviso grande con la lista de lo que falta.
- Al guardar: pantalla de confirmación + **Imprimir 3 copias** y **Descargar PDF**.
- PDF con `barryvdh/laravel-dompdf` (A4 o media carta, por definir), 3 copias con firmas.

### Fase 5 — Remisiones (historial) y cartera
- **Remisiones**: filtros por rango (Hoy, 7, 15, 30 días, mes), material, vendedor, forma de pago, búsqueda; totales en pie; ver/reimprimir; anular (admin).
- Exportar el listado filtrado a **Excel** y **PDF**.
- **Cartera**: listado de compradores con deuda y con anticipos; detalle de movimientos; registrar abono y registrar anticipo; estado de cuenta en PDF/Excel.

### Fase 6 — Equipo
- Admin: listar, crear, editar y desactivar usuarios (nombre, cédula, cargo, rol, correo, contraseña), con remisiones y valor vendido en el mes.

### Fase 7 — Reportes
- Rango con presets (Hoy, 7, 15, 30 días, mes actual, mes anterior) y fechas libres.
- KPIs, gráfico por día/semana, detalle por material, por vendedor y por forma de pago (donaciones aparte).
- **Reporte por cliente** y **reporte de todos los clientes que deben**.
- Exportación a Excel y PDF de cada reporte.

### Fase 8 — Seguridad, auditoría y pruebas
- Auditoría: inicio de sesión, remisiones emitidas/anuladas, cambios de precio y stock, abonos/anticipos, cambios de usuarios. Vista de auditoría para admin.
- Límite de intentos de login, cabeceras de seguridad, `fillable` correctos, autorización en todos los controladores.
- Pruebas de features: permisos por rol, flujo completo de remisión, cartera, exportaciones. Workflow de GitHub Actions corriendo la suite en cada push.

### Fase 9 — Despliegue en VPS Hostinger
- Script `deploy/provision.sh` (una vez): Ubuntu, Nginx, PHP 8.5-FPM y extensiones, MySQL 8, Composer, Node, usuario de despliegue, firewall (UFW), Certbot.
- Script `deploy/deploy.sh` (cada versión): `git pull`, `composer install --no-dev`, `npm ci && npm run build`, `migrate --force`, cachés, reinicio de PHP-FPM, modo mantenimiento durante el proceso.
- Plantilla de sitio Nginx y `.env.production.example`.
- DNS del dominio apuntando al VPS, HTTPS con Let's Encrypt y redirección.
- Cron del scheduler y **copias de seguridad diarias de la BD**.
- **Cualquier comando en el servidor se confirma contigo antes de ejecutarse.**

---

## 5. Decisiones confirmadas y pendientes

Confirmado por la empresa (26-nov-2026):
- Placa del contrato: **IGU-10411** (la de la foto del talonario).
- Numeración: empieza en **000001**.
- Papel: **tamaño carta**.
- Anticipo insuficiente: **la diferencia pasa a crédito** (deudor).
- Stock en cero: **el producto no aparece para vender** hasta que se actualice el stock.

Pendientes:
1. **Copias**: el documento menciona 2 y luego 3 copias. El plan usa **3** (1 vendedor + 2 cliente).
2. **Firma del cliente o del conductor**: el talonario actual dice "Conductor"; el documento dice cliente con NIT/CC. El plan usa **Cliente** y guarda el conductor como dato.
3. ¿La remisión necesita **valor unitario y total visibles** en la copia del cliente? (El talonario los tiene; el plan los muestra.)
4. Dominio que se usará para el sitio.
