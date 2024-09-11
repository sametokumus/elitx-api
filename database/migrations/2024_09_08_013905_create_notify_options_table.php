<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifyOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notify_options', function (Blueprint $table) {
            $table->id();
            $table->string('title_tr');
            $table->string('title_en');
            $table->string('title_de');
            $table->string('message_tr');
            $table->string('message_en');
            $table->string('message_de');
            $table->tinyInteger('notify')->default(0);
            $table->tinyInteger('email')->default(0);
            $table->tinyInteger('sms')->default(0);
            $table->tinyInteger('active')->default(1);
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
        Schema::dropIfExists('notify_options');
    }
}
