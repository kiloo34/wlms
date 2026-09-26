<?php

use App\Providers\AppServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\FortifyServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ModuleServiceProvider::class,
];
