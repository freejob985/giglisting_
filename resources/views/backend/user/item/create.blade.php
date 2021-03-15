@extends('backend.user.layouts.app')

@section('styles')

    @if($site_global_settings->setting_site_map == \App\Setting::SITE_MAP_OPEN_STREET_MAP)
    <link href="{{ asset('frontend/vendor/leaflet/leaflet.css') }}" rel="stylesheet" />
    @endif

    <!-- Image Crop Css -->
    <link href="{{ asset('backend/vendor/croppie/croppie.css') }}" rel="stylesheet" />

    <!-- Bootstrap FD Css-->
    <link href="{{ asset('backend/vendor/bootstrap-fd/bootstrap.fd.css') }}" rel="stylesheet" />


   <!-- start preload font styles -->
    <link rel="preload" onload="this.rel='stylesheet'" href="{{ asset('backend/vendor/fontawesome-free/css/all.min.css') }}" as="style">
    <link rel="preload" onload="this.rel='stylesheet'" href="{{ asset('frontend/fonts/icomoon/style.min.css') }}" as="style">
    <link rel="preload" onload="this.rel='stylesheet'" href="{{ asset('frontend/fonts/nanum-gothic/style.min.css') }}" as="style">
    <!-- end preload font styles -->

    <!-- Start Firefox does not support preload -->
    <link rel="stylesheet" href="{{ asset('backend/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/fonts/icomoon/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/fonts/nanum-gothic/style.min.css') }}">
    <!-- End Firefox does not support preload -->

    <link rel="stylesheet" href="{{ asset('frontend/css/stylesheet.css') }}">
      <link rel="stylesheet" href="{{ asset('frontend/css/flaticon.css') }}">

    <!-- custom colors -->
@endsection
   @include('frontend.partials.nav')
   
