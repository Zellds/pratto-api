<?php

it('allows cross-origin requests from a local frontend dev server', function () {
    $response = $this->withHeaders(['Origin' => 'http://localhost:5173'])
        ->getJson('/api/recipes');

    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});
