<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOwnerTableAddNewFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('owners', function($table) {
            $table->tinyInteger('gender')->default(0)->comment('1 - Male, 2 - Female, 3 - Other')->after('full_name');
            $table->date('dob')->nullable()->after('gender');
            $table->string('facebook_url',255)->nullable()->after('dob');
            $table->string('twitter_url',255)->nullable()->after('facebook_url');
            $table->string('linkedin_url',255)->nullable()->after('twitter_url');
            $table->string('instagram_url',255)->nullable()->after('linkedin_url');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('owners', function($table) {
            $table->dropColumn('gender');
            $table->dropColumn('dob');
            $table->dropColumn('facebook_url');
            $table->dropColumn('twitter_url');
            $table->dropColumn('linkedin_url');
            $table->dropColumn('instagram_url');
        });
    }
}
