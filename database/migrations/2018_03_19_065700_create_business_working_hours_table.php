<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessWorkingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_working_hours', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id');
            $table->string('mon_start_time',100)->nullable();
            $table->string('mon_end_time',100)->nullable();
            $table->tinyInteger('mon_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('tue_start_time',100)->nullable();
            $table->string('tue_end_time',100)->nullable();
            $table->tinyInteger('tue_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('wed_start_time',100)->nullable();
            $table->string('wed_end_time',100)->nullable();
            $table->tinyInteger('wed_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('thu_start_time',100)->nullable();
            $table->string('thu_end_time',100)->nullable();
            $table->tinyInteger('thu_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('fri_start_time',100)->nullable();
            $table->string('fri_end_time',100)->nullable();
            $table->tinyInteger('fri_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('sat_start_time',100)->nullable();
            $table->string('sat_end_time',100)->nullable();
            $table->tinyInteger('sat_open_close')->default(1)->comment('1 - Open, 0 - Close');
            $table->string('sun_start_time',100)->nullable();
            $table->string('sun_end_time',100)->nullable();
            $table->tinyInteger('sun_open_close')->default(1)->comment('1 - Open, 0 - Close');
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
        Schema::dropIfExists('business_working_hours');
    }
}
