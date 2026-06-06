<?php
/**
 * Art Cache Manager
 *
 * Register hooks introduced in version 1.0.5.
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License (AFL) v. 3.0
 *
 * @version   1.0.5
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 1.0.5.
 *
 * @param Module $module The module instance being upgraded
 *
 * @return bool True when all hooks are registered
 */
function upgrade_module_1_0_5($module)
{
    return $module->registerHook('actionModuleInstallAfter')
        && $module->registerHook('actionModuleUpgradeAfter')
        && $module->registerHook('actionModuleUninstallAfter');
}
