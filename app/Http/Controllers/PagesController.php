<?php

namespace App\Http\Controllers;

use App\Advertisement;
use App\BlogPost;
use App\Category;
use App\City;
use App\Country;
use App\Customization;
use App\Faq;
use App\Item;
use App\ItemImageGallery;
use App\ItemSection;
use App\Mail\Notification;
use App\Product;
use App\ProductImageGallery;
use App\Setting;
use App\State;
use App\Subscription;
use App\Testimonial;
use App\User;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class PagesController extends Controller
{
    public function index(Request $request)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        /**
         * Start SEO
         */
        SEOMeta::setTitle($settings->setting_site_seo_home_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
        SEOMeta::setDescription($settings->setting_site_seo_home_description);
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);

        // OpenGraph
        OpenGraph::setTitle($settings->setting_site_seo_home_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
        OpenGraph::setDescription($settings->setting_site_seo_home_description);
        OpenGraph::setUrl(URL::current());
        if (empty($settings->setting_site_logo)) {
            OpenGraph::addImage(asset('favicon-96x96.ico'));
        } else {
            OpenGraph::addImage(Storage::disk('public')->url('setting/' . $settings->setting_site_logo));
        }

        // Twitter
        TwitterCard::setTitle($settings->setting_site_seo_home_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
        /**
         * End SEO
         */

        /**
         * first 5 categories order by total listings
         */

        if (Auth::check()) {
            $login_user = Auth::user();
            $login_user->user_prefer_language = Session::get('lang');
            $login_user->save();
            Session::put('user_prefer_language', Session::get('lang'));

            Session::put('lang', Session::get('lang'));

        } else {
            if (Session::get('lang') == "") {
                Session::put('lang', "en");
                //  dd("Catch errors for script and full tracking ( 1 )");
            } else {
                Session::put('user_prefer_language', Session::get('lang'));
                Session::put('lang', Session::get('lang'));
            }

            // save to language preference to session.

        }

        $categories = Category::where('category_parent_id', null)
            ->where('lang', Session::get('lang'))
            ->orderBy('category_name')->take(5)->get();

        $total_items_count = Item::join('users as u', 'items.user_id', '=', 'u.id')
            ->where('items.item_status', Item::ITEM_PUBLISHED)
            
            ->where('u.email_verified_at', '!=', null)
            ->where('lang', Session::get('lang'))
            ->where('u.user_suspended', User::USER_NOT_SUSPENDED)
            ->count();

        /**
         * get first latest 20 paid listings
         */
        $today = new DateTime('now');
        $today = $today->format("Y-m-d");

        // paid listing
        $paid_items_query = Item::query();
        $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
            ->join('subscriptions as s', 'u.id', '=', 's.user_id')
            ->select('items.*')

            ->where(function ($query) use ($site_prefer_country_id) {
                $query->where("items.item_status", Item::ITEM_PUBLISHED)
                    ->where('items.item_featured', Item::ITEM_FEATURED)
                    ->where('lang', Session::get('lang'))
                    
                    ->where('u.email_verified_at', '!=', null)
                    ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
            })
            ->where(function ($query) use ($today) {
                $query->where(function ($sub_query) use ($today) {
                    $sub_query->where('s.subscription_end_date', '!=', null)
                        ->where('s.subscription_end_date', '>=', $today);
                })
                    ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                        $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                    });
            })
            ->distinct('items.id')
            ->orderBy('items.created_at', 'DESC')
            ->with('state')
            ->with('city')
            ->with('user');
        $paid_items = $paid_items_query->take(5)->where('lang', Session::get('lang'))->get();

        /**
         * get nearest 9 popular items by device lat and lng
         */
        if (!empty(session('user_device_location_lat', '')) && !empty(session('user_device_location_lng', ''))) {
            $latitude = session('user_device_location_lat', '');
            $longitude = session('user_device_location_lng', '');
        } else {
            $latitude = $settings->setting_site_location_lat;
            $longitude = $settings->setting_site_location_lng;
        }

        $popular_items = Item::selectRaw('*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
            
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->where('lang', Session::get('lang'))
            ->having('distance', '<', 5000)
            ->orderBy('distance')
            ->orderBy('created_at', 'DESC')
            ->with('state')
            ->with('city')
            ->with('user')
            ->take(9)->get();

        // if no items nearby, then use the default lat & lng
        if ($popular_items->count() == 0) {
            $latitude = $settings->setting_site_location_lat;
            $longitude = $settings->setting_site_location_lng;

            $popular_items = Item::selectRaw('*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                
                ->where('item_status', Item::ITEM_PUBLISHED)
                ->where('lang', Session::get('lang'))
                ->having('distance', '<', 5000)
                ->orderBy('distance')
                ->orderBy('created_at', 'DESC')
                ->with('state')
                ->with('city')
                ->with('user')
                ->take(9)->get();
        }
        $popular_items = $popular_items->shuffle();

        /**
         * get first 6 latest items
         */
        $latest_items = Item::latest('created_at')
            
            ->where('lang', Session::get('lang'))
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->with('state')
            ->with('city')
            ->with('user')
            ->take(6)
            ->get();
            

        /**
         * testimonials
         */
        $all_testimonials = Testimonial::latest('created_at')->get();

        /**
         * get latest 3 blog posts
         */
        $recent_blog = \Canvas\Post::published()->orderByDesc('published_at')->take(3)->get();

        /**
         * initial the search type head
         */
        $states_cities_array = $this->getStatesCitiesJson();
        $search_cities_json = json_encode($states_cities_array['cities']);

        /**
         * Start homepage header customization
         */
        $site_homepage_header_background_type = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_background_image = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_homepage_header_title_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End homepage header customization
         */

        return response()->view('frontend.index',
            compact('categories', 'paid_items', 'popular_items', 'latest_items',
                'all_testimonials', 'recent_blog', 'search_cities_json', 'total_items_count',
                'site_homepage_header_background_type', 'site_homepage_header_background_color',
                'site_homepage_header_background_image', 'site_homepage_header_background_youtube_video',
                'site_homepage_header_title_font_color', 'site_homepage_header_paragraph_font_color',
                'site_prefer_country_id'));
    }



    public function CourseProgresss($id)
    {
        $CourseProgress = Category::findorfail($id);

        if($CourseProgress->admin == 0)
        {
              //dd(11);
            DB::table('categories')->where('id','=',$id)->update(['admin' => "1"]);   
            return back()->with('success','Status changed to Active !');
        }
        else
        {
           // dd(1);
            DB::table('categories')->where('id','=',$id)->update(['admin' => "0"]);
            return back()->with('delete','Status changed to Deactive !');
        }
    }
    


    private function getStatesCitiesJson()
    {
        $site_prefer_country_id = app('site_prefer_country_id');

        $country = Country::find($site_prefer_country_id);
        $states = $country->states()->get();

        $states_json_str = array();
        $cities_json_str = array();
        foreach ($states as $key => $state) {
            $states_json_str[] = $state->state_name;

            $cities = $state->cities()->select('city_name')->orderBy('city_name')->get();
            foreach ($cities as $city) {
                $cities_json_str[] = $city->city_name . ', ' . $state->state_name;
            }
        }

        $states_cities_array = array();
        $states_cities_array['states'] = $states_json_str;
        $states_cities_array['cities'] = $cities_json_str;

        return $states_cities_array;

    }

    public function search()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.search', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        /**
         * Start fetch ads blocks
         */
        $advertisement = new Advertisement();

        $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_AFTER_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );
        /**
         * End fetch ads blocks
         */

        $states_cities_array = $this->getStatesCitiesJson();
        $search_cities_json = json_encode($states_cities_array['cities']);

        /**
         * Start homepage header customization
         */
        $site_homepage_header_background_type = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_background_image = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_homepage_header_title_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End homepage header customization
         */

        return response()->view('frontend.search',
            compact('search_cities_json',
                'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                'site_homepage_header_background_type', 'site_homepage_header_background_color',
                'site_homepage_header_background_image', 'site_homepage_header_background_youtube_video',
                'site_homepage_header_title_font_color', 'site_homepage_header_paragraph_font_color'));
    }

    public function doSearch(Request $request)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        $request->validate([
            'search_query' => 'required|max:255',
            'city_state' => 'required|max:255',
        ]);

        $last_search_query = $request->search_query;
        $last_search_city_state = $request->city_state;

        $query = $last_search_query;
        $city_state = explode(',', $last_search_city_state);

        $city = '';
        $state = '';
        if (count($city_state) == 2) {
            $city = trim($city_state[0]);
            $state = trim($city_state[1]);
        } else {
            throw ValidationException::withMessages(array(
                'city_state' => __('categories.search-city-format-alert'),
            ));
        }

        $country = Country::find($site_prefer_country_id);
        $state_obj = $country->states()->where('state_name', $state)->first();

        if ($state_obj) {
            $city_obj = $state_obj->cities()->where('city_name', $city)->first();

            if ($city_obj) {
                // First get nearby cities by the current city of lat and lng
                $latitude = $city_obj->city_lat;
                $longitude = $city_obj->city_lng;

                $nearby_cities = City::selectRaw('*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( city_lat ) ) * cos( radians( city_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( city_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                    ->having('distance', '<', 500)
                    ->orderBy('distance')
                    ->get();

                $nearby_cities_ids = array();
                $nearby_cities_ids[] = $city_obj->id;

                foreach ($nearby_cities as $key => $nearby_city) {
                    $nearby_cities_ids[] = $nearby_city->id;
                }

                $items = Item::search($query, null, true)
                    ->where('item_status', Item::ITEM_PUBLISHED)
                    ->where('lang', Session::get('lang'))
                    ->whereIn('city_id', $nearby_cities_ids)
                    ->paginate(10);
            } else {
                $items = [];
            }

        } else {
            $items = [];
        }

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.search', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        /**
         * Start fetch ads blocks
         */
        $advertisement = new Advertisement();

        $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_AFTER_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_SEARCH_PAGE,
            Advertisement::AD_POSITION_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );
        /**
         * End fetch ads blocks
         */

        $states_cities_array = $this->getStatesCitiesJson();
        $search_cities_json = json_encode($states_cities_array['cities']);

        /**
         * Start homepage header customization
         */
        $site_homepage_header_background_type = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_background_image = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_homepage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_homepage_header_title_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_homepage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_HOMEPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End homepage header customization
         */

        return response()->view('frontend.search',
            compact('items', 'search_cities_json',
                'last_search_query', 'last_search_city_state',
                'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                'site_homepage_header_background_type', 'site_homepage_header_background_color',
                'site_homepage_header_background_image', 'site_homepage_header_background_youtube_video',
                'site_homepage_header_title_font_color', 'site_homepage_header_paragraph_font_color'));
    }

    public function about()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.about', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        if ($settings->setting_page_about_enable == Setting::ABOUT_PAGE_ENABLED) {
            $about = $settings->setting_page_about;

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.about',
                compact('about', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            return redirect()->route('page.home');
        }
    }

    public function contact()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.contact', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $all_faq = Faq::orderBy('faqs_order')->get();

        /**
         * Start inner page header customization
         */
        $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End inner page header customization
         */

        return response()->view('frontend.contact',
            compact('all_faq', 'site_innerpage_header_background_type',
                'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                'site_innerpage_header_paragraph_font_color'));
    }

    public function doContact(Request $request)
    {
        $settings = app('site_global_settings');

        $validation_array = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|max:255',
            'message' => 'required',
        ];

        // Start Google reCAPTCHA version 2
        if ($settings->setting_site_recaptcha_contact_enable == Setting::SITE_RECAPTCHA_CONTACT_ENABLE) {
            $validation_array = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|max:255',
                'message' => 'required',
                'g-recaptcha-response' => 'recaptcha',
            ];
        }
        // End Google reCAPTCHA version 2

        $request->validate($validation_array);

        /**
         * Start initial SMTP settings
         */
        if ($settings->settings_site_smtp_enabled == Setting::SITE_SMTP_ENABLED) {
            // config SMTP
            config_smtp(
                $settings->settings_site_smtp_sender_name,
                $settings->settings_site_smtp_sender_email,
                $settings->settings_site_smtp_host,
                $settings->settings_site_smtp_port,
                $settings->settings_site_smtp_encryption,
                $settings->settings_site_smtp_username,
                $settings->settings_site_smtp_password
            );
        }
        /**
         * End initial SMTP settings
         */

        // send an email notification to admin
        $email_admin = User::getAdmin();
        $email_subject = __('email.contact.subject');
        $email_notify_message = [
            __('email.contact.body.body-1', ['first_name' => $request->first_name, 'last_name' => $request->last_name]),
            __('email.contact.body.body-2', ['subject' => $request->subject]),
            __('email.contact.body.body-3', ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'email' => $request->email]),
            __('email.contact.body.body-4'),
            $request->message,
        ];

        try
        {
            // to admin
            Mail::to($email_admin)->send(
                new Notification(
                    $email_subject,
                    $email_admin->name,
                    null,
                    $email_notify_message
                )
            );

            \Session::flash('flash_message', __('alert.message-send'));
            \Session::flash('flash_type', 'success');

        } catch (\Exception $e) {
            Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
            $error_message = $e->getMessage();

            \Session::flash('flash_message', $error_message);
            \Session::flash('flash_type', 'danger');
        }

        return redirect()->route('page.contact');
    }

    public function categories(Request $request)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.categories', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $categories = Category::where('category_parent_id', null)
            ->where('lang', Session::get('lang'))
            ->orderBy('category_name')->get();

        /**
         * Do listing query
         * 1. get paid listings and free listings.
         * 2. decide how many paid and free listings per page and total pages.
         * 3. decide the pagination to paid or free listings
         * 4. run query and render
         */
        $today = new DateTime('now');
        $today = $today->format("Y-m-d");

        // paid listing
        $paid_items_query = Item::query();

        /**
         * Start filter categories for paid listing
         */
        $filter_categories = empty($request->filter_categories) ? array() : $request->filter_categories;
        if (count($filter_categories) > 0) {
            $paid_items_query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->whereIn("ci.category_id", $filter_categories);
        }
        /**
         * End filter categories for paid listing
         */

        // get paid users id array
        $paid_user_ids = array();
        $paid_subscriptions = Subscription::join('users as u', 'subscriptions.user_id', '=', 'u.id')
            ->where('u.email_verified_at', '!=', null)
            ->where('u.user_suspended', User::USER_NOT_SUSPENDED)
            ->where('subscriptions.subscription_end_date', '!=', null)
            ->where('subscriptions.subscription_end_date', '>=', $today)->get();

        foreach ($paid_subscriptions as $paid_subscriptions_key => $paid_subscription) {
            $paid_user_ids[] = $paid_subscription->user_id;
        }

        $paid_items_query->where("items.item_status", Item::ITEM_PUBLISHED)
            
            ->where('items.item_featured', Item::ITEM_FEATURED)
            ->where('lang', Session::get('lang'))
            ->where(function ($query) use ($paid_user_ids) {

                $query->whereIn('items.user_id', $paid_user_ids)
                    ->orWhere('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
            })
            ->orderBy('items.created_at', 'ASC');

        $total_paid_items = $paid_items_query->count();

        // free listing
        $free_items_query = Item::query();

        /**
         * Start filter categories for free listing
         */
        if (count($filter_categories) > 0) {
            $free_items_query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->whereIn("ci.category_id", $filter_categories);
        }
        /**
         * End filter categories for free listing
         */

        // get free users id array
        $free_user_ids = array();
        $free_subscriptions = Subscription::join('users as u', 'subscriptions.user_id', '=', 'u.id')
            ->where('u.email_verified_at', '!=', null)
            ->where('u.user_suspended', User::USER_NOT_SUSPENDED)
            ->where('subscriptions.subscription_end_date', null)
            ->orWhere(function ($query) use ($today) {
                $query->where('subscriptions.subscription_end_date', '!=', null)
                    ->where('subscriptions.subscription_end_date', '<=', $today);
            })->get();

        foreach ($free_subscriptions as $free_subscriptions_key => $free_subscription) {
            $free_user_ids[] = $free_subscription->user_id;
        }

        $free_items_query->where("items.item_status", Item::ITEM_PUBLISHED)
            
            ->where('items.item_featured_by_admin', Item::ITEM_NOT_FEATURED_BY_ADMIN)
            ->whereIn('items.user_id', $free_user_ids);

        /**
         * Start filter sort by for free listing
         */
        $filter_sort_by = empty($request->filter_sort_by) ? Item::ITEMS_SORT_BY_NEWEST_CREATED : $request->filter_sort_by;
        if ($filter_sort_by == Item::ITEMS_SORT_BY_NEWEST_CREATED) {
            $free_items_query->orderBy('items.created_at', 'DESC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_OLDEST_CREATED) {
            $free_items_query->orderBy('items.created_at', 'ASC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_HIGHEST_RATING) {
            $free_items_query->orderBy('items.item_average_rating', 'DESC');
        } elseif ($filter_sort_by == Item::ITEMS_SORT_BY_LOWEST_RATING) {
            $free_items_query->orderBy('items.item_average_rating', 'ASC');
        }
        /**
         * End filter sort by for free listing
         */

        $total_free_items = $free_items_query->count();

        $querystringArray = ['filter_categories' => $filter_categories, 'filter_sort_by' => $filter_sort_by];

        if ($total_free_items == 0 || $total_paid_items == 0) {
            $paid_items = $paid_items_query->paginate(10);
            $free_items = $free_items_query->paginate(10);

            if ($total_free_items == 0) {
                $pagination = $paid_items->appends($querystringArray);
            }
            if ($total_paid_items == 0) {
                $pagination = $free_items->appends($querystringArray);
            }
        } else {
            $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);

            $paid_items_per_page = ceil($total_paid_items / $num_of_pages) > 4 ? 4 : ceil($total_paid_items / $num_of_pages);

            $free_items_per_page = 10 - $paid_items_per_page;

            $paid_items = $paid_items_query->paginate($paid_items_per_page);
            $free_items = $free_items_query->paginate($free_items_per_page);

            if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                $pagination = $paid_items->appends($querystringArray);
            } else {
                $pagination = $free_items->appends($querystringArray);
            }
        }

        /**
         * End do listing query
         */

        /**
         * Start fetch ads blocks
         */
        $advertisement = new Advertisement();

        $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_AFTER_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
            Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );
        /**
         * End fetch ads blocks
         */

        //$all_states = Country::find($settings->setting_site_location_country_id)
        $all_states = Country::find($site_prefer_country_id)
            ->states()
            ->withCount(['items' => function ($query) use ($settings, $site_prefer_country_id) {
                $query;
            }])
            ->orderBy('state_name')->get();

        /**
         * initial search bar
         */
        $states_cities_array = $this->getStatesCitiesJson();
        $search_cities_json = json_encode($states_cities_array['cities']);

        /**
         * Start inner page header customization
         */
        $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End inner page header customization
         */

        /**
         * Start initial filter
         */
        $all_printable_categories = new Category();
        $all_printable_categories = $all_printable_categories->getPrintableCategoriesNoDash();
        /**
         * End initial filter
         */

        return response()->view('frontend.categories',
            compact('categories', 'paid_items', 'free_items', 'pagination', 'all_states', 'search_cities_json',
                'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                'site_innerpage_header_background_color', 'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color', 'filter_sort_by',
                'all_printable_categories', 'filter_categories', 'site_prefer_country_id'));
    }

    public function category(string $category_slug)
    {
        $category = Category::where('category_slug', $category_slug)->first();

        if ($category) {
            $settings = app('site_global_settings');
            $site_prefer_country_id = app('site_prefer_country_id');

            /**
             * Start SEO
             */
            SEOMeta::setTitle($category->category_name . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Get parent and children categories
             */
            $parent_categories = $category->allParents();

            // get one level down sub-categories
            $children_categories = $category->children()->orderBy('category_name')->get();

            // Get all child categories of this category
            $all_child_categories = collect();
            $all_child_categories_ids = array();
            $category->allChildren($category, $all_child_categories);
            foreach ($all_child_categories as $key => $all_child_category) {
                $all_child_categories_ids[] = $all_child_category->id;
            }

            // need to give a root category for each item in a category listing page
            $parent_category_id = $category->id;

            /**
             * Do listing query
             * 1. get paid listings and free listings.
             * 2. decide how many paid and free listings per page and total pages.
             * 3. decide the pagination to paid or free listings
             * 4. run query and render
             */
            $today = new DateTime('now');
            $today = $today->format("Y-m-d");

            // paid listing
            $paid_items_query = Item::query();
            $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        ->where('items.item_featured', Item::ITEM_FEATURED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('s.subscription_end_date', '!=', null)
                            ->where('s.subscription_end_date', '>=', $today);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                            $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'ASC');
            $total_paid_items = $paid_items_query->count();

            // free listing
            $free_items_query = Item::query();
            $free_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('items.item_featured', Item::ITEM_NOT_FEATURED);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
                            $sub_query->where('items.item_featured', Item::ITEM_FEATURED)
                                ->where('s.subscription_end_date', '!=', null)
                                ->where('s.subscription_end_date', '<=', $today);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'DESC');
            $total_free_items = $free_items_query->count();

            if ($total_free_items == 0 || $total_paid_items == 0) {
                $paid_items = $paid_items_query->paginate(10);
                $free_items = $free_items_query->paginate(10);

                if ($total_free_items == 0) {
                    $pagination = $paid_items;
                }
                if ($total_paid_items == 0) {
                    $pagination = $free_items;
                }
            } else {
                $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);
                $paid_items_per_page = ceil($total_paid_items / $num_of_pages) < 4 ? 4 : ceil($total_paid_items / $num_of_pages);
                $free_items_per_page = 10 - $paid_items_per_page;

                $paid_items = $paid_items_query->paginate($paid_items_per_page);
                $free_items = $free_items_query->paginate($free_items_per_page);

                if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                    $pagination = $paid_items;
                } else {
                    $pagination = $free_items;
                }
            }
            /**
             * End do listing query
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $all_states = State::whereHas('items', function ($query) use ($category, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                $query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                    ->whereIn('ci.category_id', $all_child_categories_ids)
                    ;
            }, '>', 0)->orderBy('state_name')->get();

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.category',
                compact('category', 'paid_items', 'free_items', 'pagination', 'all_states', 'search_cities_json',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'parent_categories', 'children_categories',
                    'parent_category_id', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function categoryByState(string $category_slug, string $state_slug)
    {
        $category = Category::where('category_slug', $category_slug)->first();
        $state = State::where('state_slug', $state_slug)->first();

        if ($category && $state) {
            $settings = app('site_global_settings');
            $site_prefer_country_id = app('site_prefer_country_id');

            /**
             * Start SEO
             */
            SEOMeta::setTitle($category->category_name . ' of ' . $state->state_name . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Get parent and children categories
             */
            $parent_categories = $category->allParents();

            // get one level down sub-categories
            $children_categories = $category->children()->orderBy('category_name')->get();

            // Get all child categories of this category
            $all_child_categories = collect();
            $all_child_categories_ids = array();
            $category->allChildren($category, $all_child_categories);
            foreach ($all_child_categories as $key => $all_child_category) {
                $all_child_categories_ids[] = $all_child_category->id;
            }

            // need to give a root category for each item in a category listing page
            $parent_category_id = $category->id;

            /**
             * Do listing query
             * 1. get paid listings and free listings.
             * 2. decide how many paid and free listings per page and total pages.
             * 3. decide the pagination to paid or free listings
             * 4. run query and render
             */
            $today = new DateTime('now');
            $today = $today->format("Y-m-d");

            // paid listing
            $paid_items_query = Item::query();
            $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $state, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where('items.state_id', $state->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        ->where('items.item_featured', Item::ITEM_FEATURED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('s.subscription_end_date', '!=', null)
                            ->where('s.subscription_end_date', '>=', $today);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                            $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'ASC');
            $total_paid_items = $paid_items_query->count();

            // free listing
            $free_items_query = Item::query();
            $free_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $state, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where('items.state_id', $state->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('items.item_featured', Item::ITEM_NOT_FEATURED);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
                            $sub_query->where('items.item_featured', Item::ITEM_FEATURED)
                                ->where('s.subscription_end_date', '!=', null)
                                ->where('s.subscription_end_date', '<=', $today);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'DESC');
            $total_free_items = $free_items_query->count();

            if ($total_free_items == 0 || $total_paid_items == 0) {
                $paid_items = $paid_items_query->paginate(10);
                $free_items = $free_items_query->paginate(10);

                if ($total_free_items == 0) {
                    $pagination = $paid_items;
                }
                if ($total_paid_items == 0) {
                    $pagination = $free_items;
                }
            } else {
                $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);
                $paid_items_per_page = ceil($total_paid_items / $num_of_pages) < 4 ? 4 : ceil($total_paid_items / $num_of_pages);
                $free_items_per_page = 10 - $paid_items_per_page;

                $paid_items = $paid_items_query->paginate($paid_items_per_page);
                $free_items = $free_items_query->paginate($free_items_per_page);

                if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                    $pagination = $paid_items;
                } else {
                    $pagination = $free_items;
                }
            }
            /**
             * End do listing query
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

    $all_cities = City::whereHas('items', function ($query) use ($category, $state, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                $query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                    ->whereIn('ci.category_id', $all_child_categories_ids)
                //->where('category_id', $category->id)
                    ->where('state_id', $state->id);

            }, '>', 0)->orderBy('city_name')->get();

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.category.state',
                compact('category', 'state', 'paid_items', 'free_items', 'pagination',
                    'all_cities', 'search_cities_json',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'parent_categories', 'children_categories',
                    'parent_category_id', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function categoryByStateCity(string $category_slug, string $state_slug, string $city_slug)
    {
        $category = Category::where('category_slug', $category_slug)->first();
        $state = State::where('state_slug', $state_slug)->first();
        $city = $state->cities()->where('city_slug', $city_slug)->first();

        if ($category && $state && $city) {
            $settings = app('site_global_settings');
            $site_prefer_country_id = app('site_prefer_country_id');

            /**
             * Start SEO
             */
            SEOMeta::setTitle($category->category_name . ' of ' . $state->state_name . ', ' . $city->city_name . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Get parent and children categories
             */
            $parent_categories = $category->allParents();

            // get one level down sub-categories
            $children_categories = $category->children()->orderBy('category_name')->get();

            // Get all child categories of this category
            $all_child_categories = collect();
            $all_child_categories_ids = array();
            $category->allChildren($category, $all_child_categories);
            foreach ($all_child_categories as $key => $all_child_category) {
                $all_child_categories_ids[] = $all_child_category->id;
            }

            // need to give a root category for each item in a category listing page
            $parent_category_id = $category->id;

            /**
             * Do listing query
             * 1. get paid listings and free listings.
             * 2. decide how many paid and free listings per page and total pages.
             * 3. decide the pagination to paid or free listings
             * 4. run query and render
             */
            $today = new DateTime('now');
            $today = $today->format("Y-m-d");

            // paid listing
            $paid_items_query = Item::query();
            $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $state, $city, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where('items.state_id', $state->id)
                        ->where('items.city_id', $city->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        ->where('items.item_featured', Item::ITEM_FEATURED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('s.subscription_end_date', '!=', null)
                            ->where('s.subscription_end_date', '>=', $today);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                            $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'ASC');
            $total_paid_items = $paid_items_query->count();

            // free listing
            $free_items_query = Item::query();
            $free_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                ->select('items.*')
                ->where(function ($query) use ($category, $state, $city, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                    $query->whereIn("ci.category_id", $all_child_categories_ids)
                    //->where("items.category_id", $category->id)
                        ->where('items.state_id', $state->id)
                        ->where('items.city_id', $city->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('items.item_featured', Item::ITEM_NOT_FEATURED);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
                            $sub_query->where('items.item_featured', Item::ITEM_FEATURED)
                                ->where('s.subscription_end_date', '!=', null)
                                ->where('s.subscription_end_date', '<=', $today);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'DESC');
            $total_free_items = $free_items_query->count();

            if ($total_free_items == 0 || $total_paid_items == 0) {
                $paid_items = $paid_items_query->paginate(10);
                $free_items = $free_items_query->paginate(10);

                if ($total_free_items == 0) {
                    $pagination = $paid_items;
                }
                if ($total_paid_items == 0) {
                    $pagination = $free_items;
                }
            } else {
                $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);
                $paid_items_per_page = ceil($total_paid_items / $num_of_pages) < 4 ? 4 : ceil($total_paid_items / $num_of_pages);
                $free_items_per_page = 10 - $paid_items_per_page;

                $paid_items = $paid_items_query->paginate($paid_items_per_page);
                $free_items = $free_items_query->paginate($free_items_per_page);

                if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                    $pagination = $paid_items;
                } else {
                    $pagination = $free_items;
                }
            }
            /**
             * End do listing query
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $all_cities = City::whereHas('items', function ($query) use ($category, $state, $all_child_categories_ids, $settings, $site_prefer_country_id) {
                $query->join('category_item as ci', 'items.id', '=', 'ci.item_id')
                    ->whereIn('ci.category_id', $all_child_categories_ids)
                //->where('category_id', $category->id)
                    ->where('state_id', $state->id)
                    ;
            }, '>', 0)->orderBy('city_name')->get();

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.category.city',
                compact('category', 'state', 'city', 'paid_items', 'free_items', 'pagination',
                    'all_cities', 'search_cities_json',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'parent_categories', 'children_categories',
                    'parent_category_id', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function state(string $state_slug)
    {
        $state = State::where('state_slug', $state_slug)->first();

        if ($state) {
            $settings = app('site_global_settings');
            $site_prefer_country_id = app('site_prefer_country_id');

            /**
             * Start SEO
             */
            SEOMeta::setTitle(__('seo.frontend.categories-state', ['state_name' => $state->state_name, 'site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Do listing query
             * 1. get paid listings and free listings.
             * 2. decide how many paid and free listings per page and total pages.
             * 3. decide the pagination to paid or free listings
             * 4. run query and render
             */
            $today = new DateTime('now');
            $today = $today->format("Y-m-d");

            // paid listing
            $paid_items_query = Item::query();
            $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->select('items.*')
                ->where('lang', Session::get('lang'))
                ->where(function ($query) use ($state, $settings, $site_prefer_country_id) {
                    $query->where("items.state_id", $state->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        ->where('items.item_featured', Item::ITEM_FEATURED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('s.subscription_end_date', '!=', null)
                            ->where('s.subscription_end_date', '>=', $today);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                            $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'ASC');
            $total_paid_items = $paid_items_query->count();

            // free listing
            $free_items_query = Item::query();
            $free_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->select('items.*')
                ->where('lang', Session::get('lang'))
                ->where(function ($query) use ($state, $settings, $site_prefer_country_id) {
                    $query->where("items.state_id", $state->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('items.item_featured', Item::ITEM_NOT_FEATURED);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
                            $sub_query->where('items.item_featured', Item::ITEM_FEATURED)
                                ->where('s.subscription_end_date', '!=', null)
                                ->where('s.subscription_end_date', '<=', $today);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'DESC');
            $total_free_items = $free_items_query->count();

            if ($total_free_items == 0 || $total_paid_items == 0) {
                $paid_items = $paid_items_query->paginate(10);
                $free_items = $free_items_query->paginate(10);

                if ($total_free_items == 0) {
                    $pagination = $paid_items;
                }
                if ($total_paid_items == 0) {
                    $pagination = $free_items;
                }
            } else {
                $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);
                $paid_items_per_page = ceil($total_paid_items / $num_of_pages) < 4 ? 4 : ceil($total_paid_items / $num_of_pages);
                $free_items_per_page = 10 - $paid_items_per_page;

                $paid_items = $paid_items_query->paginate($paid_items_per_page);
                $free_items = $free_items_query->paginate($free_items_per_page);

                if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                    $pagination = $paid_items;
                } else {
                    $pagination = $free_items;
                }
            }
            /**
             * End do listing query
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $all_cities = City::whereHas('items', function ($query) use ($state, $settings, $site_prefer_country_id) {
                $query->where('state_id', $state->id)
                    ;
            }, '>', 0)->orderBy('city_name')->get();

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.state',
                compact('state', 'paid_items', 'free_items', 'pagination', 'all_cities', 'search_cities_json',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                    'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                    'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                    'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function city(string $state_slug, string $city_slug)
    {
        $state = State::where('state_slug', $state_slug)->first();
        $city = $state->cities()->where('city_slug', $city_slug)->first();

        if ($state && $city) {
            $settings = app('site_global_settings');
            $site_prefer_country_id = app('site_prefer_country_id');

            /**
             * Start SEO
             */
            SEOMeta::setTitle(__('seo.frontend.categories-state-city', ['state_name' => $state->state_name, 'city_name' => $city->city_name, 'site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Do listing query
             * 1. get paid listings and free listings.
             * 2. decide how many paid and free listings per page and total pages.
             * 3. decide the pagination to paid or free listings
             * 4. run query and render
             */
            $today = new DateTime('now');
            $today = $today->format("Y-m-d");

            // paid listing
            $paid_items_query = Item::query();
            $paid_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->select('items.*')
                ->where(function ($query) use ($state, $city, $settings, $site_prefer_country_id) {
                    $query->where("items.state_id", $state->id)
                        ->where("items.city_id", $city->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        ->where('items.item_featured', Item::ITEM_FEATURED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('s.subscription_end_date', '!=', null)
                            ->where('s.subscription_end_date', '>=', $today);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
//                        $sub_query->where('s.subscription_end_date', null)
                            $sub_query->where('items.item_featured_by_admin', Item::ITEM_FEATURED_BY_ADMIN);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'ASC');
            $total_paid_items = $paid_items_query->count();

            // free listing
            $free_items_query = Item::query();
            $free_items_query->join('users as u', 'items.user_id', '=', 'u.id')
                ->join('subscriptions as s', 'u.id', '=', 's.user_id')
                ->select('items.*')
                ->where(function ($query) use ($state, $city, $settings, $site_prefer_country_id) {
                    $query->where("items.state_id", $state->id)
                        ->where("items.city_id", $city->id)
                        ->where("items.item_status", Item::ITEM_PUBLISHED)
                        
                        ->where('u.email_verified_at', '!=', null)
                        ->where('u.user_suspended', User::USER_NOT_SUSPENDED);
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($sub_query) use ($today) {
                        $sub_query->where('items.item_featured', Item::ITEM_NOT_FEATURED);
                    })
                        ->orWhere(function ($sub_query) use ($today) {
                            $sub_query->where('items.item_featured', Item::ITEM_FEATURED)
                                ->where('s.subscription_end_date', '!=', null)
                                ->where('s.subscription_end_date', '<=', $today);
                        });
                })
                ->distinct('items.id')
                ->orderBy('items.created_at', 'DESC');
            $total_free_items = $free_items_query->count();

            if ($total_free_items == 0 || $total_paid_items == 0) {
                $paid_items = $paid_items_query->paginate(10);
                $free_items = $free_items_query->paginate(10);

                if ($total_free_items == 0) {
                    $pagination = $paid_items;
                }
                if ($total_paid_items == 0) {
                    $pagination = $free_items;
                }
            } else {
                $num_of_pages = ceil(($total_paid_items + $total_free_items) / 10);
                $paid_items_per_page = ceil($total_paid_items / $num_of_pages) < 4 ? 4 : ceil($total_paid_items / $num_of_pages);
                $free_items_per_page = 10 - $paid_items_per_page;

                $paid_items = $paid_items_query->paginate($paid_items_per_page);
                $free_items = $free_items_query->paginate($free_items_per_page);

                if (ceil($total_paid_items / $paid_items_per_page) > ceil($total_free_items / $free_items_per_page)) {
                    $pagination = $paid_items;
                } else {
                    $pagination = $free_items;
                }
            }
            /**
             * End do listing query
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_LISTING_RESULTS_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $all_cities = City::whereHas('items', function ($query) use ($state, $settings, $site_prefer_country_id) {
                $query->where('state_id', $state->id)
                    ;
            }, '>', 0)->orderBy('city_name')->get();

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.city',
                compact('state', 'city', 'paid_items', 'free_items', 'pagination', 'all_cities', 'search_cities_json',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                    'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                    'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                    'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function product(Request $request, string $item_slug, string $product_slug)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
            
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            // validate product record
            $product = Product::where('product_slug', $product_slug)
                ->where('product_status', Product::STATUS_APPROVED)
                ->get()->first();

            if ($product) {
                // validate if the item has collected the product in the listing page
                if ($item->hasCollectedProduct($product)) {
                    /**
                     * Start SEO
                     */
                    SEOMeta::setTitle($product->product_name . ' - ' . $item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
                    SEOMeta::setDescription($product->product_description);
                    SEOMeta::setCanonical(URL::current());
                    SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);

                    // OpenGraph
                    OpenGraph::setTitle($product->product_name . ' - ' . $item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
                    OpenGraph::setDescription($product->product_description);
                    OpenGraph::setUrl(URL::current());
                    if (empty($product->product_image_large)) {
                        OpenGraph::addImage(asset('frontend/images/placeholder/full_item_feature_image.webp'));
                    } else {
                        OpenGraph::addImage(Storage::disk('public')->url('product/' . $product->product_image_large));
                    }

                    // Twitter
                    TwitterCard::setTitle($product->product_name . ' - ' . $item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
                    /**
                     * End SEO
                     */

                    $item_display_categories = $item->getAllCategories(Item::ITEM_TOTAL_SHOW_CATEGORY);
                    $item_total_categories = $item->allCategories()->count();
                    $item_all_categories = $item->getAllCategories();

                    $item_count_rating = $item->getCountRating();
                    $item_average_rating = $item->item_average_rating;

                    $product_features = $product->productFeatures()
                        ->orderBy('product_feature_order')
                        ->get();

                    /**
                     * get 6 nearby items by current item lat and lng
                     */
                    $latitude = $item->item_lat;
                    $longitude = $item->item_lng;

                    $nearby_items = Item::selectRaw('items.*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                        ->where('id', '!=', $item->id)
                        
                        ->where('item_status', Item::ITEM_PUBLISHED)
                        ->having('distance', '<', 500)
                        ->orderBy('distance')
                        ->orderBy('created_at', 'DESC')
                        ->take(6)->get();

                    /**
                     * get 6 similar items by current item lat and lng
                     */
                    $item_category_ids = array();
                    foreach ($item_all_categories as $key => $category) {
                        $item_category_ids[] = $category->id;
                    }

                    $similar_items = \Illuminate\Support\Facades\DB::table('category_item')
                        ->whereIn('category_id', $item_category_ids)
                        ->distinct('item_id')
                        ->get();

                    $similar_item_ids = array();
                    foreach ($similar_items as $key => $similar_item) {
                        $similar_item_ids[] = $similar_item->item_id;
                    }

                    $similar_items = Item::join('category_item as ci', 'ci.item_id', '=', 'items.id')
                        ->selectRaw('items.*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                        ->whereIn('items.id', $similar_item_ids)
                        ->where('items.id', '!=', $item->id)
                        ->where('items.item_status', Item::ITEM_PUBLISHED)
                        ->where('items.state_id', $item->state_id)
                        
                        ->having('distance', '<', 500)
                        ->distinct('items.id')
                        ->orderBy('distance')
                        ->orderBy('items.created_at', 'DESC')
                        ->take(6)->get();

                    /**
                     * Start item claim
                     */
                    $item_has_claimed = $item->hasClaimed();
                    /**
                     * End item claim
                     */

                    /**
                     * Start fetch ads blocks
                     */
                    $advertisement = new Advertisement();

                    $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                        Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                        Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                        Advertisement::AD_STATUS_ENABLE
                    );

                    $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                        Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                        Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                        Advertisement::AD_STATUS_ENABLE
                    );
                    /**
                     * End fetch ads blocks
                     */

                    /**
                     * initial search bar
                     */
                    $states_cities_array = $this->getStatesCitiesJson();
                    $search_cities_json = json_encode($states_cities_array['cities']);

                    return response()->view('frontend.product',
                        compact('product', 'item', 'product_features', 'nearby_items', 'similar_items', 'search_cities_json',
                            'ads_before_sidebar_content', 'ads_after_sidebar_content', 'item_display_categories',
                            'item_total_categories', 'item_all_categories', 'item_count_rating', 'item_average_rating',
                            'item_has_claimed'));
                } else {
                    abort(404);
                }
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    /**
     * @param Request $request
     * @param string $item_slug
     * @return Response
     */
    public function item(Request $request, string $item_slug)
    {
        $settings = app('site_global_settings');
        //$site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
        //
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            /**
             * Start SEO
             */
            SEOMeta::setTitle($item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            SEOMeta::setDescription($item->item_description);
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);

            // OpenGraph
            OpenGraph::setTitle($item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            OpenGraph::setDescription($item->item_description);
            OpenGraph::setUrl(URL::current());
            if (empty($item->item_image)) {
                OpenGraph::addImage(asset('frontend/images/placeholder/full_item_feature_image.webp'));
            } else {
                OpenGraph::addImage(Storage::disk('public')->url('item/' . $item->item_image));
            }

            // Twitter
            TwitterCard::setTitle($item->item_title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            /**
             * End SEO
             */

            $item_display_categories = $item->getAllCategories(Item::ITEM_TOTAL_SHOW_CATEGORY);
            $item_total_categories = $item->allCategories()->count();
            $item_all_categories = $item->getAllCategories();

            /**
             * Start initla item features
             */
            $item_features = $item->features()->where('item_feature_value', '<>', '')
                ->whereNotNull('item_feature_value')
                ->get();
            /**
             * End initial item features
             */

            /**
             * get 6 nearby items by current item lat and lng
             */
            $latitude = $item->item_lat;
            $longitude = $item->item_lng;

            $nearby_items = Item::selectRaw('items.*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->where('id', '!=', $item->id)
            //
                ->where('item_status', Item::ITEM_PUBLISHED)
                ->having('distance', '<', 500)
                ->orderBy('distance')
                ->orderBy('created_at', 'DESC')
                ->take(6)->get();

            /**
             * get 6 similar items by current item lat and lng
             */
            $item_category_ids = array();
            foreach ($item_all_categories as $key => $category) {
                $item_category_ids[] = $category->id;
            }

            $similar_items = \Illuminate\Support\Facades\DB::table('category_item')
                ->whereIn('category_id', $item_category_ids)
                ->distinct('item_id')
                ->get();

            $similar_item_ids = array();
            foreach ($similar_items as $key => $similar_item) {
                $similar_item_ids[] = $similar_item->item_id;
            }

            $similar_items = Item::join('category_item as ci', 'ci.item_id', '=', 'items.id')
                ->selectRaw('items.*, ( 6367 * acos( cos( radians( ? ) ) * cos( radians( item_lat ) ) * cos( radians( item_lng ) - radians( ? ) ) + sin( radians( ? ) ) * sin( radians( item_lat ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
                ->whereIn('items.id', $similar_item_ids)
                ->where('items.id', '!=', $item->id)
                ->where('items.item_status', Item::ITEM_PUBLISHED)
                ->where('items.state_id', $item->state_id)
            //
                ->having('distance', '<', 500)
                ->distinct('items.id')
                ->orderBy('distance')
                ->orderBy('items.created_at', 'DESC')
                ->take(6)->get();

            /**
             * Start get all item approved reviews
             */
            $item_count_rating = $item->getCountRating();
            $item_average_rating = $item->item_average_rating;

            $rating_sort_by = empty($request->rating_sort_by) ? Item::ITEM_RATING_SORT_BY_NEWEST : $request->rating_sort_by;
            $reviews = $item->getApprovedRatingsSortBy($rating_sort_by);

            if ($item_count_rating > 0) {
                $item_one_star_count_rating = $item->getStarsCountRating(Item::ITEM_REVIEW_RATING_ONE);
                $item_two_star_count_rating = $item->getStarsCountRating(Item::ITEM_REVIEW_RATING_TWO);
                $item_three_star_count_rating = $item->getStarsCountRating(Item::ITEM_REVIEW_RATING_THREE);
                $item_four_star_count_rating = $item->getStarsCountRating(Item::ITEM_REVIEW_RATING_FOUR);
                $item_five_star_count_rating = $item->getStarsCountRating(Item::ITEM_REVIEW_RATING_FIVE);

                $item_one_star_percentage = ($item_one_star_count_rating / $item_count_rating) * 100;
                $item_two_star_percentage = ($item_two_star_count_rating / $item_count_rating) * 100;
                $item_three_star_percentage = ($item_three_star_count_rating / $item_count_rating) * 100;
                $item_four_star_percentage = ($item_four_star_count_rating / $item_count_rating) * 100;
                $item_five_star_percentage = ($item_five_star_count_rating / $item_count_rating) * 100;
            } else {
                $item_one_star_percentage = 0;
                $item_two_star_percentage = 0;
                $item_three_star_percentage = 0;
                $item_four_star_percentage = 0;
                $item_five_star_percentage = 0;
            }
            /**
             * End get all item approved reviews
             */

            /**
             * Start item claim
             */
            $item_has_claimed = $item->hasClaimed();
            /**
             * End item claim
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_gallery = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_GALLERY,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_description = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_DESCRIPTION,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_location = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_LOCATION,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_features = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_FEATURES,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_reviews = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_REVIEWS,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_comments = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_COMMENTS,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_share = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_BEFORE_SHARE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_share = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_AFTER_SHARE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BUSINESS_LISTING_PAGE,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            /**
             * Start fetch item sections
             */
            $item_sections_after_breadcrumb = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_BREADCRUMB)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_gallery = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_GALLERY)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_description = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_DESCRIPTION)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_location_map = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_LOCATION_MAP)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_features = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_FEATURES)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_reviews = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_REVIEWS)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_comments = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_COMMENTS)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();

            $item_sections_after_share = $item->itemSections()
                ->where('item_section_position', ItemSection::POSITION_AFTER_SHARE)
                ->where('item_section_status', ItemSection::STATUS_PUBLISHED)
                ->orderBy('item_section_order')
                ->get();
            /**
             * End fetch item sections
             */

            /**
             * initial search bar
             */
            $states_cities_array = $this->getStatesCitiesJson();
            $search_cities_json = json_encode($states_cities_array['cities']);

            return response()->view('frontend.item', compact('item', 'nearby_items',
                'similar_items', 'search_cities_json',
                'reviews',
                'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_gallery', 'ads_before_description',
                'ads_before_location', 'ads_before_features', 'ads_before_reviews', 'ads_before_comments',
                'ads_before_share', 'ads_after_share', 'ads_before_sidebar_content', 'ads_after_sidebar_content',
                'item_display_categories', 'item_total_categories', 'item_all_categories', 'item_count_rating',
                'item_average_rating', 'item_one_star_percentage', 'item_two_star_percentage', 'item_three_star_percentage',
                'item_four_star_percentage', 'item_five_star_percentage', 'rating_sort_by', 'item_has_claimed',
                'item_sections_after_breadcrumb', 'item_sections_after_gallery', 'item_sections_after_description',
                'item_sections_after_location_map', 'item_sections_after_features', 'item_sections_after_reviews',
                'item_sections_after_comments', 'item_sections_after_share', 'item_features'));
        } else {
            abort(404);
        }
    }

    public function emailItem(string $item_slug, Request $request)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
            
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            if (Auth::check()) {
                $request->validate([
                    'item_share_email_name' => 'required|max:255',
                    'item_share_email_from_email' => 'required|email|max:255',
                    'item_share_email_to_email' => 'required|email|max:255',
                ]);

                // send an email notification to admin
                $email_to = $request->item_share_email_to_email;
                $email_from_name = $request->item_share_email_name;
                $email_note = $request->item_share_email_note;
                $email_subject = __('frontend.item.send-email-subject', ['name' => $email_from_name]);

                $email_notify_message = [
                    __('frontend.item.send-email-body', ['from_name' => $email_from_name, 'url' => route('page.item', $item->item_slug)]),
                    __('frontend.item.send-email-note'),
                    $email_note,
                ];

                try
                {
                    // to admin
                    Mail::to($email_to)->send(
                        new Notification(
                            $email_subject,
                            $email_to,
                            null,
                            $email_notify_message,
                            __('frontend.item.view-listing'),
                            'success',
                            route('page.item', $item->item_slug)
                        )
                    );

                    \Session::flash('flash_message', __('frontend.item.send-email-success'));
                    \Session::flash('flash_type', 'success');

                } catch (\Exception $e) {
                    Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
                    $error_message = $e->getMessage();

                    \Session::flash('flash_message', $error_message);
                    \Session::flash('flash_type', 'danger');
                }

                return redirect()->route('page.item', $item->item_slug);
            } else {
                \Session::flash('flash_message', __('frontend.item.send-email-error-login'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('page.item', $item->item_slug);
            }
        } else {
            abort(404);
        }

    }

    public function saveItem(Request $request, string $item_slug)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
            
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            if (Auth::check()) {
                $login_user = Auth::user();

                if ($login_user->hasSavedItem($item->id)) {
                    \Session::flash('flash_message', __('frontend.item.save-item-error-exist'));
                    \Session::flash('flash_type', 'danger');

                    return redirect()->route('page.item', $item->item_slug);
                } else {
                    $login_user->savedItems()->attach($item->id);

                    \Session::flash('flash_message', __('frontend.item.save-item-success'));
                    \Session::flash('flash_type', 'success');

                    return redirect()->route('page.item', $item->item_slug);
                }

                //return response()->json(['success' => __('frontend.item.save-item-success')]);
            } else {
                \Session::flash('flash_message', __('frontend.item.save-item-error-login'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('page.item', $item->item_slug);

                //return response()->json(['error' => __('frontend.item.save-item-error-login')]);
            }
        } else {
            abort(404);
        }
    }

    public function unSaveItem(Request $request, string $item_slug)
    {
        $settings = app('site_global_settings');
        $site_prefer_country_id = app('site_prefer_country_id');

        $item = Item::where('item_slug', $item_slug)
            
            ->where('item_status', Item::ITEM_PUBLISHED)
            ->get()->first();

        if ($item) {
            if (Auth::check()) {
                $login_user = Auth::user();

                if ($login_user->hasSavedItem($item->id)) {
                    $login_user->savedItems()->detach($item->id);

                    \Session::flash('flash_message', __('frontend.item.unsave-item-success'));
                    \Session::flash('flash_type', 'success');

                    return redirect()->route('page.item', $item->item_slug);
                } else {
                    \Session::flash('flash_message', __('frontend.item.unsave-item-error-exist'));
                    \Session::flash('flash_type', 'danger');
                }
            } else {
                \Session::flash('flash_message', __('frontend.item.unsave-item-error-login'));
                \Session::flash('flash_type', 'danger');

                return redirect()->route('page.item', $item->item_slug);

                //return response()->json(['error' => __('frontend.item.save-item-error-login')]);
            }
        } else {
            abort(404);
        }

    }

    public function blog()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.blog', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        /**
         * Start fetch ads blocks
         */
        $advertisement = new Advertisement();

        $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_AFTER_BREADCRUMB,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );

        $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
            Advertisement::AD_PLACE_BLOG_POSTS_PAGES,
            Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
            Advertisement::AD_STATUS_ENABLE
        );
        /**
         * End fetch ads blocks
         */

        $data = [
            'posts' => \Canvas\Post::published()->orderByDesc('published_at')->simplePaginate(10),
        ];

        $all_topics = \Canvas\Topic::orderBy('name')->get();
        $all_tags = \Canvas\Tag::orderBy('name')->get();

        $recent_posts = \Canvas\Post::published()->orderByDesc('published_at')->take(5)->get();

        /**
         * Start inner page header customization
         */
        $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
            ->get()->first()->customization_value;

        $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
            ->get()->first()->customization_value;

        $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
            ->get()->first()->customization_value;

        $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
            ->get()->first()->customization_value;
        /**
         * End inner page header customization
         */

        return response()->view('frontend.blog.index',
            compact('data', 'all_topics', 'all_tags', 'recent_posts',
                'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                'site_innerpage_header_paragraph_font_color'));
    }

    public function blogByTag(string $tag_slug)
    {
        $tag = \Canvas\Tag::where('slug', $tag_slug)->first();

        if ($tag) {

            $settings = app('site_global_settings');

            /**
             * Start SEO
             */
            SEOMeta::setTitle(__('seo.frontend.blog-tag', ['tag_name' => $tag->name, 'site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TAG_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $data = [
                'posts' => \Canvas\Post::whereHas('tags', function ($query) use ($tag_slug) {
                    $query->where('slug', $tag_slug);
                })->published()->orderByDesc('published_at')->simplePaginate(10),
            ];

            $all_topics = \Canvas\Topic::orderBy('name')->get();
            $all_tags = \Canvas\Tag::orderBy('name')->get();

            $recent_posts = \Canvas\Post::published()->orderByDesc('published_at')->take(5)->get();

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.blog.tag',
                compact('tag', 'data', 'all_topics', 'all_tags', 'recent_posts',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                    'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                    'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                    'site_innerpage_header_paragraph_font_color'));

        } else {
            abort(404);
        }
    }

    public function blogByTopic(string $topic_slug)
    {
        $topic = \Canvas\Topic::where('slug', $topic_slug)->first();

        if ($topic) {

            $settings = app('site_global_settings');

            /**
             * Start SEO
             */
            SEOMeta::setTitle(__('seo.frontend.blog-topic', ['topic_name' => $topic->name, 'site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_BLOG_TOPIC_PAGES,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $data = [
                'posts' => \Canvas\Post::whereHas('topic', function ($query) use ($topic_slug) {
                    $query->where('slug', $topic_slug);
                })->published()->orderByDesc('published_at')->simplePaginate(10),
            ];

            $all_topics = \Canvas\Topic::orderBy('name')->get();
            $all_tags = \Canvas\Tag::orderBy('name')->get();

            $recent_posts = \Canvas\Post::published()->orderByDesc('published_at')->take(5)->get();

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.blog.topic',
                compact('topic', 'data', 'all_topics', 'all_tags', 'recent_posts',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_content', 'ads_after_content',
                    'ads_before_sidebar_content', 'ads_after_sidebar_content', 'site_innerpage_header_background_type',
                    'site_innerpage_header_background_color', 'site_innerpage_header_background_image',
                    'site_innerpage_header_background_youtube_video', 'site_innerpage_header_title_font_color',
                    'site_innerpage_header_paragraph_font_color'));

        } else {
            abort(404);
        }
    }

    public function blogPost(string $blog_slug)
    {
        $posts = \Canvas\Post::with('tags', 'topic')->published()->get();
        //$posts = BlogPost::with('tags', 'topic')->published()->get();
        $post = $posts->firstWhere('slug', $blog_slug);

        if (optional($post)->published) {

            $settings = app('site_global_settings');

            /**
             * Start SEO
             */
            SEOMeta::setTitle($post->title . ' - ' . (empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            /**
             * Start fetch ads blocks
             */
            $advertisement = new Advertisement();

            $ads_before_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_breadcrumb = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_AFTER_BREADCRUMB,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_feature_image = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_FEATURE_IMAGE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_title = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_TITLE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_post_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_POST_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_post_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_AFTER_POST_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_comments = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_COMMENTS,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_share = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_BEFORE_SHARE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_share = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_AFTER_SHARE,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_before_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_SIDEBAR_BEFORE_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );

            $ads_after_sidebar_content = $advertisement->fetchAdvertisements(
                Advertisement::AD_PLACE_SINGLE_POST_PAGE,
                Advertisement::AD_POSITION_SIDEBAR_AFTER_CONTENT,
                Advertisement::AD_STATUS_ENABLE
            );
            /**
             * End fetch ads blocks
             */

            $data = [
                'author' => $post->user,
                'post' => $post,
                'meta' => $post->meta,
            ];

            // IMPORTANT: This event must be called for tracking visitor/view traffic
            event(new \Canvas\Events\PostViewed($post));

            $all_topics = \Canvas\Topic::orderBy('name')->get();
            $all_tags = \Canvas\Tag::orderBy('name')->get();

            $recent_posts = \Canvas\Post::published()->orderByDesc('published_at')->take(5)->get();

            // used for comment
            $blog_post = BlogPost::published()->get()->firstWhere('slug', $blog_slug);

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.blog.show',
                compact('data', 'all_topics', 'all_tags', 'blog_post', 'recent_posts',
                    'ads_before_breadcrumb', 'ads_after_breadcrumb', 'ads_before_feature_image',
                    'ads_before_title', 'ads_before_post_content', 'ads_after_post_content',
                    'ads_before_comments', 'ads_before_share', 'ads_after_share', 'ads_before_sidebar_content',
                    'ads_after_sidebar_content', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            abort(404);
        }
    }

    public function jsonGetCitiesByState(int $state_id)
    {
        $state = State::findOrFail($state_id);
        $cities = $state->cities()->select('id', 'city_name')->orderBy('city_name')->get()->toJson();

        return response()->json($cities);
    }

    public function jsonGetStatesByCountry(int $country_id)
    {
        $country = Country::findOrFail($country_id);
        $states = $country->states()->select('id', 'state_name')->orderBy('state_name')->get()->toJson();

        return response()->json($states);
    }

    public function jsonDeleteItemImageGallery(int $item_image_gallery_id)
    {
        $item_image_gallery = ItemImageGallery::findOrFail($item_image_gallery_id);

        Gate::authorize('delete-item-image-gallery', $item_image_gallery);

        if (Storage::disk('public')->exists('item/gallery/' . $item_image_gallery->item_image_gallery_name)) {
            Storage::disk('public')->delete('item/gallery/' . $item_image_gallery->item_image_gallery_name);
        }

        if (!empty($item_image_gallery->item_image_gallery_thumb_name) && Storage::disk('public')->exists('item/gallery/' . $item_image_gallery->item_image_gallery_thumb_name)) {
            Storage::disk('public')->delete('item/gallery/' . $item_image_gallery->item_image_gallery_thumb_name);
        }

        $item_image_gallery->delete();

        return response()->json(['success' => 'item image gallery deleted.']);
    }

    public function jsonDeleteReviewImageGallery(int $review_image_gallery_id)
    {

        if (!Auth::check()) {
            return response()->json(['error' => 'user not login']);
        }

        $review_image_gallery = DB::table('review_image_galleries')
            ->where('id', $review_image_gallery_id)
            ->get();

        if ($review_image_gallery->count() == 0) {
            return response()->json(['error' => 'review image gallery not found.']);
        }

        $review_image_gallery = $review_image_gallery->first();

        $review_id = $review_image_gallery->review_id;

        $review = DB::table('reviews')
            ->where('id', $review_id)
            ->get();

        if ($review->count() == 0) {
            return response()->json(['error' => 'review not found.']);
        }

        $review = $review->first();

        if (Auth::user()->id != $review->author_id) {
            return response()->json(['error' => 'you cannot delete review image gallery which does not belong to you.']);
        }

        if (Storage::disk('public')->exists('item/review/' . $review_image_gallery->review_image_gallery_name)) {
            Storage::disk('public')->delete('item/review/' . $review_image_gallery->review_image_gallery_name);
        }

        if (Storage::disk('public')->exists('item/review/' . $review_image_gallery->review_image_gallery_thumb_name)) {
            Storage::disk('public')->delete('item/review/' . $review_image_gallery->review_image_gallery_thumb_name);
        }

        DB::table('review_image_galleries')
            ->where('id', $review_image_gallery_id)
            ->delete();

        return response()->json(['success' => 'review image gallery deleted.']);
    }

    public function ajaxLocationSave(string $lat, string $lng)
    {
        session(['user_device_location_lat' => $lat]);
        session(['user_device_location_lng' => $lng]);

        return response()->json(['success' => 'location lat & lng saved to session']);
    }

    public function termsOfService()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.terms-service', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        if ($settings->setting_page_terms_of_service_enable == Setting::TERM_PAGE_ENABLED) {
            $terms_of_service = $settings->setting_page_terms_of_service;

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.terms-of-service',
                compact('terms_of_service', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            return redirect()->route('page.home');
        }
    }

    public function privacyPolicy()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.frontend.privacy-policy', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        if ($settings->setting_page_privacy_policy_enable == Setting::PRIVACY_PAGE_ENABLED) {
            $privacy_policy = $settings->setting_page_privacy_policy;

            /**
             * Start inner page header customization
             */
            $site_innerpage_header_background_type = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_TYPE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_image = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_IMAGE)
                ->get()->first()->customization_value;

            $site_innerpage_header_background_youtube_video = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_BACKGROUND_YOUTUBE_VIDEO)
                ->get()->first()->customization_value;

            $site_innerpage_header_title_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_TITLE_FONT_COLOR)
                ->get()->first()->customization_value;

            $site_innerpage_header_paragraph_font_color = Customization::where('customization_key', Customization::SITE_INNERPAGE_HEADER_PARAGRAPH_FONT_COLOR)
                ->get()->first()->customization_value;
            /**
             * End inner page header customization
             */

            return response()->view('frontend.privacy-policy',
                compact('privacy_policy', 'site_innerpage_header_background_type', 'site_innerpage_header_background_color',
                    'site_innerpage_header_background_image', 'site_innerpage_header_background_youtube_video',
                    'site_innerpage_header_title_font_color', 'site_innerpage_header_paragraph_font_color'));
        } else {
            return redirect()->route('page.home');
        }
    }

    /**
     * Update site language by the request of website footer language selector
     * @param Request $request
     * @param string $user_prefer_language
     * @return RedirectResponse
     */
    public function updateLocale(Request $request, string $user_prefer_language)
    {

//        $request->validate([
        //            'user_prefer_language' => 'nullable|max:5',
        //        ]);

        //$user_prefer_language = $request->user_prefer_language;

        if (Auth::check()) {
            $login_user = Auth::user();
            $login_user->user_prefer_language = $user_prefer_language;
            $login_user->save();
            Session::put('user_prefer_language', $user_prefer_language);

            Session::put('lang', $user_prefer_language);

        } else {

            // save to language preference to session.
            Session::put('user_prefer_language', $user_prefer_language);
            Session::put('lang', $user_prefer_language);

        }
        //  dd(Session::get('lang'));
        return redirect()->back();
    }

    /**
     * Update site country by the request of website footer country selector
     * @param Request $request
     * @param int $user_prefer_country_id
     * @return RedirectResponse
     */
    public function updateCountry(Request $request, int $user_prefer_country_id)
    {
//        $request->validate([
        //            'user_prefer_country_id' => 'nullable|numeric',
        //        ]);

        //$user_prefer_country_id = $request->user_prefer_country_id;

        $country_exist = Country::find($user_prefer_country_id);
        if ($country_exist) {
            if (Auth::check()) {
                $login_user = Auth::user();
                $login_user->user_prefer_country_id = $country_exist->id;
                $login_user->save();
            } else {
                // save to language preference to session.
                Session::put('user_prefer_country_id', $country_exist->id);
            }
        }

        return redirect()->back();
    }

    /**
     * @param int $product_image_gallery_id
     * @return JsonResponse
     */
    public function jsonDeleteProductImageGallery(int $product_image_gallery_id)
    {
        $product_image_gallery = ProductImageGallery::findOrFail($product_image_gallery_id);

        Gate::authorize('delete-product-image-gallery', $product_image_gallery);

        if (Storage::disk('public')->exists('product/gallery/' . $product_image_gallery->product_image_gallery_name)) {
            Storage::disk('public')->delete('product/gallery/' . $product_image_gallery->product_image_gallery_name);
        }

        if (Storage::disk('public')->exists('product/gallery/' . $product_image_gallery->product_image_gallery_thumb_name)) {
            Storage::disk('public')->delete('product/gallery/' . $product_image_gallery->product_image_gallery_thumb_name);
        }

        $product_image_gallery->delete();

        return response()->json(['success' => 'product image gallery deleted.']);
    }

}
