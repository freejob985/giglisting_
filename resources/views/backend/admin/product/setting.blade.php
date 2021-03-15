@extends('backend.admin.layouts.app')

@section('styles')
    <!-- Image Crop Css -->
    <link href="{{ asset('backend/vendor/croppie/croppie.css') }}" rel="stylesheet" />

    <!-- Bootstrap FD Css-->
    <link href="{{ asset('backend/vendor/bootstrap-fd/bootstrap.fd.css') }}" rel="stylesheet" />
@endsection

@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            <h1 class="h3 mb-2 text-gray-800">{{ __('products.setting') }}</h1>
            <p class="mb-4">{{ __('products.setting-desc') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('admin.products.index') }}" class="btn btn-info btn-icon-split">
                <span class="icon text-white-50">
                  <i class="fas fa-backspace"></i>
                </span>
                <span class="text">{{ __('backend.shared.back') }}</span>
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row bg-white pt-4 pl-3 pr-3 pb-4">
        <div class="col-12">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-sm-12">
                    <form method="POST" action="{{ route('admin.product.setting.update') }}" class="">
                        @csrf

                        <div class="row form-group">
                            <div class="col-md-4">
                                <label for="setting_product_auto_approval_enable" class="text-black">{{ __('products.auto-approval') }}</label>
                                <select id="setting_product_auto_approval_enable" class="custom-select" name="setting_product_auto_approval_enable">
                                    <option value="{{ \App\Setting::SITE_PRODUCT_AUTO_APPROVAL_DISABLED }}" {{ $settings->setting_product_auto_approval_enable == \App\Setting::SITE_PRODUCT_AUTO_APPROVAL_DISABLED ? 'selected' : '' }}>{{ __('products.disabled') }}</option>
                                    <option value="{{ \App\Setting::SITE_PRODUCT_AUTO_APPROVAL_ENABLED }}" {{ $settings->setting_product_auto_approval_enable == \App\Setting::SITE_PRODUCT_AUTO_APPROVAL_ENABLED ? 'selected' : '' }}>{{ __('products.enabled') }}</option>
                                </select>
                                <small class="form-text text-muted">
                                    {{ __('products.auto-approval-help') }}
                                </small>
                                @error('setting_product_auto_approval_enable')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="setting_product_max_gallery_photos" class="text-black">{{ __('products.max-gallery-photos') }}</label>
                                <input id="setting_product_max_gallery_photos" type="number" class="form-control @error('setting_product_max_gallery_photos') is-invalid @enderror" name="setting_product_max_gallery_photos" value="{{ $settings->setting_product_max_gallery_photos }}">
                                <small class="form-text text-muted">
                                    {{ __('products.max-gallery-photos-help') }}
                                </small>
                                @error('setting_product_max_gallery_photos')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="setting_product_currency_symbol" class="text-black">{{ __('products.currency-symbol') }}</label>
                                <select id="setting_product_currency_symbol" class="custom-select" name="setting_product_currency_symbol">
                                    <option value="$" {{ $settings->setting_product_currency_symbol == '$' ? 'selected' : '' }}>$</option>
                                    <option value="€" {{ $settings->setting_product_currency_symbol == '€' ? 'selected' : '' }}>€</option>
                                    <option value="£" {{ $settings->setting_product_currency_symbol == '£' ? 'selected' : '' }}>£</option>
                                    <option value="¥" {{ $settings->setting_product_currency_symbol == '¥' ? 'selected' : '' }}>¥</option>
                                    <option value="CHF" {{ $settings->setting_product_currency_symbol == 'CHF' ? 'selected' : '' }}>CHF</option>
                                    <option value="kr" {{ $settings->setting_product_currency_symbol == 'kr' ? 'selected' : '' }}>kr</option>
                                    <option value="Rp" {{ $settings->setting_product_currency_symbol == 'Rp' ? 'selected' : '' }}>Rp</option>
                                    <option value="руб" {{ $settings->setting_product_currency_symbol == 'руб' ? 'selected' : '' }}>руб</option>
                                    <option value="₩" {{ $settings->setting_product_currency_symbol == '₩' ? 'selected' : '' }}>₩</option>
                                    <option value="₫" {{ $settings->setting_product_currency_symbol == '₫' ? 'selected' : '' }}>₫</option>
                                    <option value="lei" {{ $settings->setting_product_currency_symbol == 'lei' ? 'selected' : '' }}>lei</option>
                                    <option value="GH₵" {{ $settings->setting_product_currency_symbol == 'GH₵' ? 'selected' : '' }}>GH₵</option>
                                    <option value="₹" {{ $settings->setting_product_currency_symbol == '₹' ? 'selected' : '' }}>₹</option>
                                    <option value="KD" {{ $settings->setting_product_currency_symbol == 'KD' ? 'selected' : '' }}>KD</option>
                                    <option value="د.إ" {{ $settings->setting_product_currency_symbol == 'د.إ' ? 'selected' : '' }}>د.إ</option>
                                    <option value="RM" {{ $settings->setting_product_currency_symbol == 'RM' ? 'selected' : '' }}>RM</option>
                                </select>
                                <small class="form-text text-muted">
                                    {{ __('products.currency-symbol-help') }}
                                </small>
                                @error('setting_product_currency_symbol')
                                <span class="invalid-tooltip">
                                <strong>{{ $message }}</strong>
                            </span>
                                @enderror
                            </div>
                        </div>



                        <div class="row form-group">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success py-2 px-4 text-white">
                                    {{ __('backend.shared.update') }}
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
        });
    </script>
@endsection
