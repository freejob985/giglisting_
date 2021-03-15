@extends('backend.admin.layouts.app')

@section('styles')
    <!-- Custom styles for this page -->
    <link href="{{ asset('backend/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('content')

    <div class="row justify-content-between">
        <div class="col-9">
            <h1 class="h3 mb-2 text-gray-800">{{ __('backend.category.category') }}</h1>
            <p class="mb-4">{{ __('backend.category.category-desc') }}</p>
        </div>
        <div class="col-3 text-right">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-info btn-icon-split">
                <span class="icon text-white-50">
                  <i class="fas fa-plus"></i>
                </span>
                <span class="text">{{ __('backend.category.add-category') }}</span>
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row bg-white pt-4 pl-3 pr-3 pb-4">
        <div class="col-12">

            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>{{ __('backend.category.id') }}</th>
                                <th>{{ __('backend.category.name') }}</th>
                                <th>{{ __('backend.category.slug') }}</th>
                                <th>{{ __('backend.category.icon') }}</th>
                                <th>{{ __('categories.parent-cat') }}</th>
                                <th>ADMIN</th>
                                <th>{{ __('backend.shared.action') }}</th>
                            </tr>
                            </thead>
                            <tfoot>
                            <tr>
                                <th>{{ __('backend.category.id') }}</th>
                                <th>{{ __('backend.category.name') }}</th>
                                <th>{{ __('backend.category.slug') }}</th>
                                <th>{{ __('backend.category.icon') }}</th>
                                <th>{{ __('categories.parent-cat') }}</th>
                                <th>{{ __('backend.shared.action') }}</th>
                            </tr>
                            </tfoot>
                            <tbody>
                            @foreach($all_categories as $key => $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->category_name }}</td>
                                    <td>{{ $category->category_slug }}</td>
                                    <td>
                                        <i class="{{ $category->category_icon }}"></i>
                                        {{ $category->category_icon }}
                                    </td>
                                    <td>
                                        @if(!empty($category->category_parent_id))
                                            {{ \App\Category::find($category->category_parent_id)->category_name }}
                                        @endif
                                    </td>

                                    <td>
                                        <form action="{{ route('CourseProgresss.quick',$category->id) }}" method="POST">
                                            {{ csrf_field() }}
                                            <button  type="Submit" class="btn btn-xs {{ $category->admin ==1 ? 'btn-success' : 'btn-danger' }}">
                                              @if($category->admin ==1)
                                              Active
                                              @else
                                              Deactive
                                              @endif
                                            </button>
                                          </form>
                      
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary btn-circle">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Page level plugins -->
    <script src="{{ asset('backend/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('backend/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        // Call the dataTables jQuery plugin
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
@endsection
