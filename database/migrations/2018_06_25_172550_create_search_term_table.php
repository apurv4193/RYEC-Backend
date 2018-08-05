<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchTermTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_term', function (Blueprint $table) {
            $table->increments('id');
            $table->text('search_term')->nullable();
            $table->tinyInteger('type')->default(0)->comment('1 - category, 2 - metatags, 3 - searchtext');
            $table->string('city',255)->nullable();
            $table->integer('count')->default(0);
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
        Schema::dropIfExists('search_term');
    }
}
