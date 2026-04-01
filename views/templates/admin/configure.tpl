{*
 * Art Cache Manager — configure.tpl
 * Compatible with PrestaShop 1.7 → 9.x (Bootstrap 3/4/5 safe)
 *}

<style>
.artcm-section { margin-bottom: 24px; }
.artcm-badge   { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.artcm-badge-ok   { background: #dff0d8; color: #3c763d; }
.artcm-badge-warn { background: #fcf8e3; color: #8a6d3b; }
.artcm-badge-err  { background: #f2dede; color: #a94442; }
.artcm-progress-wrap { background: #e9ecef; border-radius: 4px; height: 18px; overflow: hidden; margin: 4px 0; }
.artcm-progress-bar  { height: 100%; border-radius: 4px; transition: width .4s ease; font-size: 11px; line-height: 18px; color: #fff; text-align: right; padding-right: 6px; }
.artcm-tbl td, .artcm-tbl th { padding: 5px 10px; }
.artcm-tbl th { font-weight: 600; color: #555; width: 200px; }
.artcm-note   { font-size: 12px; color: #777; margin-top: 4px; }
</style>

{* ── Flash messages ─────────────────────────────────────────────────── *}
{foreach from=$artcm_messages item=msg}
<div class="alert alert-{$msg.type|escape:'html':'UTF-8'}" role="alert">
    {$msg.text|escape:'html':'UTF-8'}
</div>
{/foreach}

<div class="row artcm-section">

{* ═══════════════════════════════════════════════════════════════════
   OPcache panel
═══════════════════════════════════════════════════════════════════ *}
    <div class="col-lg-6 col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-bolt"></i>
                {l s='OPcache' mod='artcachemanager'}
            </div>
            <div class="panel-body">

            {if !$artcm_opcache.available}

                <div class="alert alert-warning">
                    {if $artcm_opcache.reason == 'extension_not_loaded'}
                        {l s='OPcache extension is not loaded (opcache.so / zend_extension).' mod='artcachemanager'}
                    {else}
                        {l s='OPcache is disabled (opcache.enable=0 in php.ini / vhost).' mod='artcachemanager'}
                    {/if}
                </div>

            {elseif !$artcm_opcache.enabled}

                <div class="alert alert-warning">
                    {l s='OPcache extension is loaded but currently disabled.' mod='artcachemanager'}
                </div>

            {else}

                {* validate_timestamps notice *}
                {if !$artcm_opcache.validate_timestamps}
                <div class="alert alert-info" style="font-size:12px;padding:8px 12px;">
                    <strong>{l s='opcache.validate_timestamps = 0' mod='artcachemanager'}</strong> —
                    {l s='PHP will NOT auto-detect file changes. Use the Clear button after every deploy.' mod='artcachemanager'}
                </div>
                {/if}

                {* Restart warning *}
                {if $artcm_opcache.restart_pending || $artcm_opcache.restart_in_progress}
                <div class="alert alert-warning" style="font-size:12px;padding:8px 12px;">
                    {l s='A restart is pending or in progress. OPcache is being reset.' mod='artcachemanager'}
                </div>
                {/if}

                {* Memory bar *}
                <p style="margin-bottom:2px;font-weight:600;">{l s='Memory usage' mod='artcachemanager'}</p>
                <div class="artcm-progress-wrap">
                    {assign var="bar_color" value="#5cb85c"}
                    {if $artcm_opcache.used_pct > 80}{assign var="bar_color" value="#d9534f"}{/if}
                    {if $artcm_opcache.used_pct > 60}{assign var="bar_color" value="#f0ad4e"}{/if}
                    <div class="artcm-progress-bar"
                         style="width:{$artcm_opcache.used_pct|escape:'html':'UTF-8'}%; background:{$bar_color};">
                         {$artcm_opcache.used_pct|escape:'html':'UTF-8'}%
                    </div>
                </div>
                <p class="artcm-note">
                    {l s='Used' mod='artcachemanager'}: {$artcm_opcache.memory_used_fmt|escape:'html':'UTF-8'} &nbsp;|&nbsp;
                    {l s='Free' mod='artcachemanager'}: {$artcm_opcache.memory_free_fmt|escape:'html':'UTF-8'} &nbsp;|&nbsp;
                    {l s='Total' mod='artcachemanager'}: {$artcm_opcache.memory_total_fmt|escape:'html':'UTF-8'}
                    {if $artcm_opcache.wasted_pct > 0}
                    &nbsp;|&nbsp; {l s='Wasted' mod='artcachemanager'}: {$artcm_opcache.wasted_pct|escape:'html':'UTF-8'}%
                    {/if}
                </p>

                {* Hit rate bar *}
                <p style="margin-bottom:2px;font-weight:600;">{l s='Hit rate' mod='artcachemanager'}</p>
                <div class="artcm-progress-wrap">
                    {assign var="hit_color" value="#5cb85c"}
                    {if $artcm_opcache.hit_rate < 80}{assign var="hit_color" value="#f0ad4e"}{/if}
                    {if $artcm_opcache.hit_rate < 50}{assign var="hit_color" value="#d9534f"}{/if}
                    <div class="artcm-progress-bar"
                         style="width:{$artcm_opcache.hit_rate|escape:'html':'UTF-8'}%; background:{$hit_color};">
                         {$artcm_opcache.hit_rate|escape:'html':'UTF-8'}%
                    </div>
                </div>

                {* Stats table *}
                <table class="artcm-tbl" style="margin-top:12px;width:100%;">
                    <tr>
                        <th>{l s='Cached scripts' mod='artcachemanager'}</th>
                        <td>{$artcm_opcache.cached_scripts|intval}</td>
                    </tr>
                    <tr>
                        <th>{l s='Max cached keys' mod='artcachemanager'}</th>
                        <td>{$artcm_opcache.max_cached_keys|intval}</td>
                    </tr>
                    <tr>
                        <th>{l s='Hits / Misses' mod='artcachemanager'}</th>
                        <td>{$artcm_opcache.hits|intval} / {$artcm_opcache.misses|intval}</td>
                    </tr>
                    <tr>
                        <th>{l s='Memory consumption (ini)' mod='artcachemanager'}</th>
                        <td>{$artcm_opcache.memory_consumption|escape:'html':'UTF-8'} MB</td>
                    </tr>
                    <tr>
                        <th>{l s='Max accelerated files (ini)' mod='artcachemanager'}</th>
                        <td>{$artcm_opcache.max_accelerated_files|escape:'html':'UTF-8'}</td>
                    </tr>
                    <tr>
                        <th>{l s='Revalidate freq (ini)' mod='artcachemanager'}</th>
                        <td>
                            {$artcm_opcache.revalidate_freq|intval}s
                            {if !$artcm_opcache.validate_timestamps}
                            &nbsp;<span class="artcm-badge artcm-badge-warn">{l s='ignored (validate_timestamps=0)' mod='artcachemanager'}</span>
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <th>{l s='validate_timestamps' mod='artcachemanager'}</th>
                        <td>
                            {if $artcm_opcache.validate_timestamps}
                            <span class="artcm-badge artcm-badge-ok">{l s='ON' mod='artcachemanager'}</span>
                            {else}
                            <span class="artcm-badge artcm-badge-warn">{l s='OFF' mod='artcachemanager'}</span>
                            {/if}
                        </td>
                    </tr>
                </table>

            {/if}{* available *}
            </div>

            {if $artcm_opcache.available && $artcm_opcache.enabled}
            <div class="panel-footer">
                <form method="post" action="{$artcm_form_action|escape:'html':'UTF-8'}">
                    <button type="submit" name="artcm_clear_opcache" class="btn btn-danger"
                            onclick="return confirm('{l s='Clear OPcache now?' mod='artcachemanager' js=1}');">
                        <i class="icon-trash"></i>
                        {l s='Clear OPcache' mod='artcachemanager'}
                    </button>
                </form>
            </div>
            {/if}
        </div>{* panel *}
    </div>{* col *}

{* ═══════════════════════════════════════════════════════════════════
   Memcached panel
═══════════════════════════════════════════════════════════════════ *}
    <div class="col-lg-6 col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-tasks"></i>
                {l s='Memcached / Memcache' mod='artcachemanager'}
            </div>
            <div class="panel-body">

            {if !$artcm_memcached.active}

                <div class="alert alert-info">
                    {if $artcm_memcached.reason == 'ps_cache_disabled'}
                        {l s='PrestaShop external cache is disabled (Admin → Performance).' mod='artcachemanager'}
                    {elseif $artcm_memcached.reason == 'not_memcached'}
                        {l s='Active cache system:' mod='artcachemanager'}
                        <strong>{$artcm_memcached.system|default:'none'|escape:'html':'UTF-8'}</strong>
                        — {l s='not Memcached/Memcache.' mod='artcachemanager'}
                    {else}
                        {l s='Memcached/Memcache is not active.' mod='artcachemanager'}
                    {/if}
                </div>

            {else}

                <table class="artcm-tbl" style="width:100%;margin-bottom:12px;">
                    <tr>
                        <th>{l s='Cache system' mod='artcachemanager'}</th>
                        <td><span class="artcm-badge artcm-badge-ok">{$artcm_memcached.system|escape:'html':'UTF-8'}</span></td>
                    </tr>
                    <tr>
                        <th>{l s='Servers configured' mod='artcachemanager'}</th>
                        <td>{$artcm_memcached.servers|@count}</td>
                    </tr>
                </table>

                {if isset($artcm_memcached.stats.error)}

                    <div class="alert alert-danger">
                        {$artcm_memcached.stats.error|escape:'html':'UTF-8'}
                    </div>

                {else}

                    {* Totals *}
                    {assign var="totals" value=$artcm_memcached.stats.totals}
                    <p style="font-weight:600;margin-bottom:4px;">{l s='Global totals' mod='artcachemanager'}</p>

                    {* Hit rate bar *}
                    <div class="artcm-progress-wrap">
                        {assign var="mc_hit_color" value="#5cb85c"}
                        {if $totals.hit_rate < 80}{assign var="mc_hit_color" value="#f0ad4e"}{/if}
                        {if $totals.hit_rate < 50}{assign var="mc_hit_color" value="#d9534f"}{/if}
                        <div class="artcm-progress-bar"
                             style="width:{$totals.hit_rate|escape:'html':'UTF-8'}%; background:{$mc_hit_color};">
                             {$totals.hit_rate|escape:'html':'UTF-8'}%
                        </div>
                    </div>
                    <p class="artcm-note">
                        {l s='Hit rate' mod='artcachemanager'}: {$totals.hit_rate|escape:'html':'UTF-8'}%
                        &nbsp;|&nbsp; {l s='Hits' mod='artcachemanager'}: {$totals.hits|intval}
                        &nbsp;|&nbsp; {l s='Misses' mod='artcachemanager'}: {$totals.misses|intval}
                    </p>

                    <table class="artcm-tbl" style="width:100%;margin-top:8px;">
                        <tr>
                            <th>{l s='Items in cache' mod='artcachemanager'}</th>
                            <td>{$totals.items|intval}</td>
                        </tr>
                        <tr>
                            <th>{l s='Memory used' mod='artcachemanager'}</th>
                            <td>{$totals.bytes_fmt|escape:'html':'UTF-8'}</td>
                        </tr>
                        <tr>
                            <th>{l s='Evictions' mod='artcachemanager'}</th>
                            <td>{$totals.evictions|intval}</td>
                        </tr>
                    </table>

                    {* Per-server details *}
                    {if $artcm_memcached.stats.servers|@count > 1}
                    <p style="font-weight:600;margin:12px 0 4px;">{l s='Per-server details' mod='artcachemanager'}</p>
                    {foreach from=$artcm_memcached.stats.servers item=srv}
                    <div style="border:1px solid #ddd;border-radius:4px;padding:8px;margin-bottom:8px;">
                        <strong>{$srv.host|escape:'html':'UTF-8'}</strong>
                        &nbsp;<span class="artcm-badge artcm-badge-ok">v{$srv.version|escape:'html':'UTF-8'}</span>
                        <table class="artcm-tbl" style="width:100%;margin-top:6px;">
                            <tr><th>{l s='Uptime' mod='artcachemanager'}</th><td>{$srv.uptime_fmt|escape:'html':'UTF-8'}</td></tr>
                            <tr><th>{l s='Items' mod='artcachemanager'}</th><td>{$srv.items|intval}</td></tr>
                            <tr><th>{l s='Memory used' mod='artcachemanager'}</th><td>{$srv.bytes_fmt|escape:'html':'UTF-8'} / {$srv.max_bytes_fmt|escape:'html':'UTF-8'}</td></tr>
                            <tr><th>{l s='Connections' mod='artcachemanager'}</th><td>{$srv.conns|intval}</td></tr>
                        </table>
                    </div>
                    {/foreach}
                    {/if}

                {/if}{* stats.error *}

            {/if}{* active *}
            </div>

            {if $artcm_memcached.active && !isset($artcm_memcached.stats.error)}
            <div class="panel-footer">
                <form method="post" action="{$artcm_form_action|escape:'html':'UTF-8'}">
                    <button type="submit" name="artcm_clear_memcached" class="btn btn-danger"
                            onclick="return confirm('{l s='Flush all Memcached data now?' mod='artcachemanager' js=1}');">
                        <i class="icon-trash"></i>
                        {l s='Flush Memcached' mod='artcachemanager'}
                    </button>
                </form>
            </div>
            {/if}
        </div>{* panel *}
    </div>{* col *}
</div>{* row *}

{* ═══════════════════════════════════════════════════════════════════
   Configuration panel
═══════════════════════════════════════════════════════════════════ *}
<div class="panel artcm-section">
    <div class="panel-heading">
        <i class="icon-cog"></i>
        {l s='Configuration — auto-clear on PrestaShop cache flush' mod='artcachemanager'}
    </div>
    <div class="panel-body">
        <p style="color:#555;margin-bottom:16px;">
            {l s='When PrestaShop clears its own cache (Performance page or programmatic flush), also clear:' mod='artcachemanager'}
        </p>
        <form method="post" action="{$artcm_form_action|escape:'html':'UTF-8'}">

            <div class="form-group">
                <label class="control-label" style="font-weight:600;">
                    <input type="checkbox"
                           name="artcm_opcache_with_ps"
                           value="1"
                           {if $artcm_cfg_opcache_ps}checked="checked"{/if}
                           style="margin-right:6px;">
                    {l s='Clear OPcache when PrestaShop clears its cache' mod='artcachemanager'}
                </label>
                <p class="help-block artcm-note">
                    {l s='Recommended when opcache.validate_timestamps = 0 (production vhost). Hooks: actionClearCache, actionAdminPerformanceSave.' mod='artcachemanager'}
                </p>
            </div>

            <div class="form-group">
                <label class="control-label" style="font-weight:600;">
                    <input type="checkbox"
                           name="artcm_memcached_with_ps"
                           value="1"
                           {if $artcm_cfg_memcached_ps}checked="checked"{/if}
                           {if !$artcm_memcached.active}disabled="disabled"{/if}
                           style="margin-right:6px;">
                    {l s='Flush Memcached when PrestaShop clears its cache' mod='artcachemanager'}
                </label>
                {if !$artcm_memcached.active}
                <p class="help-block artcm-note" style="color:#a94442;">
                    {l s='(Memcached/Memcache is not active — this option has no effect)' mod='artcachemanager'}
                </p>
                {/if}
            </div>

            <button type="submit" name="artcm_save_config" class="btn btn-primary">
                <i class="icon-save"></i>
                {l s='Save configuration' mod='artcachemanager'}
            </button>
        </form>
    </div>
</div>
