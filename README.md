# LMS V2 — Moodle + PostgreSQL + pgvector

Fresh Moodle LMS instance running on PostgreSQL with pgvector for vector search capabilities.

## Architecture

```
┌──────────────────────────────────────────┐
│  Docker Host (159.65.149.161)            │
│                                          │
│  ┌───────────────┐  ┌─────────────────┐ │
│  │ lmsv2-web     │  │ lmsv2-db        │ │
│  │ Moodle 4.5.10 │──│ PostgreSQL 16   │ │
│  │ Apache + PHP  │  │ + pgvector 0.5  │ │
│  │ :10183 → 80   │  │ :5432 (private) │ │
│  └───────────────┘  └─────────────────┘ │
│         │                    │           │
│  ┌──────┴─────┐     ┌──────┴─────────┐ │
│  │ /moodledata│     │ PG data vol    │ │
│  │ (volume)   │     │ (persistent)   │ │
│  └────────────┘     └────────────────┘ │
└──────────────────────────────────────────┘
```

## Access

- **URL:** http://159.65.149.161:10183/lmsv2/
- **Admin:** admin / (check config)
- **DB:** PostgreSQL — `moodlev2` database, `moodlev2user` user
- **Container:** `lmsv2-web` (8dfdb58f1624), `lmsv2-db` (f38ff87437c4)

## Repository Structure

```
├── docker/                  # Docker compose & Dockerfiles
│   ├── docker-compose.yml   # Full stack definition
│   └── apache-lmsv2.conf    # Apache vhost config
├── config/                  # Moodle config files
│   └── config.php           # Moodle config (PostgreSQL)
├── scripts/
│   ├── setup/               # Data population scripts (run in order)
│   │   ├── 01_users_cohorts.php
│   │   ├── 02_courses_activities.php
│   │   ├── 03_enrollments.php
│   │   ├── 04_completions_grades.php
│   │   ├── 05_certificates.php
│   │   └── ...
│   ├── fixes/               # Fix/repair scripts
│   ├── audit/               # Read-only audit/diagnostic scripts
│   ├── data-gen/            # Data generation (SCORM packages, PDFs)
│   └── backup/              # Backup & restore scripts
├── docs/                    # Test data plans, requirements
│   └── lms_test_data_plan.md
├── moodle-plugins/          # Plugin zips/configs to install
├── backups/                 # DB dumps, moodledata snapshots (gitignored for large files)
│   └── .gitkeep
└── .gitignore
```

## Quick Start — Restore from Scratch

```bash
# 1. Clone this repo
git clone https://github.com/rajmachers/lms-v2.git && cd lms-v2

# 2. Deploy containers
cd docker && docker compose up -d

# 3. Import DB dump
cat backups/latest.sql | docker exec -i lmsv2-db psql -U moodlev2user -d moodlev2

# 4. Restore moodledata
docker cp backups/moodledata/. lmsv2-web:/moodledata/

# 5. Run setup scripts (inside container)
docker exec -i lmsv2-web php /scripts/setup/01_users_cohorts.php
```

## Setup Scripts Execution Order

| Step | Script | What it does |
|------|--------|-------------|
| 1 | `01_users_cohorts.php` | Creates 60 students, 6 teachers, 5 cohorts |
| 2 | `02_courses_activities.php` | Creates courses with sections, quizzes, forums, assignments |
| 3 | `03_enrollments.php` | Batch enrollment + competency frameworks |
| 4 | `04_completions_grades.php` | Simulates realistic completion & grade data |
| 5 | `05_certificates.php` | Certificate templates + issues certificates |
| 6 | `06_badges.php` | Creates & issues course badges |
| 7 | `07_theme_content.php` | Theme slider, stats, branding |
| 8 | `08_ai_provider.php` | DeepSeek AI integration |

## Database

- **Type:** PostgreSQL 16 with pgvector extension
- **Vector Search:** `CREATE EXTENSION vector;` (already enabled)
- **Table Prefix:** `mdl2_`
- **Collation:** utf8mb4_unicode_ci
