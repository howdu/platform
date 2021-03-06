<?php

declare(strict_types=1);

namespace Orchid\Platform\Http\Forms\Systems\Users;

use Orchid\Platform\Forms\Form;
use Illuminate\Contracts\View\View;
use Orchid\Platform\Core\Models\Role;
use Orchid\Platform\Core\Models\User;
use Orchid\Platform\Facades\Dashboard;

class AccessUserForm extends Form
{
    /**
     * Base Model.
     *
     * @var
     */
    protected $model = User::class;

    /**
     * AccessUserForm constructor.
     *
     * @param null $request
     */
    public function __construct($request = null)
    {
        $this->name = trans('dashboard::systems/users.tabs.permission');
        parent::__construct($request);
    }

    /**
     * Validation Rules Request.
     *
     * @return array
     */
    public function rules() : array
    {
        return [
            'permissions' => 'array',
            'roles'       => 'array',
        ];
    }

    /**
     * Display Settings App.
     *
     * @param User|null $user
     *
     * @return \Illuminate\Contracts\View\Factory|View|\Illuminate\View\View
     */
    public function get(User $user = null) : View
    {
        if (! is_null($user)) {
            $rolePermission = $user->permissions ?: [];
            $permission = Dashboard::getPermission();

            $permission->transform(function ($array) use ($rolePermission) {
                foreach ($array as $key => $value) {
                    $array[$key]['active'] = array_key_exists($value['slug'], $rolePermission);
                }

                return $array;
            });

            $roles = Role::all();
            $userRoles = $user->getRoles();

            $userRoles->transform(function ($role) {
                $role->active = true;

                return $role;
            });

            $roles = $userRoles->union($roles);
        } else {
            $permission = Dashboard::getPermission();
            $roles = Role::all();
        }

        return view('dashboard::container.systems.users.access', [
            'permission' => $permission,
            'user'       => $user,
            'roles'      => $roles,
        ]);
    }

    /**
     * Save Base Role.
     *
     * @param null $request
     * @param null $user
     *
     * @return void
     */
    public function persist($request = null, $user = null)
    {
        if (is_null($user)) {
            $user = User::where('email', $request->get('email'))->firstOrFail();
        }

        if (! is_null($this->roles)) {
            $roles = Role::whereIn('slug', $this->roles)->get();
            $user->replaceRoles($roles);
        }

        $user->save();
    }
}
