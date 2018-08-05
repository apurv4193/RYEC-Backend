<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBusinessAddressAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_address_attributes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->default(0);
            $table->string('village')->nullable();
            $table->string('taluka')->nullable();
            $table->string('district')->nullable();
            $table->string('premise')->nullable();
            $table->string('route')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('street_number')->nullable();
            $table->string('sublocality_level_3')->nullable();
            $table->string('sublocality_level_2')->nullable();
            $table->string('sublocality_level_1')->nullable();
            $table->string('locality')->nullable();
            $table->string('administrative_area_level_2')->nullable();
            $table->string('administrative_area_level_1')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('address')->nullable();
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
        Schema::drop('business_address_attributes');
    }
}
