<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_root_redirects_to_the_localized_site()
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
