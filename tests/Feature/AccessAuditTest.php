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

describe('empleados (decisiones 7, 9 y 14)', function () {
    it('keeps costs, prices, reports and order deletion for administrators', function () {
        [$admin, $branch] = branchUser(admin: true);
        [$employee] = branchUser(branch: $branch);
        $product = stockedProduct($branch, 3, cost: 12345, price: 20000);
        $order = branchOrder($branch);
        $session = ['current_branch_id' => $branch->id];
        $as = fn ($user) => $this->actingAs($user)->withSession($session);

        foreach ([route('reports'), route('inventory.index'), route('inventory.purchase'), route('products.create'), route('products.edit', $product)] as $url) {
            $as($employee)->get($url)->assertForbidden();
        }
        $as($employee)->put(route('products.update', $product), ['sale_price' => 1])->assertForbidden();
        $as($employee)->post(route('inventory.store-purchase'), ['product_id' => $product->id])->assertForbidden();
        $as($employee)->delete(route('service-orders.destroy', $order))->assertForbidden();
        expect($order->fresh())->not->toBeNull();

        // Ve el producto con su precio y stock, pero no el costo ni el margen.
        $as($employee)->get(route('products.index'))->assertOk()
            ->assertSee('$20.000')->assertDontSee('$12.345')->assertDontSee('Margen')->assertDontSee('Nuevo producto');
        $as($employee)->get(route('products.show', $product))->assertOk()
            ->assertSee('$20.000')->assertDontSee('Costo de referencia')->assertDontSee('Registrar compra');
        $as($employee)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Comisiones del mes')->assertDontSee(route('reports'))->assertDontSee(route('inventory.index'));

        $as($admin)->get(route('products.index'))->assertOk()->assertSee('$12.345')->assertSee('Margen');
        $as($admin)->get(route('dashboard'))->assertOk()->assertSee('Comisiones del mes')->assertSee(route('reports'));
        $as($admin)->delete(route('service-orders.destroy', $order))->assertSessionHas('success');
    });
});
