# CanteraLajitas

Sistema de control de ventas para la **Cantera Lajitas** (Villavicencio, Meta). Registra ventas de materiales por metro cúbico, genera la factura al instante y consolida reportes por rango de fechas.

## Stack

- PHP 8.5 + Laravel 13
- MySQL 8.4 (Docker)
- Blade + Laravel Breeze + Tailwind CSS

## Requisitos

- PHP 8.5 y Composer
- Node.js y npm
- Docker Desktop (para MySQL)

## Instalación

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Base de datos (MySQL vía Docker):

```bash
docker compose up -d
php artisan migrate --seed
```

Servidor de desarrollo:

```bash
php artisan serve
npm run dev
```

## Plan de desarrollo

Ver [`PLAN-DESARROLLO.md`](PLAN-DESARROLLO.md) para el desglose por fases del proyecto.
