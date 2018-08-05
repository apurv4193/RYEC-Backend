<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->string('category_id',100)->nullable();
            $table->string('category_hierarchy',255)->nullable();
            $table->string('name',100)->nullable();
            $table->string('business_slug',100)->nullable()->comment('Business name slug');
            $table->longText('description')->nullable();
            $table->string('phone',15)->nullable();
            $table->string('mobile',15)->nullable();
            $table->integer('country')->default(0);
            $table->integer('state')->default(0);
            $table->string('city',100)->nullable();
            $table->string('latitude',100)->nullable();
            $table->string('longitude',100)->nullable();
            $table->tinyInteger('promoted')->default(0)->comment('0 - Not promoted, 1 - Promoted');
            $table->string('address',100)->nullable();
            $table->string('email_id',100)->nullable();
            $table->integer('establishment_year')->default(0);
            $table->string('website_url',100)->nullable();
            $table->string('facebook_url',255)->nullable();
            $table->string('twitter_url',255)->nullable();
            $table->string('linkedin_url',255)->nullable();
            $table->string('instagram_url',255)->nullable();
            $table->tinyInteger('approved')->default(0)->comment('0 - Disapproved, 1 - Approved');
            $table->bigInteger('visits')->default(0)->comment('Business visits counts');
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
        Schema::dropIfExists('business');
    }
}
