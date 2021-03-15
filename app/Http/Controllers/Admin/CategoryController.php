<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use Artesaos\SEOTools\Facades\SEOMeta;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Session;

class CategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.category.categories', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

       // $all_categories = Category::all();
        $all_categories  = Category::where('lang',Session::get('lang'))->orderBy('category_name')->get();

        return response()->view('backend.admin.category.index', compact('all_categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.category.create-category', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $printable_categories = new Category();
        $printable_categories = $printable_categories->getPrintableCategories();

        return response()->view('backend.admin.category.create', compact('printable_categories'));
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
        $request->validate([
            'category_name' => 'required|unique:categories,category_name|max:255',
            'category_slug' => 'required|unique:categories,category_slug|regex:/^[\w-]*$/|max:255',
            'category_parent_id' => 'required|numeric',
            'category_description' => 'nullable|max:255',
        ]);

        $validate_error = array();

        $category_parent_id = empty($request->category_parent_id) ? null : $request->category_parent_id;
        if (!empty($category_parent_id)) {
            $category_exist = Category::where('id', $category_parent_id)
            ->where('lang',Session::get('user_prefer_language'))->get()->count();

            if ($category_exist == 0) {
                $validate_error['category_parent_id'] = __('categories.create-cat-not-found');
            }
        }

        if (count($validate_error) > 0) {
            throw ValidationException::withMessages($validate_error);
        }

        if ($request->Type == "Link") {
            $Type = $request->Link;

        } else if ($request->Type == "photo") {

            if ($request->hasFile('photo')) {
                $file = $request->photo;
                $extension = $file->getClientOriginalExtension();
                $Type = rand(111, 99999) . "_mrbean" . '.' . $extension;
                //$file->move("assets/front/img/Logo/", $filename);
                $file->move(public_path() . '/files/', $Type);

            } else {

                //  $filename = "";
                $Type = "";

            }
        } else {
            $Type = $request->category_icon;
        }

        $category = new Category();
        $category->category_name = $request->category_name;
        $category->category_slug = strtolower($request->category_slug);
        $category->category_icon = $request->category_icon;
        $category->category_parent_id = $category_parent_id;
        $category->category_description = $request->category_description;
        $category->Type = $request->Type;
        $category->Link = $request->Link;
        $category->photo = $Type;
        $category->lang =  Session::get('lang');
        $category->save();
        \Session::flash('flash_message', __('alert.category-created'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.categories.edit', $category);
    }

    /**
     * Display the specified resource.
     *
     * @param Category $category
     * @return RedirectResponse
     */
    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Category $category
     * @return Response
     */
    public function edit(Category $category)
    {
        $settings = app('site_global_settings');

        /**
         * Start SEO
         */
        SEOMeta::setTitle(__('seo.backend.admin.category.edit-category', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $printable_categories = new Category();
        $printable_categories = $printable_categories->getPrintableCategories();

        return response()->view('backend.admin.category.edit',
            compact('category', 'printable_categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Category $category
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'category_name' => 'required|max:255',
            'category_slug' => 'required|regex:/^[\w-]*$/|max:255',
            'category_parent_id' => 'required|numeric',
            'category_description' => 'nullable|max:255',
        ]);

        $category_name = $request->category_name;
        $category_slug = strtolower($request->category_slug);
        $category_icon = $request->category_icon;
        $category_parent_id = empty($request->category_parent_id) ? null : $request->category_parent_id;
        $category_description = empty($request->category_description) ? null : $request->category_description;

        $validate_error = array();
        $category_name_exist = Category::where('category_name', $category_name)
            ->where('id', '!=', $category->id)->get()->count();
        if ($category_name_exist > 0) {
            $validate_error['category_name'] = __('categories.category-name-taken-error');
        }
        $category_slug_exist = Category::where('category_slug', $category_slug)
            ->where('id', '!=', $category->id)->get()->count();
        if ($category_slug_exist > 0) {
            $validate_error['category_slug'] = __('categories.category-slug-taken-error');
        }

        if (!empty($category_parent_id)) {
            if ($category_parent_id == $category->id) {
                $validate_error['category_parent_id'] = __('categories.self-parent-cat-error');
            }

            $category_exist = Category::where('id', $category_parent_id)->get()->count();
            if ($category_exist == 0) {
                $validate_error['category_parent_id'] = __('categories.create-cat-not-found');
            }
        }

        if (count($validate_error) > 0) {
            throw ValidationException::withMessages($validate_error);
        } else {
            //dd("Trace ( 1 )");
            if ($request->Type == "Link") {
                $Type = $request->Link;
    
            } else if ($request->Type == "photo") {
    
                if ($request->hasFile('photo')) {
                    $file = $request->photo;
                    $extension = $file->getClientOriginalExtension();
                    $Type = rand(111, 99999) . "_mrbean" . '.' . $extension;
                    //$file->move("assets/front/img/Logo/", $filename);
                    $file->move(public_path() . '/files/', $Type);
    
                } else {
    
                    //  $filename = "";
                    $Type = "";
    
                }
            } else {
                $Type = $request->category_icon;
            }

            
            $category->category_name = $category_name;
            $category->category_slug = $category_slug;
            $category->category_icon = $category_icon;
            $category->category_parent_id = $category_parent_id;
            $category->category_description = $category_description;
            $category->Type = $request->Type;
            $category->Link = $request->Link;
            $category->photo = $Type;
            $category->lang =  Session::get('lang');
            $category->save();

            \Session::flash('flash_message', __('alert.category-updated'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.categories.edit', $category);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(Category $category)
    {
        // check model relations before delete
        if ($category->allCustomFields()->get()->count() > 0) {
            \Session::flash('flash_message', __('alert.category-delete-error-custom-field'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.categories.edit', $category);
        } elseif ($category->allItems()->get()->count() > 0) {
            \Session::flash('flash_message', __('alert.category-delete-error-listing'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.categories.edit', $category);
        } elseif ($category->children()->get()->count() > 0) {
            \Session::flash('flash_message', __('categories.category-delete-error-children'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.categories.edit', $category);
        } else {
            $category->delete();

            \Session::flash('flash_message', __('alert.category-deleted'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.categories.index');
        }
    }
}
