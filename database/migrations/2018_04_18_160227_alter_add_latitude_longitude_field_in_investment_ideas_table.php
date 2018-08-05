<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddLatitudeLongitudeFieldInInvestmentIdeasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('investment_ideas', function($table) {
            $table->string('latitude',100)->nullable()->after('city');
            $table->string('longitude',100)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('investment_ideas', function($table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });
    }
}
