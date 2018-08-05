<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddPremimumFieldInBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business', function($table) {
            $table->tinyInteger('membership_type')->default(0)->nullable()->comment('1 - Premium , 0 - Basic')->after('promoted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function($table) {
            $table->dropColumn('membership_type');
        });
    }
}
