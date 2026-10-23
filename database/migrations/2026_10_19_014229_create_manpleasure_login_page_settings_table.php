<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manpleasure_login_page_settings', function (Blueprint $table) {
            $table->id();
            $table->longText('draft_html')->nullable();
            $table->longText('draft_css')->nullable();
            $table->longText('draft_project_json')->nullable();
            $table->longText('published_html')->nullable();
            $table->longText('published_css')->nullable();
            $table->longText('published_project_json')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manpleasure_login_page_settings');
    }
};
