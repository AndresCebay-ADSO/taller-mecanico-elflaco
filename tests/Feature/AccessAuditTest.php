<?php

use App\Models\Branch;

describe('sesiones', function () {
    it('closes other sessions of a user whose password changed', function () {
        [$employee, $branch] = branchUser();

        // Sesión abierta antes del cambio de contraseña: guarda el hash viejo.
        $this->actingAs($employee)
            ->withSession(['current_branch_id' => $branch->id, 'password_hash_web' => 'hash-de-la-contraseña-anterior'])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('keeps the user signed in after changing their own password', function () {
        [$employee, $branch] = branchUser();

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'nueva-clave-123',
                'password_confirmation' => 'nueva-clave-123',
            ])
            ->assertSessionHasNoErrors();

        $this->get(route('dashboard'))->assertOk();
        $this->assertAuthenticatedAs($employee);
    });
});

describe('sucursales', function () {
    it('does not deactivate the last active branch', function (string $route) {
        Branch::query()->update(['is_active' => false]);
        [$admin, $branch] = branchUser(admin: true);
        $method = $route === 'branches.destroy' ? 'delete' : 'post';

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->{$method}(route($route, $branch))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('error');

        expect($branch->fresh()->is_active)->toBeTrue();
    })->with(['branches.destroy', 'branches.toggle-active']);

    it('deactivates a branch while another one stays active', function () {
        [$admin, $branch] = branchUser(admin: true);
        $other = Branch::create(['name' => 'Sede cerrada', 'is_active' => true]);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->post(route('branches.toggle-active', $other))
            ->assertSessionHas('success');

        expect($other->fresh()->is_active)->toBeFalse();
    });
});
