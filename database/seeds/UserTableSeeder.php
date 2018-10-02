<?php

use App\Role;
use App\User;
use App\UserDetail;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_student = Role::where('name', 'student')->first();
        $role_agent = Role::where('name', 'agent')->first();
        $role_councilor = Role::where('name', 'councilor')->first();


        $student = new User();
        $student->phone = '0426778778';
        $student->national_id = '2323232323';
        $student->email = 'student@example.com';
        $student->password = bcrypt('123456');
        $student->verified = true;
        $student->save();

        $studentDetail = new UserDetail();
        $studentDetail->firstname = 'John Student';
        $studentDetail->lastname = 'Doe';
        $studentDetail->dob = date('m.d.y');
        $studentDetail->gender = "Male";


        $student->userDetails()->save($studentDetail);
        $student->roles()->attach($role_student);


        $agent = new User();
        $agent->phone = '042292929';
        $agent->national_id = '46543434';
        $agent->email = 'agent@example.com';
        $agent->password = bcrypt('123456');
        $agent->verified = true;
        $agent->save();

        $agentDetail = new UserDetail();
        $agentDetail->agent_name = "John Agent Doe";
        $agentDetail->legal_registration_number = "738273823782";
        $agentDetail->location = "2/22 Elizabeth st, Melbourne VIC 3000";

        $agent->userDetails()->save($agentDetail);

        $agent->roles()->attach($role_agent);
//
//        $councilor = new User();
//        $councilor->firstname ='John Councilor';
//        $councilor->lastname ='Doe';
//        $councilor->dob=date('m.d.y');
//        $councilor->gender= "Male";
//        $councilor->phone='042292929';
//        $councilor->national_id='465445453434';
//        $councilor->email = 'councilor@example.com';
//        $councilor->password = bcrypt('123456');
//        $councilor->save();
//        $councilor->roles()->attach($role_councilor);
    }
}
