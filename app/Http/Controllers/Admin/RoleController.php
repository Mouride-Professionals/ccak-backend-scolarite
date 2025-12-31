<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:roles.view')->only('index');
        $this->middleware('permission:roles.manage')->only(['store', 'update']);
    }

    public function index()
    {
        $roles = QueryBuilder::for(Role::query())
            ->with('permissions')
            ->allowedIncludes(['permissions'])
            ->allowedFilters([AllowedFilter::partial('name')])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->paginate(request()->integer('per_page') ?? 15)
            ->appends(request()->query());

        return $this->success($roles);
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => config('auth.defaults.guard'),
            ]);

            if (! empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });

        return $this->success($role->load('permissions'), 'Role created', Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();

        DB::transaction(function () use ($role, $data) {
            if (array_key_exists('name', $data)) {
                $role->name = $data['name'];
            }

            $role->save();

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions'] ?? []);
            }
        });

        return $this->success($role->refresh()->load('permissions'), 'Role updated');
    }
}
