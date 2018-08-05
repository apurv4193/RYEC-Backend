<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('activity_user_id')->default(0);
            $table->integer('thread_id')->default(0);
            $table->string('thread_title',255)->nullable();
            $table->integer('business_id')->default(0);
            $table->string('title',255)->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('type')->default(0);
            $table->string('business_name',255)->nullable();
            $table->string('user_name',255)->nullable();
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
        Schema::dropIfExists('notification_list');
    }
}
