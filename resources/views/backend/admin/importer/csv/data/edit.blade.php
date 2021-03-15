@extends('backend.admin.layouts.app')

@section('styles')
@endsection

@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            <h1 class="h3 mb-2 text-gray-800">{{ __('importer_csv.import-csv-data-edit') }}</h1>
            <p class="mb-4">{{ __('importer_csv.import-csv-data-edit-desc') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('admin.importer.csv.upload.data.index') }}" class="btn btn-info btn-icon-split">
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
                <div class="col-12">
                    <h4>{{ $import_csv_data->import_csv_data_filename }}</h4>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12">
                    @if($import_csv_data->import_csv_data_for_model == \App\ImportCsvData::IMPORT_CSV_FOR_MODEL_LISTING)
                        <span class="bg-info pl-2 pr-2 pt-1 pb-1 text-white rounded">{{ __('importer_csv.csv-for-model-listing') }}</span>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    {{ __('importer_csv.parsed-percentage', ['parsed_count' => $import_csv_data->import_csv_data_parsed_rows, 'total_count' => $import_csv_data->import_csv_data_total_rows]) }}
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    @foreach($import_csv_data_sample_header as $import_csv_data_sample_header_key => $import_csv_data_sample_header_data)
                        <div class="row align-items-center border-left-info pt-1 pb-1 mb-2 {{ $import_csv_data_sample_header_key%2 == 0 ? 'bg-light' : '' }}">
                            <div class="col-12 col-md-2">
                                {{ __('importer_csv.column') . ' ' . strval($import_csv_data_sample_header_key+1) }}
                            </div>
                            <div class="col-12 col-md-7">
                                {{ $import_csv_data_sample_header_data }}
                            </div>
                            <div class="col-12 col-md-3">
                                <select class="custom-select select_csv_data_columns" name="csv_data_columns[]">
                                    @foreach(\App\ImportItemData::DATA_COLUMNS as $data_column_key => $data_column_value)
                                        <option {{ $import_csv_data_sample_header_key == $data_column_key ? 'selected' : '' }} value="{{ $data_column_key }}">
                                            @if($data_column_value == 'import_item_data_item_title')
                                                {{ __('importer_csv.column-item-title') }}
                                            @elseif($data_column_value == 'import_item_data_item_slug')
                                                {{ __('importer_csv.column-item-slug') }}
                                            @elseif($data_column_value == 'import_item_data_item_address')
                                                {{ __('importer_csv.column-item-address') }}
                                            @elseif($data_column_value == 'import_item_data_city')
                                                {{ __('importer_csv.column-item-city') }}
                                            @elseif($data_column_value == 'import_item_data_state')
                                                {{ __('importer_csv.column-item-state') }}
                                            @elseif($data_column_value == 'import_item_data_country')
                                                {{ __('importer_csv.column-item-country') }}
                                            @elseif($data_column_value == 'import_item_data_item_lat')
                                                {{ __('importer_csv.column-item-lat') }}
                                            @elseif($data_column_value == 'import_item_data_item_lng')
                                                {{ __('importer_csv.column-item-lng') }}
                                            @elseif($data_column_value == 'import_item_data_item_postal_code')
                                                {{ __('importer_csv.column-item-postal-code') }}
                                            @elseif($data_column_value == 'import_item_data_item_description')
                                                {{ __('importer_csv.column-item-description') }}
                                            @elseif($data_column_value == 'import_item_data_item_phone')
                                                {{ __('importer_csv.column-item-phone') }}
                                            @elseif($data_column_value == 'import_item_data_item_website')
                                                {{ __('importer_csv.column-item-website') }}
                                            @elseif($data_column_value == 'import_item_data_item_social_facebook')
                                                {{ __('importer_csv.column-item-facebook') }}
                                            @elseif($data_column_value == 'import_item_data_item_social_twitter')
                                                {{ __('importer_csv.column-item-twitter') }}
                                            @elseif($data_column_value == 'import_item_data_item_social_linkedin')
                                                {{ __('importer_csv.column-item-linkedin') }}
                                            @elseif($data_column_value == 'import_item_data_item_youtube_id')
                                                {{ __('importer_csv.column-item-youtube-id') }}
                                            @endif
                                        </option>
                                    @endforeach
                                    <option value="{{ \App\ImportItemData::DATA_COLUMNS_DO_NOT_PARSE }}">{{ __('importer_csv.import-listing-do-not-parse') }}</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <hr>

            <div class="row mt-3">
                <div class="col-12">
                    <form class="form-inline">
                        <div class="form-group mr-2">
                            <label for="import_item_data_markup">{{ __('importer_csv.import-listing-markup') }}</label>
                            <input type="text" name="import_item_data_markup" id="import_item_data_markup" class="form-control mx-sm-3" aria-describedby="import_item_data_markup" value="{{ $import_csv_data->import_csv_data_filename }}">
                            <small id="import_item_data_markup" class="text-muted">
                                {{ __('importer_csv.import-listing-markup-help') }}
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary text-white rounded" id="start_parse_button">
                        {{ __('importer_csv.start-parse') }}
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="progress">
                        <div id="div_parse_progress_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 text-center">

                    <div class="alert alert-info" role="alert">
                        <span id="span_parse_progress_box"></span>
                        <br>
                        <span class="text-danger" id="span_parse_error_box"></span>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            $('#start_parse_button').on('click', function(){

                /**
                 * Start ajax csv parse process
                 */
                $("#start_parse_button").attr("disabled", true);

                $('#span_parse_progress_box').text('{{ __('importer_csv.csv-parse-in-progress') }}');

                var markup = $("#import_item_data_markup").val();

                var csv_data_columns = [];

                $(".select_csv_data_columns").each(function( index ) {
                    csv_data_columns.push($(this).val());
                });

                var ajax_url = '{{ route('admin.importer.csv.upload.data.parse', ['import_csv_data' => $import_csv_data->id]) }}';

                var timeout_progress_continue = true;

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                jQuery.ajax({
                    url: ajax_url,
                    method: 'post',
                    data: {
                        'csv_data_columns' : csv_data_columns,
                        'import_item_data_markup' : markup,
                    },
                    success: function(result){

                        $('#span_parse_progress_box').text(result.success);
                    },
                    error: function(xhr){
                        console.log("an error occured: " + xhr.status + " " + xhr.statusText);

                        $('#span_parse_error_box').text("{{ __('importer_csv.import-csv-data-parse-error') }}");

                        timeout_progress_continue = false;
                    }
                });
                /**
                 * End ajax csv parse process
                 */

                /**
                 * Start ajax csv parse progress process
                 */
                var ajax_progress_url = '{{ route('admin.importer.csv.upload.data.parse.progress', ['import_csv_data' => $import_csv_data->id]) }}';

                (function worker() {
                    $.ajax({
                        url: ajax_progress_url,
                        method: 'post',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(result) {
                            console.log(result);

                            $('#div_parse_progress_bar').attr("aria-valuenow", result.progress_percent);
                            $('#div_parse_progress_bar').attr("style", "width: " + result.progress_percent + "%;");
                            $('#div_parse_progress_bar').text(result.progress);

                            if(result.end === 1)
                            {
                                timeout_progress_continue = false;
                            }
                        },
                        complete: function() {
                            if(timeout_progress_continue)
                            {
                                // Schedule the next request when the current one's complete
                                timeout_progress = setTimeout(worker, 200);
                            }
                        }
                    });
                })();
                /**
                 * End ajax csv parse progress process
                 */
            });



        });
    </script>
@endsection
