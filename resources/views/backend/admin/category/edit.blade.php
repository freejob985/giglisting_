@extends('backend.admin.layouts.app')

@section('styles')
@endsection

@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            <h1 class="h3 mb-2 text-gray-800">{{ __('backend.category.edit-category') }}</h1>
            <p class="mb-4">{{ __('backend.category.edit-category-desc') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-info btn-icon-split">
                <span class="icon text-white-50">
                  <i class="fas fa-backspace"></i>
                </span>
                <span class="text">{{ __('backend.shared.back') }}</span>
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row bg-white pt-4 pl-3 pr-3 pb-4">
        <div class="col-12">
            <div class="row">
                <div class="col-12 col-md-10 col-lg-6">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class=""   enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row form-group">

                            <div class="col-md-12">
                                <label class="text-black" for="category_parent_id">{{ __('categories.choose-parent-cat') }}</label>
                                <select class="custom-select @error('category_parent_id') is-invalid @enderror Type " name="Type">
                                    <option value="0">Choose the type of download</option>
                                    <option value="Link" {{ ( $category->Type == 'Link') ? 'selected' : '' }}>Link</option>
                                     <option value="photo" {{ ( $category->Type == 'photo') ? 'selected' : '' }}>photo</option>
                                      <option value="ICON" {{ ( $category->Type == 'ICON') ? 'selected' : '' }}>ICON</option>
                                </select>
                                @error('category_parent_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-12">
                                <label for="category_name" class="text-black">{{ __('backend.category.category-name') }}</label>
                                <input id="category_name" type="text" class="form-control @error('category_name') is-invalid @enderror" name="category_name" value="{{ old('category_name') ? old('category_name') : $category->category_name }}" autofocus>
                                @error('category_name')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row form-group">
                            <div class="col-md-12">
                                <label for="category_slug" class="text-black">{{ __('backend.category.slug') }}</label>
                                <input id="category_slug" type="text" class="form-control @error('category_slug') is-invalid @enderror" name="category_slug" value="{{ old('category_slug') ? old('category_slug') : $category->category_slug }}">
                                <small class="text-muted">
                                    {{ __('categories.category-slug-help') }}
                                </small>
                                @error('category_slug')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row form-group">

                            <div class="col-md-12">
                                <label class="text-black" for="category_icon">{{ __('backend.category.category-icon') }}</label>
                                <input id="category_icon" type="text" class="form-control @error('category_icon') is-invalid @enderror" name="category_icon" value="{{ old('category_icon') ? old('category_icon') : $category->category_icon }}">
                                <small class="text-muted">
                                    {!! __('backend.category.category-icon-help') !!}
                                </small>
                                @error('category_icon')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row form-group Link">
                            <div class="col-md-12">
                                <label class="text-black" for="Link">Link</label>
                                <input id="Link" type="text" class="form-control @error('Link') is-invalid @enderror " name="Link" value="{{ $category->Link }}">
                                <small class="text-muted">
        
                                </small>
                                @error('Link')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>


                             <div class="row form-group photo" >
                            <div class="col-md-12">
                                <label class="text-black" for="photo">photo</label>
                                <input id="photo" type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" value="{{ old('photo') }}">
                                <small class="text-muted">
        
                                </small>
                                @error('photo')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row form-group">

                            <div class="col-md-12">
                                <label class="text-black" for="category_description">{{ __('category_description.category-description') }}</label>
                                <textarea id="category_description" class="form-control @error('category_description') is-invalid @enderror" name="category_description">{{ old('category_description') ? old('category_description') : $category->category_description }}</textarea>
                                @error('category_description')
                                <span class="invalid-tooltip">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row form-group">

                            <div class="col-md-12">
                                <label class="text-black" for="category_parent_id">{{ __('categories.choose-parent-cat') }}</label>
                                <select class="custom-select @error('category_parent_id') is-invalid @enderror" name="category_parent_id">
                                    <option value="0" {{ empty(old('category_parent_id') ? old('category_parent_id') : $category->category_parent_id) ? 'selected' : '' }}>{{ __('categories.no-parent-cat') }}</option>
                                    @foreach($printable_categories as $key => $printable_category)
                                        <option value="{{ $printable_category["category_id"] }}" {{ (old('category_parent_id') ? old('category_parent_id') : $category->category_parent_id) == $printable_category["category_id"] ? 'selected' : '' }}>{{ $printable_category["category_name"] }}</option>
                                    @endforeach
                                </select>
                                @error('category_parent_id')
                                <span class="invalid-tooltip">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row form-group">
                            <div class="col-8">
                                <button type="submit" class="btn btn-success text-white">
                                    Update
                                </button>
                            </div>
                            <div class="col-4 text-right">
                                <a class="text-danger" href="#" data-toggle="modal" data-target="#deleteModal">
                                    Delete
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <span class="icon photo"><span><img  style="
        width: 10%;
    " class="img-responsive" src="{{Request::root()}}/laravel_project/public/files/{{ $category->photo }}" alt="Chania"></span></span>

    <!-- Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('backend.shared.delete-confirm') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ __('backend.shared.delete-message', ['record_type' => __('backend.shared.category'), 'record_name' => $category->category_name]) }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('backend.shared.cancel') }}</button>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('backend.shared.delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    
@endsection

@section('scripts')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>
  $(document).ready(function(){
   
    var day= $(".Type").children("option:selected").val();

          if(day==="photo"){
               $(".photo").show();
                 $(".Link").hide();
                   $(".category_icon").hide();
          }else if(day==="Link"){
             $(".photo").hide();
             $(".Link").show();
             $(".category_icon").hide();  
          }else{
               $(".photo").hide();
             $(".Link").hide();
             $(".category_icon").show();      
              
          }
          $(".Type").change(function(){
          var day=  this.value;
          if(day==="photo"){
               $(".photo").show();
                 $(".Link").hide();
                   $(".category_icon").hide();
          }else if(day==="Link"){
             $(".photo").hide();
             $(".Link").show();
             $(".category_icon").hide();  
          }else{
               $(".photo").hide();
             $(".Link").hide();
             $(".category_icon").show();      
              
          }
          
       //  alert(day);
 

          });
        });
</script>
@endsection
