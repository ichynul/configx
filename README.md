laravel-admin configx
======

## Installation

need to install laravel-admin-ext/config first, see https://github.com/laravel-admin-extensions/config

Then run :
```
$ composer require ichynul/configx
```
Then run: 
```
$ php artisan admin:import configx
```
Add a tabs config in `config/admin.php`:
```php
    'extensions' => [
        'configx' => [
            'config' => [
                'tabs' => [
                    'base' => '基本设置',
                    'shop' => '店铺设置',
                    'uplaod' => '上传设置'
                ]
            ]
        ],
    ],

```

Open `http://your-host/admin/configx`

## Usage

The keys needs start with tab-keys in config :
## demo
+ base.site_name
+ base.site_tel
+ base.site_logo
+ base.site_open
+ ...
+ shop.shipping_compnay
+ shop.open_time
+ shop.open_date
+ ...
+ uplaod.size_limit
+ uplaod.allow_type
+ ...

Add a lang config in `resources/lang/{zh-CN}/admin.php`
```php
'configx' => [
        'new_config_type' => '新配置类型',
        'new_config_key' => '新配置key',
        'new_config_element' => '新配置表单元素',
        'new_config_options' => '新配置表扩展项',
        'header' => '网站设置',
        'desc' => '网站设置设置',
        'base' => [
            'site_name' => '网站名称',
            'site_logo_image' => '网站logo',
            'open_if' => '网站开关'
        ],
        'element' => [
            'normal' => '默认',
            'date' => '日期',
            'time' => '时间',
            'datetime' => '日期时间',
            'image' => '图片',
            'yes_or_no' => '是或否',
            'editor' => '编辑器',
            'radio_group' => '单选框组',
            'checkbox_group' => '多选框组',
            'number' => '数字',
            'rate' => '比例',
            'select' => '下拉框'
        ]
    ],
'yes' => '是',
'no' => '否'
```
you can click "+" to add an new config key :

step 1 select config type from ['base' , 'shop' , 'uplaod']

step 2 select form-element type from ['normal', 'date', 'time', 'datetime', 'image', 'yes_or_no', 'number', 'rate', 'editor', 'radio_group' ,'checkbox_group', 'select'] 

step 3 if you selected form-element type is ['radio_group' ,'checkbox_group', 'select'] ,you need input [options]

```html
just text:

text1
text2
...

and key-text:

key1:text1
key2:text2

```
this will save a config named '__configx__' like this ：
```json
{
    "shop.shipping_compnay":{
        "options":{
            "中兴":"中兴",
            "华为":"华为",
            "小米":"小米"
        },
        "element":"radio_group"
    },
    "shop.os_type":{
        "options":{
            "android":"安卓",
            "ios":"苹果",
            "other":"其他"
        },
        "element":"checkbox_group"
    },
    "base.open":{
        "options":[

        ],
        "element":"normal"
    }
}
```
you can copy it to json-eidtor and change it .
...

if you need add a new config tab, chang it in `config/admin.php`.

After add config in the panel, use `config($key)` to get value you configured.

License
------------
Licensed under [The MIT License (MIT)](LICENSE).