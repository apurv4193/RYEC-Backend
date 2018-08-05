@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.membershipplan')}}
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">

        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.membershipplan')}}</h3>
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
                <form id="addsubscription" class="form-horizontal" method="post" action="{{ url('/admin/savesubscription') }}"  enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
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
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.name')}}<span class="star_red">*</span></label>
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
                                    @if($data->logo != '' && Storage::size(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$data->logo) > 0)
                                        <img src="{{ Storage::url(Config::get('constant.SUBSCRIPTION_PLAN_THUMBNAIL_IMAGE_PATH').$data->logo) }}" width="50" height="50"/>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <?php
                            if (old('description'))
                                $description = old('description');
                            elseif (isset($data))
                                $description = $data->description;
                            else
                                $description = '';
                            ?>
                            <label for="description" class="col-sm-2 control-label">{{trans('labels.description')}}</label>
                            <div class="col-sm-8">
                                <textarea type="text" class="form-control" id="description" name="description" placeholder="{{trans('labels.description')}}">{{$description}}</textarea>
                            <div class="descriptionerror"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('months'))
                                $months = old('months');
                            elseif (isset($data))
                                $months = $data->months;
                            else
                                $months = '';
                            ?>
                            <label for="months" class="col-sm-2 control-label">{{trans('labels.months')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="months" name="months" placeholder="{{trans('labels.months')}}" value="{{$months}}">
                                <div class="montherror"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('price'))
                                $price = old('price');
                            elseif (isset($data))
                                $price = $data->price;
                            else
                                $price = '';
                            ?>
                            <label for="price" class="col-sm-2 control-label">{{trans('labels.price')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="price" name="price" placeholder="{{trans('labels.price')}}" value="{{$price}}">
                                <div class="priceerror"></div>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/subscriptions') }}">{{trans('labels.cancelbtn')}}</a>
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
<script>

$(document).ready(function () {

    CKEDITOR.replace('description');

    jQuery.validator.addMethod("emptyetbody", function(value, element){
        var et_body_data = CKEDITOR.instances['description'].getData();
        return et_body_data != '';
    }, "Please Enter description");
    var id = <?php echo (isset($data) && !empty($data)) ? $data->id : '0'; ?>;
    if(id == 0)
    {
        var subscriptionsRules = {
            name: {
                required: true
            },
            months:{
                required: true,
                digits: true,
            },
            price:{
                required: true,
                digits: true,
            },
            logo:{
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }  
        };
    }
    else
    {
        var subscriptionsRules = {
            name: {
                required: true
            },
            months:{
                required: true,
                digits: true,
            },
            price:{
                required: true,
                digits: true,
            },
            logo:{
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }  
        };
    }

    $("#addsubscription").validate({
        ignore: "",
        rules: subscriptionsRules,
        messages: {
            name: {
                required: "<?php echo trans('labels.namerequired')?>"
            },
            months: {
                required: "<?php echo trans('labels.noofmonthrequired') ?>",
                digits: "<?php echo trans('labels.digitsrequired') ?>",
            },
            price: {
                required: "<?php echo trans('labels.pricerequired') ?>",
                digits: "<?php echo trans('labels.digitsrequired') ?>",
            }
        },
        errorPlacement: function(error, element) {
            if (element.attr("name") == "description" )
                error.insertAfter(".descriptionerror");
            else if  (element.attr("name") == "price" )
                error.insertAfter(".priceerror");
            else
                error.insertAfter(element);
        }            
    });
});
</script>
@stop