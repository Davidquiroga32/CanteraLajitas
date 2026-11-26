<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las vistas no dependen del build de Vite (en CI no existe public/build).
        $this->withoutVite();
    }
}
