<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * الصفحة الرئيسية محمية بتسجيل الدخول.
     */
    public function test_the_dashboard_requires_authentication(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
