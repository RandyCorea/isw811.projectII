<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reddit_token')->nullable();
            $table->string('reddit_refresh_token')->nullable();
            $table->timestamp('reddit_token_expires_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reddit_token', 'reddit_refresh_token', 'reddit_token_expires_at']);
        });
    }
    
};
