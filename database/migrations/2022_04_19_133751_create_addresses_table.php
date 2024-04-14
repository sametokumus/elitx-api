<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('city_id');
            $table->bigInteger('country_id');
            $table->string('title');
            $table->string('name');
            $table->string('citizen_number')->nullable();
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('postal_code');
            $table->string('phone');
            $table->mediumText('comment')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->tinyInteger('type')->default(1);
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
        Schema::dropIfExists('addresses');
    }
}
