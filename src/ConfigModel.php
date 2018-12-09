<?php

namespace Ichynul\Configx;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Config\ConfigModel;

class ConfigxModel extends ConfigModel
{
    protected $fillable = array('name', 'value', 'description');

    public static function group($prefix)
    {
        return self::where('name', 'like', "%{$prefix}.%")/*->orderBy('name')*/->get()->toArray();
    }
}
