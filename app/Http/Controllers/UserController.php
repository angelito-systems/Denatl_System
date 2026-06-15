<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'schedules']);

        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::pluck('name');

        return Inertia::render('Staff/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'room' => 'nullable|string|max:255',
            'dni' => 'nullable|string|max:15|unique:users',
            'cmp' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'room' => $validated['room'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'cmp' => $validated['cmp'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->back()->with('success', 'Personal creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|string|exists:roles,name',
            'room' => 'nullable|string|max:255',
            'dni' => ['nullable', 'string', 'max:15', Rule::unique('users')->ignore($user->id)],
            'cmp' => 'nullable|string|max:255',
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'room' => $validated['room'] ?? null,
            'dni' => $validated['dni'] ?? null,
            'cmp' => $validated['cmp'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->back()->with('success', 'Personal actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('success', 'Personal eliminado exitosamente.');
    }
}
