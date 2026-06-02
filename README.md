# PayMonitor

Multi-tenant SaaS platform for Philippine lending cooperatives. Built with Laravel 12, multi-tenancy (stancl/tenancy), and Tailwind CSS.

## Features

- **Multi-Tenant Architecture** — Isolated databases per cooperative, subdomain-based routing
- **Central Admin Portal** — Tenant health monitoring, billing, plans, support tickets, version management
- **Tenant Portal** — Members, loans, payments, amortization schedules, branches, RBAC, reports
- **PayMongo Integration** — Card, GCash, PayMaya, QRPh payment support
- **Plan-Based Feature Gating** — Tiered subscription plans with configurable limits
- **Dark/Light Theme** — Per-tenant theming with custom accent colors
- **Audit Logging** — Activity tracking for plan-enabled tenants
- **Health Scoring** — Automated tenant health monitoring (quota, DB size, billing risk)

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+, Laravel 12 |
| Multi-Tenancy | stancl/tenancy ^3.9 |
| RBAC | spatie/laravel-permission ^7.2 |
| Payments | luigel/laravel-paymongo |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Build | Vite 7 + PostCSS |
| PDF | barryvdh/laravel-dompdf |
| Excel | maatwebsite/laravel-excel |
| Testing | Pest PHP ^3.8 |

## Getting Started

```bash
cp .env.example .env
composer install
npm install
npm run build
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## Design System

Design tokens live in `tailwind.config.js` and `resources/css/app.css`. See `resources/css/` for the full design system layer:

| File | Purpose |
|------|---------|
| `app.css` | Core design tokens, base styles, component classes, utilities |
| `paymonitor.css` | Portal-specific styles (background orbs, legacy overrides) |
| `paymonitor-landing.css` | Marketing page styles |

## License

Proprietary — All rights reserved.
