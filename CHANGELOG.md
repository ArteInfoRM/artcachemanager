# Changelog

All notable changes to **Art Cache Manager** will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.4] — 2026-04-01

### Added
- OPcache detection now distinguishes three failure modes: extension not loaded, `opcache.restrict_api` blocking web-path access (shows the actual path value), and `opcache_get_status` blocked via `disable_functions`
- **"Clear OPcache (blind)"** button (amber) shown when `opcache_get_status` is blocked by `disable_functions` but `opcache_reset()` is still accessible — typical on shared hosting; confirmation dialog warns that the reset affects the entire server
- README: new *Shared hosting considerations* section documenting OPcache and Memcached behaviour on shared vs. VPS/dedicated servers, with summary table; module scope clarified as VPS/dedicated only

### Changed
- Shared hosting notice added to the `disable_functions` warning message in the admin panel

---

## [1.0.0] — 2026-04-01

### Added

#### OPcache
- Display OPcache enabled/disabled status with the active PHP version
- Memory usage bar (used / free / wasted) with colour-coded thresholds
- Hit-rate bar with colour-coded thresholds (green ≥ 80 %, amber ≥ 50 %, red < 50 %)
- Counters: cached scripts, max cached keys, hits, misses
- `ini` snapshot: `opcache.memory_consumption`, `opcache.max_accelerated_files`, `opcache.revalidate_freq`
- Prominent notice when `opcache.validate_timestamps = 0` is detected (production vhost scenario), reminding operators to clear manually after every deploy
- Warning banner when a restart is pending or in progress
- **Clear OPcache** button (calls `opcache_reset()`) with confirmation dialog

#### Memcached / Memcache
- Reads cache state from `app/config/parameters.php` (`ps_cache_enable` / `ps_caching`), the authoritative source written by the PrestaShop Performance admin page — not from `ps_configuration`, which is never updated at runtime
- Supports both the modern PHP `Memcached` extension (`CacheMemcached`) and the legacy `Memcache` extension (`CacheMemcache`)
- Displays global stats: hit rate bar, items in cache, memory used, evictions
- Per-server breakdown (version, uptime, items, memory used / max, active connections) when more than one server is configured
- Reads server list from `ps_memcached_servers` table (PS 1.7+)
- **Flush Memcached** button with confirmation dialog; uses `Cache::getInstance()->flush()` with direct-extension fallback

#### Configuration
- Checkbox: *Clear OPcache when PrestaShop clears its cache* — hooks `actionClearCache` and `actionAdminPerformanceSave`
- Checkbox: *Flush Memcached when PrestaShop clears its cache* — same hooks; option is disabled in the UI when Memcached is not active
- Settings persisted via `Configuration` (`ARTCM_OPCACHE_WITH_PS`, `ARTCM_MEMCACHED_WITH_PS`)

### Technical notes
- Compatible with PrestaShop **1.7.0 → 9.1** and PHP **7.4 → 8.5**
- Bootstrap 3 / 4 / 5 safe (uses only shared panel / grid / alert classes)
- `Db::getValue()` usage: the method internally appends `LIMIT 1` to the query before execution; passing `LIMIT 1` in the SQL string as well results in it being added twice, causing a SQL error
- Cache state detection: `app/config/parameters.php` is a Symfony boot requirement and is always present; no fallback to `ps_configuration` is needed or desirable (stale data risk)
