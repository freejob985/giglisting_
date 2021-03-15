<form action="{{ route('page.search.do') }}" method="POST">
    @csrf
    <div class="mb-2">
        <h3 class="h5 text-black mb-3">{{ __('frontend.search.title-search') }}</h3>
        <div class="form-group">
            <input name="search_query" type="text" class="form-control rounded @error('search_query') is-invalid @enderror" value="{{ old('search_query') }}" placeholder="{{ __('frontend.search.what-are-you-looking-for') }}">
            @error('search_query')
            <div class="invalid-tooltip invalid-tooltip-side-search-query">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="form-group">
            <!-- select-wrap, .wrap-icon -->
            <div id="multiple-datasets" class="wrap-icon">
                <span class="icon icon-room z-index-1000"></span>
                <input name="city_state" type="text" class="form-control rounded typeahead @error('city_state') is-invalid @enderror" value="{{ old('city_state') }}" placeholder="{{ __('categories.search-city-placeholder') }}" aria-describedby="basic-addon1">
                @error('city_state')
                <div class="invalid-tooltip state-city-invalid-tooltip">
                    {{ $message }}
                </div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-5">
        <input type="submit" class="btn btn-primary btn-block rounded text-white" value="{{ __('frontend.search.search') }}">
    </div>
</form>
