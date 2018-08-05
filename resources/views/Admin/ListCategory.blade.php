@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.lblcategorymanagement')}}
        <div class="pull-right">
            <a href="{{url('admin/category/create')}}" class="btn bg-purple pull-right"><i class="fa fa-plus"></i>&nbsp;Add Category</a>
        </div>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
                    <strong>{{trans('labels.whoops')}}</strong> {{trans('labels.someproblems')}}<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="box-body table-responsive">
                    <table id="categoryListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.lblcategoryname')}}</th>
                                <th>{{trans('labels.catlogo')}}</th>
                                <th>{{trans('labels.bannerimage')}}</th>
                                <th>{{trans('labels.noofbusiness')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $key=>$value)
                            <tr>
                                <td>{{$value->id}}</td>
                                <td><a href="{{url('admin/category/subcategories')}}/{{Crypt::encrypt($value->id)}}">{{$value->name}}</a></td>
                                <td>
                                    @if($value->cat_logo != '' && Storage::size(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) > 0)
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->cat_logo), 0, -10)}}' src="{{ Storage::url(Config::get('constant.CATEGORY_LOGO_THUMBNAIL_IMAGE_PATH').$value->cat_logo) }}" width="50" height="50" class="img-circle"/>
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->cat_logo), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ Storage::url(Config::get('constant.CATEGORY_LOGO_ORIGINAL_IMAGE_PATH').$value->cat_logo) }}" style='width:100%; border-radius:5px;' title="{{$value->profile_pic}}" />
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <img src="{{ url('images/default.png') }}" width="50" height="50" class="img-circle"/>
                                    @endif
                                </td>
                                <td>
                                    @if($value->banner_img != '' && Storage::size(Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH').$value->banner_img) > 0)
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->banner_img), 0, -10)}}' src="{{ Storage::url(Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH').$value->banner_img) }}" width="100" height="50"/>
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->banner_img), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ Storage::url(Config::get('constant.CATEGORY_BANNER_ORIGINAL_IMAGE_PATH').$value->banner_img) }}" style='width:100%; border-radius:5px;' title="{{$value->profile_pic}}" />
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else

                                    @endif
                                </td>
                                <td>
                                    <?php   $businesses = Helpers::categoryHasBusinesses($value->id); ?>
                                    @if($businesses->count() != 0)
                                        <a href="{{ url('/admin/allbusiness') }}/{{Crypt::encrypt($value->id)}}" target="_blank">
                                            {{$businesses->count()}}
                                        </a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    <a href="{{url('admin/category/edit')}}/{{Crypt::encrypt($value->id)}}">
                                        <span class='glyphicon glyphicon-edit' data-toggle="tooltip" data-original-title="Edit"></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{url('admin/category/delete')}}/{{Crypt::encrypt($value->id)}}" onClick="return confirm(&#39;{{trans('labels.confirmdeletemsg')}}&#39;)">
                                        <span class='glyphicon glyphicon-remove' data-toggle="tooltip" data-original-title="Delete"></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{url('admin/category/subcategories')}}/{{Crypt::encrypt($value->id)}}">
                                        <span class='glyphicon glyphicon-log-out' data-toggle="tooltip" data-original-title="Sub Categories"></span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            @endforelse

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#categoryListing').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop
