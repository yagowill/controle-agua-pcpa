<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // A raiz do SCEA redireciona para o painel principal (que exige autenticação)
        $response->assertRedirect(route('entregas.nova'));
    }
}
