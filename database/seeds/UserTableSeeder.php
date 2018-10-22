<?php

use App\Role;
use App\User;
use App\StudentDetail;
use App\AgentDetail;
use App\CouncilorDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

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

        $agent = new User();
        $agent->phone = '042292929';
        $agent->email = 'agent@example.com';
        $agent->password = bcrypt('123456');
        $agent->verified = true;
        $agent->status = Config::get('enums.status.ACTIVE');


        $agent->role()->associate($role_agent);
        $agent->save();


        $agentDetail = new AgentDetail();
        $agentDetail->name = "John Agent Doe";
        $agentDetail->national_id = "323232323";
        $agentDetail->legal_registration_number = "738273823782";
        $agentDetail->location = "2/22 Elizabeth st, Melbourne VIC 3000";
        $agentDetail->valid_bank_opening = "anz";
        $agentDetail->bank_account_number = "0939393";
        $agentDetail->bank_account_name = "John Agent";
        $agentDetail->status = 0;

        $agent->agentDetails()->save($agentDetail);


        $councilor = new User();
        $councilor->phone = '042292929';
        $councilor->email = 'councilor@example.com';
        $councilor->password = bcrypt('123456');
        $councilor->verified = true;
        $councilor->status = Config::get('enums.status.ACTIVE');
        $councilor->role()->associate($role_councilor);
        $councilor->save();

        $councilorDetail = new CouncilorDetail();
        $councilorDetail->firstname = 'John Councilor';
        $councilorDetail->lastname = 'Doe';
        $councilorDetail->national_id = "483838838";
        $councilorDetail->status = 0;
        $councilorDetail->agent()->associate($agentDetail);

        $councilor->councilorDetails()->save($councilorDetail);


        $student = new User();
        $student->phone = '0426778778';
        $student->email = 'student@example.com';
        $student->password = bcrypt('123456');
        $student->verified = true;
        $student->status =Config::get('enums.status.ACTIVE');

        $student->role()->associate($role_student);

        $student->save();
        $studentDetail = new StudentDetail();
        $studentDetail->firstname = 'John Student';
        $studentDetail->lastname = 'Doe';
        $studentDetail->dob = date('d.m.y');
        $studentDetail->gender = "Male";
        $studentDetail->national_id = "2323232323";
        $studentDetail->student_id_number = "12345678";
        $studentDetail->councilor()->associate($councilorDetail);


        $student->studentDetails()->save($studentDetail);


    }
}
