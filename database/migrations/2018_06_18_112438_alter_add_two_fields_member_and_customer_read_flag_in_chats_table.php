<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddTwoFieldsMemberAndCustomerReadFlagInChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chats', function($table) {
            $table->tinyInteger('customer_read_flag')->default(1)->comment('0 - false , 1 - true')->after('type');
            $table->tinyInteger('member_read_flag')->default(1)->comment('0 - false , 1 - true')->after('customer_read_flag');
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
            $table->dropColumn('agent_user');
        });
    }
}
