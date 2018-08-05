<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvestmentIdeasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investment_ideas', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('category_id');
            $table->string('title', 200)->nullable();
            $table->string('title_slug', 200)->nullable()->comment('Investment title slug');
            $table->longText('description')->nullable();
            $table->string('investment_amount_start', 100)->nullable();
            $table->string('investment_amount_end', 100)->nullable();
            $table->string('project_duration', 100)->nullable();
            $table->string('member_name', 100)->nullable();
            $table->string('member_email', 100)->nullable();
            $table->string('member_phone', 100)->nullable();
            $table->string('offering_percent', 100)->nullable();
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
        Schema::dropIfExists('investment_ideas');
    }
}
