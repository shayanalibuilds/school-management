<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('landing page is public and shows the school name', function (): void {
    get('/')->assertOk()->assertSee(config('app.name'));
});

test('landing page links to the public check pages', function (): void {
    get('/')->assertSee('/results')->assertSee('/attendance')->assertSee('/fees');
});

test('fission pages are gone', function (): void {
    get('/playground')->assertNotFound();
});
