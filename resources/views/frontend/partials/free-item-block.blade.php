<div class="d-block d-md-flex listing vertical">
    <a href="{{ route('page.item', ['item_slug' => $item->item_slug]) }}" class="img d-block" style="background-image: url({{ !empty($item->item_image_medium) ? Storage::disk('public')->url('item/' . $item->item_image_medium) : (!empty($item->item_image) ? Storage::disk('public')->url('item/' . $item->item_image) : asset('frontend/images/placeholder/full_item_feature_image_medium.webp')) }})">
    </a>
    <div class="lh-content">

        @foreach($item->getAllCategories(\App\Item::ITEM_TOTAL_SHOW_CATEGORY, isset($parent_category_id) ? $parent_category_id : null) as $key => $category)
            <a href="{{ route('page.category', $category->category_slug) }}">
                <span class="category">{{ $category->category_name }}</span>
            </a>
        @endforeach

        @if($item->allCategories()->count() > \App\Item::ITEM_TOTAL_SHOW_CATEGORY)
            <span class="category">{{ __('categories.and') . " " . strval($item->allCategories()->count() - \App\Item::ITEM_TOTAL_SHOW_CATEGORY) . " " . __('categories.more') }}</span>
        @endif

        @if(!empty($item->item_price))
            <span class="category">${{ number_format($item->item_price) }}</span>
        @endif

        <h3 class="pt-2"><a href="{{ route('page.item', $item->item_slug) }}">{{ $item->item_title }}</a></h3>
        <address>
            {{ $item->item_address_hide == 0 ? $item->item_address . ',' : '' }}
            <a href="{{ route('page.city', ['state_slug'=>$item->state->state_slug, 'city_slug'=>$item->city->city_slug]) }}">{{ $item->city->city_name }}</a>,
            <a href="{{ route('page.state', ['state_slug'=>$item->state->state_slug]) }}">{{ $item->state->state_name }}</a>
            {{ $item->item_postal_code }}
        </address>

        @if($item->getCountRating() > 0)
        <div class="row">
            <div class="col-12">
                <div class="pl-0 rating_stars rating_stars_{{ $item->item_slug }}" data-id="rating_stars_{{ $item->item_slug }}" data-rating="{{ $item->item_average_rating }}"></div>
                <address class="mt-1">
                    @if($item->getCountRating() == 1)
                        {{ '(' . $item->getCountRating() . ' ' . __('review.frontend.review') . ')' }}
                    @else
                        {{ '(' . $item->getCountRating() . ' ' . __('review.frontend.reviews') . ')' }}
                    @endif
                </address>
            </div>
        </div>
        @endif

        <div class="row align-items-center">
            <div class="col-2 pr-0">
                @if(empty($item->user->user_image))
                    <img src="{{ asset('frontend/images/placeholder/profile-'. intval($item->user->id % 10) . '.webp') }}" alt="Image" class="img-fluid rounded-circle">
                @else
                    <img src="{{ Storage::disk('public')->url('user/' . $item->user->user_image) }}" alt="{{ $item->user->name }}" class="img-fluid rounded-circle">
                @endif
            </div>
            <div class="col-10 line-height-1-2">

                <div class="row pb-1">
                    <div class="col-12">
                        <span class="font-size-13">{{ $item->user->name }}</span>
                    </div>
                </div>
                <div class="row line-height-1-0">
                    <div class="col-12">

                        @if($item->totalComments() > 1)
                            <span class="review">{{ $item->totalComments() . ' comments' }}</span>
                        @elseif($item->totalComments() == 1)
                            <span class="review">{{ $item->totalComments() . ' comment' }}</span>
                        @endif
                        <span class="review">{{ $item->created_at->diffForHumans() }}</span>

                    </div>
                </div>
            </div>
        </div>


    </div>


</div>
