@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.lblsubcategorymanagement')}}
        <div class="pull-right">
            <a href="{{url('admin/category/subcategory')}}/{{Crypt::encrypt($parentId)}}/addsubcategory" class="btn bg-purple"><i class="fa fa-plus"></i>&nbsp;Add Subcategory</a>
        </div>
    </h1>
    <!-- <ol class="breadcrumb">
        <i class="fa fa fa-list-ul"></i> 
        @foreach($reverseCategoryHierarchy as $parentCat)
            <li>
                <a href="{{url('admin/category/subcategories')}}/{{Crypt::encrypt($parentCat['id'])}}">
                    {{$parentCat['name']}}
                </a>
            </li>
        @endforeach
        <li class="active">{{trans('labels.lblsubcategorymanagement')}}</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header">
                    <ol class="breadcrumb">
                        <li>
                            <i class="fa fa fa-list-ul"></i> 
                            <a href="{{url('admin/categories')}}">
                               {{trans('labels.lblcategorymanagement')}}
                            </a>
                        </li>
                        @foreach($reverseCategoryHierarchy as $parentCat)
                            <li>
                                <a href="{{url('admin/category/subcategories')}}/{{Crypt::encrypt($parentCat['id'])}}">
                                    {{$parentCat['name']}}
                                </a>
                            </li>
                        @endforeach
                         - {{trans('labels.lblsubcategorymanagement')}}
                    </ol>
                </div>
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
                <div class="box-body">
                    <table id="categoryListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.lblcategoryname')}}</th>
                                <th>{{trans('labels.parentcategoryname')}}</th>
                                <th>{{trans('labels.catlogo')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subCategories as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    <a href="{{url('admin/category/subcategories')}}/{{Crypt::encrypt($value->id)}}">
                                        {{$value->name}}
                                    </a>    
                                </td>
                                
                                <td>
                                    @if (isset($value->parentCatData) && count($value->parentCatData) > 0 && $value->parentCatData->id == $parentId)
                                        {{ $value->parentCatData->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                
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
                                    <a href="{{url('admin/category/subcategory')}}/{{Crypt::encrypt($parentId)}}/editsubcategory/{{Crypt::encrypt($value->id)}}">
                                        <span class='glyphicon glyphicon-edit' data-toggle="tooltip" data-original-title="Edit"></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{url('admin/category/subcategory')}}/{{Crypt::encrypt($parentId)}}/deletesubcategory/{{Crypt::encrypt($value->id)}}" onClick="return confirm(&#39;{{trans('labels.confirmdeletemsg')}}&#39;)">
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
