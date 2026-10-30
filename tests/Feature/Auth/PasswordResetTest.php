<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A recuperação de senha por e-mail foi desativada: o reset é feito pelo administrador.
     */
    public function test_forgot_password_routes_are_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'teste@pcpa.gov.br'])->assertNotFound();
        $this->get('/reset-password/token-qualquer')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }
}
