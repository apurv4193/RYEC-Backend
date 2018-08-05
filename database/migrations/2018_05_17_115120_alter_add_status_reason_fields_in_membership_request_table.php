<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddStatusReasonFieldsInMembershipRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('membership_requests', function($table) {
            $table->string('reasons',255)->nullable()->after('user_id');
            $table->tinyInteger('status')->default(0)->comment('0 - pending , 1 - approved, 2 - rejected')->after('reasons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('membership_requests', function($table) {
            $table->dropColumn('reasons');
            $table->dropColumn('status');
        });
    }
}
