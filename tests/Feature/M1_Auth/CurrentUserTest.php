<?php

it('rejects a guest asking for the current user', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('answers a guest with JSON 401 even without an Accept: application/json header', function () {
    $this->get('/api/user')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});
