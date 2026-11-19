<?php

namespace App\Http\Requests;

use App\Support\CryptoAddress;
use App\Support\ManualPaymentPolicy;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;
use Webkul\Admin\Http\Requests\ConfigurationForm;

/**
 * Guard manual receiving details before the shared configuration repository saves them.
 */
class ManualPaymentConfigurationRequest extends ConfigurationForm
{
    /**
     * Treat an unchecked image deletion control as keeping the saved image.
     */
    protected function prepareForValidation(): void
    {
        $path = 'sales.payment_methods.duitnowqr_manual.qr_image';
        $image = $this->input($path);

        if (! $this->hasFile($path) && is_array($image) && array_keys($image) === ['delete']
            && in_array($image['delete'], [0, '0', false], true)) {
            $input = $this->input();
            data_forget($input, $path);
            $this->replace($input);
        }
    }

    /**
     * Add server-only rules without exposing custom validation names to the form renderer.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ((array) $this->input('sales.payment_methods', []) as $code => $settings) {
            $rules["sales.payment_methods.{$code}.active"] = ['sometimes', 'boolean'];
        }

        foreach (['banktransfer', 'duitnowqr_manual'] as $code) {
            foreach (['bank_name', 'account_name', 'account_number'] as $field) {
                $rules["sales.payment_methods.{$code}.{$field}"] = ['sometimes', 'nullable', 'string', 'max:255'];
            }
        }

        if ($this->hasFile('sales.payment_methods.duitnowqr_manual.qr_image')) {
            $rules['sales.payment_methods.duitnowqr_manual.qr_image'] = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'];
        } else {
            $rules['sales.payment_methods.duitnowqr_manual.qr_image'] = ['sometimes', 'nullable', 'array:delete'];
            $rules['sales.payment_methods.duitnowqr_manual.qr_image.delete'] = ['sometimes', 'accepted'];
        }

        foreach (['btc_address', 'usdt_trc20_address', 'usdt_erc20_address'] as $field) {
            $rules["sales.payment_methods.cryptotransfer.{$field}"] = ['sometimes', 'nullable', 'string', 'max:90'];
        }

        return $rules;
    }

    /**
     * Validate the complete effective configuration, including unchanged saved fields.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $methods = $this->input('sales.payment_methods');

            if (! is_array($methods)) {
                return;
            }

            foreach ($methods as $code => $settings) {
                if (! in_array($code, ManualPaymentPolicy::METHODS, true) && $this->boolean("sales.payment_methods.{$code}.active")) {
                    $validator->errors()->add("sales.payment_methods.{$code}.active", trans('payment::setup.manual-only'));
                }
            }

            $qrPath = 'sales.payment_methods.duitnowqr_manual.qr_image';

            if ($this->hasFile($qrPath) && ! $this->validQrUpload()) {
                $validator->errors()->add($qrPath, trans('payment::setup.qr-required'));
            }

            if ($this->enabled('banktransfer')) {
                foreach (['bank_name', 'account_name', 'account_number'] as $field) {
                    if ($this->value('banktransfer', $field) === '') {
                        $validator->errors()->add("sales.payment_methods.banktransfer.{$field}", trans('payment::setup.bank-required'));
                    }
                }
            }

            if ($this->enabled('duitnowqr_manual') && ! $this->hasQrImage()) {
                if (collect(['bank_name', 'account_name', 'account_number'])->contains(fn ($field) => $this->value('duitnowqr_manual', $field) === '')) {
                    $validator->errors()->add('sales.payment_methods.duitnowqr_manual.qr_image', trans('payment::setup.qr-required'));
                }
            }

            $wallets = ['btc_address' => 'btc', 'usdt_trc20_address' => 'trc20', 'usdt_erc20_address' => 'erc20'];
            $hasWallet = false;

            foreach ($wallets as $field => $network) {
                $path = "sales.payment_methods.cryptotransfer.{$field}";
                $address = $this->value('cryptotransfer', $field);
                $hasWallet = $hasWallet || $address !== '';

                if (($this->has($path) || $this->enabled('cryptotransfer')) && $address !== '' && ! CryptoAddress::valid($address, $network)) {
                    $validator->errors()->add($path, trans('payment::setup.wallet-invalid'));
                }

                if ($this->has($path) && ! is_null($this->input($path)) && ! is_string($this->input($path))) {
                    $validator->errors()->add($path, trans('payment::setup.wallet-invalid'));
                }
            }

            if ($this->enabled('cryptotransfer') && ! $hasWallet) {
                $validator->errors()->add('sales.payment_methods.cryptotransfer.btc_address', trans('payment::setup.wallet-required'));
            }
        }];
    }

    /**
     * Determine whether a submitted method will be active after this save.
     */
    private function enabled(string $code): bool
    {
        return $this->has("sales.payment_methods.{$code}")
            && ($this->has("sales.payment_methods.{$code}.active")
                ? $this->boolean("sales.payment_methods.{$code}.active")
                : (bool) $this->saved($code, 'active'));
    }

    /**
     * Read the submitted text or the saved value for the selected configuration channel.
     */
    private function value(string $code, string $field): string
    {
        $path = "sales.payment_methods.{$code}.{$field}";
        $value = $this->has($path) ? $this->input($path) : $this->saved($code, $field);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Resolve saved configuration through the core configuration repository abstraction.
     */
    private function saved(string $code, string $field): mixed
    {
        return core()->getConfigData("sales.payment_methods.{$code}.{$field}", $this->input('channel'), $this->input('locale'));
    }

    /**
     * Require a genuine uploaded raster image or an existing local configuration image.
     */
    private function hasQrImage(): bool
    {
        $path = 'sales.payment_methods.duitnowqr_manual.qr_image';

        if ($this->hasFile($path)) {
            return $this->validQrUpload();
        }

        if ($this->has("{$path}.delete")) {
            return false;
        }

        $image = $this->saved('duitnowqr_manual', 'qr_image');

        return is_string($image) && preg_match('/\Aconfiguration\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)\z/i', $image)
            && Storage::exists($image)
            && in_array(Storage::mimeType($image), ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /**
     * Inspect raster bytes instead of relying only on the declared file MIME type.
     */
    private function validQrUpload(): bool
    {
        $file = $this->file('sales.payment_methods.duitnowqr_manual.qr_image');

        if (! $file || ! $file->isValid()) {
            return false;
        }

        $image = @getimagesize($file->getRealPath());

        return $image !== false && in_array($image[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }
}
