<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_categories');
    }
};
