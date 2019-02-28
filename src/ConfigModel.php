<?php

namespace Ichynul\Configx;

use Encore\Admin\Config\ConfigModel;
use Encore\Admin\Auth\Database\Permission;

class ConfigxModel extends ConfigModel
{
    protected $fillable = array('name', 'value', 'description');

    /**
     * Group configs by prefix
     *
     * @param [string] $prefix
     * @return void
     */
    public static function group($prefix)
    {
        return self::where('name', 'like', "{$prefix}.%")->get()->toArray();
    }

    /**
     * Get name prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        $arr = explode('.', $this->name);
        return count($arr) ? $arr[0] : '';
    }

    /**
     * Create prefix permission
     *
     * @param [string] $prefix
     * @return void
     */
    public static function createPermission($prefix, $name)
    {
        $slug = 'confix.tab.' . $prefix;
        $name = trans('admin.configx.header') . '-' . $name;
        if (Permission::where('slug', $slug)->orWhere('name', $name)->first()) {
            return;
        }
        Permission::create([
            'name'      => $name,
            'slug'      => $slug,
            'http_path' => '/configx/*'
        ]);
    }
}
