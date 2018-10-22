<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouncilorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('councilor_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('firstname', 255);
            $table->string('middlename', 255)->nullable();
            $table->string('lastname', 255);
            $table->string('national_id');
            $table->integer('agent_id')->nullable();
            $table->integer('status');
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
        Schema::dropIfExists('councilor_details');
    }
}
