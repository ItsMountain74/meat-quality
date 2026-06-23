<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meat_scans', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('image_path');
            $table->string('label')->nullable()->change();
            $table->decimal('confidence', 5, 2)->nullable()->change();
            $table->text('explanation')->nullable()->change();
            $table->json('recommendations')->nullable()->change();
            $table->timestamp('scanned_at')->nullable()->change();
        });

        DB::table('meat_scans')->update(['status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('meat_scans', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('label')->nullable(false)->change();
            $table->decimal('confidence', 5, 2)->nullable(false)->change();
            $table->text('explanation')->nullable(false)->change();
            $table->json('recommendations')->nullable(false)->change();
            $table->timestamp('scanned_at')->nullable(false)->change();
        });
    }
};
