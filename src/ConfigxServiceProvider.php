<?php

namespace Ichynul\Configx;

use Illuminate\Support\ServiceProvider;

class ConfigxServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Configx $extension)
    {
        if (! Configx::boot()) {
            return ;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'configx');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/laravel-admin-ext/configx')],
                'configx'
            );
        }

        $this->app->booted(function () {
            Configx::routes(__DIR__.'/../routes/web.php');
        });
    }
}