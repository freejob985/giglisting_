<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plan;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
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
        SEOMeta::setTitle(__('seo.backend.admin.plan.plans', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        $all_plans = Plan::whereIn('plan_type', [Plan::PLAN_TYPE_FREE, Plan::PLAN_TYPE_PAID])
            ->orderBy('plan_type')
            ->orderBy('plan_period')
            ->get();

        return response()->view('backend.admin.plan.index', compact('all_plans'));
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
        SEOMeta::setTitle(__('seo.backend.admin.plan.create-plan', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
        SEOMeta::setDescription('');
        SEOMeta::setCanonical(URL::current());
        SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
        /**
         * End SEO
         */

        return response()->view('backend.admin.plan.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_name' => 'required|max:255',
            'plan_price' => 'required|numeric',
            'plan_status' => 'required|numeric',
            'plan_period' => 'nullable|numeric',
            'plan_max_featured_listing' => 'nullable|numeric',

        ]);
        $plan_name = $request->plan_name;
        $plan_features = $request->plan_features;
        $plan_price = $request->plan_price;
        $plan_status = empty($request->plan_status) ? 0 : 1;
        $plan_max_featured_listing = empty($request->plan_max_featured_listing) ? null : $request->plan_max_featured_listing;

        $plan_period = $request->plan_period;
        if($plan_period != \App\Plan::PLAN_MONTHLY && $plan_period != \App\Plan::PLAN_QUARTERLY && $plan_period != \App\Plan::PLAN_YEARLY)
        {
            throw ValidationException::withMessages(
                [
                    'plan_period' => 'Plan period must in monthly, quarterly, or yearly.',
                ]);
        }

        $plan = new Plan();
        $plan->plan_type = Plan::PLAN_TYPE_PAID;
        $plan->plan_name = $plan_name;
        $plan->plan_features = $plan_features;
        $plan->plan_price = $plan_price;
        $plan->plan_status = $plan_status;
        $plan->plan_period = $plan_period;
        $plan->plan_max_featured_listing = $plan_max_featured_listing;
        $plan->save();

        \Session::flash('flash_message', __('alert.plan-created'));
        \Session::flash('flash_type', 'success');

        return redirect()->route('admin.plans.edit', compact('plan'));
    }

    /**
     * Display the specified resource.
     *
     * @param Plan $plan
     * @return RedirectResponse
     */
    public function show(Plan $plan)
    {
        return redirect()->route('admin.plans.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Plan $plan
     * @return Response
     */
    public function edit(Plan $plan)
    {
        if($plan->plan_type != Plan::PLAN_TYPE_ADMIN)
        {
            $settings = app('site_global_settings');

            /**
             * Start SEO
             */
            SEOMeta::setTitle(__('seo.backend.admin.plan.edit-plan', ['site_name' => empty($settings->setting_site_name) ? config('app.name', 'Laravel') : $settings->setting_site_name]));
            SEOMeta::setDescription('');
            SEOMeta::setCanonical(URL::current());
            SEOMeta::addKeyword($settings->setting_site_seo_home_keywords);
            /**
             * End SEO
             */

            return response()->view('backend.admin.plan.edit', compact('plan'));
        }
        else
        {
            return redirect()->route('admin.plans.index');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Plan $plan
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, Plan $plan)
    {
        if($plan->plan_type == Plan::PLAN_TYPE_PAID)
        {
            $request->validate([
                'plan_name' => 'required|max:255',
                'plan_price' => 'required|numeric',
                'plan_status' => 'required|numeric',
                'plan_period' => 'nullable|numeric',
                'plan_max_featured_listing' => 'nullable|numeric',

            ]);

            $plan_name = $request->plan_name;
            $plan_features = $request->plan_features;
            $plan_price = $request->plan_price;
            $plan_status = empty($request->plan_status) ? 0 : 1;

            $plan_period = $request->plan_period;
            if($plan_period != \App\Plan::PLAN_MONTHLY && $plan_period != \App\Plan::PLAN_QUARTERLY && $plan_period != \App\Plan::PLAN_YEARLY)
            {
                throw ValidationException::withMessages(
                    [
                        'plan_period' => 'Must in monthly, quarterly, or yearly.',
                    ]);
            }
            $plan_max_featured_listing = empty($request->plan_max_featured_listing) ? null : $request->plan_max_featured_listing;

            $plan->plan_name = $plan_name;
            $plan->plan_features = $plan_features;
            $plan->plan_price = $plan_price;
            $plan->plan_status = $plan_status;
            $plan->plan_period = $plan_period;
            $plan->plan_max_featured_listing = $plan_max_featured_listing;
            $plan->save();

            \Session::flash('flash_message', __('alert.plan-updated'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.plans.edit', compact('plan'));

        }
        elseif($plan->plan_type == Plan::PLAN_TYPE_FREE)
        {
            $request->validate([
                'plan_name' => 'required|max:255',
            ]);

            $plan_name = $request->plan_name;
            $plan_features = $request->plan_features;

            $plan->plan_name = $plan_name;
            $plan->plan_features = $plan_features;
            $plan->save();

            \Session::flash('flash_message', __('alert.plan-updated'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.plans.edit', compact('plan'));
        }
        else
        {
            return redirect()->route('admin.plans.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Plan $plan
     * @return RedirectResponse
     */
    public function destroy(Plan $plan)
    {
        if($plan->subscriptions()->get()->count() > 0)
        {
            \Session::flash('flash_message', __('alert.plan-delete-error-user'));
            \Session::flash('flash_type', 'danger');

            return redirect()->route('admin.plans.edit', $plan);
        }
        else
        {
            $plan->delete();

            \Session::flash('flash_message', __('alert.plan-deleted'));
            \Session::flash('flash_type', 'success');

            return redirect()->route('admin.plans.index');
        }
    }
}
