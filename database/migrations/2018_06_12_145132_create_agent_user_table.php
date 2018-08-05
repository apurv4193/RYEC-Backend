<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgentUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->text('city')->nullable();
            $table->string('bank_detail',255)->nullable();
            $table->tinyInteger('status')->default(1)->comment('0- Deactive, 1- Active');
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
        Schema::dropIfExists('agent_users');
    }
}
