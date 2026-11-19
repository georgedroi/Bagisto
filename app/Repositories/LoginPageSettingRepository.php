<?php

namespace App\Repositories;

use App\Models\ManPleasureLoginPageSetting;
use Illuminate\Database\UniqueConstraintViolationException;
use Webkul\Core\Eloquent\Repository;

/**
 * Persist branding drafts separately from the unchanged admin authentication form.
 */
class LoginPageSettingRepository extends Repository
{
    /**
     * Return the existing settings model.
     */
    public function model(): string
    {
        return ManPleasureLoginPageSetting::class;
    }

    /**
     * Resolve the single editable design record for an authorized editor. When a concurrent first request creates
     * row 1 between the lookup and the insert, the primary key refuses the second insert and that row is returned.
     */
    public function current(): ManPleasureLoginPageSetting
    {
        if ($settings = $this->model->newQuery()->find(1)) {
            return $settings;
        }

        try {
            return $this->model->newQuery()->forceCreate(['id' => 1, 'is_published' => false]);
        } catch (UniqueConstraintViolationException $exception) {
            return $this->model->newQuery()->useWritePdo()->find(1) ?? throw $exception;
        }
    }

    /**
     * Read optional published branding without creating records during anonymous login.
     */
    public function published(): ?ManPleasureLoginPageSetting
    {
        if (! $this->model->getConnection()->getSchemaBuilder()->hasTable($this->model->getTable())) {
            return null;
        }

        return $this->model->newQuery()->whereKey(1)->where('is_published', true)->first();
    }

    /**
     * Save validated and sanitized draft values.
     */
    public function saveDraft(array $data): void
    {
        $this->update($data + ['updated_by' => auth('admin')->id()], $this->current()->id);
    }

    /**
     * Publish checked branding while retaining the editable draft.
     */
    public function publishDraft(string $html, string $css): bool
    {
        if (blank($html) && blank($css)) {
            return false;
        }

        $settings = $this->current();
        $this->update([
            'published_html' => $html,
            'published_css' => $css,
            'published_project_json' => $settings->draft_project_json,
            'is_published' => true,
            'updated_by' => auth('admin')->id(),
        ], $settings->id);

        return true;
    }

    /**
     * Restore the default login presentation without losing the draft.
     */
    public function unpublish(): void
    {
        $this->update(['is_published' => false, 'updated_by' => auth('admin')->id()], $this->current()->id);
    }
}
