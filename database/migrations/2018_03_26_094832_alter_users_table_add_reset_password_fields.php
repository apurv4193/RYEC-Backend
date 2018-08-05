<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUsersTableAddResetPasswordFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function($table) {
            $table->string('reset_password_otp',255)->nullable()->after('social_type');
            $table->dateTime('reset_password_otp_date')->nullable()->after('reset_password_otp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function($table) {
            $table->dropColumn('reset_password_otp');
            $table->dropColumn('reset_password_otp_date');
        });
    }
}
