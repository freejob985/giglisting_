<footer class="site-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-9 mb-3">
                <div class="row">
                    <div class="col-md-6">
<h1 class="mb-0 mt-1 site-logo">
                <a href="http://gigworldgo.com" class="text-black mb-0">
                    <img class="full" style="
                        width: 58% !important;
                    " alt="" data-src="http://gigworldgo.com/assets/uploads/media-uploader/logo1588283745.gif"
                        src="http://gigworldgo.com/assets/uploads/media-uploader/logo1588283745.gif">
                </a>
            </h1>                        <p>{!! clean(nl2br($site_global_settings->setting_site_about), array('HTML.Allowed' => 'b,strong,i,em,u,ul,ol,li,p,br')) !!}</p>
                    </div>

                    <div class="col-md-3">
                        <h2 class="footer-heading mb-4"><strong>{{ __('frontend.footer.navigations') }}</strong></h2>
                        <ul class="list-unstyled">
                          <li>
	<a href="http://gigworldgo.com/faq"> Faq</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/price-plan"> Price Plan</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/blog"> Blog</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/p/33/terms-and-conditions"> Terms and conditions</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/p/34/privacy-policy"> Privacy policy</a>
	</li>
</ul>
                       
                    </div>
                    <div class="col-md-3">
                        <ul  class="list-unstyled">	<li>
	<a href="http://gigworldgo.com"> Home</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/about"> About Us</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/our-works"> Our Works</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/blog"> Blog</a>
	</li>
	<li>
	<a href="http://gigworldgo.com/contact"> Contact</a>
	</li>
</ul>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="footer-widget widget"><h4 class="widget-title">Contact us</h4><ul class="contact_info_list"> <li class="single-info-item">
                    <div class="icon">
                        <i class="fa fa-home"></i>
                    </div>
                    <div class="details">
                        14 Canada
                    </div>
                </li><li class="single-info-item">
                    <div class="icon">
                        <i class="fa fa-phone"></i>
                    </div>
                    <div class="details">
                       +8801847111881
                    </div>
                </li><li class="single-info-item">
                    <div class="icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="details">
                       info@gigworld.com
                    </div>
                </li></ul></div>
            </div>
        </div>

        <div class="row pt-2 mt-5 pb-2" style="display:none;">
            <div class="col-md-12">
                <div class="btn-group dropup">
                    <button class="btn btn-sm btn-outline-secondary text-white rounded dropdown-toggle" style="
                    display: none;
                "  type="button" id="table_option_dropdown_country" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-globe"></i>
                        {{ $site_prefer_country_name }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="table_option_dropdown_country">
                        @foreach($site_available_countries as $site_available_countries_key => $country)
                            <a class="dropdown-item" href="{{ route('page.country.update', ['user_prefer_country_id' => $country->id]) }}">
                                {{ $country->country_name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row pt-2 pb-2" style="display:none;">
            <div class="col-md-12">
                <div class="btn-group dropup">
                    <button class="btn btn-sm btn-outline-secondary text-white rounded dropdown-toggle" type="button" id="table_option_dropdown_locale" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-language"></i>
                        {{ __('prefer_languages.' . app()->getLocale()) }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="table_option_dropdown_locale">
                        @foreach(\App\Setting::LANGUAGES as $setting_languages_key => $language)
                            <a class="dropdown-item" href="{{ route('page.locale.update', ['user_prefer_language' => $language]) }}">
                                {{ __('prefer_languages.' . $language) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

       
    </div>
   
</footer>
  <div class="row text-center" style="background:gold;color:#000;padding:13px 0px;width:100%;">
            <div class="col-md-12">
                <div >
                    <p>
                        {{ __('frontend.footer.copyright') }} &copy; {{ empty($site_global_settings->setting_site_name) ? config('app.name', 'Laravel') : $site_global_settings->setting_site_name }} {{ date('Y') }} {{ __('frontend.footer.rights-reserved') }}
                    </p>
                </div>
            </div>

        </div>
<style>.site-footer ul li {color: #fff;
    margin-bottom: 10px;
    display: block!important;
}.single-info-item .icon{color:gold;}.site-footer {
    padding: 4em 0;
}</style>
