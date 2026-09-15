<?php

uses(Tests\TestCase::class);

test('the app is configured to use mongo as the primary database connection', function () {
    expect(config('database.default'))->toBe('mongodb')
        ->and(config('database.connections.mongodb.driver'))->toBe('mongodb');
});