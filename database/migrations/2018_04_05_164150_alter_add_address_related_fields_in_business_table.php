<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddAddressRelatedFieldsInBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business', function($table) {
            $table->string('street_address','255')->nullable()->after('address');
            $table->string('locality','255')->nullable()->after('street_address');
            $table->string('country','255')->nullable()->after('locality');
            $table->string('state','255')->nullable()->after('country');
            $table->string('city','255')->nullable()->after('state');
            $table->string('taluka','255')->nullable()->after('city');
            $table->string('district','255')->nullable()->after('taluka');
            $table->string('pincode','100')->nullable()->after('district');
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
            $table->dropColumn('street_address');
            $table->dropColumn('locality');
            $table->dropColumn('country');
            $table->dropColumn('state');
            $table->dropColumn('city');
            $table->dropColumn('taluka');
            $table->dropColumn('district');
            $table->dropColumn('pincode');
        });
    }
}
