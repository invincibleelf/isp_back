<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name',255);
            $table->string('location');
            $table->string('national_id')->unique();
            $table->string('legal_registration_number')->nullable();
            $table->string('valid_bank_opening');
            $table->integer('bank_account_number');
            $table->string('bank_account_name');
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
        Schema::dropIfExists('agent_details');
    }
}
