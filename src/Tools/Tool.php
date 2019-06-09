<?php

namespace Ichynul\Configx\Tools;

use Ichynul\Configx\Configx;
use Ichynul\Configx\ConfigxModel;

class Tool
{
    public static function optionsFilter($options)
    {
        $_options = [];

        foreach ($options as $k => $v) {
            if (preg_match('/^@\w+/', $k)) {
                continue;
            }
            if ($k == 'divide' && in_array($v, ['befor', 'after'])) {
                continue;
            }

            $_options[$k] = $v;
        }

        return $_options;
    }

    public static function callUserfunctions($field, $options)
    {
        foreach ($options as $k => $v) {
            if (preg_match('/^@\w+/', $k)) {
                $args = array_filter(explode(',', $v));
                $args = collect($args)->map(function ($s) {
                    $s = trim($s);
                    if (preg_match('/^\d+$/', $s)) {
                        return intval($s);
                    }
                    if (preg_match('/^\d+\.\d+$/', $s)) {
                        return doubleval($s);
                    }
                    if (preg_match('/^(false|true)$/i', $s)) {
                        return strtolower($s) == 'true';
                    }
                    return preg_replace("/^\s*['\"]|['\"]\s*$/", '', $s);
                })->all();

                try {
                    call_user_func_array(
                        [$field, str_replace_first('@', '', $k)],
                        $args
                    );
                } catch (\Exception $e) {
                    admin_warning('Error', "'" . $field->label() . "' call method : " . class_basename($field) . '->' . str_replace_first('@', '', $k) . "('" . implode("','", $args) . "')" . ' failed !<br />' . $e->getMessage());
                    \Log::error($e->__toString());
                }
            }
        }

        return $field;
    }

    public static function remove($cx_options)
    {
        $forget = [];
        foreach (array_keys($cx_options) as $k) {
            if ($k == '__configx_tabs__') {
                continue;
            }
            $config = ConfigxModel::where('name', $k)->first();
            if (!$config) {
                $forget[] = $k;
            }
            if (preg_match('/admin_\d+?/i', $k)) {
                $forget[] = $k;
            }
        }
        if (count($forget)) {
            array_forget($cx_options, $forget);
        }
        return $cx_options;
    }

    public static function sortGroupConfigs($configs, $cx_options = [])
    {
        $order = [];
        $i = 0;
        foreach ($configs as $conf) {
            $i += 1;
            if (isset($cx_options[$conf['name']]) && isset($cx_options[$conf['name']]['order'])) {
                $order[] = $cx_options[$conf['name']]['order'] ?: 999 + $i;
            } else {
                $order[] = 999 + $i;
            }
        }
        array_multisort($order, SORT_ASC, $configs);
        return $configs;
    }

    public static function sortTGroupOptions($type, $cx_options = [])
    {
        $subs = ConfigxModel::group($type);

        $subs = static::sortGroupConfigs($subs, $cx_options);

        $i = 0;
        foreach ($subs as $sub) {
            if (isset($cx_options[$sub['name']]) && isset($cx_options[$sub['name']]['table_field'])) {
                continue;
            }
            $i += 5;
            $cx_options[$sub['name']]['order'] = $i;
        }

        return $cx_options;
    }

    public static function createTableConfigs($tableInfo, $cx_options)
    {
        if (empty($tableInfo)) {
            return;
        }
        foreach ($tableInfo as $k => $v) {
            if ($k == $v || '' == $v) {
                $conf = ConfigxModel::where('name', $k)->first();
                if (!$conf) {
                    ConfigxModel::create(['name' => $k, 'value' => '1', 'description' => 'Table field:' . $k]);
                }
            }
            if (!isset($cx_options[$k])) {
                $cx_options[$k] = ['element' => 'normal', 'options' => [], 'help' => '', 'name' => '', 'order' => 999];
            }
            if ($k == $v || '' == $v) {
                $cx_options[$k]['table_field'] = 1;
            } else {
                if (isset($cx_options[$k]['table_field'])) {
                    array_forget($cx_options[$k], 'table_field');
                }
            }
        }
        return $cx_options;
    }

    public static function createPermissions($tabs)
    {
        foreach ($tabs as $key => $val) {
            ConfigxModel::createPermission($key, $val);
        }
    }

    public static function tabs($cx_options = [])
    {
        $tabs = Configx::config('tabs', ['base' => 'Base']);

        if (isset($cx_options['__configx_tabs__']) && isset($cx_options['__configx_tabs__']['options'])) {
            $tabs = !empty($cx_options['__configx_tabs__']['options']) ? $cx_options['__configx_tabs__']['options'] : $tabs;
        }
        return $tabs;
    }
}
