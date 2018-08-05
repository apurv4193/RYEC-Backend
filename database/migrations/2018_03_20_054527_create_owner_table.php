<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->string('full_name',100)->nullable();
            $table->string('mobile',13)->nullable();
            $table->string('email_id',100)->nullable();
            $table->string('photo',100)->nullable();
            $table->string('father_name',100)->nullable();
            $table->string('native_village',100)->nullable();
            $table->string('maternal_home',100)->nullable();
            $table->string('kul_gotra',100)->nullable();
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
        Schema::dropIfExists('owners');
    }
}
