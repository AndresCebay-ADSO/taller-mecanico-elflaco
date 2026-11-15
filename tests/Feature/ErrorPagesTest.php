<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

describe('páginas de error y sesión vencida', function () {
    it('returns to the form with what was typed when the session expired', function () {
        [$user, $branch] = branchUser();
        // En pruebas Laravel no revisa el token CSRF: se simula lo que pasa cuando la sesión venció.
        Route::middleware('web')->post('/_prueba-sesion-vencida', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->from(route('sales.create'))
            ->post('/_prueba-sesion-vencida', ['customer_name' => 'Pedro Ruiz', 'password' => 'no-se-guarda'])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHas('error', fn ($text) => str_contains($text, 'la sesión se cerró por seguridad'))
            ->assertSessionHasInput('customer_name', 'Pedro Ruiz')
            ->assertSessionMissing('_old_input.password');
    });

    it('shows the 404 and 403 pages in Spanish', function () {
        [$employee, $branch] = branchUser();

        $this->get('/no-existe-esta-pagina')->assertNotFound()->assertSee('No encontramos esta página');

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->get(route('reports'))->assertForbidden()->assertSee('No tienes permiso para esto');
    });

    it('warns when the session would close in the middle of a workday', function () {
        config(['session.lifetime' => 120]);
        $this->artisan('taller:verificar')->expectsOutputToContain('SESSION_LIFETIME=720');
    });

    it('accepts a session that lasts a workday', function () {
        config(['session.lifetime' => 720]);
        $this->artisan('taller:verificar')->expectsOutputToContain('12 horas');
    });
});
