<?php

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

it('services do not depend on Filament')
    ->expect('Doedoe123boop\Sharepoint\Services')
    ->not->toUse('Filament');

it('exceptions extend SharepointException')
    ->expect('Doedoe123boop\Sharepoint\Exceptions')
    ->toExtend('Doedoe123boop\Sharepoint\Exceptions\SharepointException')
    ->ignoring('Doedoe123boop\Sharepoint\Exceptions\SharepointException');

it('contracts are interfaces')
    ->expect('Doedoe123boop\Sharepoint\Contracts')
    ->toBeInterfaces();
