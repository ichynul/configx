laravel-admin configx
======

## Installation

need to install laravel-admin-ext/config first, see https://github.com/laravel-admin-extensions/config

Then run :
```
$ composer require laravel-admin-ext/configx
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
                    'base' => 'base-tab title',
                    'shop' => 'shop-tab title',
                    'uplaod' => 'uplaod-tab title'
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
+ ...
+ shop.shipping_compnay
+ shop.open_time (As Time piker)
+ shop.open_date (As Date piker)
+ ...
+ uplaod.size_limit
+ uplaod.allow_type
+ ...

you can click "+" to add an new config key .

if you need add a new config tab, chang it in `config/admin.php`.

After add config in the panel, use `config($key)` to get value you configured.

License
------------
Licensed under [The MIT License (MIT)](LICENSE).