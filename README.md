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
+ base.site_logo_image (As Image uploader)
+ base.site_open_if (As Radio group)
+ ...
+ shop.shipping_compnay
+ shop.open_time (As Time piker)
+ shop.open_date (As Date piker)
+ ...
+ uplaod.size_limit
+ uplaod.allow_type
+ ...

Add a lang config in `resources/lang/{zh-CN}/admin.php`
```php
'configx' => [
        'base' => [
            'site_name' => '网站名称',
            'site_tel' => '联系电话',
            'site_logo_image' => '网站logo',
            'site_open_if' => '网站开启'
        ],
         'shop' => [
            'shipping_compnay' => '店铺名称',
            'open_time' => '开启时间',
            'open_date' => '开启日期'
        ],
        'uplaod' => [
            'size_limit' => '大小限制',
            'allow_type' => '允许类型'
        ]
    ],
    'yes' => '是',
    'no' => '否'
```
you can click "+" to add an new config key .

if you need add a new config tab, chang it in `config/admin.php`.

After add config in the panel, use `config($key)` to get value you configured.

License
------------
Licensed under [The MIT License (MIT)](LICENSE).