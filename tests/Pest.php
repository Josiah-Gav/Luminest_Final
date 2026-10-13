<?php

use Tests\TestCase;

/*
| Every test in tests/Feature runs on Laravel's TestCase, which boots the app
| and gives HTTP helpers like getJson(). Unit tests stay plain PHPUnit.
*/

pest()->extend(TestCase::class)
    ->in('Feature');
