<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Subscription extends Model
{
    const PAY_METHOD_PAYPAL = 'PayPal';
    const PAY_METHOD_RAZORPAY = 'Razorpay';
    const PAY_METHOD_STRIPE = 'Stripe';
    const PAY_METHOD_BANK_TRANSFER = 'Bank Transfer';
    const PAY_METHOD_PAYUMONEY = 'PayUMoney';

    const PAID_SUBSCRIPTION_LEFT_DAYS = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'plan_id', 'subscription_start_date', 'subscription_end_date', 'subscription_max_featured_listing',
        'subscription_paypal_profile_id', 'subscription_razorpay_plan_id', 'subscription_razorpay_subscription_id',
        'subscription_pay_method', 'subscription_stripe_customer_id', 'subscription_stripe_subscription_id',
        'subscription_stripe_future_plan_id',
    ];

    /**
     * Get the plan that owns the subscription.
     */
    public function plan()
    {
        return $this->belongsTo('App\Plan');
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice');
    }

    /**
     * @param int $plan_id
     * @param int $subscription_id
     * @param int $user_id
     * @return bool
     */
    public function planSubscriptionValidation($plan_id, $subscription_id, $user_id)
    {
        $validation = true;
        $plan_id_exist = Plan::where('id', $plan_id)
            ->where('plan_type', Plan::PLAN_TYPE_PAID)
            ->where('plan_status', Plan::PLAN_ENABLED)
            ->get()->count();
        if($plan_id_exist == 0)
        {
            $validation =  false;
        }
        $subscription_id_exist = Subscription::where('id', $subscription_id)
            ->where('user_id', $user_id)
            ->get()->count();
        if($subscription_id_exist == 0)
        {
            $validation =  false;
        }

        return $validation;
    }
}
