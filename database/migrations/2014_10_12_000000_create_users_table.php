<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('password', 100);
            $table->string('phone', 15)->unique()->nullable();
            $table->date('dob', 15)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('profile_pic', 255)->nullable();
            $table->tinyInteger('gender')->default(0)->comment('1 - Male, 2 - Female, 3 - Other');
            $table->tinyInteger('agent_approved')->default(0)->comment('0- Pending, 1- Approved, 2- Decline');
            $table->tinyInteger('notification')->default(1)->comment('0 - Disable, 1 - Enable');
            $table->tinyInteger('subscription')->default(0)->comment('1 - Subscribed, 0 - Unsubscribed');
            $table->tinyInteger('social_type')->default(1)->comment('1 - Normal, 2 - Facebook, 3 - Google');
            $table->string('facebook_id', 255)->nullable();
            $table->text('facebook_token')->nullable();
            $table->string('google_id', 255)->nullable();
            $table->text('google_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
