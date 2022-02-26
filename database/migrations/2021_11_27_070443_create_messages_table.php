<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('bot_user_id')->nullable()->references('chat_id')->on('bot_users')->onDelete('CASCADE');
            $table->string('message_type')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_bot')->default(true);
            $table->integer('type')->nullable();
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
        Schema::dropIfExists('messages');
    }
}
