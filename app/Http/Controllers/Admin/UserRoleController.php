<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Roles\AssignUserRolesRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRoleController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:users.assign_roles')->only('update');
    }

    public function update(AssignUserRolesRequest $request, User $user)
    {
        $roles = $request->validated()['roles'] ?? [];

        DB::transaction(fn() => $user->syncRoles($roles));

        return $this->success($user->load('roles'), 'User roles updated');
    }
}
