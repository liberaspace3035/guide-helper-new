<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_from_admin')->default(false);
            $table->text('body');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        $now = now();
        foreach (
            [
                [
                    'setting_key' => 'support_auto_reply_subject',
                    'setting_value' => '【One Step】お問い合わせを受け付けました',
                ],
                [
                    'setting_key' => 'support_auto_reply_body',
                    'setting_value' => "{{name}} 様\n\nお問い合わせありがとうございます。内容を確認のうえ、担当者よりご連絡いたします。\n\n一般社団法人With Blind\nOne Step 運営",
                ],
            ] as $row
        ) {
            if (! DB::table('admin_settings')->where('setting_key', $row['setting_key'])->exists()) {
                DB::table('admin_settings')->insert([
                    'setting_key' => $row['setting_key'],
                    'setting_value' => $row['setting_value'],
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
