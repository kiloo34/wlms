<?php
declare(strict_types=1);

test('Collaboration domain layer does not depend on other layers')
    ->expect('App\Modules\Collaboration\Domain')
    ->not->toUse([
        'App\Modules\Collaboration\Application',
        'App\Modules\Collaboration\Infrastructure',
        'App\Modules\Collaboration\Presentation',
        'Illuminate',
    ]);

test('Collaboration application layer does not depend on presentation or infrastructure')
    ->expect('App\Modules\Collaboration\Application')
    ->not->toUse([
        'App\Modules\Collaboration\Presentation',
        'App\Modules\Collaboration\Infrastructure',
        'Illuminate\Http',
        'Illuminate\Database',
    ])
    ->ignoring([
        'App\Modules\Collaboration\Application\UseCases\GetIssueTimelineQuery'
    ]);

test('Collaboration presentation layer only calls application layer (and CQRS queries)')
    ->expect('App\Modules\Collaboration\Presentation')
    ->not->toUse([
        'App\Modules\Collaboration\Infrastructure\Persistence',
    ]);
