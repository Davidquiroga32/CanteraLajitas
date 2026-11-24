# Plan de Desarrollo — Cantera Lajitas

> Sistema de control de ventas para cantera (materiales por m³, compradores, facturas, vendedores y reportes).
> Stack: **PHP 8.5 + Laravel 13 + MySQL 8.4 + Blade/Breeze + Tailwind CSS**.
> Despliegue objetivo: **VPS Contabo** + dominio **Hostinger**.

---

## 1. Contexto y alcance

Basado en el boceto `Bocetos San Jose/cantera-lajitas.html` ("Control de ventas").

El sistema reemplaza el control manual de ventas por una herramienta web donde:

- Los **vendedores** registran ventas, consultan el historial y generan la factura.
- El **administrador** ve toda la operación: reportes, equipo, materiales y estadísticas.

### Funcionalidades (según boceto)

| Módulo | Descripción | Acceso |
|---|---|---|
| Login | Ingreso con correo/contraseña + rol | Todos |
| Inicio (Dashboard) | KPIs, gráfico 30 días, material más vendido, últimas facturas, mejores compradores | Todos |
| Nueva venta | Buscar/crear comprador, líneas de materiales, pago, vendedor, vista previa de factura | Todos |
| Ventas | Historial con filtros (rango, material, vendedor, búsqueda) + exportar Excel | Todos |
| Compradores | Directorio CRUD + drawer con historial de compras | Todos |
| Materiales | Catálogo con precio m³, stock (patio) y edición de precio | Ver todos / editar admin |
| Equipo y vendedores | Listado de personal y su facturación mensual | Solo admin |
| Reportes | Rango de fechas, KPIs, gráficos, detalle por material/vendedor, exportar Excel/PDF | Solo admin |

### Roles

- **Administrador**: acceso total (reportes, equipo, editar precios, ver todas las ventas).
- **Vendedor**: registra ventas, consulta las suyas y el catálogo/compradores.

---

## 2. Identidad visual (del boceto)

Paleta a trasladar a Tailwind:

| Variable | Valor | Uso |
|---|---|---|
| `--stone` | `#1e1c19` | Sidebar, textos sobre oscuro |
| `--stone2` | `#2a2723` | Hover sidebar |
| `--stone3` | `#3a362f` | Borde demo / misc |
| `--bg` | `#ece8de` | Fondo general |
| `--card` | `#faf8f2` | Superficies/cards |
| `--line` | `#dbd5c6` | Bordes suaves |
| `--line2` | `#c8c1ae` | Bordes inputs |
| `--ink` | `#1f1d19` | Texto principal |
| `--muted` | `#7a7466` | Texto secundario |
| `--moss` | `#3f5c3a` | Acento principal (verde) |
| `--mossd` | `#2e4a2a` | Hover moss |
| `--mossl` | `#e3ebdc` | Fondo moss claro (tags) |
| `--ochre` | `#b9812a` | Acento secundario (ocre) |
| `--clay` | `#a8553a` | Advertencia / negativo |
| `--sand` | `#d9ceb2` | Acento arena |

Tipografías (Google Fonts):
- **Fraunces** (serif) → títulos.
- **Public Sans** (sans) → cuerpo.
- **JetBrains Mono** (mono) → números, facturas, códigos.

Formato de moneda: `$ 1.234.567` (es-CO). Formato de cantidades: `num` con separador de miles.

---

## 3. Modelo de datos

### Tablas y relaciones

```
users               (login + equipo/vendedores)
 ├─ id, name, email, password, cargo, role{admin,vendedor}, estado
 └─ (relación 1:N con ventas como "vendedor")

compradores         (clientes)
 ├─ id, nombre, documento(CC/NIT), telefono, tipo{empresa,persona}, ciudad
 └─ (1:N ventas)

materiales
 ├─ id, nombre, precio_m3 (int COP), stock_actual, stock_min, color, activo
 └─ (N:M ventas vía venta_lineas)

ventas              (facturas)
 ├─ id, numero(FV-000001), fecha, hora, comprador_id, vendedor_id(user),
 │   forma_pago{efectivo,transferencia,credito_15}, placa, observaciones, total
 └─ (1:N venta_lineas)

venta_lineas
 ├─ id, venta_id, material_id, cantidad_m3, precio_m3, subtotal
```

> Nota de diseño: `users` se usa también como "equipo/vendedores" (el boceto muestra nombre, cargo, rol y facturación por persona). Cada `venta.vendedor_id` referencia a `users`.

### Convenciones
- **Precios/valores**: enteros en pesos colombianos (COP), sin decimales. Cálculos en servidor.
- **Número de factura**: secuencial `FV-XXXXXX` (6 dígitos), único.
- **Precio histórico**: `venta_lineas.precio_m3` guarda el precio al momento de la venta (si el material cambia de precio, las facturas pasadas no se alteran).
- **Stock**: se decrementa al registrar una venta (opcional en fase 1; confirmar regla de negocio).

---

## 4. Plan por fases

### Fase 0 — Arranque (COMPLETADA)
- [x] Crear proyecto Laravel 13 en `C:\Proyectos\CanteraLajitas`.
- [x] Instalar Laravel Breeze (stack Blade).
- [x] `.env` apuntando a MySQL (`cantera_lajitas`).
- [x] `docker-compose.yml` con MySQL 8.4.
- [ ] Instalar **Docker Desktop** en la PC y levantar MySQL (`docker compose up -d`).
- [ ] `php artisan migrate` contra MySQL (verificar conexión).

**Entregable:** proyecto base funcionando con login de Breeze y BD MySQL.

---

