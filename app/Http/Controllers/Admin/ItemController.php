<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\City;
use App\Country;
use App\CustomField;
use App\Http\Controllers\Controller;
use App\Item;
use App\ItemFeature;
use App\ItemSection;
use App\ItemSectionCollection;
use App\Product;
use App\State;
use Artesaos\SEOTools\Facades\SEOMeta;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Session;

class ItemController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.item.items', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        /**
         * Start initial filter
         */
        $request_query_array = $request->query();

        $all_printable_categories = new Category();
        $all_printable_categories = $all_printable_categories->getPrintableCategoriesNoDash();

        // filter categories
        $filter_categories = $request->filter_categories;
        if (empty($filter_categories)) {
            $filter_categories = array();

            foreach ($all_printable_categories as $all_printable_categories_key => $printable_category) {
                $filter_categories[] = $printable_category['category_id'];
            }
        }

        // filter location
        $filter_country = empty($request->filter_country) ? null : $request->filter_country;
        $filter_state = empty($request->filter_state) ? null : $request->filter_state;
        $filter_city = empty($request->filter_city) ? null : $request->filter_city;

        $all_countries = Country::orderBy('country_name')->get();
        $all_states = collect([]);
        $all_cities = collect([]);
        if (!empty($filter_country)) {
            $country = Country::find($filter_country);
            $all_states = $country->states()->orderBy('state_name')->get();
        }
        if (!empty($filter_state)) {
            $state = State::find($filter_state);
            $all_cities = $state->cities()->orderBy('city_name')->get();
        }

        // filter item status & featured
        $filter_item_status = $request->filter_item_status;
        if (empty($filter_item_status)) {
            $filter_item_status = array(Item::ITEM_SUBMITTED, Item::ITEM_PUBLISHED, Item::ITEM_SUSPENDED);
        }
        $filter_item_featured = $request->filter_item_featured;
        if (empty($filter_item_featured)) {
            $filter_item_featured = array(Item::ITEM_FEATURED, Item::ITEM_NOT_FEATURED);
        }

        // filter sort by
        $filter_sort_by = empty($request->filter_sort_by) ? Item::ITEMS_SORT_BY_NEWEST_CREATED : $request->filter_sort_by;

        // filter rows per page
        $filter_count_per_page = empty($request->filter_count_per_page) ? Item::COUNT_PER_PAGE_10 : $request->filter_count_per_page;
        /**
         * End initial filter
         */

        /**
         * Start build query
         */
        $items_query = Item::query();

        $items_query->select('items.*');

        // categories
        $items_query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
            ->whereIn("ci.category_id", $filter_categories);

        // location
        if (!empty($filter_country)) {
            $items_query->where('items.country_id', $filter_country);
        }
        if (!empty($filter_state)) {
            $items_query->where('items.state_id', $filter_state);
        }
        if (!empty($filter_city)) {
            $items_query->where('items.city_id', $filter_city);
        }

        // item status
        $items_query->whereIn('items.item_status', $filter_item_status);

        // item featured
        $items_query->whereIn('items.item_featured', $filter_item_featured);

        // sort by
        if ($filter_sort_by == Item::ITEMS_SORT_BY_NEWEST_CREATED) {
            $items_query->orderBy('items.created_at', 'DESC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_OLDEST_CREATED) {
            $items_query->orderBy('items.created_at', 'ASC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_NEWEST_UPDATED) {
            $items_query->orderBy('items.updated_at', 'DESC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_OLDEST_UPDATED) {
            $items_query->orderBy('items.updated_at', 'ASC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_HIGHEST_RATING) {
            $items_query->orderBy('items.item_average_rating', 'DESC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_LOWEST_RATING) {
            $items_query->orderBy('items.item_average_rating', 'ASC');
        }
        /**
         * End build query
         */

        $items_query->distinct('items.id');

        /**
         * Start getting query result
         */
        $items_count = $items_query->count();
        $items = $items_query->paginate($filter_count_per_page);
        /**
         * End getting query result
         */

        return response()->view('backend.admin.item.index',
            compact('items', 'items_count', 'all_printable_categories', 'filter_categories',
                'filter_country', 'filter_state', 'filter_city', 'filter_item_status', 'filter_item_featured',
                'filter_sort_by', 'filter_count_per_page', 'all_countries', 'all_states', 'all_cities',
                'request_query_array'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {


      //  dd($request->all());
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.item.create-item', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $all_categories = new Category();
        $all_categories = $all_categories->getPrintableCategories();

        $category_ids = empty($request->category) ? array() : $request->category;

        if (!empty($category_ids) && !is_array($category_ids)) {
            return redirect()->back();
        }

        $all_customFields = collect();

        if (count($category_ids) > 0) {
            $all_customFields = new CustomField();
            $all_customFields = $all_customFields->getDistinctCustomFieldsByCategories($category_ids);
        }

        /**
         * Start initial country selector
         */
        $all_countries = Country::orderBy('country_name')->get();
        /**
         * End initial country selector
         */

        $setting_site_location_lat = $settings->setting_site_location_lat;
        $setting_site_location_lng = $settings->setting_site_location_lng;

        return response()->view('backend.admin.item.create',
            compact('all_categories', 'category_ids', 'all_customFields', 'setting_site_location_lat',
                'setting_site_location_lng', 'all_countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
      //  dd($request->all());
        // prepare rule for general information
        $validate_rule = [
            'category' => 'required',
            'category.*' => 'numeric',
            'item_status' => 'required|numeric',
            'item_featured' => 'required|numeric',
            'item_title' => 'required|max:255',
            'item_description' => 'required',
            'city_id' => 'required|numeric',
            'state_id' => 'required|numeric',
            'country_id' => 'required|numeric',
            'item_postal_code' => 'required|max:255',
            'item_phone' => 'nullable|max:255',
            'item_website' => 'nullable|url|max:255',
            'item_social_facebook' => 'nullable|url|max:255',
            'item_social_twitter' => 'nullable|url|max:255',
            'item_social_linkedin' => 'nullable|url|max:255',
            'item_youtube_id' => 'nullable|max:255',
//            'feature_image' => 'image|max:5120',
            //            'image_gallery.*' => 'image|max:5120',
        ];

        // validate category_ids
        $select_categories = $request->category;
        if (!is_null($select_categories)) {
            if (count($select_categories) > 0) {
                foreach ($select_categories as $select_categories_key => $select_category) {
                    $select_category = Category::find($select_category);
                    if (!$select_category) {
                        throw ValidationException::withMessages(
                            [
                            'category' => __('prefer_country.category-not-found'),
                        ]
                        );
                    }

                    // prepare validate rule for custom fields
                    $custom_field_validation = array();
                    $custom_field_link = $select_category->allCustomFields()
                    ->where('custom_field_type', CustomField::TYPE_LINK)
                    ->get();

                    if ($custom_field_link->count() > 0) {
                        foreach ($custom_field_link as $custom_field_link_key => $a_link) {
                            $custom_field_validation[str_slug($a_link->custom_field_name . $a_link->id)] = 'nullable|url';
                        }
                    }

                    $validate_rule = array_merge($validate_rule, $custom_field_validation);
                }
            }
        }
        // validate request
        $request->validate($validate_rule);

        // validate country_id
        $select_country = Country::find($request->country_id);
        if (!$select_country) {
            throw ValidationException::withMessages(
                [
                    'country_id' => __('prefer_country.country-not-found'),
                ]);
        }

        // validate state_id
        $select_state = State::find($request->state_id);
        if (!$select_state) {
            throw ValidationException::withMessages(
                [
                    'state_id' => __('prefer_country.state-not-found'),
                ]);
        }
        // validate city_id
        $select_city = City::find($request->city_id);
        if (!$select_city) {
            throw ValidationException::withMessages(
                [
                    'city_id' => __('prefer_country.city-not-found'),
                ]);
        }

        $user_id = Auth::user()->id;
        //$user_id = 1;
        //$category_id = $select_category->id;
        $item_status = $request->item_status;
        $item_featured = $request->item_featured == Item::ITEM_FEATURED ? Item::ITEM_FEATURED : Item::ITEM_NOT_FEATURED;
        $item_featured_by_admin = $item_featured == Item::ITEM_FEATURED ? Item::ITEM_FEATURED_BY_ADMIN : Item::ITEM_NOT_FEATURED_BY_ADMIN;
        $item_title = $request->item_title;

        // generate item slug based on combination of uniq id and item_title slug
        $item_slug = uniqid() . '-' . str_slug($item_title);

        $item_description = $request->item_description;
        $item_address = $request->item_address;
        $item_address_hide = $request->item_address_hide == Item::ITEM_ADDR_HIDE ? Item::ITEM_ADDR_HIDE : Item::ITEM_ADDR_NOT_HIDE;
        $city_id = $select_city->id;
        $state_id = $select_state->id;
        $country_id = $select_country->id;

        $item_postal_code = $request->item_postal_code;
        $item_lat = $request->item_lat;
        $item_lng = $request->item_lng;

        $item_youtube_id = $request->item_youtube_id;

        // guess lat and lng if empty
        if (empty($item_lat) || empty($item_lng)) {
            $item_lat = $select_city->city_lat;
            $item_lng = $select_city->city_lng;
        }

        $item_phone = empty($request->item_phone) ? null : $request->item_phone;
        $item_website = $request->item_website;
        $item_social_facebook = $request->item_social_facebook;
        $item_social_twitter = $request->item_social_twitter;
        $item_social_linkedin = $request->item_social_linkedin;

        // start upload feature image
        $feature_image = $request->feature_image;
        $item_feature_image_name = null;
        $item_feature_image_name_medium = null;
        $item_feature_image_name_small = null;
        $item_feature_image_name_tiny = null;
        $item_feature_image_name_blur = null;
        if (!empty($feature_image)) {

            $currentDate = Carbon::now()->toDateString();

            $item_feature_image_name = $item_slug . '-' . $currentDate . '-' . uniqid() . '.jpg';
            $item_feature_image_name_medium = $item_slug . '-' . $currentDate . '-' . uniqid() . '-medium.jpg';
            $item_feature_image_name_small = $item_slug . '-' . $currentDate . '-' . uniqid() . '-small.jpg';
            $item_feature_image_name_tiny = $item_slug . '-' . $currentDate . '-' . uniqid() . '-tiny.jpg';

            // blur feature image name
            $item_feature_image_name_blur = $item_slug . '-' . $currentDate . '-' . uniqid() . '-blur.jpg';

            if (!Storage::disk('public')->exists('item')) {
                Storage::disk('public')->makeDirectory('item');
            }

            // original size
            $item_feature_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))->stream('jpg', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name, $item_feature_image);

            // medium size
            $item_feature_image_medium = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(350, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_medium = $item_feature_image_medium->stream('jpg', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_medium, $item_feature_image_medium);

            // small size
            $item_feature_image_small = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(230, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_small = $item_feature_image_small->stream('jpg', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_small, $item_feature_image_small);

            // tiny size
            $item_feature_image_tiny = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(160, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_tiny = $item_feature_image_tiny->stream('jpg', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_tiny, $item_feature_image_tiny);

            // blur feature image
            $item_feature_image_blur = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)));
            $item_feature_image_blur->blur(50);
            $item_feature_image_blur->stream('jpg', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_blur, $item_feature_image_blur);

        }

        // start saving category string to item_categories_string column of items table
        $item_categories_string = "";
        foreach ($select_categories as $select_categories_key => $select_category) {
            $item_categories_string = $item_categories_string . " " . Category::find($select_category)->category_name;
        }

        // fill new item data
        $new_item = new Item(array(
            'user_id' => $user_id,
            'item_status' => $item_status,
            'item_featured' => $item_featured,
            'item_featured_by_admin' => $item_featured_by_admin,
            'item_title' => $item_title,
            'category_id' => $request->category[0],
            'item_slug' => $item_slug,
            'item_description' => $item_description,
            'item_image' => $item_feature_image_name,
            'item_image_medium' => $item_feature_image_name_medium,
            'item_image_small' => $item_feature_image_name_small,
            'item_image_tiny' => $item_feature_image_name_tiny,
            'item_image_blur' => $item_feature_image_name_blur,
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
            'lang' => Session::get('lang'),

        ));
        $new_item->save();

        $new_item->allCategories()->sync($select_categories);

        // start to save custom fields data
        $category_custom_fields = new CustomField();
        $category_custom_fields = $category_custom_fields->getDistinctCustomFieldsByCategories($select_categories);

        if ($category_custom_fields->count() > 0) {
            foreach ($category_custom_fields as $category_custom_fields_key => $custom_field) {
                if ($custom_field->custom_field_type == CustomField::TYPE_MULTI_SELECT) {
                    $multi_select_values = $request->get(str_slug($custom_field->custom_field_name . $custom_field->id), '');
                    $multi_select_str = '';
                    if (is_array($multi_select_values)) {
                        foreach ($multi_select_values as $multi_select_values_key => $value) {
                            $multi_select_str .= $value . ', ';
                        }
                    }
                    $new_item_feature = new ItemFeature(array(
                        'custom_field_id' => $custom_field->id,
                        'item_feature_value' => empty($multi_select_str) ? '' : substr(trim($multi_select_str), 0, -1),
                    ));
                } else {
                    $new_item_feature = new ItemFeature(array(
                        'custom_field_id' => $custom_field->id,
                        'item_feature_value' => $request->get(str_slug($custom_field->custom_field_name . $custom_field->id), ''),
                    ));
                }

                $created_item_feature = $new_item->features()->save($new_item_feature);

                $new_item->item_features_string = $new_item->item_features_string . $created_item_feature->item_feature_value . " ";
                $new_item->save();
            }
        }

        // start to upload image galleries
        $image_gallary = $request->image_gallery;
        if (is_array($image_gallary) && count($image_gallary) > 0) {
            foreach ($image_gallary as $image_gallary_key => $image) {
                // only total 12 images are allowed
                if ($image_gallary_key < 12) {
                    $currentDate = Carbon::now()->toDateString();
                    $item_image_gallery_uniqid = uniqid();

                    $item_image_gallery['item_image_gallery_name'] = 'gallary-' . $currentDate . '-' . $item_image_gallery_uniqid . '.jpg';
                    $item_image_gallery['item_image_gallery_thumb_name'] = 'gallary-' . $currentDate . '-' . $item_image_gallery_uniqid . '-thumb.jpg';

                    //$item_image_gallery['item_image_gallery_size'] = $image->getClientSize();
                    //$item_image_gallery['property_id'] = $created_item->id;

                    if (!Storage::disk('public')->exists('item/gallery')) {
                        Storage::disk('public')->makeDirectory('item/gallery');
                    }

                    // original
                    $one_gallery_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))->stream('jpg', 80);
                    Storage::disk('public')->put('item/gallery/' . $item_image_gallery['item_image_gallery_name'], $one_gallery_image);

                    // thumb size
                    $one_gallery_image_thumb = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))
                        ->resize(null, 180, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    $one_gallery_image_thumb = $one_gallery_image_thumb->stream('jpg', 70);
                    Storage::disk('public')->put('item/gallery/' . $item_image_gallery['item_image_gallery_thumb_name'], $one_gallery_image_thumb);

                    $created_item_image_gallery = $new_item->galleries()->create($item_image_gallery);
                }
            }
        }

        // success, flash message
        \Session::flash('flash_message', __('alert.item-created'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.edit', $new_item);
    }

    /**
     * Display the specified resource.
     *
     * @param Item $item
     * @return RedirectResponse
     */
    public function show(Item $item)
    {
        return redirect()->route('page.item', $item->item_slug);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Item $item
     * @return Response
     */
    public function edit(Item $item)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.item.edit-item', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $item_owner = $item->user()->first();

        $all_categories = new Category();
        $all_categories = $all_categories->getPrintableCategories();

        /**
         * Start initial country, state, city selector
         */
        $all_countries = Country::orderBy('country_name')->get();
        $all_states = $item->country()->first()->states()->orderBy('state_name')->get();
        $all_cities = $item->state()->first()->cities()->orderBy('city_name')->get();
        /**
         * End initial country, state, city selector
         */

        // get all custom fields based on the categories of the item
        $categories = $item->allCategories()->get();
        $category_ids = array();
        foreach ($categories as $key => $category) {
            $category_ids[] = $category->id;
        }

        $custom_field_ids = DB::table('category_custom_field')
            ->whereIn('category_id', $category_ids)
            ->distinct('custom_field_id')
            ->get();

        $select_custom_field = array();
        foreach ($custom_field_ids as $key => $custom_field_id) {
            $select_custom_field[] = $custom_field_id->custom_field_id;
        }

        $all_customFields = CustomField::whereIn('id', $select_custom_field)
            ->orderBy('custom_field_order')
            ->orderBy('created_at')
            ->get();

        return response()->view('backend.admin.item.edit',
            compact('item_owner', 'all_countries', 'all_states', 'all_cities', 'all_customFields', 'item',
                'categories', 'all_categories', 'category_ids'));
    }

    /**
     * @param Request $request
     * @param Item $item
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function updateItemSlug(Request $request, Item $item)
    {
        $request->validate([
            'item_slug' => 'required|regex:/^[\w-]*$/|max:255',
        ]);

        $item_slug = $request->item_slug;

        $validate_error = array();

        $item_slug_exist = Item::where('item_slug', $item_slug)
            ->where('id', '!=', $item->id)
            ->get()->count();

        if ($item_slug_exist > 0) {
            $validate_error['item_slug'] = __('item_slug.alert.item-slug-exist');
        }

        if (count($validate_error) > 0) {
            throw ValidationException::withMessages($validate_error);
        }

        $item->item_slug = $item_slug;
        $item->save();

        \Session::flash('flash_message', __('item_slug.alert.item-slug-update-success'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.edit', $item);
    }

    /**
     * @param Request $request
     * @param Item $item
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function updateItemCategory(Request $request, Item $item)
    {
        $request->validate([
            'category' => 'required',
            'category.*' => 'numeric',
        ]);

        // validate category_ids
        $select_categories = $request->category;

        $item_categories_string = "";

        foreach ($select_categories as $key => $select_category) {
            $select_category = Category::find($select_category);
            if (!$select_category) {
                throw ValidationException::withMessages(
                    [
                        'category' => 'Category not found',
                    ]);
            } else {
                $item_categories_string = $item_categories_string . " " . $select_category->category_name;
            }
        }

        // update item category
        $item->allCategories()->sync($select_categories);

        // start saving item_categories_string
        $item->item_categories_string = $item_categories_string;

        if (!Auth::user()->isAdmin()) {
            // if the user is regular user, then need approve this item category update
            $item->item_status = Item::ITEM_SUBMITTED;
        }
        $item->save();

        // success, flash message
        \Session::flash('flash_message', __('categories.item-category-update-alert'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.edit', $item);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Item $item
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, Item $item)
    {
        // prepare rule for general information
        $validate_rule = [
            'item_status' => 'required|numeric',
            'item_featured' => 'required|numeric',
            'item_title' => 'required|max:255',
            'item_description' => 'required',
            'city_id' => 'required|numeric',
            'state_id' => 'required|numeric',
            'country_id' => 'required|numeric',
            'item_postal_code' => 'required|max:255',
            'item_phone' => 'nullable|max:255',
            'item_website' => 'nullable|url|max:255',
            'item_social_facebook' => 'nullable|url|max:255',
            'item_social_twitter' => 'nullable|url|max:255',
            'item_social_linkedin' => 'nullable|url|max:255',
            'item_youtube_id' => 'nullable|max:255',
//            'feature_image' => 'image|max:5120',
            //            'image_gallery.*' => 'image|max:5120',
        ];

        // prepare validate rule for custom fields
        $select_categories = $item->allCategories()->get();

        foreach ($select_categories as $select_categories_key => $select_category) {
            $custom_field_validation = array();
            $custom_field_link = $select_category->allCustomFields()
                ->where('custom_field_type', CustomField::TYPE_LINK)
                ->get();

            if ($custom_field_link->count() > 0) {
                foreach ($custom_field_link as $custom_field_link_key => $a_link) {
                    $custom_field_validation[str_slug($a_link->custom_field_name . $a_link->id)] = 'nullable|url';
                }
            }

            $validate_rule = array_merge($validate_rule, $custom_field_validation);
        }

        // validate request
        $request->validate($validate_rule);

        // validate country_id
        $select_country = Country::find($request->country_id);
        if (!$select_country) {
            throw ValidationException::withMessages(
                [
                    'country_id' => __('prefer_country.country-not-found'),
                ]);
        }

        // validate state_id
        $select_state = State::find($request->state_id);
        if (!$select_state) {
            throw ValidationException::withMessages(
                [
                    'state_id' => __('prefer_country.state-not-found'),
                ]);
        }
        // validate city_id
        $select_city = City::find($request->city_id);
        if (!$select_city) {
            throw ValidationException::withMessages(
                [
                    'city_id' => __('prefer_country.city-not-found'),
                ]);
        }

        $user_id = Auth::user()->id;
        $item_status = $request->item_status;
        $item_featured = $request->item_featured == Item::ITEM_FEATURED ? Item::ITEM_FEATURED : Item::ITEM_NOT_FEATURED;
        $item_featured_by_admin = $item_featured == Item::ITEM_FEATURED ? Item::ITEM_FEATURED_BY_ADMIN : Item::ITEM_NOT_FEATURED_BY_ADMIN;
        $item_title = $request->item_title;

        $item_description = $request->item_description;
        $item_address = $request->item_address;
        $item_address_hide = $request->item_address_hide == Item::ITEM_ADDR_HIDE ? Item::ITEM_ADDR_HIDE : Item::ITEM_ADDR_NOT_HIDE;
        $city_id = $select_city->id;
        $state_id = $select_state->id;
        $country_id = $select_country->id;

        $item_postal_code = $request->item_postal_code;
        $item_lat = $request->item_lat;
        $item_lng = $request->item_lng;

        $item_youtube_id = $request->item_youtube_id;

        // guess lat and lng if empty
        if (empty($item_lat) || empty($item_lng)) {
            $item_lat = $select_city->city_lat;
            $item_lng = $select_city->city_lng;
        }

        $item_phone = empty($request->item_phone) ? null : $request->item_phone;
        $item_website = $request->item_website;
        $item_social_facebook = $request->item_social_facebook;
        $item_social_twitter = $request->item_social_twitter;
        $item_social_linkedin = $request->item_social_linkedin;

        // start upload feature image
        $feature_image = $request->feature_image;
        $item_feature_image_name = $item->item_image;
        $item_feature_image_name_medium = $item->item_image_medium;
        $item_feature_image_name_small = $item->item_image_small;
        $item_feature_image_name_tiny = $item->item_image_tiny;
        $item_feature_image_name_blur = $item->item_image_blur;
        if (!empty($feature_image)) {

            $currentDate = Carbon::now()->toDateString();

            $item_feature_image_name = $item->item_slug . '-' . $currentDate . '-' . uniqid() . '.jpg';
            $item_feature_image_name_medium = $item->item_slug . '-' . $currentDate . '-' . uniqid() . '-medium.jpg';
            $item_feature_image_name_small = $item->item_slug . '-' . $currentDate . '-' . uniqid() . '-small.jpg';
            $item_feature_image_name_tiny = $item->item_slug . '-' . $currentDate . '-' . uniqid() . '-tiny.jpg';

            // blur feature image name
            $item_feature_image_name_blur = $item->item_slug . '-' . $currentDate . '-' . uniqid() . '-blur.jpg';

            if (!Storage::disk('public')->exists('item')) {
                Storage::disk('public')->makeDirectory('item');
            }
            if (Storage::disk('public')->exists('item/' . $item->item_image)) {

                Storage::disk('public')->delete('item/' . $item->item_image);
                Storage::disk('public')->delete('item/' . $item->item_image_medium);
                Storage::disk('public')->delete('item/' . $item->item_image_small);
                Storage::disk('public')->delete('item/' . $item->item_image_tiny);
                Storage::disk('public')->delete('item/' . $item->item_image_blur);

            }

            // original size
            $item_feature_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))->stream('jpg', 70);
            //$item_feature_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$feature_image)))->encode('webp', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name, $item_feature_image);

            // medium size
            $item_feature_image_medium = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(350, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_medium = $item_feature_image_medium->stream('jpg', 70);
            //$item_feature_image_medium = $item_feature_image_medium->encode('webp', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_medium, $item_feature_image_medium);

            // small size
            $item_feature_image_small = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(230, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_small = $item_feature_image_small->stream('jpg', 70);
            //$item_feature_image_small = $item_feature_image_small->encode('webp', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_small, $item_feature_image_small);

            // tiny size
            $item_feature_image_tiny = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)))
                ->resize(160, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            $item_feature_image_tiny = $item_feature_image_tiny->stream('jpg', 70);
            //$item_feature_image_tiny = $item_feature_image_tiny->encode('webp', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_tiny, $item_feature_image_tiny);

            // blur feature image
            $item_feature_image_blur = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $feature_image)));
            $item_feature_image_blur->blur(50);
            $item_feature_image_blur->stream('jpg', 70);
            //$item_feature_image_blur->encode('webp', 70);
            Storage::disk('public')->put('item/' . $item_feature_image_name_blur, $item_feature_image_blur);
        }

        // start saving category string to item_categories_string column of items table
        $item_categories_string = "";
        foreach ($select_categories as $select_categories_key => $select_category) {
            $item_categories_string = $item_categories_string . " " . $select_category->category_name;
        }

        $item->item_status = $item_status;
        $item->item_featured = $item_featured;
        $item->item_featured_by_admin = $item_featured_by_admin;
        $item->item_title = $item_title;

        $item->item_description = $item_description;

        $item->item_image = $item_feature_image_name;
        $item->item_image_medium = $item_feature_image_name_medium;
        $item->item_image_small = $item_feature_image_name_small;
        $item->item_image_tiny = $item_feature_image_name_tiny;
        $item->item_image_blur = $item_feature_image_name_blur;

        $item->item_address = $item_address;
        $item->item_address_hide = $item_address_hide;
        $item->city_id = $city_id;
        $item->state_id = $state_id;
        $item->country_id = $country_id;
        $item->item_postal_code = $item_postal_code;
        $item->item_lat = $item_lat;
        $item->item_lng = $item_lng;

        $item->item_youtube_id = $item_youtube_id;

        $item->item_phone = $item_phone;
        $item->item_website = $item_website;
        $item->item_social_facebook = $item_social_facebook;
        $item->item_social_twitter = $item_social_twitter;
        $item->item_social_linkedin = $item_social_linkedin;

        $item->item_features_string = null;
        $item->item_categories_string = $item_categories_string;
        $item->lang = Session::get('lang');
        $item->save();

        // start to save custom fields data
        $item->features()->delete();

        $item_categories = $item->allCategories()->get();
        $select_categories = array();
        foreach ($item_categories as $item_categories_key => $item_category) {
            $select_categories[] = $item_category->id;
        }

        $category_custom_fields = new CustomField();
        $category_custom_fields = $category_custom_fields->getDistinctCustomFieldsByCategories($select_categories);

        //$category_custom_fields = $select_category->customFields()->orderBy('custom_field_order')->get();

        if ($category_custom_fields->count() > 0) {
            foreach ($category_custom_fields as $category_custom_fields_key => $custom_field) {
                if ($custom_field->custom_field_type == CustomField::TYPE_MULTI_SELECT) {
                    $multi_select_values = $request->get(str_slug($custom_field->custom_field_name . $custom_field->id), '');
                    $multi_select_str = '';
                    if (is_array($multi_select_values)) {
                        foreach ($multi_select_values as $multi_select_values_key => $value) {
                            $multi_select_str .= $value . ', ';
                        }
                    }
                    $new_item_feature = new ItemFeature(array(
                        'custom_field_id' => $custom_field->id,
                        'item_feature_value' => empty($multi_select_str) ? '' : substr(trim($multi_select_str), 0, -1),
                    ));
                } else {
                    $new_item_feature = new ItemFeature(array(
                        'custom_field_id' => $custom_field->id,
                        'item_feature_value' => $request->get(str_slug($custom_field->custom_field_name . $custom_field->id), ''),
                    ));
                }

                $created_item_feature = $item->features()->save($new_item_feature);

                $item->item_features_string = $item->item_features_string . $created_item_feature->item_feature_value . " ";
                $item->save();
            }
        }

        // start to upload image galleries
        $image_gallary = $request->image_gallery;
        if (is_array($image_gallary) && count($image_gallary) > 0) {
            $total_item_image_gallery = $item->galleries()->get()->count();
            foreach ($image_gallary as $image_gallary_key => $image) {
                // only total 12 images are allowed
                if ($total_item_image_gallery + $image_gallary_key < 12) {
                    $currentDate = Carbon::now()->toDateString();
                    $item_image_gallery_uniqid = uniqid();

                    $item_image_gallery['item_image_gallery_name'] = 'gallary-' . $currentDate . '-' . $item_image_gallery_uniqid . '.jpg';
                    $item_image_gallery['item_image_gallery_thumb_name'] = 'gallary-' . $currentDate . '-' . $item_image_gallery_uniqid . '-thumb.jpg';
                    //$item_image_gallery['item_image_gallery_size'] = $image->getClientSize();
                    //$item_image_gallery['property_id'] = $created_item->id;

                    if (!Storage::disk('public')->exists('item/gallery')) {
                        Storage::disk('public')->makeDirectory('item/gallery');
                    }

                    // original
                    $one_gallery_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))->stream('jpg', 80);
                    Storage::disk('public')->put('item/gallery/' . $item_image_gallery['item_image_gallery_name'], $one_gallery_image);

                    // thumb size
                    $one_gallery_image_thumb = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))
                        ->resize(null, 180, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    $one_gallery_image_thumb = $one_gallery_image_thumb->stream('jpg', 70);
                    Storage::disk('public')->put('item/gallery/' . $item_image_gallery['item_image_gallery_thumb_name'], $one_gallery_image_thumb);

                    $created_item_image_gallery = $item->galleries()->create($item_image_gallery);
                }
            }
        }

        // success, flash message
        \Session::flash('flash_message', __('alert.item-updated'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.edit', $item);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Item $item
     * @return RedirectResponse
     */
    public function destroy(Item $item)
    {
        $item->deleteItem();

        \Session::flash('flash_message', __('alert.item-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.index');
    }

    public function approveItem(Item $item)
    {
        if (Auth::user()->isAdmin() || Auth::user()->id == $item->user_id) {
            $item->setApproved();

            \Session::flash('flash_message', __('alert.item-approved'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.edit', $item);
        } else {
            return redirect()->route('admin.items.index');
        }
    }

    public function disApproveItem(Item $item)
    {
        if (Auth::user()->isAdmin() || Auth::user()->id == $item->user_id) {
            $item->setDisapproved();

            \Session::flash('flash_message', __('alert.item-disapproved'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.edit', $item);
        } else {
            return redirect()->route('admin.items.index');
        }
    }

    public function suspendItem(Item $item)
    {
        if (Auth::user()->isAdmin() || Auth::user()->id == $item->user_id) {
            $item->setSuspended();

            \Session::flash('flash_message', __('alert.item-suspended'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.edit', $item);
        } else {
            return redirect()->route('admin.items.index');
        }
    }

    public function savedItems()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.item.saved-items', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $login_user = Auth::user();

        $saved_items = $login_user->savedItems()->get();

        return response()->view('backend.admin.item.saved',
            compact('saved_items'));
    }

    public function unSaveItem(Request $request, string $item_slug)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            $login_user = Auth::user();

            if ($login_user->hasSavedItem($item->id)) {
                $login_user->savedItems()->detach($item->id);

                \Session::flash('flash_message', __('backend.item.unsave-item-success'));
                \Session::flash('flash_type', 'success');

                return redirect()->route('admin.items.saved');
            } else {
                \Session::flash('flash_message', __('backend.item.unsave-item-error-exist'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('admin.items.saved');
            }
        } else {
            abort(404);
        }

    }

    /**
     * @param string $item_slug
     * @return Response
     */
    public function itemReviewsCreate(string $item_slug)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('review.seo.write-a-review', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('user_id', '!=', Auth::user()->id)
            ->get()->first();

        if ($item) {
            if ($item->reviewedByUser(Auth::user()->id)) {
                \Session::flash('flash_message', __('review.alert.cannot-post-more-one-review'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('page.item', $item->item_slug);
            } else {
                return response()->view('backend.admin.item.review.create',
                    compact('item'));
            }

        } else {
            abort(404);
        }
    }

    public function itemReviewsStore(Request $request, string $item_slug)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('user_id', '!=', Auth::user()->id)
            ->get()->first();

        if ($item) {
            if ($item->reviewedByUser(Auth::user()->id)) {
                \Session::flash('flash_message', __('review.alert.cannot-post-more-one-review'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('page.item', $item->item_slug);
            } else {
                $request->validate([
                    'rating' => 'required|numeric|max:5',
                    'customer_service_rating' => 'required|numeric|max:5',
                    'quality_rating' => 'required|numeric|max:5',
                    'friendly_rating' => 'required|numeric|max:5',
                    'pricing_rating' => 'required|numeric|max:5',
                    'title' => 'nullable|max:255',
                    'body' => 'required|max:65535',
                    'recommend' => 'nullable|numeric|max:1',
                ]);

                $login_user = Auth::user();
                $rating_title = empty($request->title) ? '' : $request->title;
                $rating_body = $request->body;
                $overall_rating = $request->rating;
                $customer_service_rating = $request->customer_service_rating;
                $quality_rating = $request->quality_rating;
                $friendly_rating = $request->friendly_rating;
                $pricing_rating = $request->pricing_rating;
                $recommend = $request->recommend == 1 ? Item::ITEM_REVIEW_RECOMMEND_YES : Item::ITEM_REVIEW_RECOMMEND_NO;
                $approved = $login_user->isAdmin() ? true : false;

                $new_rating = $item->rating([
                    'title' => $rating_title,
                    'body' => $rating_body,
                    'customer_service_rating' => $customer_service_rating,
                    'quality_rating' => $quality_rating,
                    'friendly_rating' => $friendly_rating,
                    'pricing_rating' => $pricing_rating,
                    'rating' => $overall_rating,
                    'recommend' => $recommend,
                    'approved' => $approved, // This is optional and defaults to false
                ], $login_user);

                // sync item_average_rating in item table
                $item->syncItemAverageRating();

                // start to upload image galleries
                $image_gallary = $request->review_image_galleries;
                if (is_array($image_gallary) && count($image_gallary) > 0) {
                    foreach ($image_gallary as $key => $image) {
                        // only total 12 images are allowed
                        if ($key < 12) {
                            $currentDate = Carbon::now()->toDateString();
                            $review_image_gallery_uniqid = uniqid();

                            $review_image_gallery['review_image_gallery_name'] = 'review-gallary-' . $currentDate . '-' . $review_image_gallery_uniqid . '.jpg';
                            $review_image_gallery['review_image_gallery_thumb_name'] = 'review-gallary-' . $currentDate . '-' . $review_image_gallery_uniqid . '-thumb.jpg';

                            if (!Storage::disk('public')->exists('item/review')) {
                                Storage::disk('public')->makeDirectory('item/review');
                            }

                            // original
                            $one_gallery_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))->stream('jpg', 80);
                            Storage::disk('public')->put('item/review/' . $review_image_gallery['review_image_gallery_name'], $one_gallery_image);

                            // thumb size
                            $one_gallery_image_thumb = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))
                                ->resize(150, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                            $one_gallery_image_thumb = $one_gallery_image_thumb->stream('jpg', 70);
                            Storage::disk('public')->put('item/review/' . $review_image_gallery['review_image_gallery_thumb_name'], $one_gallery_image_thumb);

                            // insert review image galleries record to review_image_galleries table
                            $item->insertReviewGalleriesByReviewId($new_rating->id,
                                $review_image_gallery['review_image_gallery_name'],
                                $review_image_gallery['review_image_gallery_thumb_name']);
                        }
                    }
                }

                \Session::flash('flash_message', __('review.alert.review-posted-success'));
                \Session::flash('flash_type', 'success');

                return redirect()->route('admin.items.reviews.edit', ['item_slug' => $item->item_slug, 'review' => $new_rating->id]);
            }

        } else {
            abort(404);
        }
    }

    public function itemReviewsEdit(string $item_slug, int $review)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('review.seo.edit-a-review', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('user_id', '!=', Auth::user()->id)
            ->get()->first();

        if ($item) {
            if ($item->hasReviewByIdAndUser($review, Auth::user()->id)) {
                $review = $item->getReviewById($review);

                $review_image_galleries = $item->getReviewGalleriesByReviewId($review->id);

                return response()->view('backend.admin.item.review.edit',
                    compact('item', 'review', 'review_image_galleries'));
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function itemReviewsUpdate(Request $request, string $item_slug, int $review)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('user_id', '!=', Auth::user()->id)
            ->get()->first();

        if ($item) {
            if ($item->hasReviewByIdAndUser($review, Auth::user()->id)) {
                $request->validate([
                    'rating' => 'required|numeric|max:5',
                    'customer_service_rating' => 'required|numeric|max:5',
                    'quality_rating' => 'required|numeric|max:5',
                    'friendly_rating' => 'required|numeric|max:5',
                    'pricing_rating' => 'required|numeric|max:5',
                    'title' => 'nullable|max:255',
                    'body' => 'required|max:65535',
                    'recommend' => 'nullable|numeric|max:1',
                ]);

                $login_user = Auth::user();
                $rating_title = empty($request->title) ? '' : $request->title;
                $rating_body = $request->body;
                $overall_rating = $request->rating;
                $customer_service_rating = $request->customer_service_rating;
                $quality_rating = $request->quality_rating;
                $friendly_rating = $request->friendly_rating;
                $pricing_rating = $request->pricing_rating;
                $recommend = $request->recommend == 1 ? Item::ITEM_REVIEW_RECOMMEND_YES : Item::ITEM_REVIEW_RECOMMEND_NO;
                $approved = $login_user->isAdmin() ? true : false;

                $updated_rating = $item->updateRating($review, [
                    'title' => $rating_title,
                    'body' => $rating_body,
                    'rating' => $overall_rating,
                    'customer_service_rating' => $customer_service_rating,
                    'quality_rating' => $quality_rating,
                    'friendly_rating' => $friendly_rating,
                    'pricing_rating' => $pricing_rating,
                    'recommend' => $recommend,
                    'approved' => $approved, // This is optional and defaults to false
                ]);

                // sync item_average_rating in item table
                $item->syncItemAverageRating();

                // start to upload image galleries
                $image_gallary = $request->review_image_galleries;
                if (is_array($image_gallary) && count($image_gallary) > 0) {
                    $total_review_image_gallery = $item->reviewGalleryCountByReviewId($review);
                    foreach ($image_gallary as $key => $image) {
                        // only total 12 images are allowed
                        if ($total_review_image_gallery + $key < 12) {
                            $currentDate = Carbon::now()->toDateString();
                            $review_image_gallery_uniqid = uniqid();

                            $review_image_gallery['review_image_gallery_name'] = 'review-gallary-' . $currentDate . '-' . $review_image_gallery_uniqid . '.jpg';
                            $review_image_gallery['review_image_gallery_thumb_name'] = 'review-gallary-' . $currentDate . '-' . $review_image_gallery_uniqid . '-thumb.jpg';

                            if (!Storage::disk('public')->exists('item/review')) {
                                Storage::disk('public')->makeDirectory('item/review');
                            }

                            // original
                            $one_gallery_image = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))->stream('jpg', 80);
                            Storage::disk('public')->put('item/review/' . $review_image_gallery['review_image_gallery_name'], $one_gallery_image);

                            // thumb size
                            $one_gallery_image_thumb = Image::make(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)))
                                ->resize(150, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                            $one_gallery_image_thumb = $one_gallery_image_thumb->stream('jpg', 70);
                            Storage::disk('public')->put('item/review/' . $review_image_gallery['review_image_gallery_thumb_name'], $one_gallery_image_thumb);

                            // insert review image galleries record to review_image_galleries table
                            $item->insertReviewGalleriesByReviewId($updated_rating->id,
                                $review_image_gallery['review_image_gallery_name'],
                                $review_image_gallery['review_image_gallery_thumb_name']);
                        }
                    }
                }

                \Session::flash('flash_message', __('review.alert.review-updated-success'));
                \Session::flash('flash_type', 'success');

                return redirect()->route('admin.items.reviews.edit', ['item_slug' => $item->item_slug, 'review' => $updated_rating->id]);
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function itemReviewsDestroy(string $item_slug, int $review)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
        //->where('country_id', $site_prefer_country_id)
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('user_id', '!=', Auth::user()->id)
            ->get()->first();

        if ($item) {
            if ($item->hasReviewByIdAndUser($review, Auth::user()->id)) {
                $item->deleteRating($review);

                // sync item_average_rating in item table
                $item->syncItemAverageRating();

                \Session::flash('flash_message', __('review.alert.review-deleted-success'));
                \Session::flash('flash_type', 'success');

                return redirect()->route('admin.items.reviews.index');
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function itemReviewsIndex(Request $request)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('review.seo.manage-reviews', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $reviews_type = $request->reviews_type;

        if (empty($reviews_type) || $reviews_type == 'all') {
            $reviews = DB::table('reviews')
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            if ($reviews_type == 'pending') {
                $reviews = DB::table('reviews')
                    ->where('approved', Item::ITEM_REVIEW_PENDING)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }

            if ($reviews_type == 'approved') {
                $reviews = DB::table('reviews')
                    ->where('approved', Item::ITEM_REVIEW_APPROVED)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }

            if ($reviews_type == 'me') {
                $reviews = DB::table('reviews')
                    ->where('author_id', Auth::user()->id)
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }

        }

        return response()->view('backend.admin.item.review.index',
            compact('reviews_type', 'reviews'));
    }

    public function itemReviewsShow(int $review_id)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('review.seo.show-review', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $review = DB::table('reviews')
            ->where('id', $review_id)
            ->get()->first();

        if ($review) {
            $item_id = $review->reviewrateable_id;
            $item = Item::findOrFail($item_id);

            $review_image_galleries = $item->getReviewGalleriesByReviewId($review->id);

            return response()->view('backend.admin.item.review.show',
                compact('item', 'review', 'review_image_galleries'));
        } else {
            abort(404);
        }
    }

    public function itemReviewsApprove(int $review_id)
    {
        $review = DB::table('reviews')
            ->where('id', $review_id)
            ->get()->first();

        if ($review) {
            DB::table('reviews')
                ->where('id', $review_id)
                ->update(['approved' => Item::ITEM_REVIEW_APPROVED]);

            $item = Item::find($review->reviewrateable_id);

            if ($item) {
                $item->syncItemAverageRating();
            }

            \Session::flash('flash_message', __('review.alert.review-approved'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.reviews.show', ['review_id' => $review->id]);
        } else {
            abort(404);
        }

    }

    public function itemReviewsDisapprove(int $review_id)
    {
        $review = DB::table('reviews')
            ->where('id', $review_id)
            ->get()->first();

        if ($review) {
            DB::table('reviews')
                ->where('id', $review_id)
                ->update(['approved' => Item::ITEM_REVIEW_PENDING]);

            $item = Item::find($review->reviewrateable_id);

            if ($item) {
                $item->syncItemAverageRating();
            }

            \Session::flash('flash_message', __('review.alert.review-disapproved'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.reviews.show', ['review_id' => $review->id]);
        } else {
            abort(404);
        }

    }

    public function itemReviewsDelete(int $review_id)
    {

        $review = DB::table('reviews')
            ->where('id', $review_id)
            ->get()->first();

        if ($review) {
            $item = Item::find($review->reviewrateable_id);

            DB::table('reviews')
                ->where('id', $review_id)
                ->delete();

            if ($item) {
                $item->syncItemAverageRating();
            }

            \Session::flash('flash_message', __('review.alert.review-deleted-success'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.items.reviews.index');
        } else {
            abort(404);
        }

    }

    /**
     * Show all item sections of an item
     *
     * @param Request $request
     * @param Item $item
     * @return Response
     */
    public function indexItemSections(Request $request, Item $item)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('item_section.seo.index', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $item_sections_after_breadcrumb = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_BREADCRUMB)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_gallery = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_GALLERY)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_description = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_DESCRIPTION)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_location_map = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_LOCATION_MAP)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_features = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_FEATURES)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_reviews = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_REVIEWS)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_comments = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_COMMENTS)
            ->orderBy('item_section_order')
            ->get();

        $item_section_after_share = $item->itemSections()
            ->where('item_section_position', ItemSection::POSITION_AFTER_SHARE)
            ->orderBy('item_section_order')
            ->get();

        $item_has_claimed = $item->hasClaimed();
        $item_claimed_user = $item->getClaimedUser();

        return response()->view('backend.admin.item.item-section.index',
            compact('item', 'item_sections_after_breadcrumb', 'item_section_after_gallery',
                'item_section_after_description', 'item_section_after_location_map', 'item_section_after_features',
                'item_section_after_reviews', 'item_section_after_comments', 'item_section_after_share',
                'item_has_claimed', 'item_claimed_user'));
    }

    /**
     * Create a new item section
     *
     * @param Request $request
     * @param Item $item
     * @return RedirectResponse
     */
    public function storeItemSection(Request $request, Item $item)
    {
        /**
         * Start form validation
         */
        $request->validate([
            'item_section_title' => 'required|max:255',
            'item_section_position' => 'required|numeric|in:1,2,3,4,5,6,7,8',
            'item_section_status' => 'required|numeric|in:1,2',
        ]);
        /**
         * End form validation
         */

        $item_section_title = $request->item_section_title;
        $item_section_position = $request->item_section_position;
        $item_section_status = $request->item_section_status == ItemSection::STATUS_DRAFT ? ItemSection::STATUS_DRAFT : ItemSection::STATUS_PUBLISHED;
        $item_section_order = $item->itemSections()->where('item_section_position', $item_section_position)->get()->count() + 1;

        $new_item_section = $item->itemSections()->create(array(
            'item_section_title' => $item_section_title,
            'item_section_position' => $item_section_position,
            'item_section_order' => $item_section_order,
            'item_section_status' => $item_section_status,
        ));

        \Session::flash('flash_message', __('item_section.alert.item-section-created'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $new_item_section->id]);
    }

    /**
     * Load the page of edit item section
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse|Response
     */
    public function editItemSection(Request $request, Item $item, ItemSection $item_section)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('item_section.seo.edit', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        } else {
            $all_item_section_collections = $item_section->itemSectionCollections()
                ->orderBy('item_section_collection_order')
                ->get();

            $item_has_claimed = $item->hasClaimed();
            $item_claimed_user = $item->getClaimedUser();

            /**
             * Start initial available collections
             */
            // Collected products
            $item_section_collection_collected_products = $item_section->itemSectionCollections()
                ->where('item_section_collection_collectible_type', ItemSectionCollection::COLLECTIBLE_TYPE_PRODUCT)
                ->get();

            $collected_product_ids = array();
            foreach ($item_section_collection_collected_products as $key => $item_section_collection_collected_product) {
                $collected_product_ids[] = $item_section_collection_collected_product->item_section_collection_collectible_id;
            }

            // Available products
            $available_products = Product::where('user_id', $item->user_id)
                ->whereNotIn('id', $collected_product_ids)
                ->get();

            $product_currency_symbol = $settings->setting_product_currency_symbol;
            /**
             * End initial available collections
             */

            return response()->view('backend.admin.item.item-section.edit',
                compact('item', 'item_section', 'all_item_section_collections',
                    'item_has_claimed', 'item_claimed_user', 'available_products', 'product_currency_symbol'));
        }
    }

    /**
     * Update an item section
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse
     */
    public function updateItemSection(Request $request, Item $item, ItemSection $item_section)
    {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        /**
         * Start form validation
         */
        $request->validate([
            'item_section_title' => 'required|max:255',
            'item_section_position' => 'required|numeric|in:1,2,3,4,5,6,7,8',
            'item_section_status' => 'required|numeric|in:1,2',
        ]);
        /**
         * End form validation
         */

        $item_section_title = $request->item_section_title;
        $item_section_position = $request->item_section_position;
        $item_section_status = $request->item_section_status;

        $item_section_order = $item_section->item_section_order;

        // check if position has updated
        if ($item_section->item_section_position != $item_section_position) {
            // the item section has updated to a new position, so we need to
            // update the item_section_order based on the new position
            $item_section_order = $item->itemSections()->where('item_section_position', $item_section_position)->get()->count() + 1;

            // also, the old position need to re-order
            $re_order_item_sections = $item->itemSections()
                ->where('item_section_position', $item_section->item_section_position)
                ->where('item_section_order', '>', $item_section->item_section_order)
                ->get();

            foreach ($re_order_item_sections as $key => $re_order_item_section) {
                $re_order_item_section->item_section_order = $re_order_item_section->item_section_order - 1;
                $re_order_item_section->save();
            }

        }

        $item_section->item_section_title = $item_section_title;
        $item_section->item_section_position = $item_section_position;
        $item_section->item_section_order = $item_section_order;
        $item_section->item_section_status = $item_section_status;
        $item_section->save();

        \Session::flash('flash_message', __('item_section.alert.item-section-updated'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section->id]);
    }

    /**
     * Delete an item section
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroyItemSection(Request $request, Item $item, ItemSection $item_section)
    {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        // #1 - delete all item_section's collections
        $all_item_section_collections = $item_section->itemSectionCollections()->get();

        foreach ($all_item_section_collections as $key => $item_section_collection) {
            $item_section_collection->delete();
        }

        // #2 - re-order item_section_order
        $item_sections = $item->itemSections()
            ->where('item_section_position', $item_section->item_section_position)
            ->where('item_section_order', '>', $item_section->item_section_order)
            ->orderBy('item_section_order')
            ->get();

        foreach ($item_sections as $key => $section) {
            $section->item_section_order = intval($section->item_section_order) - 1;
            $section->save();
        }

        // #3 - delete $item_section
        $item_section->delete();

        \Session::flash('flash_message', __('item_section.alert.item-section-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.index', ['item' => $item]);
    }

    /**
     * Rank up the item section
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse
     */
    public function rankUpItemSection(Request $request, Item $item, ItemSection $item_section)
    {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        $old_rank = intval($item_section->item_section_order);

        $move_down_item_section = $item->itemSections()
            ->where('item_section_position', $item_section->item_section_position)
            ->where('item_section_order', $old_rank - 1)
            ->get();

        if ($move_down_item_section->count() > 0) {
            $move_down_item_section = $move_down_item_section->first();
            $move_down_item_section->item_section_order = $old_rank;
            $move_down_item_section->save();

            $item_section->item_section_order = $old_rank - 1;
            $item_section->save();
        }

        \Session::flash('flash_message', __('item_section.alert.item-section-ranked-up'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.index', ['item' => $item]);
    }

    /**
     * Rank down the item section
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse
     */
    public function rankDownItemSection(Request $request, Item $item, ItemSection $item_section)
    {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        $old_rank = intval($item_section->item_section_order);

        $move_up_item_section = $item->itemSections()
            ->where('item_section_position', $item_section->item_section_position)
            ->where('item_section_order', $old_rank + 1)
            ->get();

        if ($move_up_item_section->count() > 0) {
            $move_up_item_section = $move_up_item_section->first();
            $move_up_item_section->item_section_order = $old_rank;
            $move_up_item_section->save();

            $item_section->item_section_order = $old_rank + 1;
            $item_section->save();
        }

        \Session::flash('flash_message', __('item_section.alert.item-section-ranked-down'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.index', ['item' => $item]);

    }

    /**
     * Save a collection to item_section_collections table
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function storeItemSectionCollections(Request $request, Item $item, ItemSection $item_section)
    {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        /**
         * Start form validation
         */
        $request->validate([
            'item_section_collection_collectible_type' => 'required|max:255',
            'item_section_collection_collectible_id' => 'required|array',
            'item_section_collection_collectible_id.*' => 'required|numeric',
        ]);

        $item_section_collection_collectible_type = $request->item_section_collection_collectible_type;
        $item_section_collection_collectible_ids = $request->item_section_collection_collectible_id;

        $validate_error = array();

        $available_item_section_collection_collectible_types = array(
            ItemSectionCollection::COLLECTIBLE_TYPE_PRODUCT,
        );
        if (!in_array($item_section_collection_collectible_type, $available_item_section_collection_collectible_types)) {
            $validate_error['item_section_collection_collectible_id'] = __('item_section.alert.item-section-collection-collectible-type-not-exist');
        }

        foreach ($item_section_collection_collectible_ids as $key => $item_section_collection_collectible_id) {
            if ($item_section_collection_collectible_type == ItemSectionCollection::COLLECTIBLE_TYPE_PRODUCT) {
                $product_exist = Product::find($item_section_collection_collectible_id);
                if (!$product_exist) {
                    $validate_error['item_section_collection_collectible_id'] = __('item_section.alert.product-not-exist');
                } else {
                    if ($product_exist->user_id != $item->user_id) {
                        $validate_error['item_section_collection_collectible_id'] = __('item_section.alert.not-product-owner');
                    }
                }
            }
        }
        if (count($validate_error) > 0) {
            throw ValidationException::withMessages($validate_error);
        }
        /**
         * End form validation
         */

        /**
         * Start saving collections to the item_section_collections table
         */
        $item_section_collections_count = $item_section->itemSectionCollections()->get()->count();

        foreach ($item_section_collection_collectible_ids as $key => $collection_id) {
            $new_item_section_collection = new ItemSectionCollection(array(
                'item_section_collection_order' => $item_section_collections_count + ($key + 1),
                'item_section_collection_collectible_type' => $item_section_collection_collectible_type,
                'item_section_collection_collectible_id' => $collection_id,
            ));

            $item_section->itemSectionCollections()->save($new_item_section_collection);
        }
        /**
         * End saving collections to the item_section_collections table
         */

        \Session::flash('flash_message', __('item_section.alert.item-section-collection-product-added'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section->id]);
    }

    /**
     * Rank up item_section_collection record
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @param ItemSectionCollection $item_section_collection
     * @return RedirectResponse
     */
    public function rankUpItemSectionCollection(Request $request,
        Item $item,
        ItemSection $item_section,
        ItemSectionCollection $item_section_collection) {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        // Validate item_section and item_section_collection relation
        if ($item_section_collection->item_section_id != $item_section->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-collection-not-match-item-section'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
        }

        $old_rank = intval($item_section_collection->item_section_collection_order);

        $move_down_item_section_collection = $item_section->itemSectionCollections()
            ->where('item_section_collection_order', $old_rank - 1)
            ->get();

        if ($move_down_item_section_collection->count() > 0) {
            $move_down_item_section_collection = $move_down_item_section_collection->first();
            $move_down_item_section_collection->item_section_collection_order = $old_rank;
            $move_down_item_section_collection->save();

            $item_section_collection->item_section_collection_order = $old_rank - 1;
            $item_section_collection->save();
        }

        \Session::flash('flash_message', __('item_section.alert.item-section-collection-ranked-up'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
    }

    /**
     * Rank down item_section_collection record
     *
     * @param Request $request
     * @param Item $item
     * @param ItemSection $item_section
     * @param ItemSectionCollection $item_section_collection
     * @return RedirectResponse
     */
    public function rankDownItemSectionCollection(Request $request,
        Item $item,
        ItemSection $item_section,
        ItemSectionCollection $item_section_collection) {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        // Validate item_section and item_section_collection relation
        if ($item_section_collection->item_section_id != $item_section->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-collection-not-match-item-section'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
        }

        $old_rank = intval($item_section_collection->item_section_collection_order);

        $move_up_item_section_collection = $item_section->itemSectionCollections()
            ->where('item_section_collection_order', $old_rank + 1)
            ->get();

        if ($move_up_item_section_collection->count() > 0) {
            $move_up_item_section_collection = $move_up_item_section_collection->first();
            $move_up_item_section_collection->item_section_collection_order = $old_rank;
            $move_up_item_section_collection->save();

            $item_section_collection->item_section_collection_order = $old_rank + 1;
            $item_section_collection->save();
        }

        \Session::flash('flash_message', __('item_section.alert.item-section-collection-ranked-down'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
    }

    public function destroyItemSectionCollection(Request $request,
        Item $item,
        ItemSection $item_section,
        ItemSectionCollection $item_section_collection) {
        // Validate item and item_section relation
        if ($item_section->item_id != $item->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-not-match-item'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.index', ['item' => $item]);
        }

        // Validate item_section and item_section_collection relation
        if ($item_section_collection->item_section_id != $item_section->id) {
            \Session::flash('flash_message', __('item_section.alert.item-section-collection-not-match-item-section'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
        }

        // #1 - re-order the collections
        $re_order_item_section_collections = $item_section->itemSectionCollections()
            ->where('item_section_collection_order', '>', $item_section_collection->item_section_collection_order)
            ->get();

        foreach ($re_order_item_section_collections as $key => $collection) {
            $collection->item_section_collection_order = $collection->item_section_collection_order - 1;
            $collection->save();
        }

        // #2 - delete record in item_section_collections table
        $item_section_collection->delete();

        \Session::flash('flash_message', __('item_section.alert.item-section-collection-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.sections.edit', ['item' => $item, 'item_section' => $item_section]);
    }

    public function bulkApproveItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|array',
        ]);

        $item_ids = $request->item_id;

        foreach ($item_ids as $item_ids_key => $a_item_id) {
            $item = Item::find($a_item_id);

            if ($item) {
                $item->setApproved();
            }
        }

        \Session::flash('flash_message', __('item_index.alert.bulk-approved'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.index', $request->query());
    }

    public function bulkDisapproveItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|array',
        ]);

        $item_ids = $request->item_id;

        foreach ($item_ids as $item_ids_key => $a_item_id) {
            $item = Item::find($a_item_id);

            if ($item) {
                $item->setDisapproved();
            }
        }

        \Session::flash('flash_message', __('item_index.alert.bulk-disapproved'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.index', $request->query());
    }

    public function bulkSuspendItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|array',
        ]);

        $item_ids = $request->item_id;

        foreach ($item_ids as $item_ids_key => $a_item_id) {
            $item = Item::find($a_item_id);

            if ($item) {
                $item->setSuspended();
            }
        }

        \Session::flash('flash_message', __('item_index.alert.bulk-suspended'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.index', $request->query());
    }

    public function bulkDeleteItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|array',
        ]);

        $item_ids = $request->item_id;

        foreach ($item_ids as $item_ids_key => $a_item_id) {
            $item = Item::find($a_item_id);

            if ($item) {
                $item->deleteItem();
            }
        }

        \Session::flash('flash_message', __('item_index.alert.bulk-deleted'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.items.index', $request->query());
    }
}
