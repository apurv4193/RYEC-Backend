<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->default(0);
            $table->integer('category_id')->default(0);
            $table->string('category_hierarchy',255)->nullable();
            $table->string('name',100)->nullable(); 
            $table->longText('description')->nullable();
            $table->text('metatags')->nullable();
            $table->integer('cost')->default(0);
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
        Schema::dropIfExists('product');
    }
}
