<div class="site-mobile-menu">
    <div class="site-mobile-menu-header">
        <div class="site-mobile-menu-close mt-3">
            <span class="icon-close2 js-menu-toggle"></span>
        </div>
    </div>
    <div class="site-mobile-menu-body"></div>
</div>
<div class="info-bar-area style-three">
    <div class="info-bar-inner">
        <div class="container">
            <div class="row">


                <div class="left-content col-md-6">
                    <ul class="info-items-two">
                        <li>
                            <div class="single-info-item">
                                <div class="icon">
                                    <i class="flaticon-email"></i>
                                </div>
                                <div class="content">
                                    <h5 class="title"> <span class="details">support@gig.com</span></h5>

                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="single-info-item">
                                <div class="icon">
                                    <i class="flaticon-phone-call"></i>
                                </div>
                                <div class="content">
                                    <h5 class="title"> <span class="details">+ 000 11 22 33</span></h5>

                                </div>
                            </div>
                        </li>

                    </ul>
                </div>
                <div class="right-content col-md-3 col-xs-6">
                    <ul class="social-icon">


                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                        <li><a href="#"><i class="fab fa-pinterest-p"></i></a></li>
                    </ul>
                </div>
                <div class="language_dropdown col-md-3 col-xs-6" id="languages_selector">
                    <div class="site-footer " style="padding:0;background:#fff!important;">

                        <div class="btn-group dropup">
                            <button class="btn btn-sm btn-outline-secondary text-white rounded dropdown-toggle"
                                type="button" id="table_option_dropdown_locale" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-language"></i>
                                {{ __('prefer_languages.' . app()->getLocale()) }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right"
                                aria-labelledby="table_option_dropdown_locale">
                                @foreach(\App\Setting::LANGUAGES as $setting_languages_key => $language)
                                <a class="dropdown-item"
                                    href="{{ route('page.locale.update', ['user_prefer_language' => $language]) }}">
                                    {{ __('prefer_languages.' . $language) }}
                                </a>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
<header class="site-navbar container py-0 bg-white customization-header-background-color" role="banner"
    style="max-width:100%!important;">

    <!-- <div class="container"> -->
    <div class="row align-items-center">

        <div class="col-8 col-xl-2 pr-0">

            @if(!empty($site_global_settings->setting_site_logo))
            <h1 class="mb-0 site-logo">
                <a href="http://gigworldgo.com" class="text-black mb-0 customization-header-font-color">
                    @foreach(explode(' ', empty($site_global_settings->setting_site_name) ? config('app.name',
                    'Laravel') : $site_global_settings->setting_site_name) as $key => $word)
                    @if($key/2 == 0)
                    {{ $word }}
                    @else
                    <span class="text-primary">{{ $word }}</span>
                    @endif
                    @endforeach

                </a>
            </h1>
            @else
            <h1 class="mb-0 mt-1 site-logo">
                <a href="http://gigworldgo.com" class="text-black mb-0">
                    <img class="full" style="
                        width: 58% !important;
                    " alt="" data-src="http://gigworldgo.com/assets/uploads/media-uploader/logo1588283745.gif"
                        src="http://gigworldgo.com/assets/uploads/media-uploader/logo1588283745.gif">
                </a>
            </h1>
            @endif


        </div>
        <div class="col-12 col-md-10 d-none d-xl-block">
            <nav class="site-navigation position-relative text-right" role="navigation">

                <ul class="site-menu js-clone-nav mr-auto d-none d-lg-block pl-4">
                    <li>
                        <a href="https://gigworldgo.com">{{ \App\Setting::lang("Home",Session::get('lang')) }} </a>
                    </li>
                    <li class="menu-item-has-children has-children">
                        <a href="https://gigworldgo.com/about">{{ \App\Setting::lang("About Us",Session::get('lang')) }}
                        </a>
                        <ul class="sub-menu dropdown">
                            <li>
                                <a
                                    href="https://gigworldgo.com/about">{{ \App\Setting::lang("About Us",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/p/78/why-gig222">{{ \App\Setting::lang("Why GIG?",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/p/79/the-features-and-advantages-that-gig-brings-to-you222222">{{ \App\Setting::lang("The
                                    features and advantages that GIG brings to you",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/p/80/our-services2">{{ \App\Setting::lang("Our Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/team">{{ \App\Setting::lang("Team",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/career">{{ \App\Setting::lang("Career With Us",Session::get('lang')) }}</a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children has-children">
                        <a href="https://gigworldgo.com/service">{{ \App\Setting::lang("Service",Session::get('lang')) }}
                        </a>
                        <ul class="sub-menu dropdown">
                            <li>
                                <a
                                    href="/gigs/category/1/educational-services">{{ \App\Setting::lang("Educational Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/2/export-services-and-import">{{ \App\Setting::lang("Export Services And Import",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/3/legal-services">{{ \App\Setting::lang("Legal Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/4/real-estate">{{ \App\Setting::lang("Real Estate",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/5/restaurant-requests">{{ \App\Setting::lang("Restaurant Requests",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a href="/gigs/category/6/accounting-and-legal-audit-services">{{ \App\Setting::lang("Accounting And Legal
                                    Audit Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/7/livestock-development">{{ \App\Setting::lang("Livestock Development",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a href="/gigs/category/29/agricultural-investment-services">{{ \App\Setting::lang("Agricultural Investment
                                    Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/31/human-development">{{ \App\Setting::lang("Human Development",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/30/advertising-services">{{ \App\Setting::lang("Advertising Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/32/marriage-service">{{ \App\Setting::lang("Marriage Service",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="/gigs/category/33/visa-services">{{ \App\Setting::lang("Visa Services",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/events">{{ \App\Setting::lang("Events",Session::get('lang')) }}</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="https://gigworldgo.com/our-works">{{ \App\Setting::lang("Our Works",Session::get('lang')) }}
                        </a>
                    </li>
                    <li class="menu-item-has-children has-children">
                        <a href="http://ourstore.gigworldgo.com/">{{ \App\Setting::lang("Our Store",Session::get('lang')) }}
                        </a>
                        <ul class="sub-menu dropdown">
                            <li>
                                <a
                                    href="http://ourstore.gigworldgo.com/">{{ \App\Setting::lang("Our Products",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="http://ourstore.gigworldgo.com/categories">{{ \App\Setting::lang("Our categories",Session::get('lang')) }}</a>
                            </li>
                            <li>
                                <a
                                    href="https://gigworldgo.com/login/admin">{{ \App\Setting::lang("Join as seller",Session::get('lang')) }}</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="https://gigworldgo.com/blog">{{ \App\Setting::lang("Blog",Session::get('lang')) }} </a>
                    </li>
                    <li>
                        <a href="https://gigworldgo.com/contact">
                            {{ \App\Setting::lang("Contact",Session::get('lang')) }}</a>
                    </li>
                    @guest
                    <li class="ml-xl-3 login"><a href="{{ route('login') }}"><span
                                class="border-left pl-xl-4"></span>{{ __('frontend.header.login') }}</a></li>
                    @if (Route::has('register'))
                    <li><a href="{{ route('register') }}">{{ __('frontend.header.register') }}</a></li>
                    @endif
                    @else
                    <li class="has-children">
                        <a href="#">{{ Auth::user()->name }}</a>
                        <ul class="dropdown">
                            <li>
                                @if(Auth::user()->isAdmin())
                                <a href="{{ route('admin.index') }}">{{ __('frontend.header.dashboard') }}</a>
                                @else
                                <a href="{{ route('user.index') }}">{{ __('frontend.header.dashboard') }}</a>
                                @endif
                            </li>
                            <li><a href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('auth.logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </li>
                    @endguest
                    <li>
                        @guest
                        <a href="{{ route('user.items.create') }}" class="cta"><span
                                class="bg-primary text-white rounded"><i class="fas fa-plus mr-1"></i>
                                {{ __('frontend.header.list-business') }}</span></a>
                        @else
                        @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.items.create') }}" class="cta"><span
                                class="bg-primary text-white rounded"><i class="fas fa-plus mr-1"></i>
                                {{ __('frontend.header.list-business') }}</span></a>
                        @else
                        <a href="{{ route('user.items.create') }}" class="cta"><span
                                class="bg-primary text-white rounded"><i class="fas fa-plus mr-1"></i>
                                {{ __('frontend.header.list-business') }}</span></a>
                        @endif
                        @endguest
                    </li>
                </ul>
            </nav>
        </div>


        <div class="d-inline-block d-xl-none ml-auto py-3 col-4 text-right" id="menu-mobile-div">
            <a href="#" class="site-menu-toggle js-menu-toggle text-black"><span class="icon-menu h3"></span></a>
        </div>

    </div>
    <!-- </div> -->

</header>

<style>
    .info-bar-area.style-three .info-bar-inner {
        padding: 10px 0 18px 0;
    }

    .social-icon li {
        margin: 0px 13px;
    }

    .info-bar-area.style-two .info-bar-inner {
        padding: 0;
    }

    .info-bar-area .social-icon .title {
        font-size: 16px;
        line-height: 26px;
        font-weight: 700;
        margin-bottom: 0;
        color: var(--heading-color);
    }

    .info-bar-inner {
        padding: 32px 0 35px 0;
    }



    .info-bar-inner .logo-wrapper .logo {
        margin-top: 5px;
        display: block;
    }

    .info-bar-inner .logo-wrapper .site-title {
        font-size: 35px;
        font-weight: 700;
        margin-top: 10px;
    }

    .info-bar-inner .right-content {
        display: inline-block;
        float: right;
    }

    .info-bar-inner .right-content .request-quote {
        margin-left: 40px;
    }

    .info-bar-inner .right-content .request-quote .rq-btn {
        padding: 15px 25px;
        background-color: var(--main-color-one);
        border-radius: 30px;
        color: #fff;
        font-weight: 600;
        text-transform: capitalize;
        display: block;
        position: relative;
        top: -5px;
        -webkit-transition: all 0.3s ease-in;
        -moz-transition: all 0.3s ease-in;
        -o-transition: all 0.3s ease-in;
        transition: all 0.3s ease-in;
    }

    .info-bar-inner .right-content .request-quote .rq-btn.blank {
        background-color: #f1f1f1;
        border-radius: 0;
        top: 0;
        padding: 15px 40px;
        color: var(--heading-color);
    }

    .info-bar-inner .right-content .request-quote .rq-btn.blank i {
        color: var(--main-color-one);
        -webkit-transition: all 0.3s ease-in;
        -moz-transition: all 0.3s ease-in;
        -o-transition: all 0.3s ease-in;
        transition: all 0.3s ease-in;
    }

    .info-bar-inner .right-content .request-quote .rq-btn.blank:hover {
        background-color: var(--main-color-one);
        color: #fff;
    }

    .info-bar-inner .right-content .request-quote .rq-btn.blank:hover i {
        color: #fff;
    }

    .info-bar-inner .right-content .request-quote .rq-btn:hover {
        background-color: var(--secondary-color);
    }

    .info-items {
        display: inline-block;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .info-items li {
        display: inline-block;
        margin: 0 30px;
    }

    .info-items li:hover .single-info-item .content .title {
        color: var(--main-color-one);
    }

    .info-items li:first-child {
        margin-left: 0;
    }

    .info-items li:last-child {
        margin-right: 0;
    }

    .info-items li .single-info-item {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-item-align: start;
        align-self: flex-start;
    }

    .info-items li .single-info-item .icon {
        margin-right: 20px;
        font-size: 40px;
        line-height: 40px;
        color: var(--main-color-one);
    }

    .info-items li .single-info-item .content .title {
        font-size: 16px;
        line-height: 26px;
        font-weight: 700;
        margin-bottom: 0;
        -webkit-transition: all 0.3s ease-in;
        -moz-transition: all 0.3s ease-in;
        -o-transition: all 0.3s ease-in;
        transition: all 0.3s ease-in;
    }

    .info-items li .single-info-item .content .details {
        font-size: 14px;
        line-height: 24px;
    }

    .info-items-two {
        display: inline-block;
        margin: 0;
        padding: 0;
        list-style: none;

    }

    .info-items-two li {
        display: inline-block;
        margin: 0 30px;
    }

    .info-items-two li:hover .single-info-item .content .title {
        color: var(--main-color-one);
    }

    .info-items-two li:first-child {
        margin-left: 0;
    }

    .info-items-two li:last-child {
        margin-right: 0;
    }

    .site-footer .dropdown-menu {
        z-index: 22222222222222222;

    }

    .btn-group>.btn:first-child {
        margin-left: 0;
        color: #000 !important;
    }

    .fa-language:before {
        display: none;
    }

    .info-items-two li .single-info-item {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-item-align: start;
        align-self: flex-start;
    }

    .info-items-two li .single-info-item .icon {
        margin-right: 15px;
        font-size: 30px;
        line-height: 30px;
        color: var(--main-color-one);
    }

    .info-items-two li .single-info-item .content {
        margin-top: 3px;
    }

    .info-items-two li .single-info-item .content .title {
        font-size: 16px;
        line-height: 26px;
        font-weight: 700;
        margin-bottom: 0;
        -webkit-transition: all 0.3s ease-in;
        -moz-transition: all 0.3s ease-in;
        -o-transition: all 0.3s ease-in;
        transition: all 0.3s ease-in;
    }

    ul li {
        display: inline;
    }

    .info-items-two li .single-info-item .content .details {
        margin-left: 5px;
        font-size: 14px;
        line-height: 24px;
        font-weight: 500;
        color: var(--paragraph-color);
    }

    .info-items-two li {
        display: inline-block;
        margin: 0px 2px;
    }

    .site-navbar {
        top: 50px;
    }

    @media (max-width: 767.98px) {
        .site-navbar {
            top: 127px;
        }
    }
</style>