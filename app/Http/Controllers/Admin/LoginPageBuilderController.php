<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManPleasureLoginPageSetting;
use App\Support\ManPleasureLoginPageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class LoginPageBuilderController extends Controller
{
    public function index(): View
    {
        $settings = ManPleasureLoginPageSetting::current();

        return view('manpleasure-admin::login-page-builder.index', compact('settings'));
    }

    public function save(
        Request $request,
        ManPleasureLoginPageSanitizer $sanitizer
    ): RedirectResponse {
        $validated = $request->validate([
            'html'        => ['nullable', 'string', 'max:500000'],
            'css'         => ['nullable', 'string', 'max:200000'],
            'project_json'=> ['nullable', 'string', 'max:2000000'],
        ]);

        $projectJson = $validated['project_json'] ?? null;

        if ($projectJson !== null) {
            try {
                json_decode($projectJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $exception) {
                return back()
                    ->withErrors(['project_json' => 'The editor project data is invalid.'])
                    ->withInput();
            }
        }

        $settings = ManPleasureLoginPageSetting::current();

        $settings->fill([
            'draft_html'         => $sanitizer->cleanHtml($validated['html'] ?? ''),
            'draft_css'          => $sanitizer->cleanCss($validated['css'] ?? ''),
            'draft_project_json' => $projectJson,
            'updated_by'         => auth('admin')->id(),
        ])->save();

        return back()->with('success', 'Login page draft saved.');
    }

    public function publish(): RedirectResponse
    {
        $settings = ManPleasureLoginPageSetting::current();

        if (
            blank($settings->draft_html)
            && blank($settings->draft_css)
            && blank($settings->draft_project_json)
        ) {
            return back()->withErrors([
                'publish' => 'Save a draft before publishing.',
            ]);
        }

        // Publish the already-sanitised saved draft.
        $settings->forceFill([
            'published_html'         => $settings->draft_html,
            'published_css'          => $settings->draft_css,
            'published_project_json' => $settings->draft_project_json,
            'is_published'            => true,
            'updated_by'              => auth('admin')->id(),
        ])->save();

        return back()->with('success', 'Login page design published.');
    }

    public function unpublish(): RedirectResponse
    {
        ManPleasureLoginPageSetting::current()->forceFill([
            'is_published' => false,
            'updated_by'   => auth('admin')->id(),
        ])->save();

        return back()->with('success', 'Custom login page design unpublished.');
    }

    public function status(): JsonResponse
    {
        $settings = ManPleasureLoginPageSetting::current();

        return response()->json([
            'is_published' => $settings->is_published,
            'has_draft'     => $settings->draft_html !== null
                || $settings->draft_css !== null
                || $settings->draft_project_json !== null,
            'updated_at'    => $settings->updated_at?->toIso8601String(),
        ]);
    }
}