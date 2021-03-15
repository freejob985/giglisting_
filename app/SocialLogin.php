<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialLogin extends Model
{
    const SOCIAL_LOGIN_ENABLED = 1;
    const SOCIAL_LOGIN_DISABLED = 0;

    const SOCIAL_LOGIN_FACEBOOK = 'Facebook';
    const SOCIAL_LOGIN_GOOGLE = 'Google';
    const SOCIAL_LOGIN_TWITTER = 'Twitter';
    const SOCIAL_LOGIN_LINKEDIN = 'LinkedIn';
    const SOCIAL_LOGIN_GITHUB = 'GitHub';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'social_logins';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'social_login_provider_name',
        'social_login_provider_client_id',
        'social_login_provider_client_secret',
        'social_login_enabled'
    ];

    public function getFacebookLogin()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_FACEBOOK)
            ->get()->first();
    }

    public function getGoogleLogin()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_GOOGLE)
            ->get()->first();
    }

    public function getTwitterLogin()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_TWITTER)
            ->get()->first();
    }

    public function getLinkedInLogin()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_LINKEDIN)
            ->get()->first();
    }

    public function getGitHubLogin()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_GITHUB)
            ->get()->first();
    }

    public function isFacebookEnabled()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_FACEBOOK)
            ->where('social_login_enabled', self::SOCIAL_LOGIN_ENABLED)
            ->get()
            ->count();
    }

    public function isGoogleEnabled()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_GOOGLE)
            ->where('social_login_enabled', self::SOCIAL_LOGIN_ENABLED)
            ->get()
            ->count();
    }

    public function isTwitterEnabled()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_TWITTER)
            ->where('social_login_enabled', self::SOCIAL_LOGIN_ENABLED)
            ->get()
            ->count();
    }

    public function isLinkedInEnabled()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_LINKEDIN)
            ->where('social_login_enabled', self::SOCIAL_LOGIN_ENABLED)
            ->get()
            ->count();
    }

    public function isGitHubEnabled()
    {
        return SocialLogin::where('social_login_provider_name', self::SOCIAL_LOGIN_GITHUB)
            ->where('social_login_enabled', self::SOCIAL_LOGIN_ENABLED)
            ->get()
            ->count();
    }

}
