<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Chatbot upgrade: richer replies (list menu, URL button, header / footer,
    // image / PDF / video), a welcome flow for first-time customers, and
    // business hours with an after-hours message. Plain nullable columns only,
    // so it behaves the same on MySQL and PostgreSQL.
    public function up(): void
    {
        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->string('reply_type', 20)->nullable();      // text | buttons | list | link (null = derive from quick_replies)
            $table->string('header_text', 60)->nullable();
            $table->string('footer_text', 60)->nullable();
            $table->string('list_button_text', 20)->nullable(); // label of the button that opens a list
            $table->string('media_type', 20)->nullable();       // image | video | document
            $table->string('media_url', 500)->nullable();
            $table->boolean('is_welcome')->default(false);      // sent to a first-time (or long-gone) customer
        });

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->boolean('hours_enabled')->default(false);
            $table->string('hours_start', 5)->default('09:00');
            $table->string('hours_end', 5)->default('18:00');
            $table->json('hours_days')->nullable();             // [1,2,3,4,5] = Mon–Fri (0 = Sunday)
            $table->text('after_hours_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['hours_enabled', 'hours_start', 'hours_end', 'hours_days', 'after_hours_message']);
        });

        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->dropColumn(['reply_type', 'header_text', 'footer_text', 'list_button_text', 'media_type', 'media_url', 'is_welcome']);
        });
    }
};
