<?php

namespace App\Http\Controllers;

use App\Category;
use App\Item;
use App\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_index_enable == Setting::SITE_SITEMAP_INDEX_DISABLE)
        {
            abort(404);
        }
        else
        {
            // create new sitemap object
            $sitemap_index = App::make('sitemap');

            if($settings->setting_site_sitemap_page_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.page'));
            }

            if($settings->setting_site_sitemap_category_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.category'));
            }

            if($settings->setting_site_sitemap_listing_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.listing'));
            }

            if($settings->setting_site_sitemap_post_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.post'));
            }

            if($settings->setting_site_sitemap_tag_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.tag'));
            }

            if($settings->setting_site_sitemap_topic_include_to_index == Setting::SITE_SITEMAP_INCLUDE_TO_INDEX)
            {
                $sitemap_index->addSitemap(route('page.sitemap.topic'));
            }

            return $sitemap_index->render('sitemapindex');
        }
    }

    public function page(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_page_enable == Setting::SITE_SITEMAP_PAGE_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_page = App::make('sitemap');

            // start include website pages
            $sitemap_page->add(route('page.home'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);
            $sitemap_page->add(route('page.search'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);

            if($settings->setting_page_about_enable == Setting::ABOUT_PAGE_ENABLED)
            {
                $sitemap_page->add(route('page.about'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);
            }
            if($settings->setting_page_terms_of_service_enable == Setting::TERM_PAGE_ENABLED)
            {
                $sitemap_page->add(route('page.terms-of-service'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);
            }
            if($settings->setting_page_privacy_policy_enable == Setting::PRIVACY_PAGE_ENABLED)
            {
                $sitemap_page->add(route('page.privacy-policy'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);
            }

            $sitemap_page->add(route('page.contact'), $settings->updated_at, '1', $settings->setting_site_sitemap_page_frequency);
            // end include website pages

            // show your sitemap (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
            return $sitemap_page->render($settings->setting_site_sitemap_page_format);
        }
    }

    public function category(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_category_enable == Setting::SITE_SITEMAP_CATEGORY_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_category = App::make('sitemap');

            $sitemap_category->add(route('page.categories'), $settings->updated_at, '1', $settings->setting_site_sitemap_category_frequency);

            $categories = Category::all();

            foreach($categories as $key => $category)
            {
                $sitemap_category->add(route('page.category', ['category_slug' => $category->category_slug]), $category->updated_at, '1', $settings->setting_site_sitemap_category_frequency);
            }

            return $sitemap_category->render($settings->setting_site_sitemap_category_format);
        }
    }

    public function listing(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_listing_enable == Setting::SITE_SITEMAP_LISTING_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_listing = App::make('sitemap');

            $items = Item::where('item_status', Item::ITEM_PUBLISHED)->get();

            foreach($items as $key => $item)
            {
                $sitemap_listing->add(route('page.item', ['item_slug' => $item->item_slug]), $item->updated_at, '1', $settings->setting_site_sitemap_listing_frequency);
            }

            return $sitemap_listing->render($settings->setting_site_sitemap_listing_format);
        }
    }

    public function post(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_post_enable == Setting::SITE_SITEMAP_POST_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_post = App::make('sitemap');

            $posts = \Canvas\Post::published()->orderByDesc('published_at')->get();

            foreach($posts as $key => $post)
            {
                $sitemap_post->add(route('page.blog.show', ['blog_slug' => $post->slug]), $post->updated_at, '1', $settings->setting_site_sitemap_post_frequency);
            }

            return $sitemap_post->render($settings->setting_site_sitemap_post_format);
        }
    }

    public function tag(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_tag_enable == Setting::SITE_SITEMAP_TAG_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_tag = App::make('sitemap');

            $tags = \Canvas\Tag::orderBy('name')->get();

            foreach($tags as $key => $tag)
            {
                $sitemap_tag->add(route('page.blog.tag', ['tag_slug' => $tag->slug]), $tag->updated_at, '1', $settings->setting_site_sitemap_tag_frequency);
            }

            return $sitemap_tag->render($settings->setting_site_sitemap_tag_format);
        }
    }

    public function topic(Request $request)
    {
        $settings = app('site_global_settings');

        if($settings->setting_site_sitemap_topic_enable == Setting::SITE_SITEMAP_TOPIC_DISABLE)
        {
            abort(404);
        }
        else
        {
            $sitemap_topic = App::make('sitemap');

            $topics = \Canvas\Topic::orderBy('name')->get();

            foreach($topics as $key => $topic)
            {
                $sitemap_topic->add(route('page.blog.topic', ['topic_slug' => $topic->slug]), $topic->updated_at, '1', $settings->setting_site_sitemap_topic_frequency);
            }

            return $sitemap_topic->render($settings->setting_site_sitemap_topic_format);
        }
    }
}
