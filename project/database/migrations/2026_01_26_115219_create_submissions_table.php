<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id('submission_id');
            $table->foreignId('assignment_id')->constrained('assignments', 'assignment_id')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('content_url');
            $table->integer('score')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users', 'user_id');
            $table->timestamps();
            
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
