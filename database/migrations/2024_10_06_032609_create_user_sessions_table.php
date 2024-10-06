<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->text('session_id')->nullable();
            $table->text('session_lang')->nullable();
            $table->text('as')->nullable();
            $table->text('city')->nullable();
            $table->text('country')->nullable();
            $table->text('countryCode')->nullable();
            $table->text('district')->nullable();
            $table->text('isp')->nullable();
            $table->text('lat')->nullable();
            $table->text('lon')->nullable();
            $table->text('org')->nullable();
            $table->text('ip')->nullable();
            $table->text('region')->nullable();
            $table->text('regionName')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_sessions');
    }
}
