@extends('backend.admin.layouts.app')

@section('styles')
    <!-- Custom styles for this page -->
    <link href="{{ asset('backend/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            <h1 class="h3 mb-2 text-gray-800">{{ __('backend.custom-field.custom-field') }}</h1>
            <p class="mb-4">{{ __('backend.custom-field.custom-field-desc') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('admin.custom-fields.create') }}" class="btn btn-info btn-icon-split">
                <span class="icon text-white-50">
                  <i class="fas fa-plus"></i>
                </span>
                <span class="text">{{ __('backend.custom-field.add-custom-field') }}</span>
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row bg-white pt-4 pl-3 pr-3 pb-4">
        <div class="col-12">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="row mb-2">
                        <div class="col-12"><span class="text-lg">{{ __('backend.shared.data-filter') }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12">
                            <form class="form-inline" action="{{ route('admin.custom-fields.index') }}" method="GET">
                                <div class="form-group mr-2">
                                    <select class="custom-select" name="category">
                                        <option value="0">{{ __('backend.custom-field.select-category') }}</option>
                                        @foreach($all_categories as $key => $category)
                                            <option value="{{ $category['category_id'] }}" {{ $category['category_id'] == $category_id ? 'selected' : '' }}>{{ $category['category_name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">{{ __('backend.shared.update') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>{{ __('backend.custom-field.id') }}</th>
                                <th>{{ __('backend.custom-field.name') }}</th>
                                <th>{{ __('backend.custom-field.type') }}</th>
                                <th>{{ __('backend.custom-field.seed-value') }}</th>
                                <th>{{ __('backend.custom-field.order') }}</th>
                                <th>{{ __('backend.category.category') }}</th>
                                <th>{{ __('backend.shared.action') }}</th>
                            </tr>
                            </thead>
                            <tfoot>
                            <tr>
                                <th>{{ __('backend.custom-field.id') }}</th>
                                <th>{{ __('backend.custom-field.name') }}</th>
                                <th>{{ __('backend.custom-field.type') }}</th>
                                <th>{{ __('backend.custom-field.seed-value') }}</th>
                                <th>{{ __('backend.custom-field.order') }}</th>
                                <th>{{ __('backend.category.category') }}</th>
                                <th>{{ __('backend.shared.action') }}</th>
                            </tr>
                            </tfoot>
                            <tbody>
                            @foreach($all_custom_fields as $key => $custom_field)
                                <tr>
                                    <td>{{ $custom_field->id }}</td>
                                    <td>{{ $custom_field->custom_field_name }}</td>
                                    <td>
                                        @if($custom_field->custom_field_type == \App\CustomField::TYPE_TEXT)
                                            {{ __('backend.custom-field.text') }}
                                        @elseif($custom_field->custom_field_type == \App\CustomField::TYPE_SELECT)
                                            {{ __('backend.custom-field.select') }}
                                        @elseif($custom_field->custom_field_type == \App\CustomField::TYPE_MULTI_SELECT)
                                            {{ __('backend.custom-field.multi-select') }}
                                        @elseif($custom_field->custom_field_type == \App\CustomField::TYPE_LINK)
                                            {{ __('backend.custom-field.link') }}
                                        @endif
                                    </td>
                                    <td>{{ $custom_field->custom_field_seed_value }}</td>
                                    <td>{{ $custom_field->custom_field_order }}</td>
                                    <td>
                                        @foreach($custom_field->allCategories()->get() as $key => $category)
                                            @if($custom_field->allCategories()->get()->count() == $key + 1)
                                                {{ $category->category_name }}
                                            @else
                                                {{ $category->category_name . ", " }}
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.custom-fields.edit', $custom_field->id) }}" class="btn btn-primary btn-circle">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Page level plugins -->
    <script src="{{ asset('backend/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('backend/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
@endsection
