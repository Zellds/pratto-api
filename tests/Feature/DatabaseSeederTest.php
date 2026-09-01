<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds a realistic development scenario without errors', function () {
    Artisan::call('db:seed');

    expect(DB::table('users')->count())->toBeGreaterThanOrEqual(5);
    expect(DB::table('recipes')->count())->toBeGreaterThanOrEqual(10);
    expect(DB::table('ratings')->count())->toBeGreaterThan(0);
    expect(DB::table('follows')->count())->toBeGreaterThan(0);

    $testUser = DB::table('users')->where('username', 'usuario_teste')->first();
    expect($testUser)->not->toBeNull();
    expect(Hash::check('senha123', $testUser->password))->toBeTrue();
});
