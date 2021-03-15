<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\City;
use App\Country;
use App\CustomField;
use App\Http\Controllers\Controller;
use App\ImportCsvData;
use App\ImportItemData;
use App\Item;
use App\ItemFeature;
use App\Role;
use App\State;
use App\User;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use function GuzzleHttp\Psr7\str;

class ImporterController extends Controller
{
    public function showUpload(Request $request)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('importer_csv.seo.upload', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        return response()->view('backend.admin.importer.csv.upload');
    }

    public function processUpload(Request $request)
    {
        $request->validate([
            'import_csv_data_skip_first_row' => 'nullable|numeric|in:0,1',
            'import_csv_data_for_model' => 'required|numeric|in:1,2,3',
            'import_csv_data_file' => 'required|file|mimes:csv,txt|max:10280',
        ]);

        // prepare for file name
        $import_csv_data_skip_first_row = empty($request->import_csv_data_skip_first_row) ? ImportCsvData::IMPORT_CSV_SKIP_FIRST_ROW_NO : ImportCsvData::IMPORT_CSV_SKIP_FIRST_ROW_YES;
        $import_csv_data_filename = uniqid() . '-' . $request->file('import_csv_data_file')->getClientOriginalName();
        $import_csv_data_for_model = $request->import_csv_data_for_model;
        $import_csv_data_sample = array();

        // save the csv file
        $request->file('import_csv_data_file')->storeAs('importer', $import_csv_data_filename);

        // Read a CSV file
        $handle = fopen(storage_path('app/importer/') . $import_csv_data_filename, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;

        // Iterate over every line of the file
        while ((($raw_string = fgets($handle)) !== false)) {

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string);

            if(!empty($row[0]))
            {
                // Increase the current line
                $lineNumber++;

                if($lineNumber == 1 && $import_csv_data_skip_first_row == ImportCsvData::IMPORT_CSV_SKIP_FIRST_ROW_YES)
                {
                    continue;
                }

                if(count($import_csv_data_sample) < 3)
                {
                    // into an array: ['1', 'a', 'b', 'c']
                    // And do what you need to do with every line
                    //var_dump($row);
                    $import_csv_data_sample[] = $row;
                }
            }
        }
        fclose($handle);

        if($lineNumber == 0)
        {
            if (Storage::disk('local')->exists('importer/' . $import_csv_data_filename)) {
                Storage::disk('local')->delete('importer/' . $import_csv_data_filename);
            }

            \Session::flash('flash_message', __('importer_csv.alert.upload-empty-file'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.importer.csv.upload.show');
        }

        $lineNumber = $import_csv_data_skip_first_row == ImportCsvData::IMPORT_CSV_SKIP_FIRST_ROW_YES ? $lineNumber-1 : $lineNumber;

        $new_import_csv_data = New ImportCsvData(array(
            'import_csv_data_filename' => $import_csv_data_filename,
            'import_csv_data_sample' => json_encode($import_csv_data_sample),
            'import_csv_data_skip_first_row' => $import_csv_data_skip_first_row,
            'import_csv_data_total_rows' => $lineNumber,
            'import_csv_data_parse_status' => ImportCsvData::IMPORT_CSV_STATUS_NOT_PARSED,
            'import_csv_data_for_model' => $import_csv_data_for_model,
        ));
        $new_import_csv_data->save();

        \Session::flash('flash_message', __('importer_csv.alert.upload-success'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.importer.csv.upload.data.edit',
            ['import_csv_data' => $new_import_csv_data]);
    }

    public function indexCsvData(Request $request)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('importer_csv.seo.csv-data-index', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $all_import_csv_data_query = ImportCsvData::orderBy('updated_at', 'DESC');
        $all_import_csv_data_count = $all_import_csv_data_query->count();
        $all_import_csv_data = $all_import_csv_data_query->paginate(10);


        return response()->view('backend.admin.importer.csv.data.index',
            compact('all_import_csv_data_count', 'all_import_csv_data'));
    }

    public function editCsvData(Request $request, ImportCsvData $import_csv_data)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('importer_csv.seo.csv-data-edit', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        if($import_csv_data->import_csv_data_parse_status == ImportCsvData::IMPORT_CSV_STATUS_ALL_PARSED)
        {
            \Session::flash('flash_message', __('importer_csv.alert.fully-parsed'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.importer.csv.upload.data.index');
        }
        else
        {
            $import_csv_data_sample = json_decode($import_csv_data->import_csv_data_sample, true);
            $import_csv_data_sample_header = $import_csv_data_sample[0];

            return response()->view('backend.admin.importer.csv.data.edit',
                compact('import_csv_data', 'import_csv_data_sample', 'import_csv_data_sample_header'));
        }
    }

    public function ajaxParseCsvData(Request $request, ImportCsvData $import_csv_data)
    {
        if($import_csv_data->import_csv_data_parse_status == ImportCsvData::IMPORT_CSV_STATUS_ALL_PARSED)
        {
            return response()->json(['error' => __('importer_csv.alert.fully-parsed')]);
        }

        $parsed_row_count = $import_csv_data->import_csv_data_parsed_rows;
        $start_row_at = $parsed_row_count + 1;

        // get the csv data fields of selector
        $csv_data_columns = $request->csv_data_columns;

        // get mark up text
        $import_item_data_markup = $request->import_item_data_markup;

        // Read a CSV file
        $handle = fopen(storage_path('app/importer/') . $import_csv_data->import_csv_data_filename, "r");

        // Optionally, you can keep the number of the line where
        // the loop its currently iterating over
        $lineNumber = 0;

        // Iterate over every line of the file
        while ((($raw_string = fgets($handle)) !== false)) {

            // Parse the raw csv string: "1, a, b, c"
            $row = str_getcsv($raw_string);

            if(!empty($row[0]))
            {
                // Increase the current line
                $lineNumber++;

                if($lineNumber == 1 && $import_csv_data->import_csv_data_skip_first_row == ImportCsvData::IMPORT_CSV_SKIP_FIRST_ROW_YES)
                {
                    continue;
                }

                if($lineNumber >= $start_row_at)
                {
                    $import_item_data_columns = ImportItemData::DATA_COLUMNS;
                    $new_import_item_data = new ImportItemData();

                    foreach($row as $key => $row_value)
                    {
                        $selected_column = $csv_data_columns[$key];

                        if($selected_column != ImportItemData::DATA_COLUMNS_DO_NOT_PARSE)
                        {
                            $import_item_data_column = $import_item_data_columns[$selected_column];
                            $new_import_item_data->$import_item_data_column = $row_value;
                        }
                    }

                    $new_import_item_data->import_item_data_markup = $import_item_data_markup;
                    $new_import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_NOT_PROCESSED;
                    $new_import_item_data->import_item_data_source = ImportItemData::SOURCE_CSV;
                    $new_import_item_data->save();

                    // update the parsed row count of $import_csv_data
                    $parsed_row_count++;
                    $import_csv_data->import_csv_data_parsed_rows = $parsed_row_count;
                    if($parsed_row_count >= $import_csv_data->import_csv_data_total_rows)
                    {
                        $import_csv_data->import_csv_data_parse_status = ImportCsvData::IMPORT_CSV_STATUS_ALL_PARSED;
                    }
                    $import_csv_data->save();
                }
            }
        }
        fclose($handle);

        return response()->json(['success' => __('importer_csv.alert.parsed-success')]);
    }

    public function ajaxParseProgressCsvData(Request $request, ImportCsvData $import_csv_data)
    {
        $message = __('importer_csv.parsed-percentage',
            ['parsed_count' => $import_csv_data->import_csv_data_parsed_rows, 'total_count' => $import_csv_data->import_csv_data_total_rows]);

        $end = 0;
        if($import_csv_data->import_csv_data_parse_status == ImportCsvData::IMPORT_CSV_STATUS_ALL_PARSED)
        {
            $end = 1;
        }

        $progress_percent = strval(intval($import_csv_data->import_csv_data_parsed_rows/$import_csv_data->import_csv_data_total_rows * 100));

        return response()->json(['progress' => $message, 'end' => $end, 'progress_percent' => $progress_percent]);
    }

    public function destroyCsvData(Request $request, ImportCsvData $import_csv_data)
    {
        if (Storage::disk('local')->exists('importer/' . $import_csv_data->import_csv_data_filename)) {
            Storage::disk('local')->delete('importer/' . $import_csv_data->import_csv_data_filename);
        }

        $import_csv_data->delete();

        \Session::flash('flash_message', __('importer_csv.alert.csv-file-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.importer.csv.upload.data.index');
    }


    public function indexItemData(Request $request)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('importer_csv.seo.item-index', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $import_item_data_process_status = $request->import_item_data_process_status;
        $selected_import_item_data_markup = $request->selected_import_item_data_markup;
        $order_by = $request->order_by;
        $count_per_page = $request->count_per_page;

        if(empty($import_item_data_process_status))
        {
            $import_item_data_process_status = array(ImportItemData::PROCESS_STATUS_NOT_PROCESSED, ImportItemData::PROCESS_STATUS_PROCESSED_ERROR);
        }
        if(empty($order_by))
        {
            $order_by = ImportItemData::ORDER_BY_ITEM_NEWEST_PARSED;
        }
        if(empty($count_per_page))
        {
            $count_per_page = ImportItemData::COUNT_PER_PAGE_10;
        }

        $all_import_item_data_query = ImportItemData::query();

        // where import_item_data_process_status
        $all_import_item_data_query->whereIn('import_item_data_process_status', $import_item_data_process_status);

        // markup
        if(!empty($selected_import_item_data_markup))
        {
            $all_import_item_data_query->where('import_item_data_markup', $selected_import_item_data_markup);
        }

        // orderBy
        if($order_by == ImportItemData::ORDER_BY_ITEM_NEWEST_PROCESSED)
        {
            $all_import_item_data_query->orderBy('updated_at', 'DESC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_OLDEST_PROCESSED)
        {
            $all_import_item_data_query->orderBy('updated_at', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_NEWEST_PARSED)
        {
            $all_import_item_data_query->orderBy('created_at', 'DESC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_OLDEST_PARSED)
        {
            $all_import_item_data_query->orderBy('created_at', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_TITLE_A_Z)
        {
            $all_import_item_data_query->orderBy('import_item_data_item_title', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_TITLE_Z_A)
        {
            $all_import_item_data_query->orderBy('import_item_data_item_title', 'DESC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_CITY_A_Z)
        {
            $all_import_item_data_query->orderBy('import_item_data_city', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_CITY_Z_A)
        {
            $all_import_item_data_query->orderBy('import_item_data_city', 'DESC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_STATE_A_Z)
        {
            $all_import_item_data_query->orderBy('import_item_data_state', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_STATE_Z_A)
        {
            $all_import_item_data_query->orderBy('import_item_data_state', 'DESC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_COUNTRY_A_Z)
        {
            $all_import_item_data_query->orderBy('import_item_data_country', 'ASC');
        }
        elseif($order_by == ImportItemData::ORDER_BY_ITEM_COUNTRY_Z_A)
        {
            $all_import_item_data_query->orderBy('import_item_data_country', 'DESC');
        }

        $all_import_item_data_count = $all_import_item_data_query->count();
        $all_import_item_data = $all_import_item_data_query->paginate($count_per_page);

        /**
         * Start initial categories
         */
        $all_categories = new Category();
        $all_categories = $all_categories->getPrintableCategoriesNoDash();
        /**
         * End initial categories
         */

        /**
         * Start initial users
         */
        $all_users = User::where('email_verified_at', '!=', null)
            ->where('role_id', Role::USER_ROLE_ID)
            ->where('user_suspended', User::USER_NOT_SUSPENDED)
            ->orderBy('name')
            ->get();

        $admin_user = Auth::user();
        /**
         * End initial users
         */

        /**
         * Start initial markup
         */
        $all_import_item_data_markup = ImportItemData::select('import_item_data_markup')
            ->orderBy('import_item_data_markup')->groupBy('import_item_data_markup')->get();
        /**
         * End initial markup
         */


        return response()->view('backend.admin.importer.item.index',
            compact('all_import_item_data_count', 'all_import_item_data', 'import_item_data_process_status',
                'order_by', 'all_categories', 'all_users', 'admin_user', 'count_per_page', 'all_import_item_data_markup',
                'selected_import_item_data_markup'));
    }

    public function editItemData(Request $request, ImportItemData $import_item_data)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('importer_csv.seo.item-edit', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        /**
         * Start initial categories
         */
        $all_categories = new Category();
        $all_categories = $all_categories->getPrintableCategoriesNoDash();
        /**
         * End initial categories
         */

        /**
         * Start initial users
         */
        $all_users = User::where('email_verified_at', '!=', null)
            ->where('role_id', Role::USER_ROLE_ID)
            ->where('user_suspended', User::USER_NOT_SUSPENDED)
            ->orderBy('name')
            ->get();

        $admin_user = Auth::user();
        /**
         * End initial users
         */

        $imported_item = null;
        if($import_item_data->import_item_data_process_status == ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS)
        {
            // if imported success, we will verify the item_id and get it.
            $item_exist = Item::find($import_item_data->import_item_data_item_id);

            if($item_exist)
            {
                $imported_item = $item_exist;
            }
        }

        return response()->view('backend.admin.importer.item.edit',
            compact('import_item_data', 'all_categories', 'all_users', 'admin_user', 'imported_item'));
    }

    public function updateItemData(Request $request, ImportItemData $import_item_data)
    {
        if($import_item_data->import_item_data_process_status == ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS)
        {
            \Session::flash('flash_message', __('importer_csv.alert.import-item-cannot-edit-success-processed'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.importer.item.data.edit',
                ['import_item_data' => $import_item_data]);
        }

        $request->validate([
            'import_item_data_item_title' => 'nullable|max:255',
            'import_item_data_item_slug' => 'nullable|max:255',
            'import_item_data_item_address' => 'nullable|max:255',
            'import_item_data_city' => 'nullable|max:255',
            'import_item_data_state' => 'nullable|max:255',
            'import_item_data_country' => 'nullable|max:255',
            'import_item_data_item_lat' => 'nullable|max:255',
            'import_item_data_item_lng' => 'nullable|max:255',
            'import_item_data_item_postal_code' => 'nullable|max:255',
            'import_item_data_item_description' => 'nullable|max:65535',
            'import_item_data_item_phone' => 'nullable|max:255',
            'import_item_data_item_website' => 'nullable|url|max:255',
            'import_item_data_item_social_facebook' => 'nullable|url|max:255',
            'import_item_data_item_social_twitter' => 'nullable|url|max:255',
            'import_item_data_item_social_linkedin' => 'nullable|url|max:255',
            'import_item_data_item_youtube_id' => 'nullable|max:255',
        ]);

        $import_item_data->import_item_data_item_title = $request->import_item_data_item_title;
        $import_item_data->import_item_data_item_slug = $request->import_item_data_item_slug;
        $import_item_data->import_item_data_item_address = $request->import_item_data_item_address;
        $import_item_data->import_item_data_city = $request->import_item_data_city;
        $import_item_data->import_item_data_state = $request->import_item_data_state;
        $import_item_data->import_item_data_country = $request->import_item_data_country;
        $import_item_data->import_item_data_item_lat = $request->import_item_data_item_lat;
        $import_item_data->import_item_data_item_lng = $request->import_item_data_item_lng;
        $import_item_data->import_item_data_item_postal_code = $request->import_item_data_item_postal_code;
        $import_item_data->import_item_data_item_description = $request->import_item_data_item_description;
        $import_item_data->import_item_data_item_phone = $request->import_item_data_item_phone;
        $import_item_data->import_item_data_item_website = $request->import_item_data_item_website;
        $import_item_data->import_item_data_item_social_facebook = $request->import_item_data_item_social_facebook;
        $import_item_data->import_item_data_item_social_twitter = $request->import_item_data_item_social_twitter;
        $import_item_data->import_item_data_item_social_linkedin = $request->import_item_data_item_social_linkedin;
        $import_item_data->import_item_data_item_youtube_id = $request->import_item_data_item_youtube_id;
        $import_item_data->save();

        \Session::flash('flash_message', __('importer_csv.alert.import-item-updated'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.importer.item.data.edit',
            ['import_item_data' => $import_item_data]);
    }

    public function destroyItemData(Request $request, ImportItemData $import_item_data)
    {
        $import_item_data->delete();

        \Session::flash('flash_message', __('importer_csv.alert.import-item-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.importer.item.data.index');
    }

    public function ajaxDestroyItemData(Request $request, ImportItemData $import_item_data)
    {
        $import_item_data->delete();

        return response()->json([
            'message' => __('importer_csv.alert.import-item-deleted'),
        ]);
    }

    public function importItemData(Request $request, ImportItemData $import_item_data)
    {
        if($import_item_data->import_item_data_process_status == ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS)
        {
            \Session::flash('flash_message', __('importer_csv.import-errors.import-item-cannot-process-success-processed'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.importer.item.data.edit',
                ['import_item_data' => $import_item_data]);
        }

        $request->validate([
            'category' => 'required',
            'category.*' => 'numeric',
            'user_id' => 'required|numeric',
            'item_status' => 'required|numeric|in:1,2,3',
            'item_featured' => 'required|numeric|in:0,1',
        ]);

        $user_id = $request->user_id;
        $select_categories = $request->category;
        $item_status = $request->item_status;
        $item_featured = $request->item_featured;

        $process_result = $this->processItemImport(
                                    $import_item_data,
                                    $user_id,
                                    $item_status,
                                    $item_featured,
                                    $select_categories
                                );

        if($process_result)
        {
            \Session::flash('flash_message', __('importer_csv.alert.import-process-success'));
            \Session::flash('flash_type', 'success');
        }
        else
        {
            \Session::flash('flash_message', __('importer_csv.alert.import-process-error'));
            \Session::flash('flash_type', 'danger');
        }

        return redirect()->route('admin.importer.item.data.edit',
            ['import_item_data' => $import_item_data]);
    }

    public function ajaxImportItemData(Request $request, ImportItemData $import_item_data)
    {
        $user_id = $request->user_id;
        $select_categories = $request->category;
        $item_status = $request->item_status;
        $item_featured = $request->item_featured;

        $process_result = $this->processItemImport(
            $import_item_data,
            $user_id,
            $item_status,
            $item_featured,
            $select_categories
        );

        if($process_result)
        {
            return response()->json([
                'status' => ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS,
                'message' => __('importer_csv.alert.import-process-success'),
            ]);
        }
        else
        {
            return response()->json([
                'status' => ImportItemData::PROCESS_STATUS_PROCESSED_ERROR,
                'message' => __('importer_csv.alert.import-process-error'),
            ]);
        }
    }

    public function ajaxImportAllItemData(Request $request)
    {
        $user_id = $request->user_id;
        $select_categories = $request->category;
        $item_status = $request->item_status;
        $item_featured = $request->item_featured;

        $all_import_item_data = ImportItemData::where('import_item_data_process_status', '!=', ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS)
            ->orderBy('created_at')->get();

        foreach($all_import_item_data as $all_import_item_data_key => $import_item_data)
        {
            $process_result = $this->processItemImport(
                $import_item_data,
                $user_id,
                $item_status,
                $item_featured,
                $select_categories
            );
        }

        return response()->json([
            'message' => __('importer_csv.alert.import-all-process-completed'),
        ]);
    }


    /**
     * process import of an record in import_item_data table to listing
     *
     * @param ImportItemData $import_item_data
     * @param int $user_id
     * @param int $item_status
     * @param int $item_featured
     * @param array $select_categories
     * @return bool
     */
    private function processItemImport(ImportItemData $import_item_data,
                                       int $user_id,
                                       int $item_status,
                                       int $item_featured,
                                       array $select_categories)
    {
        // validate import_item_data_process_status
        if($import_item_data->import_item_data_process_status == ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.import-item-cannot-process-success-processed');
            $import_item_data->save();
            return false;
        }

        // validate item_title
        $item_title = $import_item_data->import_item_data_item_title;
        if(empty($item_title))
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.item-title-required');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate item_description
        $item_description = $import_item_data->import_item_data_item_description;
        if(empty($item_description))
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.item-description-required');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate item_postal_code
        $item_postal_code = $import_item_data->import_item_data_item_postal_code;
        if(empty($item_postal_code))
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.item-postal-code-required');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate user
        $item_user = User::find($user_id);
        if(!$item_user)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.user-not-exist');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate item_status
        if($item_status != Item::ITEM_SUBMITTED && $item_status != Item::ITEM_PUBLISHED && $item_status != Item::ITEM_SUSPENDED)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.item-status-not-exist');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate item_featured
        if($item_featured != Item::ITEM_FEATURED && $item_featured != Item::ITEM_NOT_FEATURED)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.item-featured-not-exist');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate categories array, remove out any non-exist categories
        $select_categories_array = array();
        $item_categories_string = "";
        foreach($select_categories as $select_categories_key => $select_category)
        {
            $select_category = Category::find($select_category);
            if($select_category)
            {
                $select_categories_array[] = $select_category->id;
                $item_categories_string = $item_categories_string . " " . $select_category->category_name;
            }
        }
        if(count($select_categories_array) == 0)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.categories-required');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        // validate country
        $country_id = null;
        $country_origin = $import_item_data->import_item_data_country;
        $country_find = true;

        /**
         * Start guess country by country name
         */
        $country_exist = Country::where('country_name', $country_origin)->get();
        if($country_exist->count() == 0)
        {
            $country_lowercase = strtolower($country_origin);
            $country_exist = Country::where('country_name', $country_lowercase)->get();

            if($country_exist->count() == 0)
            {
                $country_ucfirst = ucfirst($country_lowercase);
                $country_exist = Country::where('country_name', $country_ucfirst)->get();

                if($country_exist->count() == 0)
                {
                    $country_ucwords = ucwords($country_lowercase);
                    $country_exist = Country::where('country_name', $country_ucwords)->get();

                    if($country_exist->count() == 0)
                    {
                        $country_uppercase = strtoupper($country_lowercase);
                        $country_exist = Country::where('country_name', $country_uppercase)->get();

                        if($country_exist->count() == 0)
                        {
                            $country_find = false;
                        }
                    }
                }
            }
        }
        /**
         * End guess country by country name
         */

        /**
         * Start guess country by country abbr
         */
        if(!$country_find)
        {
            $country_find = true;

            $country_exist = Country::where('country_abbr', $country_origin)->get();
            if($country_exist->count() == 0)
            {
                $country_lowercase = strtolower($country_origin);
                $country_exist = Country::where('country_abbr', $country_lowercase)->get();

                if($country_exist->count() == 0)
                {
                    $country_ucfirst = ucfirst($country_lowercase);
                    $country_exist = Country::where('country_abbr', $country_ucfirst)->get();

                    if($country_exist->count() == 0)
                    {
                        $country_ucwords = ucwords($country_lowercase);
                        $country_exist = Country::where('country_abbr', $country_ucwords)->get();

                        if($country_exist->count() == 0)
                        {
                            $country_uppercase = strtoupper($country_lowercase);
                            $country_exist = Country::where('country_abbr', $country_uppercase)->get();

                            if($country_exist->count() == 0)
                            {
                                $country_find = false;
                            }
                        }
                    }
                }
            }

        }
        /**
         * End guess country by country abbr
         */

        if(!$country_find)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.country-not-exist');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        $country_exist = $country_exist->first();
        $country_id = $country_exist->id;

        // validate state
        $state_id = null;
        $state_origin = $import_item_data->import_item_data_state;
        $state_find = true;

        /**
         * Start guess state by state name
         */
        $state_exist = State::where('state_name', $state_origin)->where('country_id', $country_id)->get();
        if($state_exist->count() == 0)
        {
            $state_lowercase = strtolower($state_origin);
            $state_exist = State::where('state_name', $state_lowercase)->where('country_id', $country_id)->get();

            if($state_exist->count() == 0)
            {
                $state_ucfirst = ucfirst($state_lowercase);
                $state_exist = State::where('state_name', $state_ucfirst)->where('country_id', $country_id)->get();

                if($state_exist->count() == 0)
                {
                    $state_ucwords = ucwords($state_lowercase);
                    $state_exist = State::where('state_name', $state_ucwords)->where('country_id', $country_id)->get();

                    if($state_exist->count() == 0)
                    {
                        $state_uppercase = strtoupper($state_lowercase);
                        $state_exist = State::where('state_name', $state_uppercase)->where('country_id', $country_id)->get();

                        if($state_exist->count() == 0)
                        {
                            $state_find = false;
                        }
                    }
                }
            }
        }
        /**
         * End guess state by state name
         */

        /**
         * Start guess state by state abbr
         */
        if(!$state_find)
        {
            $state_find = true;

            $state_exist = State::where('state_abbr', $state_origin)->where('country_id', $country_id)->get();
            if($state_exist->count() == 0)
            {
                $state_lowercase = strtolower($state_origin);
                $state_exist = State::where('state_abbr', $state_lowercase)->where('country_id', $country_id)->get();

                if($state_exist->count() == 0)
                {
                    $state_ucfirst = ucfirst($state_lowercase);
                    $state_exist = State::where('state_abbr', $state_ucfirst)->where('country_id', $country_id)->get();

                    if($state_exist->count() == 0)
                    {
                        $state_ucwords = ucwords($state_lowercase);
                        $state_exist = State::where('state_abbr', $state_ucwords)->where('country_id', $country_id)->get();

                        if($state_exist->count() == 0)
                        {
                            $state_uppercase = strtoupper($state_lowercase);
                            $state_exist = State::where('state_abbr', $state_uppercase)->where('country_id', $country_id)->get();

                            if($state_exist->count() == 0)
                            {
                                $state_find = false;
                            }
                        }
                    }
                }
            }
        }
        /**
         * End guess state by state abbr
         */

        if(!$state_find)
        {
            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.state-not-exist');
            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
            $import_item_data->save();
            return false;
        }

        $state_exist = $state_exist->first();
        $state_id = $state_exist->id;

        // validate city
        $city_id = null;
        $city_origin = $import_item_data->import_item_data_city;
        $city_exist = City::where('city_name', $city_origin)->where('state_id', $state_id)->get();
        if($city_exist->count() == 0)
        {
            $city_lowercase = strtolower($city_origin);
            $city_exist = City::where('city_name', $city_lowercase)->where('state_id', $state_id)->get();

            if($city_exist->count() == 0)
            {
                $city_ucfirst = ucfirst($city_lowercase);
                $city_exist = City::where('city_name', $city_ucfirst)->where('state_id', $state_id)->get();

                if($city_exist->count() == 0)
                {
                    $city_ucwords = ucwords($city_lowercase);
                    $city_exist = City::where('city_name', $city_ucwords)->where('state_id', $state_id)->get();

                    if($city_exist->count() == 0)
                    {
                        $city_uppercase = strtoupper($state_lowercase);
                        $city_exist = City::where('city_name', $city_uppercase)->where('state_id', $state_id)->get();

                        if($city_exist->count() == 0)
                        {
                            $import_item_data->import_item_data_process_error_log = __('importer_csv.import-errors.city-not-exist');
                            $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_ERROR;
                            $import_item_data->save();
                            return false;
                        }
                    }
                }
            }
        }
        $city_exist = $city_exist->first();
        $city_id = $city_exist->id;

        // prepare item_slug
        $item_slug = str_slug($item_title);

        $item_slug_exist = Item::where('item_slug', $item_slug)->get();

        if($item_slug_exist->count() > 0)
        {
            $item_slug = $item_slug . '-' . uniqid();
        }

        // prepare item_address_hide
        $item_address = $import_item_data->import_item_data_item_address;
        $item_address_hide = Item::ITEM_ADDR_NOT_HIDE;
        if(empty($item_address))
        {
            $item_address_hide = Item::ITEM_ADDR_HIDE;
        }

        // prepare item_lat & item_lng
        $item_lat = $import_item_data->import_item_data_item_lat;
        $item_lng = $import_item_data->import_item_data_item_lng;

        if(empty($item_lat))
        {
            $item_lat = $city_exist->city_lat;
        }

        if(empty($item_lng))
        {
            $item_lng = $city_exist->city_lng;
        }

        // prepare website, facebook, twitter, linkedin
        $item_website = empty($import_item_data->import_item_data_item_website) ? null : strtolower($import_item_data->import_item_data_item_website);
        $item_social_facebook = empty($import_item_data->import_item_data_item_social_facebook) ? null : strtolower($import_item_data->import_item_data_item_social_facebook);
        $item_social_twitter = empty($import_item_data->import_item_data_item_social_twitter) ? null : strtolower($import_item_data->import_item_data_item_social_twitter);
        $item_social_linkedin = empty($import_item_data->import_item_data_item_social_linkedin) ? null : strtolower($import_item_data->import_item_data_item_social_linkedin);

        if(!empty($item_website))
        {
            if(!starts_with($item_website, "http://") && !starts_with($item_website, "https://"))
            {
                $item_website = "https://" . $import_item_data->import_item_data_item_website;
            }
            else
            {
                $item_website = $import_item_data->import_item_data_item_website;
            }
        }
        if(!empty($item_social_facebook))
        {
            if(!starts_with($item_social_facebook, "http://") && !starts_with($item_social_facebook, "https://"))
            {
                $item_social_facebook = "https://" . $import_item_data->import_item_data_item_social_facebook;
            }
            else
            {
                $item_social_facebook = $import_item_data->import_item_data_item_social_facebook;
            }
        }
        if(!empty($item_social_twitter))
        {
            if(!starts_with($item_social_twitter, "http://") && !starts_with($item_social_twitter, "https://"))
            {
                $item_social_twitter = "https://" . $import_item_data->import_item_data_item_social_twitter;
            }
            else
            {
                $item_social_twitter = $import_item_data->import_item_data_item_social_twitter;
            }
        }
        if(!empty($item_social_linkedin))
        {
            if(!starts_with($item_social_linkedin, "http://") && !starts_with($item_social_linkedin, "https://"))
            {
                $item_social_linkedin = "https://" . $import_item_data->import_item_data_item_social_linkedin;
            }
            else
            {
                $item_social_linkedin = $import_item_data->import_item_data_item_social_linkedin;
            }
        }

        // prepare other data fields
        $item_phone = empty($import_item_data->import_item_data_item_phone) ? null : $import_item_data->import_item_data_item_phone;
        $item_youtube_id = empty($import_item_data->import_item_data_item_youtube_id) ? null : $import_item_data->import_item_data_item_youtube_id;

        // start create a new item model and save the new record to database
        $new_item = new Item(array(
            'user_id' => $user_id,
            'item_status' => $item_status,
            'item_featured' => $item_featured,
            'item_featured_by_admin' => $item_featured == Item::ITEM_FEATURED ? Item::ITEM_FEATURED_BY_ADMIN : Item::ITEM_NOT_FEATURED_BY_ADMIN,
            'item_title' => $item_title,
            'item_slug' => $item_slug,
            'item_description' => $item_description,
            'item_address' => $item_address,
            'item_address_hide' => $item_address_hide,
            'city_id' => $city_id,
            'state_id' => $state_id,
            'country_id' => $country_id,
            'item_postal_code' => $item_postal_code,
            'item_lat' => $item_lat,
            'item_lng' => $item_lng,
            'item_youtube_id' => $item_youtube_id,
            'item_phone' => $item_phone,
            'item_website' => $item_website,
            'item_social_facebook' => $item_social_facebook,
            'item_social_twitter' => $item_social_twitter,
            'item_social_linkedin' => $item_social_linkedin,
            'item_categories_string' => $item_categories_string,
        ));
        $new_item->save();

        // sync the categories for the new item record
        $new_item->allCategories()->sync($select_categories_array);

        // start create the item features for this new item
        $category_custom_fields = new CustomField();
        $category_custom_fields = $category_custom_fields->getDistinctCustomFieldsByCategories($select_categories_array);

        if($category_custom_fields->count() > 0)
        {
            foreach($category_custom_fields as $category_custom_fields_key => $custom_field)
            {

                $new_item_feature = new ItemFeature(array(
                    'custom_field_id' => $custom_field->id,
                    'item_feature_value' => "",
                ));

                $created_item_feature = $new_item->features()->save($new_item_feature);
            }
        }

        // finish the listing import
        $import_item_data->import_item_data_process_status = ImportItemData::PROCESS_STATUS_PROCESSED_SUCCESS;
        $import_item_data->import_item_data_item_id = $new_item->id;
        $import_item_data->save();
        return true;
    }
}
