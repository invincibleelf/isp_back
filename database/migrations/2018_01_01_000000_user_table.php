<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class UserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('login_users_c', function (Blueprint $table) {
            $table->increments('id')->index()->unsigned();
            $table->integer('role_id');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('password');
            $table->rememberToken();
            $table->boolean('verified')->default(false);
            $table->integer('status');
            $table->timestamps();
        });
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
//        // insert admin
//        DB::table('users')->insert(
//            array(
//                'email' => 'admin',
//                'firstname' => 'admin',
//                'middlename'=>'ad',
//                'password' => '$2y$10$/UB25CPnTCFmQhO0xOnM5elHuMVeNA2AGFha6Qdih1dv/69uqi7hG' //password
//            )
//        );
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_users_c');
        Schema::dropIfExists('password_resets');
    }
}