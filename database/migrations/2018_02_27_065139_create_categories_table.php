<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent')->default(0)->comment('Category ID');
            $table->string('name', 100)->nullable();
            $table->string('category_slug', 100)->nullable()->comment('Category Name Slug');
            $table->string('cat_logo',100)->comment('Category Logo')->nullable();
            $table->string('banner_img',100)->nullable()->comment('Only for top level category');
            $table->text('metatags')->nullable();
            $table->tinyInteger('trending_service')->default(0)->comment('0 - No, 1 - Yes');
            $table->tinyInteger('trending_category')->default(0)->comment('0 - No, 1 - Yes');
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
        Schema::dropIfExists('categories');
    }
}