@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            </br>
            <h1 class="h3 mb-2 text-gray-800">{{ __('backend.item.add-item') }}</h1>
            <p class="mb-4">{{ __('backend.item.add-item-desc-user') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('user.items.index') }}" class="btn btn-info btn-icon-split">
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

            <div class="row mb-4">
                <div class="col-12">
                    <div class="row mb-2">
                        <div class="col-12">
                            <span class="text-lg text-gray-800">{{ __('backend.item.select-category') }}</span>
                            <form class="form-inline pt-2" action="{{ route('user.items.create') }}" method="GET">
                                <div class="form-group mr-2">
                                    <select multiple size="{{ count($all_categories) }}" class="custom-select @error('category') is-invalid @enderror" name="category[]" onchange="$('#item-create-form').remove();">
                                        @foreach($all_categories as $key => $category)
                                            <option value="{{ $category['category_id'] }}" {{ in_array($category['category_id'], empty($category_ids) ? array() : $category_ids) ? 'selected' : '' }}>{{ $category['category_name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                    <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">{{ __('backend.item.load-form') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if(is_array($category_ids) && count($category_ids) > 0)
                    <form method="POST" action="{{ route('user.items.store') }}" id="item-create-form">
                        @csrf

                       <hr/>
                        <div class="form-row mb-3">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <div class="form-check">
                                    <input checked class="form-check-input" type="radio" name="item_type" id="item_type_regular" value="{{ \App\Item::ITEM_TYPE_REGULAR }}" aria-describedby="item_type_regularHelpBlock">
                                    <label class="form-check-label" for="item_type_regular">
                                        {{ __('theme_directory_hub.online-listing.regular-listing') }}
                                    </label>
                                    <small id="item_type_regularHelpBlock" class="form-text text-muted">
                                        {{ __('theme_directory_hub.online-listing.regular-listing-help') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="item_type" id="item_type_online" value="{{ \App\Item::ITEM_TYPE_ONLINE }}" aria-describedby="item_type_onlineHelpBlock">
                                    <label class="form-check-label" for="item_type_online">
                                        {{ __('theme_directory_hub.online-listing.online-listing') }}
                                    </label>
                                    <small id="item_type_onlineHelpBlock" class="form-text text-muted">
                                        {{ __('theme_directory_hub.online-listing.online-listing-help') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr/>
                        <div class="form-row mb-3">
                            <div class="col-md-12">
                                <span class="text-lg text-gray-800">{{ __('backend.item.general-info') }}</span>
                                <small class="form-text text-muted">
                                </small>
                            </div>
                        </div>
                        <div class="form-row mb-3">
                            <div class="col-md-4">
                                <label for="item_title" class="text-black">{{ __('backend.item.title') }}</label>
                                <input id="item_title" type="text" class="form-control @error('item_title') is-invalid @enderror" name="item_title" value="{{ old('item_title') }}">
                                @error('item_title')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                @foreach($category_ids as $key => $category_id)
                                    <input name="category[]" value="{{ $category_id }}" type="hidden" class="input_category_id">
                                @endforeach
                            </div>

                            <div class="col-md-4">
                                <label for="item_address" class="text-black">{{ __('backend.item.address') }}</label>
                                <input id="item_address" type="text" class="form-control @error('item_address') is-invalid @enderror" name="item_address" value="{{ old('item_address') }}">
                                @error('item_address')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                @if(Auth::user()->hasPaidSubscription())

                                    <label for="item_featured" class="text-black">{{ __('backend.item.featured') }}</label>
                                    <select class="custom-select @error('item_featured') is-invalid @enderror" name="item_featured">

                                        <option value="0" selected>{{ __('backend.shared.no') }}</option>
                                        <option value="{{ \App\Item::ITEM_FEATURED }}">{{ __('backend.shared.yes') }}</option>

                                    </select>
                                    @error('item_featured')
                                    <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                @else
                                    <input type="hidden" name="item_featured" value="{{ \App\Item::ITEM_NOT_FEATURED }}">
                                @endif
                            </div>
                        </div>

                        <div class="form-row mb-3">

                            <div class="col-md-12">
                                <div class="form-check form-check-inline">
                                    <input {{ old('item_address_hide') == 1 ? 'checked' : '' }} class="form-check-input" type="checkbox" id="item_address_hide" name="item_address_hide" value="1">
                                    <label class="form-check-label" for="item_address_hide">
                                        {{ __('backend.item.hide-address') }}
                                        <small class="text-muted">
                                            {{ __('backend.item.hide-address-help') }}
                                        </small>
                                    </label>
                                </div>
                                @error('item_address_hide')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row mb-3">

                            <div class="col-md-3">
                                <label for="select_country_id" class="text-black">{{ __('backend.setting.country') }}</label>
                                <select id="select_country_id" class="custom-select @error('country_id') is-invalid @enderror" name="country_id">
                                    <option selected>{{ __('prefer_country.select-country') }}</option>
                                    @foreach($all_countries as $all_countries_key => $country)
                                        <option value="{{ $country->id }}" {{ $country->id == old('country_id') ? 'selected' : '' }}>{{ $country->country_name }}</option>
                                    @endforeach
                                </select>
                                @error('country_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="select_state_id" class="text-black">{{ __('backend.state.state') }}</label>
                                <select id="select_state_id" class="custom-select @error('state_id') is-invalid @enderror" name="state_id">
                                    <option selected>{{ __('backend.item.select-state') }}</option>
                                </select>
                                @error('state_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="select_city_id" class="text-black">{{ __('backend.city.city') }}</label>
                                <select id="select_city_id" class="custom-select @error('city_id') is-invalid @enderror" name="city_id">
                                    <option selected>{{ __('backend.item.select-city') }}</option>
                                </select>
                                @error('city_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_postal_code" class="text-black">{{ __('backend.item.postal-code') }}</label>
                                <input id="item_postal_code" type="text" class="form-control @error('item_postal_code') is-invalid @enderror" name="item_postal_code" value="{{ old('item_postal_code') }}">
                                @error('item_postal_code')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                        </div>

                        <div class="form-row mb-3">

                            <div class="col-md-3">
                                <label for="item_lat" class="text-black">{{ __('backend.item.lat') }}</label>
                                <input id="item_lat" type="text" class="form-control @error('item_lat') is-invalid @enderror" name="item_lat" value="{{ old('item_lat') }}" aria-describedby="latHelpBlock">
                                <small id="latHelpBlock" class="form-text text-muted">
                                    <a class="lat_lng_select_button btn btn-sm btn-primary text-white">{{ __('backend.item.select-map') }}</a>
                                </small>
                                @error('item_lat')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_lng" class="text-black">{{ __('backend.item.lng') }}</label>
                                <input id="item_lng" type="text" class="form-control @error('item_lng') is-invalid @enderror" name="item_lng" value="{{ old('item_lng') }}" aria-describedby="lngHelpBlock">
                                <small id="lngHelpBlock" class="form-text text-muted">
                                    <a class="lat_lng_select_button btn btn-sm btn-primary text-white">{{ __('backend.item.select-map') }}</a>
                                </small>
                                @error('item_lng')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_phone" class="text-black">{{ __('backend.item.phone') }}</label>
                                <input id="item_phone" type="text" class="form-control @error('item_phone') is-invalid @enderror" name="item_phone" value="{{ old('item_phone') }}">
                                @error('item_phone')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_youtube_id" class="text-black">{{ __('customization.item.youtube-id') }}</label>
                                <input id="item_youtube_id" type="text" class="form-control @error('item_youtube_id') is-invalid @enderror" name="item_youtube_id" value="{{ old('item_youtube_id') }}">
                                @error('item_youtube_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                        </div>

                        <div class="form-row mb-3">

                            <div class="col-md-12">
                                <label for="item_description" class="text-black">{{ __('backend.item.description') }}</label>
                                <textarea class="form-control @error('item_description') is-invalid @enderror" id="item_description" rows="5" name="item_description">{{ old('item_description') }}</textarea>
                                @error('item_description')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Start web & social media -->
                        <div class="form-row mb-3">
                            <div class="col-md-3">
                                <label for="item_website" class="text-black">{{ __('backend.item.website') }}</label>
                                <input id="item_website" type="text" class="form-control @error('item_website') is-invalid @enderror" name="item_website" value="{{ old('item_website') }}">
                                <small id="linkHelpBlock" class="form-text text-muted">
                                    {{ __('backend.shared.url-help') }}
                                </small>
                                @error('item_website')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_social_facebook" class="text-black">{{ __('backend.item.facebook') }}</label>
                                <input id="item_social_facebook" type="text" class="form-control @error('item_social_facebook') is-invalid @enderror" name="item_social_facebook" value="{{ old('item_social_facebook') }}">
                                <small id="linkHelpBlock" class="form-text text-muted">
                                    {{ __('backend.shared.url-help') }}
                                </small>
                                @error('item_social_facebook')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_social_twitter" class="text-black">{{ __('backend.item.twitter') }}</label>
                                <input id="item_social_twitter" type="text" class="form-control @error('item_social_twitter') is-invalid @enderror" name="item_social_twitter" value="{{ old('item_social_twitter') }}">
                                <small id="linkHelpBlock" class="form-text text-muted">
                                    {{ __('backend.shared.url-help') }}
                                </small>
                                @error('item_social_twitter')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="item_social_linkedin" class="text-black">{{ __('backend.item.linkedin') }}</label>
                                <input id="item_social_linkedin" type="text" class="form-control @error('item_social_linkedin') is-invalid @enderror" name="item_social_linkedin" value="{{ old('item_social_linkedin') }}">
                                <small id="linkHelpBlock" class="form-text text-muted">
                                    {{ __('backend.shared.url-help') }}
                                </small>
                                @error('item_social_linkedin')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                        </div>
                        <!-- End web & social media -->

                        <!-- Start custom field section -->
                        <div class="form-row mb-3">
                            <div class="col-md-12">
                                <span class="text-lg text-gray-800">{{ __('backend.item.custom-fields') }}</span>
                                <small class="form-text text-muted">
                                    {{ __('backend.item.custom-field-help') }}
                                </small>
                            </div>
                        </div>
                        <div class="form-row">
                            @foreach($all_customFields as $key => $customField)
                                <div class="col-md-4 mb-3">
                                    @if($customField->custom_field_type == \App\CustomField::TYPE_TEXT)
                                        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}" class="text-black">{{ $customField->custom_field_name }}</label>
                                        <textarea class="form-control @error(str_slug($customField->custom_field_name . $customField->id)) is-invalid @enderror" id="{{ str_slug($customField->custom_field_name . $customField->id) }}" rows="5" name="{{ str_slug($customField->custom_field_name . $customField->id) }}">{{ old(str_slug($customField->custom_field_name . $customField->id)) }}</textarea>
                                        @error(str_slug($customField->custom_field_name . $customField->id))
                                        <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    @endif
                                    @if($customField->custom_field_type == \App\CustomField::TYPE_SELECT)
                                        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}" class="text-black">{{ $customField->custom_field_name }}</label>
                                            <select class="custom-select" name="{{ str_slug($customField->custom_field_name . $customField->id) }}" id="{{ str_slug($customField->custom_field_name . $customField->id) }}">
                                                @foreach(explode(',', $customField->custom_field_seed_value) as $key => $custom_field_value)
                                                    <option value="{{ $custom_field_value }}" {{ old(str_slug($customField->custom_field_name . $customField->id)) == $custom_field_value ? 'selected' : '' }}>{{ $custom_field_value }}</option>
                                                @endforeach
                                            </select>
                                        @error(str_slug($customField->custom_field_name . $customField->id))
                                        <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    @endif
                                    @if($customField->custom_field_type == \App\CustomField::TYPE_MULTI_SELECT)
                                        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}" class="text-black">{{ $customField->custom_field_name }}</label>
                                        <select multiple class="custom-select" name="{{ str_slug($customField->custom_field_name . $customField->id) }}[]" id="{{ str_slug($customField->custom_field_name . $customField->id) }}">
                                            @foreach(explode(',', $customField->custom_field_seed_value) as $key => $custom_field_value)
                                                <option value="{{ $custom_field_value }}" {{ old(str_slug($customField->custom_field_name . $customField->id)) == $custom_field_value ? 'selected' : '' }}>{{ $custom_field_value }}</option>
                                            @endforeach
                                        </select>
                                        @error($customField->custom_field_name . $customField->id)
                                        <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    @endif
                                    @if($customField->custom_field_type == \App\CustomField::TYPE_LINK)
                                        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}" class="text-black">{{ $customField->custom_field_name }}</label>
                                        <input id="{{ str_slug($customField->custom_field_name . $customField->id) }}" type="text" class="form-control @error(str_slug($customField->custom_field_name . $customField->id)) is-invalid @enderror" name="{{ str_slug($customField->custom_field_name . $customField->id) }}" value="{{ old(str_slug($customField->custom_field_name . $customField->id)) }}" aria-describedby="linkHelpBlock">
                                        <small id="linkHelpBlock" class="form-text text-muted">
                                            {{ __('backend.shared.url-help') }}
                                        </small>
                                        @error(str_slug($customField->custom_field_name . $customField->id))
                                        <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <!-- End custom field section -->

                        <div class="form-row mb-3">
                            <div class="col-md-6">
                                <span class="text-lg text-gray-800">{{ __('backend.item.feature-image') }}</span>
                                <small class="form-text text-muted">
                                    {{ __('backend.item.feature-image-help') }}
                                </small>
                                @error('feature_image')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="row mt-3">
                                    <div class="col-8">
                                        <button id="upload_image" type="button" class="btn btn-primary btn-block mb-2">{{ __('backend.item.select-image') }}</button>
                                        <img id="image_preview" src="{{ asset('frontend/images/placeholder/full_item_feature_image.webp') }}" class="img-responsive">
                                        <input id="feature_image" type="hidden" name="feature_image">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <span class="text-lg text-gray-800">{{ __('backend.item.gallery-images') }}</span>
                                <small class="form-text text-muted">
                                    {{ __('backend.item.gallery-images-help') }}
                                </small>
                                @error('image_gallery')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button id="upload_gallery" type="button" class="btn btn-primary btn-block mb-2">{{ __('backend.item.select-images') }}</button>
                                        <div class="row" id="selected-images">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr/>
                        <div class="form-row mb-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success py-2 px-4 text-white">
                                    {{ __('backend.shared.create') }}
                                </button>
                            </div>
                        </div>

                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - feature image -->
    <div class="modal fade" id="image-crop-modal" tabindex="-1" role="dialog" aria-labelledby="image-crop-modal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('backend.item.crop-feature-image') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div id="image_demo"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div class="custom-file">
                                <input id="upload_image_input" type="file" class="custom-file-input">
                                <label class="custom-file-label" for="upload_image_input">{{ __('backend.item.choose-image') }}</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('backend.shared.cancel') }}</button>
                    <button id="crop_image" type="button" class="btn btn-primary">{{ __('backend.item.crop-image') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - map -->
    <div class="modal fade" id="map-modal" tabindex="-1" role="dialog" aria-labelledby="map-modal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('backend.item.select-map-title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <div id="map-modal-body"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <span id="lat_lng_span"></span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('backend.shared.cancel') }}</button>
                    <button id="lat_lng_confirm" type="button" class="btn btn-primary">{{ __('backend.shared.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    @if($site_global_settings->setting_site_map == \App\Setting::SITE_MAP_OPEN_STREET_MAP)
    <!-- Make sure you put this AFTER Leaflet's CSS -->
    <script src="{{ asset('frontend/vendor/leaflet/leaflet.js') }}"></script>
    @endif

    <!-- Image Crop Plugin Js -->
    <script src="{{ asset('backend/vendor/croppie/croppie.js') }}"></script>

    <!-- Bootstrap Fd Plugin Js-->
    <script src="{{ asset('backend/vendor/bootstrap-fd/bootstrap.fd.js') }}"></script>

    <script>

        // Call the dataTables jQuery plugin
        $(document).ready(function() {

            @if($site_global_settings->setting_site_map == \App\Setting::SITE_MAP_OPEN_STREET_MAP)
            /**
             * Start map modal
             */
            var map = L.map('map-modal-body', {
                //center: [37.0902, -95.7129],
                center: [{{ $setting_site_location_lat }}, {{ $setting_site_location_lng }}],
                zoom: 5,
            });

            var layerGroup = L.layerGroup().addTo(map);
            var current_lat = 0;
            var current_lng = 0;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            map.on('click', function(e) {

                // remove all the markers in one go
                layerGroup.clearLayers();
                L.marker([e.latlng.lat, e.latlng.lng]).addTo(layerGroup);

                current_lat = e.latlng.lat;
                current_lng = e.latlng.lng;

                $('#lat_lng_span').text("Lat, Lng : " + e.latlng.lat + ", " + e.latlng.lng);
            });

            $('#lat_lng_confirm').on('click', function(){

                $('#item_lat').val(current_lat);
                $('#item_lng').val(current_lng);
                $('#map-modal').modal('hide')
            });
            $('.lat_lng_select_button').on('click', function(){
                $('#map-modal').modal('show');
                setTimeout(function(){ map.invalidateSize()}, 500);
            });
            /**
             * End map modal
             */
            @endif

            /**
             * Start country, state, city selector
             */
            $('#select_country_id').on('change', function() {

                $('#select_state_id').html("<option selected>{{ __('prefer_country.loading-wait') }}</option>");

                if(this.value > 0)
                {
                    var ajax_url = '/ajax/states/' + this.value;

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    jQuery.ajax({
                        url: ajax_url,
                        method: 'get',
                        data: {
                        },
                        success: function(result){
                            console.log(result);
                            $('#select_state_id').html("<option selected>{{ __('backend.item.select-state') }}</option>");
                            $.each(JSON.parse(result), function(key, value) {
                                var state_id = value.id;
                                var state_name = value.state_name;
                                $('#select_state_id').append('<option value="'+ state_id +'">' + state_name + '</option>');
                            });
                        }});
                }

            });

            $('#select_state_id').on('change', function() {

                $('#select_city_id').html("<option selected>{{ __('prefer_country.loading-wait') }}</option>");

                if(this.value > 0)
                {
                    var ajax_url = '/ajax/cities/' + this.value;

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    jQuery.ajax({
                        url: ajax_url,
                        method: 'get',
                        data: {
                        },
                        success: function(result){
                            console.log(result);
                            $('#select_city_id').html("<option selected>{{ __('backend.item.select-city') }}</option>");
                            $.each(JSON.parse(result), function(key, value) {
                                var city_id = value.id;
                                var city_name = value.city_name;
                                $('#select_city_id').append('<option value="'+ city_id +'">' + city_name + '</option>');
                            });
                    }});
                }

            });
            /**
             * End country, state, city selector
             */

            /**
             * Start image gallery uplaod
             */
            $('#upload_gallery').on('click', function(){
                window.selectedImages = [];

                $.FileDialog({
                    accept: "image/jpeg",
                }).on("files.bs.filedialog", function (event) {
                    var html = "";
                    for (var a = 0; a < event.files.length; a++) {

                        if(a == 12) {break;}
                        selectedImages.push(event.files[a]);
                        html += "<div class='col-3 mb-2' id='item_image_gallery_" + a + "'>" +
                            "<img style='max-width: 120px;' src='" + event.files[a].content + "'>" +
                            "<br/><button class='btn btn-danger btn-sm text-white mt-1' onclick='$(\"#item_image_gallery_" + a + "\").remove();'>Delete</button>" +
                            "<input type='hidden' value='" + event.files[a].content + "' name='image_gallery[]'>" +
                            "</div>";
                    }
                    document.getElementById("selected-images").innerHTML += html;
                });
            });
            /**
             * End image gallery uplaod
             */

            /**
             * Start the croppie image plugin
             */
            $image_crop = null;

            $('#upload_image').on('click', function(){

                $('#image-crop-modal').modal('show');
            });

            var window_height = $(window).height();
            var window_width = $(window).width();
            var viewport_height = 0;
            var viewport_width = 0;

            if(window_width >= 800)
            {
                viewport_width = 800;
                viewport_height = 687;
            }
            else
            {
                viewport_width = window_width * 0.8;
                viewport_height = (viewport_width * 687) / 800;
            }

            $('#upload_image_input').on('change', function(){

                if(!$image_crop)
                {
                    $image_crop = $('#image_demo').croppie({
                        enableExif: true,
                        mouseWheelZoom: false,
                        viewport: {
                            width:viewport_width,
                            height:viewport_height,
                            type:'square',
                        },
                        boundary:{
                            width:viewport_width + 5,
                            height:viewport_width + 5,
                        }
                    });

                    $('#image-crop-modal .modal-dialog').css({
                        'max-width':'100%'
                    });
                }

                var reader = new FileReader();

                reader.onload = function (event) {

                    $image_crop.croppie('bind', {
                        url: event.target.result
                    }).then(function(){
                        console.log('jQuery bind complete');
                    });

                }
                reader.readAsDataURL(this.files[0]);
            });

            $('#crop_image').on("click", function(event){

                $image_crop.croppie('result', {
                    type: 'base64',
                    size: 'viewport'
                }).then(function(response){
                    $('#feature_image').val(response);
                    $('#image_preview').attr("src", response);
                });

                $('#image-crop-modal').modal('hide');
            });
            /**
             * End the croppie image plugin
             */

        });
    </script>

    @if($site_global_settings->setting_site_map == \App\Setting::SITE_MAP_GOOGLE_MAP)

        <script>
            function initMap()
            {
                const myLatlng = { lat: {{ $site_global_settings->setting_site_location_lat }}, lng: {{ $site_global_settings->setting_site_location_lng }} };
                const map = new google.maps.Map(document.getElementById('map-modal-body'), {
                    zoom: 4,
                    center: myLatlng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                let infoWindow = new google.maps.InfoWindow({
                    content: "{{ __('google_map.select-lat-lng-on-map') }}",
                    position: myLatlng,
                });
                infoWindow.open(map);

                var current_lat = 0;
                var current_lng = 0;

                google.maps.event.addListener(map, 'click', function( event ){

                    // Close the current InfoWindow.
                    infoWindow.close();
                    // Create a new InfoWindow.
                    infoWindow = new google.maps.InfoWindow({
                        position: event.latLng,
                    });
                    infoWindow.setContent(
                        JSON.stringify(event.latLng.toJSON(), null, 2)
                    );
                    infoWindow.open(map);

                    current_lat = event.latLng.lat();
                    current_lng = event.latLng.lng();
                    console.log( "Latitude: "+current_lat+" "+", longitude: "+current_lng );
                    $('#lat_lng_span').text("Lat, Lng : " + current_lat + ", " + current_lng);
                });

                $('#lat_lng_confirm').on('click', function(){

                    $('#item_lat').val(current_lat);
                    $('#item_lng').val(current_lng);
                    $('#map-modal').modal('hide');
                });
                $('.lat_lng_select_button').on('click', function(){
                    $('#map-modal').modal('show');
                    //setTimeout(function(){ map.invalidateSize()}, 500);
                });
            }
        </script>

        <script async defer src="https://maps.googleapis.com/maps/api/js??v=quarterly&key={{ $site_global_settings->setting_site_map_google_api_key }}&callback=initMap"></script>
    @endif
    @include('frontend.partials.footer')
@endsection

<style>
    #accordionSidebar{display:none;}
.form-inline .form-group {
    display: flex;
    flex: 0 0 auto;
    flex-flow: row wrap;
    align-items: center;
    margin-bottom: 0;
    width: 80%;
    margin: auto;
}.custom-select[multiple], .custom-select[size]:not([size="1"]) {
    width: 100%;
    height: 340px!important;
    padding-right: .75rem;
    background-image: none;
}




.info-bar-area.style-three .info-bar-inner {
    padding: 10px 0 18px 0;
}

.social-icon li{margin:0px 13px;}

.info-bar-area.style-two .info-bar-inner {
    padding: 0;
}

.info-bar-area .social-icon .title {
    font-size: 16px;
    line-height: 26px;
    font-weight: 700;
    margin-bottom: 0;
    color: var(--heading-color);
}

.info-bar-inner {
    padding: 32px 0 35px 0;
}



.info-bar-inner .logo-wrapper .logo {
    margin-top: 5px;
    display: block;
}

.info-bar-inner .logo-wrapper .site-title {
    font-size: 35px;
    font-weight: 700;
    margin-top: 10px;
}

.info-bar-inner .right-content {
    display: inline-block;
    float: right;
}

.info-bar-inner .right-content .request-quote {
    margin-left: 40px;
}

.info-bar-inner .right-content .request-quote .rq-btn {
    padding: 15px 25px;
    background-color: var(--main-color-one);
    border-radius: 30px;
    color: #fff;
    font-weight: 600;
    text-transform: capitalize;
    display: block;
    position: relative;
    top: -5px;
    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}

.info-bar-inner .right-content .request-quote .rq-btn.blank {
    background-color: #f1f1f1;
    border-radius: 0;
    top: 0;
    padding: 15px 40px;
    color: var(--heading-color);
}

.info-bar-inner .right-content .request-quote .rq-btn.blank i {
    color: var(--main-color-one);
    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}

.info-bar-inner .right-content .request-quote .rq-btn.blank:hover {
    background-color: var(--main-color-one);
    color: #fff;
}

.info-bar-inner .right-content .request-quote .rq-btn.blank:hover i {
    color: #fff;
}

.info-bar-inner .right-content .request-quote .rq-btn:hover {
    background-color: var(--secondary-color);
}

.info-items {
    display: inline-block;
    margin: 0;
    padding: 0;
    list-style: none;
}

.info-items li {
    display: inline-block;
    margin: 0 30px;
}

.info-items li:hover .single-info-item .content .title {
    color: var(--main-color-one);
}

.info-items li:first-child {
    margin-left: 0;
}

.info-items li:last-child {
    margin-right: 0;
}

.info-items li .single-info-item {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-item-align: start;
    align-self: flex-start;
}

.info-items li .single-info-item .icon {
    margin-right: 20px;
    font-size: 40px;
    line-height: 40px;
    color: var(--main-color-one);
}

.info-items li .single-info-item .content .title {
    font-size: 16px;
    line-height: 26px;
    font-weight: 700;
    margin-bottom: 0;
    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}

.info-items li .single-info-item .content .details {
    font-size: 14px;
    line-height: 24px;
}

.info-items-two {
    display: inline-block;
    margin: 0;
    padding: 0;
    list-style: none;
   
}

.info-items-two li {
    display: inline-block;
    margin: 0 30px;
}

.info-items-two li:hover .single-info-item .content .title {
    color: var(--main-color-one);
}

.info-items-two li:first-child {
    margin-left: 0;
}

.info-items-two li:last-child {
    margin-right: 0;
}.site-footer .dropdown-menu {
    z-index: 22222222222222222;

}
.btn-group>.btn:first-child {
    margin-left: 0;
    color: #000!important;
}.fa-language:before{display:none;}
.info-items-two li .single-info-item {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-item-align: start;
    align-self: flex-start;
}

.info-items-two li .single-info-item .icon {
    margin-right: 15px;
    font-size: 30px;
    line-height: 30px;
    color: var(--main-color-one);
}

.info-items-two li .single-info-item .content {
    margin-top: 3px;
}

.info-items-two li .single-info-item .content .title {
    font-size: 16px;
    line-height: 26px;
    font-weight: 700;
    margin-bottom: 0;
    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}
ul li{display:inline;}
.info-items-two li .single-info-item .content .details {
    margin-left: 5px;
    font-size: 14px;
    line-height: 24px;
    font-weight: 500;
    color: var(--paragraph-color);
}.info-items-two li {
    display: inline-block;
    margin: 0px 2px;
}
@media (max-width: 767.98px){
.site-navbar {
    top: 127px;
}}
.copyright.text-center.my-auto {
    display: none;
}
.site-footer ul li {color: #fff;
    margin-bottom: 10px;
    display: block!important;
}.single-info-item .icon{color:gold;}.site-footer {
    padding: 4em 0;
}.site-footer {
    background: #7e287f!important;
    padding: 4em 0;
    color: #fff!important;
}.site-footer p {
    color: #fff!important;
}.site-footer ul  a {
    color: #fff!important;}
</style>
