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

        $this->app->booted(function () {
            Configx::routes(__DIR__.'/../routes/web.php');
        });
    }
}