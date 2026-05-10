<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meat_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('image_disk')->default('public');
            $table->string('image_path');

            $table->string('label'); // fresh | spoiled | uncertain
            $table->decimal('confidence', 5, 2);
            $table->text('explanation');
            $table->json('recommendations');
            $table->timestamp('scanned_at')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_scans');
    }
};

