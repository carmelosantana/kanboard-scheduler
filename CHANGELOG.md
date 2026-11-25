# Changelog

All notable changes to the SchedulerPlugin will be documented in this file.

## [1.0.1] - 2026-11-25

### Fixed
- Web-cron: two concurrent page requests could both see "not run today" and both run the
  sweep. The day is now claimed with an atomic conditional update (SQLite, MariaDB, Postgres),
  so only one request sweeps.
- Web-cron: an exception in the sweep no longer breaks the page render of the request that
  triggered it; it is logged as an error.

## [1.0.0] - 2026-07-07

### Added
- Daily sweep that rolls overdue, still-open tasks forward, per **opt-in project**.
- Policy pipeline: skip tasks with open blockers (DependencyPlugin), snap to today,
  shift off weekends/holidays (working-days), and de-clump days over a threshold.
- Three triggers wrapping one `SchedulerRunner`: lazy web-cron (guarded once/day),
  an admin **Run now** button with **dry-run preview**, and `./cli scheduler:run`.
- Audit: `scheduler_runs` + `scheduler_moves` tables with an admin **log page**,
  plus one clearly-automated per-run **activity-stream summary** (system actor).
- **Calendar auto-moved badge** via CalendarPlugin's `calendarEventDecorators` hook.
- Admin settings page (master switch, target hour, working days, holidays, de-clump
  threshold, badge window, respect-blocks, post-to-activity) and per-project toggle.
