# Art Cache Manager

> PrestaShop module — monitor and clear **OPcache** and **Memcached** directly from the back-office.
> **Designed for VPS and dedicated servers.** On shared hosting both caches are typically inaccessible or shared across all tenants — see the [Shared hosting considerations](#shared-hosting-considerations) section below.

![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%20→%209.1-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%20→%208.5-777bb4)
![License](https://img.shields.io/badge/license-AFL%203.0-green)
![Version](https://img.shields.io/badge/version-1.0.4-informational)

---

## Overview

Art Cache Manager gives store operators a single admin page to:

- **See** the real-time state of OPcache and Memcached without SSH access
- **Clear** each cache independently with a single click
- **Automate** cache clearing whenever PrestaShop flushes its own cache

It is particularly useful on production servers where `opcache.validate_timestamps = 0` is set in the vhost (the recommended setting for performance), because PHP will never auto-detect file changes — a manual OPcache reset after every deploy becomes mandatory.

---

## Features

### OPcache panel

| Metric | Detail |
|---|---|
| Status | Enabled / disabled, PHP version |
| Memory | Used / free / wasted with colour-coded progress bar |
| Hit rate | Colour-coded progress bar (green ≥ 80 %, amber ≥ 50 %, red < 50 %) |
| Counters | Cached scripts, max cached keys, hits, misses |
| INI snapshot | `memory_consumption`, `max_accelerated_files`, `revalidate_freq` |
| Notices | `validate_timestamps = 0` warning, restart pending/in-progress banner |

### Memcached / Memcache panel

| Metric | Detail |
|---|---|
| Status | Active / inactive with reason (PS cache disabled, wrong adapter, …) |
| Adapter | `CacheMemcached` (PHP `memcached` ext) or `CacheMemcache` (PHP `memcache` ext) |
| Global stats | Hit rate bar, items, memory used, evictions |
| Per-server | Version, uptime, items, memory used / max, connections (multi-server setups) |

### Configuration

- **Clear OPcache with PS cache** — automatically resets OPcache whenever PrestaShop clears its cache (hooks: `actionClearCache`, `actionAdminPerformanceSave`)
- **Flush Memcached with PS cache** — same trigger; disabled in the UI when Memcached is not active

---

## Compatibility

| Dimension | Range |
|---|---|
| PrestaShop | 1.7.0 → 9.1 |
| PHP | 7.4 → 8.5 |
| Memcached ext | `memcached` ≥ 3.x and legacy `memcache` |
| DB | MariaDB / MySQL (standard PS requirement) |

---

## Requirements

- PHP extension **`opcache`** (optional — OPcache panel is hidden if absent)
- PHP extension **`memcached`** or **`memcache`** (optional — Memcached panel requires it)
- PrestaShop external cache set to **Memcached** or **Memcache** in *Advanced Parameters → Performance* for the Memcached panel to be active

---

## Shared hosting considerations

> **This module is designed for VPS and dedicated servers** where the operator has full control over the PHP and system configuration. On shared hosting both OPcache and Memcached are typically inaccessible or shared among all tenants, making monitoring and selective clearing impractical or potentially disruptive.

### OPcache on shared hosting

Shared hosting providers often add `opcache_get_status` to `disable_functions` in `php.ini` as a security measure: the function exposes the list of cached files for **all** vhosts on the server, not just your own.

| Function | Blocked by default on shared hosting | Effect on this module |
|---|---|---|
| `opcache_get_status` | Often yes | Statistics panel unavailable |
| `opcache_reset` | Usually no | Clear button still works |

When `opcache_get_status` is blocked but `opcache_reset` is available, the module shows a **"Clear OPcache (blind)"** button with an amber warning. Use it with awareness: `opcache_reset()` resets the compiled-script cache for the **entire server**, not just your site — it temporarily impacts all other sites hosted on the same machine.

> **On a VPS or dedicated server:** remove `opcache_get_status` from `disable_functions` for the domain's PHP-FPM pool to enable full statistics. On shared hosting managed by a third party, leave the restriction in place and use the blind-clear button only when necessary.

### Memcached on shared hosting

Memcached requires a separate system daemon (`memcached`) that must be installed and started at OS level. On shared hosting this daemon is almost always absent or not accessible to individual tenants.

If a Memcached daemon were accessible on a shared server, calling `flush()` would empty the **entire Memcached instance**, clearing cached data for all other sites sharing the same daemon — the same server-wide impact described above for `opcache_reset()`.

| Scenario | OPcache monitoring | OPcache clear | Memcached monitoring | Memcached flush |
|---|---|---|---|---|
| VPS / dedicated | ✅ full stats | ✅ this server only | ✅ full stats | ✅ your daemon only |
| Shared hosting | ❌ disable_functions | ⚠️ entire server | ❌ daemon absent | ⚠️ entire shared daemon |

---

## Installation

### From the back-office

1. Zip the `artcachemanager/` directory
2. Go to **Modules → Module Manager → Upload a module**
3. Upload the zip and click **Install**

### Via CLI (recommended for dev environments)

```bash
php bin/console prestashop:module install artcachemanager
```

---

## How cache state is read

PrestaShop stores the cache on/off switch and the active adapter in
`app/config/parameters.php` (keys `ps_cache_enable` and `ps_caching`).
This file is written by `PhpParameters::saveConfiguration()` when the operator saves the *Performance* page. **The `ps_configuration` database table is never updated** at runtime for these settings.

This module reads `parameters.php` directly — the same source PrestaShop's Symfony kernel uses at boot time — so it always reflects the true current state, regardless of multi-shop context (the cache setting is global and applies to all shops identically).

---

## Auto-clear hook behaviour

When the **auto-clear** options are enabled, the module listens to:

| Hook | Fired by |
|---|---|
| `actionClearCache` | Programmatic cache clears in PS 1.7.7+ |
| `actionAdminPerformanceSave` | Saving the Performance admin page |

Both hooks call `opcache_reset()` and/or `Cache::getInstance()->flush()` depending on which options are active.

---

## File structure

```
artcachemanager/
├── artcachemanager.php              Main module class
├── config.xml                       Module metadata
├── views/
│   └── templates/
│       └── admin/
│           └── configure.tpl        Admin configuration page (Smarty)
├── CHANGELOG.md
├── LICENSE
└── README.md
```

---

## License

[Academic Free License 3.0](LICENSE) — © 2009-2026 Tecnoacquisti.com

---

## Support

[https://www.tecnoacquisti.com](https://www.tecnoacquisti.com) · helpdesk@tecnoacquisti.com
