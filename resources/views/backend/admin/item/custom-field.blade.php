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
        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            class="text-black">{{ $customField->custom_field_name }}</label>
        <textarea
            class="form-control @error(str_slug($customField->custom_field_name . $customField->id)) is-invalid @enderror"
            id="{{ str_slug($customField->custom_field_name . $customField->id) }}" rows="5"
            name="{{ str_slug($customField->custom_field_name . $customField->id) }}">{{ old(str_slug($customField->custom_field_name . $customField->id)) }}</textarea>
        @error(str_slug($customField->custom_field_name . $customField->id))
        <span class="invalid-tooltip">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
        @endif
        @if($customField->custom_field_type == \App\CustomField::TYPE_SELECT)
        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            class="text-black">{{ $customField->custom_field_name }}</label>
        <select class="custom-select"
            name="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            id="{{ str_slug($customField->custom_field_name . $customField->id) }}">
            @foreach(explode(',', $customField->custom_field_seed_value) as $key =>
            $custom_field_value)
            <option value="{{ $custom_field_value }}"
                {{ old(str_slug($customField->custom_field_name . $customField->id)) == $custom_field_value ? 'selected' : '' }}>
                {{ $custom_field_value }}</option>
            @endforeach
        </select>
        @error(str_slug($customField->custom_field_name . $customField->id))
        <span class="invalid-tooltip">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
        @endif
        @if($customField->custom_field_type == \App\CustomField::TYPE_MULTI_SELECT)
        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            class="text-black">{{ $customField->custom_field_name }}</label>
        <select multiple class="custom-select"
            name="{{ str_slug($customField->custom_field_name . $customField->id) }}[]"
            id="{{ str_slug($customField->custom_field_name . $customField->id) }}">
            @foreach(explode(',', $customField->custom_field_seed_value) as $key =>
            $custom_field_value)
            <option value="{{ $custom_field_value }}"
                {{ old(str_slug($customField->custom_field_name . $customField->id)) == $custom_field_value ? 'selected' : '' }}>
                {{ $custom_field_value }}</option>
            @endforeach
        </select>
        @error($customField->custom_field_name . $customField->id)
        <span class="invalid-tooltip">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
        @endif
        @if($customField->custom_field_type == \App\CustomField::TYPE_LINK)
        <label for="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            class="text-black">{{ $customField->custom_field_name }}</label>
        <input id="{{ str_slug($customField->custom_field_name . $customField->id) }}" type="text"
            class="form-control @error(str_slug($customField->custom_field_name . $customField->id)) is-invalid @enderror"
            name="{{ str_slug($customField->custom_field_name . $customField->id) }}"
            value="{{ old(str_slug($customField->custom_field_name . $customField->id)) }}"
            aria-describedby="linkHelpBlock">
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