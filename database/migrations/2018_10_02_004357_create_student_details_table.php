<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('firstname', 255);
            $table->string('middlename', 255)->nullable();
            $table->string('lastname', 255);
            $table->date('dob');
            $table->string('gender')->nullable();
            $table->string('national_id')->unique();
            $table->string('student_id_number');
            $table->integer('councilor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_details');
    }
}
