# Database migrations and seeders

## Why reports show no data

The admin report pages (Lab Performance, Radiology Analytics, Pharmacy Reports, Nutrition Compliance, OR Utilization & Reports) read from database tables that are created and filled by **module setup scripts**. Until you run these, the tables are missing or empty and the reports show zeros/blank charts.

## Run all migrations and seeders (recommended)

From the **project root** (e.g. `c:\Users\kheeel\Herd\hospital-core2-system`):

```bash
php database/run_migrations_and_seeders.php
```

Or in the browser (if your site is running):

```
http://hospital-core2-system.test/database/run_migrations_and_seeders.php
```

This script runs, in order:

1. Core schema and seeds: `001_core_schema.sql`, `002_core_seed.sql`, `003_billing_philippines.sql`
2. Module setups (tables + sample data): LIS, RIS, PMS, DNMS, SORS
3. Extra seeds for current period: LIS performance metrics, DNMS nutrition compliance, SORS schedule week

After it finishes, refresh the report pages to see data.

## Run individual module SQL manually

If you prefer to run SQL yourself (e.g. in phpMyAdmin or MySQL client):

| Module | File | Purpose |
|--------|------|---------|
| Core | `database/001_core_schema.sql` | Patients, appointments, billing, beds, etc. |
| Core | `database/002_core_seed.sql` | Roles, sample patients, beds |
| Billing | `database/003_billing_philippines.sql` | Billing columns for Philippines HMS |
| Doctor Portal | `database/004_doctor_portal.sql` | Real data for doctor role: patients, appointments, consults, referrals, signing queue, alerts, messages, chart data; seeds doctor users (id 10–15) and Philippine patients |
| LIS | `admin/modules/lis/database_setup.sql` | Lab Performance Reports tables + data |
| LIS | `admin/modules/lis/seed_performance_metrics.sql` | Extra performance metrics (e.g. Feb 2026) |
| RIS | `admin/modules/ris/database_setup.sql` | Radiology Analytics tables + data |
| PMS | `admin/modules/pms/database_setup.sql` | Pharmacy Reports tables + data |
| DNMS | `admin/modules/dnms/database_setup.sql` | Nutrition Compliance tables + data |
| DNMS | `admin/modules/dnms/seed_nutrition_compliance.sql` | Extra nutrition compliance (e.g. Feb 2026) |
| SORS | `admin/modules/sors/database_setup.sql` | Surgeons, ORs, procedures, blocks; Surgery Schedule & Doctor Availability |
| SORS | `admin/modules/sors/seed_schedule_week_php.php` | Surgery schedule for week Feb 2–8, 2026 (run via run_migrations_and_seeders.php) |

Run core and module setup before the seed files that depend on them.
