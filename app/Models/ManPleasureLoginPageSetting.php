<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManPleasureLoginPageSetting extends Model
{
    protected $table = 'manpleasure_login_page_settings';

    protected $fillable = [
        'draft_html',
        'draft_css',
        'draft_project_json',
        'published_html',
        'published_css',
        'published_project_json',
        'logo_path',
        'is_published',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['is_published' => false]
        );
    }
}
