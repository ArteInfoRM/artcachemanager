<?php
/**
 * Art Cache Manager
 *
 * Monitor and clear OPcache and Memcached directly from the PrestaShop back-office.
 * Compatible with PrestaShop 1.7 → 9.x
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL) v. 3.0
 * @version   1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Artcachemanager extends Module
{
    const CFG_OPCACHE_WITH_PS   = 'ARTCM_OPCACHE_WITH_PS';
    const CFG_MEMCACHED_WITH_PS = 'ARTCM_MEMCACHED_WITH_PS';

    public function __construct()
    {
        $this->name          = 'artcachemanager';
        $this->tab           = 'administration';
        $this->version       = '1.0.0';
        $this->author        = 'Tecnoacquisti.com';
        $this->need_instance = 0;
        $this->bootstrap     = true;

        parent::__construct();

        $this->displayName = $this->l('Art Cache Manager');
        $this->description = $this->l(
            'Monitor and clear OPcache and Memcached caches directly from the PrestaShop back-office.'
        );
        $this->ps_versions_compliancy = ['min' => '1.7.0', 'max' => _PS_VERSION_];
    }

    /* ── lifecycle ──────────────────────────────────────────────────── */

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionClearCache')
            && $this->registerHook('actionAdminPerformanceSave')
            && Configuration::updateValue(self::CFG_OPCACHE_WITH_PS, 0)
            && Configuration::updateValue(self::CFG_MEMCACHED_WITH_PS, 0);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName(self::CFG_OPCACHE_WITH_PS)
            && Configuration::deleteByName(self::CFG_MEMCACHED_WITH_PS);
    }

    /* ── hooks ──────────────────────────────────────────────────────── */

    /**
     * Fired by PS when cache is cleared programmatically (PS 1.7.7+).
     */
    public function hookActionClearCache($params)
    {
        $this->autoClear();
    }

    /**
     * Fired when the Performance admin page is saved (also triggers PS cache clearing).
     */
    public function hookActionAdminPerformanceSave($params)
    {
        $this->autoClear();
    }

    private function autoClear()
    {
        if ((int) Configuration::get(self::CFG_OPCACHE_WITH_PS)) {
            $this->clearOpcache();
        }
        if ((int) Configuration::get(self::CFG_MEMCACHED_WITH_PS)) {
            $this->clearMemcached();
        }
    }

    /* ── admin configure page ───────────────────────────────────────── */

    public function getContent()
    {
        $messages = [];

        if (Tools::isSubmit('artcm_save_config')) {
            $this->saveConfig();
            $messages[] = ['type' => 'success', 'text' => $this->l('Configuration saved.')];
        }

        if (Tools::isSubmit('artcm_clear_opcache')) {
            $ok = $this->clearOpcache();
            $messages[] = $ok
                ? ['type' => 'success', 'text' => $this->l('OPcache cleared successfully.')]
                : ['type' => 'danger',  'text' => $this->l('OPcache clear failed or OPcache not available.')];
        }

        if (Tools::isSubmit('artcm_clear_memcached')) {
            $ok = $this->clearMemcached();
            $messages[] = $ok
                ? ['type' => 'success', 'text' => $this->l('Memcached cleared successfully.')]
                : ['type' => 'danger',  'text' => $this->l('Memcached clear failed or Memcached not active.')];
        }

        $opcache   = $this->getOpcacheStatus();
        $memcached = $this->getMemcachedInfo();

        // Pre-format byte values and compute CSS color classes in PHP so the
        // template never needs to put dynamic values inside style="" attributes.
        if ($opcache['available'] && $opcache['enabled']) {
            $opcache['memory_total_fmt']  = $this->formatBytes($opcache['memory_total']);
            $opcache['memory_used_fmt']   = $this->formatBytes($opcache['memory_used']);
            $opcache['memory_free_fmt']   = $this->formatBytes($opcache['memory_free']);
            $opcache['memory_wasted_fmt'] = $this->formatBytes($opcache['memory_wasted']);
            $opcache['bar_color_class']   = $this->pctColorClass($opcache['used_pct'], false);
            $opcache['hit_color_class']   = $this->pctColorClass($opcache['hit_rate'], true);
        }

        if (!empty($memcached['stats']['servers'])) {
            foreach ($memcached['stats']['servers'] as &$srv) {
                $srv['bytes_fmt']     = $this->formatBytes((int) $srv['bytes']);
                $srv['max_bytes_fmt'] = $this->formatBytes((int) $srv['max_bytes']);
                $srv['uptime_fmt']    = $this->formatUptime((int) $srv['uptime']);
            }
            unset($srv);
            $memcached['stats']['totals']['bytes_fmt']       = $this->formatBytes(
                (int) $memcached['stats']['totals']['bytes']
            );
            $memcached['stats']['totals']['hit_color_class'] = $this->pctColorClass(
                $memcached['stats']['totals']['hit_rate'],
                true
            );
        }

        $formAction = AdminController::$currentIndex
            . '&configure=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $this->context->smarty->assign([
            'artcm_messages'         => $messages,
            'artcm_form_action'      => $formAction,
            'artcm_opcache'          => $opcache,
            'artcm_memcached'        => $memcached,
            'artcm_cfg_opcache_ps'   => (int) Configuration::get(self::CFG_OPCACHE_WITH_PS),
            'artcm_cfg_memcached_ps' => (int) Configuration::get(self::CFG_MEMCACHED_WITH_PS),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    private function saveConfig()
    {
        Configuration::updateValue(
            self::CFG_OPCACHE_WITH_PS,
            (int) (bool) Tools::getValue('artcm_opcache_with_ps')
        );
        Configuration::updateValue(
            self::CFG_MEMCACHED_WITH_PS,
            (int) (bool) Tools::getValue('artcm_memcached_with_ps')
        );
    }

    /* ── OPcache ────────────────────────────────────────────────────── */

    public function getOpcacheStatus(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['available' => false, 'reason' => 'extension_not_loaded'];
        }

        $status = @opcache_get_status(false);
        if ($status === false || empty($status)) {
            return ['available' => false, 'reason' => 'disabled'];
        }

        $mem   = $status['memory_usage'];
        $total = $mem['used_memory'] + $mem['free_memory'] + $mem['wasted_memory'];
        $stats = $status['opcache_statistics'];
        $hits   = (int) $stats['hits'];
        $misses = (int) $stats['misses'];

        return [
            'available'              => true,
            'enabled'                => (bool) $status['opcache_enabled'],
            'memory_total'           => $total,
            'memory_used'            => (int) $mem['used_memory'],
            'memory_free'            => (int) $mem['free_memory'],
            'memory_wasted'          => (int) $mem['wasted_memory'],
            'used_pct'               => (int) $total > 0 ? round($mem['used_memory'] / $total * 100, 1) : 0,
            'wasted_pct'             => round((float) ($mem['current_wasted_percentage'] ?? 0), 2),
            'cached_scripts'         => (int) $stats['num_cached_scripts'],
            'max_cached_keys'        => (int) $stats['max_cached_keys'],
            'hits'                   => $hits,
            'misses'                 => $misses,
            'hit_rate'               => ($hits + $misses) > 0
                ? round($hits / ($hits + $misses) * 100, 2) : 0,
            'restart_pending'        => (bool) ($status['restart_pending'] ?? false),
            'restart_in_progress'    => (bool) ($status['restart_in_progress'] ?? false),
            'validate_timestamps'    => (bool) ini_get('opcache.validate_timestamps'),
            'memory_consumption'     => ini_get('opcache.memory_consumption'),
            'max_accelerated_files'  => ini_get('opcache.max_accelerated_files'),
            'revalidate_freq'        => ini_get('opcache.revalidate_freq'),
        ];
    }

    public function clearOpcache(): bool
    {
        if (!function_exists('opcache_reset')) {
            return false;
        }
        return (bool) opcache_reset();
    }

    /* ── Memcached ──────────────────────────────────────────────────── */

    public function getMemcachedInfo(): array
    {
        $params    = $this->readParametersPhp();
        $psEnabled = isset($params['ps_cache_enable']) ? (bool) $params['ps_cache_enable'] : false;

        if (!$psEnabled) {
            return ['active' => false, 'reason' => 'ps_cache_disabled'];
        }

        $system = isset($params['ps_caching']) ? (string) $params['ps_caching'] : '';
        if (!in_array($system, ['CacheMemcache', 'CacheMemcached'], true)) {
            return ['active' => false, 'reason' => 'not_memcached', 'system' => $system ?: 'none'];
        }

        $servers        = $this->getMemcachedServers();
        $useNewExt      = ($system === 'CacheMemcached');
        $stats          = $this->fetchMemcachedStats($servers, $useNewExt);

        return [
            'active'       => true,
            'system'       => $system,
            'servers'      => $servers,
            'server_count' => count($servers),
            'stats'        => $stats,
        ];
    }

    private function getMemcachedServers(): array
    {
        // PS 1.7+ stores Memcached servers in ps_memcached_servers table
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT `ip`, `port`, `weight`
                   FROM `' . _DB_PREFIX_ . 'memcached_servers`
                  LIMIT 50'
            );
            if (is_array($rows) && count($rows) > 0) {
                return $rows;
            }
        } catch (Exception $e) {
            // table may not exist in this PS version
        }

        return [['ip' => '127.0.0.1', 'port' => 11211, 'weight' => 1]];
    }

    private function fetchMemcachedStats(array $servers, bool $useNewExt): array
    {
        // Prefer PHP Memcached extension (CacheMemcached)
        if ($useNewExt && class_exists('Memcached')) {
            try {
                $m = new Memcached();
                foreach ($servers as $s) {
                    $m->addServer((string) $s['ip'], (int) $s['port'], (int) ($s['weight'] ?? 1));
                }
                $raw = $m->getStats();
                if (is_array($raw) && count($raw) > 0) {
                    return $this->aggregateStats($raw);
                }
            } catch (Exception $e) {
                // fall through
            }
        }

        // Fallback to PHP Memcache extension (CacheMemcache)
        if (class_exists('Memcache')) {
            try {
                $m = new Memcache();
                foreach ($servers as $s) {
                    @$m->addServer((string) $s['ip'], (int) $s['port']);
                }
                $raw = @$m->getExtendedStats();
                if (is_array($raw) && count($raw) > 0) {
                    return $this->aggregateStats($raw);
                }
            } catch (Exception $e) {
                // fall through
            }
        }

        return ['error' => $this->l('Cannot connect to Memcached/Memcache server(s).')];
    }

    private function aggregateStats(array $perHostStats): array
    {
        $servers = [];
        $totals  = [
            'items' => 0, 'bytes' => 0,
            'hits'  => 0, 'misses' => 0, 'evictions' => 0,
        ];

        foreach ($perHostStats as $host => $s) {
            if (!is_array($s)) {
                continue;
            }
            $servers[] = [
                'host'      => $host,
                'version'   => $s['version'] ?? '?',
                'uptime'    => (int) ($s['uptime'] ?? 0),
                'items'     => (int) ($s['curr_items'] ?? 0),
                'bytes'     => (int) ($s['bytes'] ?? 0),
                'max_bytes' => (int) ($s['limit_maxbytes'] ?? 0),
                'hits'      => (int) ($s['get_hits'] ?? 0),
                'misses'    => (int) ($s['get_misses'] ?? 0),
                'conns'     => (int) ($s['curr_connections'] ?? 0),
            ];
            $totals['items']     += (int) ($s['curr_items'] ?? 0);
            $totals['bytes']     += (int) ($s['bytes'] ?? 0);
            $totals['hits']      += (int) ($s['get_hits'] ?? 0);
            $totals['misses']    += (int) ($s['get_misses'] ?? 0);
            $totals['evictions'] += (int) ($s['evictions'] ?? 0);
        }

        $totalOps = $totals['hits'] + $totals['misses'];
        $totals['hit_rate'] = $totalOps > 0 ? round($totals['hits'] / $totalOps * 100, 2) : 0;

        return ['servers' => $servers, 'totals' => $totals];
    }

    public function clearMemcached(): bool
    {
        $params    = $this->readParametersPhp();
        $psEnabled = isset($params['ps_cache_enable']) ? (bool) $params['ps_cache_enable'] : false;
        $system    = isset($params['ps_caching']) ? (string) $params['ps_caching'] : '';

        if (!$psEnabled || !in_array($system, ['CacheMemcache', 'CacheMemcached'], true)) {
            return false;
        }

        // Prefer PS Cache facade — it uses the already-configured connection
        try {
            if (class_exists('Cache') && method_exists('Cache', 'getInstance')) {
                $cache = Cache::getInstance();
                if (method_exists($cache, 'flush')) {
                    $cache->flush();
                    return true;
                }
            }
        } catch (Exception $e) {
            // fall through to direct connection
        }

        // Direct connection fallback
        $servers = $this->getMemcachedServers();

        if ($system === 'CacheMemcached' && class_exists('Memcached')) {
            try {
                $m = new Memcached();
                foreach ($servers as $s) {
                    $m->addServer((string) $s['ip'], (int) $s['port']);
                }
                return $m->flush();
            } catch (Exception $e) {
                // fall through
            }
        }

        if (class_exists('Memcache')) {
            try {
                $m = new Memcache();
                foreach ($servers as $s) {
                    if (@$m->connect((string) $s['ip'], (int) $s['port'])) {
                        return (bool) $m->flush();
                    }
                }
            } catch (Exception $e) {
                // fall through
            }
        }

        return false;
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    /**
     * Returns the Symfony parameters array from app/config/parameters.php.
     *
     * In PS 1.7+ cache state is stored here (ps_cache_enable, ps_caching) by
     * PhpParameters::saveConfiguration(). ps_configuration is never updated.
     * The file is required for Symfony to boot, so it is always present.
     *
     * @return array<string, mixed>
     */
    private function readParametersPhp(): array
    {
        $file = _PS_ROOT_DIR_ . '/app/config/parameters.php';
        if (!is_readable($file)) {
            return [];
        }
        $data = @include $file;
        if (!is_array($data) || !isset($data['parameters'])) {
            return [];
        }
        return $data['parameters'];
    }

    /**
     * Returns a CSS class name (green / amber / red) for a progress bar.
     *
     * @param float $pct      The percentage value (0–100)
     * @param bool  $highGood true  → high value = good (hit rates)
     *                        false → high value = bad  (memory usage)
     */
    private function pctColorClass(float $pct, bool $highGood): string
    {
        if ($highGood) {
            if ($pct >= 80) {
                return 'green';
            }
            if ($pct >= 50) {
                return 'amber';
            }
            return 'red';
        }
        if ($pct > 80) {
            return 'red';
        }
        if ($pct > 60) {
            return 'amber';
        }
        return 'green';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function formatUptime(int $seconds): string
    {
        $parts = [];
        $days  = (int) floor($seconds / 86400);
        $hours = (int) floor(($seconds % 86400) / 3600);
        $mins  = (int) floor(($seconds % 3600) / 60);
        if ($days) {
            $parts[] = $days . 'd';
        }
        if ($hours) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $mins . 'm';
        return implode(' ', $parts);
    }
}
