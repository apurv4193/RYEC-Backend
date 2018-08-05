@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.service')}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{url('admin/users')}}"><i class="fa fa-users"></i> Users </a></li>
        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}">{{$businessDetails->user->name}}</a></li>
        <li><a href="{{url('admin/user/business/service')}}/{{Crypt::encrypt($businessDetails->id)}}">{{$businessDetails->name}} {{trans('labels.business')}}</a></li>
        <li class="active">{{trans('labels.service')}}</li>
    </ol>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">

        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.service')}}</h3>
                </div><!-- /.box-header -->
                @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <strong>{{trans('labels.whoops')}}</strong> {{trans('labels.someproblems')}}<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form id="addservice" class="form-horizontal" method="post" action="{{ url('admin/user/business/service/save') }}" enctype="multipart/form-data" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                    <input type="hidden" name="old_logo" value="<?php echo (isset($data) && !empty($data) && $data->logo != '') ? $data->logo : '' ?>">
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('name'))
                                $name = old('name');
                            elseif (isset($data))
                                $name = $data->name;
                            else
                                $name = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.title')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="name" name="name" placeholder="{{trans('labels.name')}}" value="{{$name}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('logo'))
                                $logo = old('logo');
                            elseif (isset($data))
                                $logo = $data->logo;
                            else
                                $logo = '';
                            ?>
                            <label for="logo" class="col-sm-2 control-label">{{trans('labels.logo')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="file" id="logo" name="logo">
                            </div>
                        </div>

                        @if(isset($data) && !empty($data))
                            <div class="form-group" id="business_images">
                                <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                <div class="col-sm-8">
                                    @if($data->logo != '' && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$data->logo) > 0)
                                        <img src="{{ Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$data->logo) }}" width="50" height="50"/>
                                    @else
                                        <img src="{{ url(Config::get('constant.DEFAULT_IMAGE')) }}" width="50" height="50"/>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- <?php
                            if (old('category_hierarchy'))
                                $category_hierarchy = old('category_hierarchy');
                            elseif (isset($data))
                                $category_hierarchy = $data->category_hierarchy;
                            else
                                $category_hierarchy = '';
                        ?>
                        @if($category_hierarchy != '')
                            <div class="form-group">
                                <label for="category_id" class="col-sm-2 control-label" >
                                    {{trans('labels.categoryhierarchy')}}
                                </label>
                                <div class="col-sm-8" style="height:35px;">
                                    <?php  $categoryArray = explode(',',$category_hierarchy); ?>
                                        <ol class="breadcrumb">
                                            @foreach($categoryArray as $category)
                                                <?php  $categoryDetail = Helpers::getCategoryById($category); ?>
                                                @if(!empty($categoryDetail))
                                                    <li>{{$categoryDetail['name']}}</li>
                                                @endif 
                                            @endforeach
                                        </ol>
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <?php
                            if (old('category_id'))
                                $category_id = old('category_id');
                            elseif (isset($data))
                                $category_id = $data->category_id;
                            else
                                $category_id = '';

                            ?>
                            <label for="category_id" class="col-sm-2 control-label">
                                {{(isset($data->category_id)) ? trans('labels.changecategory') : trans('labels.category')}}
                            </label>
                            <div class="col-sm-8">
                                <select name="categoryArray[]"  class="form-control" onchange="return getSubCategory(this.value);">
                                    <option value="">Select {{trans('labels.subcategory')}}</option>
                                    @forelse($parentCategories as $category)
                                        <option class="type_parent_cat cat_type" value="{{$category->id}}">
                                            {{$category->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div id="subcategory"></div> -->

                        <div class="form-group">
                            <?php
                            if (old('description'))
                                $description = old('description');
                            elseif (isset($data))
                                $description = $data->description;
                            else
                                $description = '';
                            ?>
                            <label for="description" class="col-sm-2 control-label">{{trans('labels.description')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                               <textarea type="text" class="form-control" id="description" name="description" placeholder="{{trans('labels.description')}}">{{$description}}</textarea>
                            </div>
                        </div>

                        <div class="form-group" id="image">
                            <label for="images" class="col-sm-2 control-label">{{trans('labels.images')}}<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="file" id="service_images" name="service_images[]" multiple >
                            </div>                            
                        </div>

                        @if(isset($data) && !empty($data))
                            @if(count($data->serviceImages) > 0)
                                <div class="form-group" id="business_images">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-4">
                                            @forelse($data->serviceImages as $image)
                                                @if(Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$image['image_name']) > 0) 
                                                    <div class="business_img" id="product_img_{{$image['id']}}">
                                                    <i class="fa fa-times-circle" aria-hidden="true" onclick="return deleteServiceImage({{$image['id']}});"></i>
                                                        <img src="{{ Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$image['image_name']) }}" width="50" height="50"/>
                                                    </div>
                                                @endif                                                
                                            @empty
                                           @endforelse
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="form-group">
                            <?php
                            if (old('metatags'))
                                $metatags = old('metatags');
                            elseif (isset($data))
                                $metatags = $data->metatags;
                            else
                                $metatags = '';
                            ?>
                            <label for="metatags" class="col-sm-2 control-label">{{trans('labels.metatags')}}</label>
                            <div class="col-sm-8">
                                <textarea id="metatags" name="metatags">{{ $metatags }}</textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('cost'))
                                $cost = old('cost');
                            elseif (isset($data))
                                $cost = $data->cost;
                            else
                                $cost = '';
                            ?>
                            <label for="cost" class="col-sm-2 control-label">{{trans('labels.cost')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="cost" name="cost" placeholder="{{trans('labels.cost')}}" value="{{$cost}}">
                            </div>
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/user/business/service') }}/{{Crypt::encrypt($businessId)}}">{{trans('labels.cancelbtn')}}</a>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
        </div>
    </div>
</section><!-- /.content -->
@stop
@section('script')
<script src="{{asset('plugins/ckeditor/ckeditor.js')}}"></script>

<!-- Include tags in input box -->
<script src="{{ asset('js/jquery.caret.min.js') }}"></script>
<script src="{{ asset('js/jquery.tag-editor.js') }}"></script>

<script>

$('#metatags').tagEditor({
    placeholder: 'Enter  Metatags ...',
});

$(document).ready(function () {

    //CKEDITOR.replace('description');
    $.validator.addMethod('filesize', function (value, element, param) {
        return this.optional(element) || (element.files[0].size <= param)
    }, 'File size must be less than 5 MB');

    var id = <?php echo (isset($data) && !empty($data)) ? $data->id : '0'; ?>;
    if(id == 0)
    {
        var serviceRules = {
            name:{
                required: true,
                maxlength:100
            },
            description:{
                required: true
            },
            logo:{
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            },
            'service_images[]':
            {
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }   
            
        };
    }
    else
    {
        var serviceRules = {
            name:{
                required: true,
                maxlength:100
            },
            description:{
                required: true
            },
            logo:{
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            },
            'service_images[]':
            {
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }   
            
        };
    }
    
    $("#addservice").validate({
        ignore: "",
        rules: serviceRules,
        messages: {
            name:{
                required: "<?php echo trans('labels.titlerequired') ?>",
                maxlength: "<?php echo trans('labels.titlemaxrequired') ?>",
            },
            description: {
                required: "<?php echo trans('labels.descriptionrequired'); ?>"
            },
            logo:{
                required: "<?php echo trans('labels.logorequired'); ?>",
                extension: "<?php echo trans('labels.logoaccept'); ?>"
            },
            'service_images[]':{
                required: "<?php echo trans('labels.imagerequired')?>",
                extension: "<?php echo trans('labels.imageextension')?>",
                filesize: "<?php echo trans('labels.maxfilesizevalidate')?>",
            }
        }            
    });
    
});

function getSubCategory(categoryId)
{
    var token = '<?php echo csrf_token() ?>';
    $.ajax({
        headers: { 'X-CSRF-TOKEN': token },
        type: "POST",
        url: '/admin/search/subcategory',
        data: {categoryId: categoryId},
        success: function( data ) {
            $('#subcategory').append(data);
        }
    });
}

function deleteServiceImage(imageId)
{
    var x = confirm("Are you sure you want to delete?");
    if (x)
        {
            var token = '<?php echo csrf_token() ?>';
            $.ajax({
                headers: { 'X-CSRF-TOKEN': token },
                type: "POST",
                url: "{{url('/admin/remove/serviceimage')}}",
                data: {serviceImageId: imageId},
                success: function( data ) {
                   $('#product_img_'+imageId).remove();
                }
            });
        }
    else
        return false;
    
}
</script>
@stop