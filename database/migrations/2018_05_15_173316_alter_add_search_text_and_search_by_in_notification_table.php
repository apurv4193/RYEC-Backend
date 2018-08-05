<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddSearchTextAndSearchByInNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function($table) {
            $table->integer('search_by')->default(0)->after('message');
            $table->string('search_text',255)->nullable()->after('search_by');
            $table->string('city',255)->nullable()->after('search_text');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function($table) {
            $table->dropColumn('search_by');
            $table->dropColumn('search_text');
            $table->dropColumn('city');
        });
    }
}
