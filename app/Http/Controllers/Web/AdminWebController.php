<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-tenants');
    }

    public function usersIndex(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = DB::table('users')->where('tenant_id', $tenantId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }
        if ($role = $request->input('role')) {
            $query->whereJsonContains('roles', $role);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function usersCreate()
    {
        return view('admin.users.create');
    }

    public function usersEdit(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $user = DB::table('users')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        return view('admin.users.edit', compact('user'));
    }

    public function usersStore(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'roles'                 => 'nullable|array',
            'roles.*'               => 'in:admin,technician,billing,readonly',
            'daily_capacity_hours'  => 'nullable|numeric|min:1|max:24',
        ]);

        DB::table('users')->insert([
            'tenant_id'             => $tenantId,
            'name'                  => $request->input('name'),
            'email'                 => $request->input('email'),
            'password'              => \Illuminate\Support\Facades\Hash::make($request->input('password')),
            'roles'                 => json_encode($request->input('roles', [])),
            'daily_capacity_hours'  => $request->input('daily_capacity_hours'),
            'is_active'             => $request->boolean('is_active'),
            'email_verified_at'     => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato con successo.');
    }

    public function usersUpdate(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        DB::table('users')->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        $rules = [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email,' . $id,
            'roles'                 => 'nullable|array',
            'roles.*'               => 'in:admin,technician,billing,readonly',
            'daily_capacity_hours'  => 'nullable|numeric|min:1|max:24',
        ];
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }
        $request->validate($rules);

        $data = [
            'name'                  => $request->input('name'),
            'email'                 => $request->input('email'),
            'roles'                 => json_encode($request->input('roles', [])),
            'daily_capacity_hours'  => $request->input('daily_capacity_hours'),
            'is_active'             => $request->boolean('is_active'),
            'updated_at'            => now(),
        ];
        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->input('password'));
        }

        DB::table('users')->where('id', $id)->where('tenant_id', $tenantId)->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente aggiornato.');
    }

    public function usersDestroy(int $id)
    {
        $tenantId = auth()->user()->tenant_id;

        if ($id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminare il tuo stesso account.');
        }

        DB::table('users')->where('id', $id)->where('tenant_id', $tenantId)->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente eliminato.');
    }
}
