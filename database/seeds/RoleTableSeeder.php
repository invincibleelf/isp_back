<?php

use App\Role;
use Illuminate\Database\Seeder;


class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_student = new Role();
        $role_student->name = 'student';
        $role_student->description = 'A Student User';
        $role_student->save();

        $role_agent = new Role();
        $role_agent->name = 'agent';
        $role_agent->description = 'An Agent User';
        $role_agent->save();

        $role_councilor = new Role();
        $role_councilor->name = 'councilor';
        $role_councilor->description = 'A Councilor User';
        $role_councilor->save();

        $role_payer = new Role();
        $role_payer->name = 'payer';
        $role_payer->description = 'A Payer User';
        $role_payer->save();

        $role_customer_service = new Role();
        $role_customer_service->name = 'customer_service';
        $role_customer_service->description = 'A Customer service User';
        $role_customer_service->save();
    }
}
