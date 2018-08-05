<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessMembershipPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_membership_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->default(0);
            $table->integer('subscription_plan_id')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable(); 
            $table->float('actual_payment')->default(0);
            $table->float('agent_commision')->default(0);
            $table->float('net_payment')->default(0);
            $table->text('comments')->nullable();
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
        Schema::dropIfExists('business_membership_plans');
    }
}
