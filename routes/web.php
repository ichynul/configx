<?php

use Ichynul\Configx\Http\Controllers\ConfigxController;

Route::get('configx/edit', ConfigxController::class.'@edit');
Route::post('configx/saveall', ConfigxController::class.'@saveall');
Route::put('configx/sort', ConfigxController::class.'@sort');