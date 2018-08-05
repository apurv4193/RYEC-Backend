<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_metadata', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('father_name',100)->nullable();
            $table->string('native_village', 100)->nullable();
            $table->string('maternal_home', 100)->nullable();
            $table->string('kul_gotra', 100)->nullable();
            $table->string('children', 100)->nullable();
            $table->text('business_achievments')->nullable();
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
        Schema::dropIfExists('user_metadata');
    }
}
