<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function authenticatedToken(TestCase $test): string
{
    $test->postJson('/api/register', [
        'username' => 'gabriel',
        'display_name' => 'Gabriel Medeiros',
        'password' => 'senha-forte-123',
    ]);

    return $test->postJson('/api/login', [
        'username' => 'gabriel',
        'password' => 'senha-forte-123',
    ])->json('token');
}

function authenticatedTokenFor(TestCase $test, string $username): string
{
    $test->postJson('/api/register', [
        'username' => $username,
        'display_name' => ucfirst($username),
        'password' => 'senha-forte-123',
    ]);

    return $test->postJson('/api/login', [
        'username' => $username,
        'password' => 'senha-forte-123',
    ])->json('token');
}

function recipePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Bolo de cenoura',
        'description' => 'Bolo simples e rápido',
        'portions' => 8,
        'prep_time_minutes' => 60,
        'ingredients' => [
            ['ingredient_name' => 'Cenoura', 'quantity' => 3, 'unit' => 'unidade', 'position' => 0],
        ],
        'steps' => [
            ['position' => 0, 'instruction' => 'Bata tudo no liquidificador.'],
        ],
    ], $overrides);
}
