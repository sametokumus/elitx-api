<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCommentAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_comment_answers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('comment_id')->nullable();
            $table->bigInteger('shop_id')->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('confirmed')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->bigInteger('confirm_admin_id')->nullable();
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
        Schema::dropIfExists('product_comment_answers');
    }
}
