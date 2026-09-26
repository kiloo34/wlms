<?php

test('domain layer does not depend on infrastructure, application, presentation, or laravel framework')
    ->expect('App\Modules\*\Domain')
    ->not->toUse([
        'App\Modules\*\Infrastructure',
        'App\Modules\*\Application',
        'App\Modules\*\Presentation',
        'Illuminate',
    ]);

test('application layer does not depend on presentation or infrastructure')
    ->expect('App\Modules\*\Application')
    ->not->toUse([
        'App\Modules\*\Presentation',
        // 'App\Modules\*\Infrastructure', // <-- We will uncomment this once Identity module is clean!
        'Illuminate\Http',
    ]);

test('presentation layer only calls application layer')
    ->expect('App\Modules\*\Presentation')
    ->not->toUse('App\Modules\*\Infrastructure');

test('modules do not cross-import internal domain entities')
    ->expect('App\Modules\Workload\Domain')
    ->not->toUse([
        'App\Modules\Identity\Domain',
    ]);
