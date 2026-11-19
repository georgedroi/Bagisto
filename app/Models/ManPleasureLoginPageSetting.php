<?php

namespace App\Models;

use App\Repositories\LoginPageSettingRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Hold saved branding independently from the admin authentication fields.
 */
class ManPleasureLoginPageSetting extends Model
{
    /**
     * The existing single-record settings table.
     */
    protected $table = 'manpleasure_login_page_settings';

    /**
     * Editable and published presentation values.
     */
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

    /**
     * Cast the publication switch consistently.
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Preserve existing callers while routing persistence through its repository.
     */
    public static function current(): self
    {
        return app(LoginPageSettingRepository::class)->current();
    }
}
