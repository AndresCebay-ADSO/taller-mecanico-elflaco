<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branches')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', [
            'user' => new User(),
            'branches' => Branch::active()->orderBy('name')->get(),
            'selectedBranchIds' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->forceFill([
                'is_admin' => $validated['is_admin'],
                'is_active' => true,
            ])->save();

            $user->branches()->sync($validated['branch_ids'] ?? []);
        });

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'branches' => Branch::active()->orderBy('name')->get(),
            'selectedBranchIds' => $user->branches()->pluck('branches.id')->all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user);

        if ($user->is($request->user()) && !$validated['is_admin']) {
            return back()->withErrors([
                'is_admin' => 'No puedes quitarte a ti mismo el rol de administrador.',
            ])->withInput();
        }

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if (!empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->is_admin = $validated['is_admin'];
            $user->save();

            $user->branches()->sync($validated['branch_ids'] ?? []);
        });

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->forceFill(['is_active' => !$user->is_active])->save();

        return redirect()->route('users.index')
            ->with('success', $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'is_admin' => 'boolean',
            'branch_ids' => 'array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ], [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.confirmed' => 'La confirmacion de la contraseña no coincide.',
        ]);

        $validated['is_admin'] = $request->boolean('is_admin');

        if (!$validated['is_admin'] && empty($validated['branch_ids'])) {
            throw ValidationException::withMessages([
                'branch_ids' => 'Asigna al menos una sucursal a los usuarios que no son administradores.',
            ]);
        }

        return $validated;
    }
}
