<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
//use App\Http\Controllers\Stripe\GetPlans;
use App\Jobs\Tenant\CreateDB;
use App\Jobs\Tenant\Migration;
use App\Models\Company;
use App\Models\Person;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Traits\ActivationTrait;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use LaravelEnso\Roles\Models\Role;
use LaravelEnso\UserGroups\Models\UserGroup;

class RegisterController extends Controller
{
    use RegistersUsers;
    use ActivationTrait;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
        ]);
    }

    protected function create(Request $request)
    {
        // try {
        // create person
        $person = new Person();
        $name = $request['first_name'].' '.$request['last_name'];
        $person->name = $name;
        $person->email = $request['email'];
        $person->save();
        // get user_group_id
        $user_group = UserGroup::where('name', 'Administrators')->first();
        if ($user_group === null) {
            // create user_group
            $user_group = UserGroup::create(['name' => 'Administrators', 'description' => 'Administrator users group']);
        }

        // get role_id
//            if ($request->role_id == '') {
//                $role = Role::where('name', 'free')->first();
//            } else {
//                $role = Role::find($request->role_id);
//            }
        $role = Role::where('name', 'free')->first();
        if ($role === null) {
            $role = Role::create(['menu_id' => 1, 'name' => 'free', 'display_name' => 'Supervisor', 'description' => 'Supervisor role.']);
        }
        $user = User::create([
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'person_id' => $person->id,
            'group_id' => $user_group->id,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);

        // send verification email;

        // $this->initiateEmailActivation($user);
        $company = Company::create([
            'name' => $request['email'],
            'email' => $request['email'],
            // 'is_active' => 1,
            'is_tenant' => 1,
            'status' => 1,
        ]);
        //for creating default avatar
        //$avtar = Avatar::create([
            //    'user_id' => $user->id
        //]);

//          $company->attachPerson($person->id, 'Owner');
        // DB::commit();

        $person->companies()->attach($company->id, ['person_id' => $person->id, 'is_main' => 1, 'is_mandatary' => 1, 'company_id' => $company->id]);

        // Dispatch Tenancy Jobs
            //     $tenant = \App\Models\Tenant::create([
            //         'id' => $company->id,
            //     ]);

            //     $tenants = \App\Models\Tenant::find($tenant->id);

        // tenancy()->initialize($tenants);
        // $persons = $tenants->run(function () use($request, $name) {
            //     Person::create([
            //         'email'=>$request['email'],
            //         'name' =>  $name,

            //     ]);
        // });
        // $user_group = 1;

        // // get role_id
        // $role = 1;
        // $tenants->run(function () use($persons,$user_group,$role,$request) {
            //     User::create([
            //         'email' => $request['email'],
            //         'password' => bcrypt($request['password']),
        //   'person_id' => $persons,
        //   'group_id' => $user_group,
        //   'role_id' => $role,
        //   'is_active' => 1,

            //     ]);
        // });
        CreateDB::dispatch($company);
        Migration::dispatch($company, $name, $request['email'], $request['password']);
        if ($request->selected_plan === '' || $request->selected_plan === $user->role_id) {
            $user->plan_id = '';
        } else {
            $user->plan_id = $request->selected_plan;
        }

        return $user;
        // } catch (\Exception $e) {
        //     // DB::rollBack();
        //     throw $e;
        // }
    }

/**    protected function getSubscriptionPlan(Request $request)
 * {
 * return (new GetPlans())($request);
 * }.
 **/
}
