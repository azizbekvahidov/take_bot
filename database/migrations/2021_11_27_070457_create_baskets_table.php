<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->tinyInteger('product_type')->nullable();
            $table->double('amount')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->foreignId('bot_user_id')->nullable()->references('chat_id')->on('bot_users')->onDelete('CASCADE');
            $table->boolean('is_served')->default(false);
            $table->boolean('is_finished')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('baskets');
    }
}
