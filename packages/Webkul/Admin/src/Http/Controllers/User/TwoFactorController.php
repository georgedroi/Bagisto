<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Mail\Admin\BackupCodesNotification;
use Webkul\User\Repositories\AdminRepository;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA setup page with QR code and secret key.
     */
    public function setup()
    {
        try {
            $admin = auth('admin')->user();

            if (! $admin) {
                return response()->json([
                    'message' => trans('admin::app.errors.401.title'),
                ], 401);
            }

            $admin = app(AdminRepository::class)->findOrFail($admin->id);

            if (
                $admin->two_factor_enabled
                && $admin->two_factor_secret
                && ! session('two_factor_passed')
            ) {
                return response()->json([
                    'message' => trans('admin::app.errors.401.title'),
                ], 401);
            }

            if (! $admin->two_factor_secret) {
                $secret = two_factor_authentication()->generateSecretKey();

                app(AdminRepository::class)->update([
                    'two_factor_secret' => encrypt($secret),
                ], $admin->id);
            } else {
                $secret = decrypt($admin->two_factor_secret);
            }

            $qrCodeData = two_factor_authentication()->generateQrCode($admin->email, $secret);

            return response()->json($qrCodeData);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'error' => trans('admin::app.errors.500.title'),
            ], 500);
        }
    }

    /**
     * Enable 2FA after verifying code.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $admin = app(AdminRepository::class)->findOrFail(auth('admin')->id());

        $decryptedSecret = decrypt($admin->two_factor_secret);

        $isValidCode = two_factor_authentication()->verifyQrCode($decryptedSecret, $request->code);

        if (! $isValidCode) {
            return response()->json([
                'errors' => [
                    'code' => [trans('admin::app.account.messages.invalid-code')],
                ],
            ], 422);
        }

        $backupCodes = two_factor_authentication()->generateBackupCodes();

        app(AdminRepository::class)->update([
            'two_factor_enabled' => true,
            'two_factor_verified_at' => now(),
            'two_factor_backup_codes' => two_factor_authentication()->hashBackupCodes($backupCodes),
        ], $admin->id);

        session()->put('two_factor_passed', true);

        try {
            Mail::to($admin->email)->send(
                new BackupCodesNotification($admin, $backupCodes)
            );
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'message' => trans('admin::app.account.messages.enabled-success'),
            'backup_codes' => $backupCodes,
        ]);
    }

    /**
     * Disable 2FA configuration.
     */
    public function disable()
    {
        $admin = auth('admin')->user();

        if (! $admin) {
            return response()->json([
                'message' => trans('admin::app.errors.401.title'),
            ], 401);
        }

        $admin = app(AdminRepository::class)->findOrFail($admin->id);

        if (
            $admin->two_factor_enabled
            && ! session('two_factor_passed')
        ) {
            return response()->json([
                'message' => trans('admin::app.errors.401.title'),
            ], 401);
        }

        app(AdminRepository::class)->update(
            two_factor_authentication()->getDisableValues(),
            $admin->id
        );

        $message = trans('admin::app.account.messages.disabled-success');

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Show verification form for login.
     */
    public function showVerifyForm()
    {
        return view('admin::account.verify');
    }

    /**
     * Verify 2FA or backup code during login.
     */
    public function verifyTwoFactorCode(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $admin = app(AdminRepository::class)->findOrFail(auth('admin')->id());

        $decryptedSecret = decrypt($admin->two_factor_secret);

        if (two_factor_authentication()->verifyQrCode($decryptedSecret, $request->code)) {
            return $this->handleSuccessfulVerification();
        }

        if (app(AdminRepository::class)->consumeTwoFactorBackupCode($admin->id, $request->code)) {
            return $this->handleSuccessfulVerification();
        }

        return back()->withErrors([
            'code' => trans('admin::app.account.messages.invalid-code'),
        ]);
    }

    /**
     * Handle successful 2FA verification.
     */
    protected function handleSuccessfulVerification()
    {
        session()->put('two_factor_passed', true);

        return redirect()->intended(route('admin.dashboard.index'))
            ->with('success', trans('admin::app.account.messages.verified-success'));
    }
}