### Fase 1 — Base de datos y modelo
- Crear migraciones: `users` (agregar `cargo`, `role`), `compradores`, `materiales`, `ventas`, `venta_lineas`.
- Crear modelos y relaciones (Elocuent) + `$fillable` + casts.
- **Factories + Seeders** con los datos del boceto:
  - 8 materiales (Arena de río, Arena lavada, Recebo compactable, Gravilla ¾″, Triturado 1½″, Piedra rajón, Base granular, Crudo de río) con precios y stock.
  - Compradores de ejemplo (Constructora Llanos del Sol, Ferretería El Maestro, etc.).
  - Usuarios de ejemplo (Administrador + vendedores).
  - Datos de ventas de demostración (para desarrollo y pantallas).
- Validación de reglas básicas en modelos (stock, total, número de factura).

**Entregable:** BD migrada + datos de prueba vía `php artisan migrate --seed`.

---

### Fase 2 — Autenticación y roles
- Adaptar login de Breeze a la identidad del boceto (pantalla dividida, eslogan, colores).
- Agregar campo `role` y `cargo` al registro/gestión de usuarios.
- Middleware/`Gate` de roles: `admin`, `vendedor`.
- Proteger rutas: `reportes`, `equipo`, `editar precios` solo admin.
- Redirección tras login según rol.

**Entregable:** login funcional con roles y permisos.

---

### Fase 3 — Layout y UI base
- Crear layout Blade con sidebar (logo Cantera Lajitas, navegación, CTA "Nueva venta", selector de usuario).
- Configurar Tailwind con la paleta del boceto (colores, tipografías).
- Componentes Blade reutilizables: card, botón, input, tabla, tag/estado, modal, drawer, toast.
- Navegación y estructura de rutas por módulo.

**Entregable:** shell de la aplicación con navegación completa (estética del boceto).

---

### Fase 4 — Catálogos (CRUD)
- **Materiales**: listado en grilla con color/imagen, precio, barra de stock, editar precio (admin).
- **Compradores**: listado + búsqueda, crear/editar, drawer con historial de compras.
- **Equipo y vendedores** (admin): listado con facturación del mes, alta de usuarios/vendedores.

**Entregable:** CRUD completo de los tres catálogos.

---

### Fase 5 — Módulo de Ventas (núcleo)
- **Nueva venta**:
  - Búsqueda de comprador con autocompletado (por nombre/documento).
  - Líneas de materiales (material, cantidad m³, precio).
  - Forma de pago, vendedor, placa, observaciones.
  - Vista previa de factura en vivo.
  - Guardar comprador nuevo al facturar (opcional).
- **Listado de ventas**: filtros (rango, material, vendedor, búsqueda), totales en pie.
- **Factura**: vista de detalle + impresión/PDF.
- Decremento de stock al vender.

**Entregable:** ciclo completo de venta → factura.

---

### Fase 6 — Dashboard (Inicio)
- KPIs: ventas de hoy, ventas del mes (vs. mes anterior), material despachado, ticket promedio.
- Gráfico de ventas últimos 30 días (Chart.js).
- Material más vendido (barras).
- Últimas facturas y mejores compradores.

**Entregable:** dashboard del administrador y versión reducida para vendedor.

---

### Fase 7 — Reportes (admin)
- Rango de fechas con presets (hoy, 7, 30 días, mes, mes anterior).
- KPIs del rango, gráfico por día/semana.
- Detalle por material y por vendedor.
- Exportación a **Excel** (CSV) y **PDF**.

**Entregable:** reportes con exportación.

---

### Fase 8 — Exportación e impresión
- Factura en formato imprimible (hoja A4 con membrete, estilos print CSS).
- Exportar ventas y reportes a Excel.
- (Opcional) generación PDF server-side (p. ej. `barryvdh/laravel-dompdf`).

**Entregable:** documentos listos para imprimir/exportar.

---

### Fase 9 — Seguridad y validaciones
- Validación de formularios (server + cliente).
- Autorización por rol en controladores y vistas.
- Manejo de errores y mensajes (toast).
- Cifrado, protección CSRF, `fillable`/guarded correctos.

**Entregable:** app segura y validada.

---

### Fase 10 — Pruebas
- Tests de features: autenticación, venta (crear factura, stock), reportes, permisos.
- Tests de unitarios: cálculo de totales, número de factura, precio histórico.

**Entregable:** suite de pruebas (PHPUnit/Pest) en verde.

---

### Fase 11 — Despliegue (VPS Contabo + Hostinger)
- Preparar servidor: PHP 8.5, Composer, MySQL/MariaDB, Nginx, supervisor, certificado SSL (Let's Encrypt).
- Configurar `.env` de producción, caché (`config:cache`, `route:cache`, `view:cache`).
- Migraciones y seeders iniciales en producción.
- Apuntar dominio de Hostinger (registros A) al VPS.
- Configurar HTTPS y redirección www.
- Backups de BD y cron/scheduler de Laravel.

**Entregable:** aplicación en producción con dominio propio y SSL.

---

## 5. Orden sugerido de desarrollo

1. Fase 1 (BD/modelos) → 2. Fase 2 (auth/roles) → 3. Fase 3 (UI) → 4. Fase 4 (catálogos) → 5. Fase 5 (ventas) → 6. Fase 6 (dashboard) → 7. Fase 7-8 (reportes/export) → 8. Fase 9-10 (seguridad/pruebas) → 9. Fase 11 (deploy).

## 6. Pendientes por definir (para validar con el negocio)
- ¿El stock se descuenta automáticamente al vender? ¿Se puede vender en negativo?
- ¿Formas de pago adicionales (crédito con otros plazos, Nequi, etc.)?
- ¿La factura es oficial (resolución DIAN / facturación electrónica) o es un comprobante interno?
- ¿Manejo de devoluciones/notas crédito?
- ¿Vendedores múltiples por factura o uno solo?
