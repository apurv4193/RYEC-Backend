@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.owner')}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{url('admin/users')}}"><i class="fa fa-users"></i> Users </a></li>
        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}">{{$businessDetails->user->name}}</a></li>
        <li><a href="{{url('admin/user/business/service')}}/{{Crypt::encrypt($businessDetails->id)}}">{{$businessDetails->name}} {{trans('labels.business')}}</a></li>
        <li class="active">{{trans('labels.owner')}}</li>
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.owner')}}</h3>
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
                <form id="addowner" class="form-horizontal" method="post" action="{{ url('admin/user/business/owner/save') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                    <input type="hidden" name="old_photo" value="<?php echo (isset($data) && !empty($data) && $data->photo != '') ? $data->photo : '' ?>">
                    
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('full_name'))
                                $full_name = old('full_name');
                            elseif (isset($data))
                                $full_name = $data->full_name;
                            else
                                $full_name = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.name')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="{{trans('labels.name')}}" value="{{$full_name}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('mobile'))
                                $mobile = old('mobile');
                            elseif (isset($data))
                                $mobile = $data->mobile;
                            else
                                $mobile = '';

                            if (old('country_code'))
                                $country_code = old('country_code');
                            elseif (isset($data))
                                $country_code = $data->country_code;
                            else
                                $country_code = Config::get('constant.INDIA_CODE');
                            ?>
                            <label for="mobile" class="col-sm-2 control-label">{{trans('labels.mobile')}}</label>
                            <div class="col-sm-2">
                                    <?php $countryCodes = Helpers::getCountries(); ?>
                                    <select name="country_code" data="" class="form-control select2" id="country_code">
                                        <option value="">Country Code</option>
                                        @forelse($countryCodes as $codes)
                                            <option value="{{$codes->country_code}}" {{($country_code == $codes->country_code)?'selected':''}}>{{$codes->name}} {{$codes->country_code}} </option>
                                        @empty
                                        @endforelse
                                    </select>
                            </div>
                            <div class="col-sm-6">
                               <input type="text" class="form-control" id="mobile" name="mobile" placeholder="{{trans('labels.mobile')}}" value="{{$mobile}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('email_id'))
                                $email_id = old('email_id');
                            elseif (isset($data))
                                $email_id = $data->email_id;
                            else
                                $email_id = '';
                            ?>
                            <label for="email_id" class="col-sm-2 control-label">{{trans('labels.email')}}</label>
                            <div class="col-sm-8">
                               <input type="text" class="form-control" id="email_id" name="email_id" placeholder="{{trans('labels.email')}}" value="{{$email_id}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('dob'))
                                $dob = old('dob');
                            elseif (isset($data))
                                $dob = $data->dob;
                            else
                                $dob = '';
                            ?>
                            <label for="dob" class="col-sm-2 control-label">{{trans('labels.dob')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="birthdate" name="dob" placeholder="{{trans('labels.dob')}}" value="{{$dob}}">
                                <div class="doberror"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('gender'))
                                $gender = old('gender');
                            elseif (isset($data))
                                $gender = $data->gender;
                            else
                                $gender = '';
                            ?>
                            <label for="gender" class="col-sm-2 control-label">{{trans('labels.gender')}}</label>
                            <div class="col-sm-8">
                                <input type="radio" id="gender" name="gender" value="1" <?php if($gender == 1){?> checked <?php } ?>>{{trans('labels.male')}}
                                <input type="radio" id="gender" name="gender" value="2" <?php if($gender == 2){?> checked <?php } ?>>{{trans('labels.female')}}
                                <input type="radio" id="gender" name="gender" value="3" <?php if($gender == 3){?> checked <?php } ?>>{{trans('labels.other')}}
                            </div>
                        </div>
                        <div class="gender_error" style="margin-left: 185px;"></div>

                        <div class="form-group" id="photo">
                            <?php
                            if (old('photo'))
                                $photo = old('photo');
                            elseif (isset($data))
                                $photo = $data->photo;
                            else
                                $photo = '';
                            ?>
                            <label for="images" class="col-sm-2 control-label">{{trans('labels.photo')}}</label>
                            <div class="col-sm-8">
                                <input type="file" id="owner_images" name="photo">
                            </div>                            
                        </div>

                        @if(isset($data) && !empty($data))
                            @if($data->photo != '')
                                <div class="form-group" id="owner_images">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-8">
                                        @if(Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$data->photo) > 0) 
                                            <img src="{{ Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$data->photo) }}" width="50" height="50"/>
                                        @endif                                                
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="form-group">
                            <?php
                            if (old('designation'))
                                $designation = old('designation');
                            elseif (isset($data))
                                $designation = $data->designation;
                            else
                                $designation = '';
                            ?>
                            <label for="designation" class="col-sm-2 control-label">{{trans('labels.designation')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="designation" name="designation" placeholder="{{trans('labels.designation')}}" value="{{$designation}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('father_name'))
                                $father_name = old('father_name');
                            elseif (isset($data))
                                $father_name = $data->father_name;
                            else
                                $father_name = '';
                            ?>
                            <label for="father_name" class="col-sm-2 control-label">{{trans('labels.fathername')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="father_name" name="father_name" placeholder="{{trans('labels.fathername')}}" value="{{$father_name}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('native_village'))
                                $native_village = old('native_village');
                            elseif (isset($data))
                                $native_village = $data->native_village;
                            else
                                $native_village = '';
                            ?>
                            <label for="native_village" class="col-sm-2 control-label">{{trans('labels.nativevillage')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="native_village" name="native_village" placeholder="{{trans('labels.nativevillage')}}" value="{{$native_village}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('maternal_home'))
                                $maternal_home = old('maternal_home');
                            elseif (isset($data))
                                $maternal_home = $data->maternal_home;
                            else
                                $maternal_home = '';
                            ?>
                            <label for="maternal_home" class="col-sm-2 control-label">{{trans('labels.maternalhome')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="maternal_home" name="maternal_home" placeholder="{{trans('labels.maternalhome')}}" value="{{$maternal_home}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('kul_gotra'))
                                $kul_gotra = old('kul_gotra');
                            elseif (isset($data))
                                $kul_gotra = $data->kul_gotra;
                            else
                                $kul_gotra = '';
                            ?>
                            <label for="kul_gotra" class="col-sm-2 control-label">{{trans('labels.kulgotra')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="kul_gotra" name="kul_gotra" placeholder="{{trans('labels.kulgotra')}}" value="{{$kul_gotra}}">
                            </div>
                        </div>

                        @if(isset($data) && isset($data->ownerChildren) && !empty($data->ownerChildren->toArray()))
                            @forelse($data->ownerChildren->toArray() as $key=>$children)
                                <div class="form-group" id="children_{{$children['id']}}">
                                    @if($key == 0)
                                        <label for="children_title" class="col-sm-2 control-label">{{trans('labels.children')}}</label>
                                    @else
                                        <label for="children_title" class="col-sm-2 control-label"></label>
                                    @endif
                                    <div id='children_name'>
                                        <div class="col-sm-7">
                                            <input  class="form-control" type="text" placeholder=""  id="" name="update_children_name[]" value="{{$children['children_name']}}">
                                            <input  class="form-control" type="hidden" placeholder=""  id="" name="update_children_id[]" value="{{$children['id']}}">
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn bg-purple" onclick="remove_children({{$children['id']}})">-</button>   
                                        </div>
                                       
                                    </div>
                                </div>
                            @empty
                            @endforelse
                            <div class="form-group">
                                <label for="children_name" class="col-sm-2 control-label"></label>
                                <div id='children_name'>
                                    <div class="col-sm-7">
                                        <input  class="form-control" type="text" placeholder=""  id="" name="add_children_name[]" value="">
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn bg-purple add_children">+</button>   
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="form-group">
                                <label for="children_name" class="col-sm-2 control-label">{{trans('labels.children')}}</label>
                                <div id='children_name'>
                                    <div class="col-sm-7">
                                        <input  class="form-control" type="text" placeholder=""  id="" name="add_children_name[]" value="">
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn bg-purple add_children">+</button>   
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div id="add_more_children"></div>
                        <div id="deleted_children"></div>

                        @if(isset($data) && isset($data->ownerSocialActivities) && !empty($data->ownerSocialActivities->toArray()))
                            @forelse($data->ownerSocialActivities->toArray() as $key=>$activity)
                                <div class="form-group" id="activity_{{$activity['id']}}">
                                    @if($key == 0)
                                        <label for="activity_title" class="col-sm-2 control-label">{{trans('labels.socialactivities')}}</label>
                                    @else
                                        <label for="activity_title" class="col-sm-2 control-label"></label>
                                    @endif
                                    <div id='activity_title'>
                                        <div class="col-sm-7">
                                            <input  class="form-control" type="text" placeholder=""  id="" name="update_activity_title[]" value="{{$activity['activity_title']}}">
                                            <input  class="form-control" type="hidden" placeholder=""  id="" name="update_activity_id[]" value="{{$activity['id']}}">
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn bg-purple" onclick="remove_activity({{$activity['id']}})">-</button>   
                                        </div>
                                    </div>
                                </div>
                            @empty
                            @endforelse
                            <div class="form-group">
                                <label for="activity_title" class="col-sm-2 control-label"></label>
                                <div id='activity_title'>
                                    <div class="col-sm-7">
                                        <input  class="form-control" type="text" placeholder=""  id="" name="add_activity_title[]" value="">
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn bg-purple add_activity">+</button>   
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="form-group">
                                <label for="activity_title" class="col-sm-2 control-label">{{trans('labels.socialactivities')}}</label>
                                <div id='activity_title'>
                                    <div class="col-sm-7">
                                        <input  class="form-control" type="text" placeholder=""  id="" name="add_activity_title[]" value="">
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn bg-purple add_activity">+</button>   
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div id="add_more_activities"></div>
                        <div id="deleted_activities"></div>

                        <div class="form-group">
                            <?php

                            if (old('facebook_url'))
                                $facebook_url = old('facebook_url');
                            elseif (isset($data))
                                $facebook_url = $data->facebook_url;
                            else
                                $facebook_url = '';
                            ?>
                            <label for="facebook_url" class="col-sm-2 control-label">{{trans('labels.facebookurl')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="facebook_url" name="facebook_url" placeholder="{{trans('labels.facebookurl')}}" value="{{$facebook_url}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php

                            if (old('twitter_url'))
                                $twitter_url = old('twitter_url');
                            elseif (isset($data))
                                $twitter_url = $data->twitter_url;
                            else
                                $twitter_url = '';
                            ?>
                            <label for="twitter_url" class="col-sm-2 control-label">{{trans('labels.twitterurl')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="twitter_url" name="twitter_url" placeholder="{{trans('labels.twitterurl')}}" value="{{$twitter_url}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php

                            if (old('linkedin_url'))
                                $linkedin_url = old('linkedin_url');
                            elseif (isset($data))
                                $linkedin_url = $data->linkedin_url;
                            else
                                $linkedin_url = '';
                            ?>
                            <label for="website_url" class="col-sm-2 control-label">{{trans('labels.linkedinurl')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="linkedin_url" name="linkedin_url" placeholder="{{trans('labels.linkedinurl')}}" value="{{$linkedin_url}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php

                            if (old('instagram_url'))
                                $instagram_url = old('instagram_url');
                            elseif (isset($data))
                                $instagram_url = $data->instagram_url;
                            else
                                $instagram_url = '';
                            ?>
                            <label for="instagram_url" class="col-sm-2 control-label">{{trans('labels.instagramurl')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="instagram_url" name="instagram_url" placeholder="{{trans('labels.instagramurl')}}" value="{{$instagram_url}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('public_access'))
                                $public_access = old('public_access');
                            elseif (isset($data))
                                $public_access = $data->public_access;
                            else
                                $public_access = '';
                            ?>
                            <label for="public_access" class="col-sm-2 control-label">{{trans('labels.showpersonalinfo')}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="check_box_set" name="public_access" {{($public_access == 1)?'checked':''}}>
                            </div>
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/user/business/owner') }}/{{Crypt::encrypt($businessId)}}">{{trans('labels.cancelbtn')}}</a>
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
<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
<script>

$(document).ready(function () {
    $('.select2').select2();
    $('#mobile').mask('9999999999');
    //CKEDITOR.replace('description');
    // $.validator.addMethod('filesize', function (value, element, param) {
    //     return this.optional(element) || (element.files[0].size <= param)
    // }, 'File size must be less than 5 MB');
    // var id = <?php echo (isset($data) && !empty($data)) ? $data->id : '0'; ?>;
    // if(id == 0)
    // {
    //     var ownerRules = {
    //         full_name: {
    //             required: true
    //         },
    //         mobile:{
    //                 required: true,
    //                 digits: true,
    //                 maxlength: 10,
    //                 minlength: 10,
    //             },
    //         email_id:{
    //             required: true,
    //             email:true
    //         },
    //         photo:
    //         {
    //             required: true,
    //             extension: "jpeg|jpg|bmp|png",
    //             filesize: 5000000 // 5 mb
    //         },
    //         dob:{
    //             required: true
    //         }, 
    //         gender: {
    //             required: true
    //         },
    //         facebook_url:{
    //             url: true
    //         },
    //         twitter_url:{
    //             url: true
    //         },
    //         linkedin_url:{
    //             url: true
    //         },
    //         instagram_url:{
    //             url: true
    //         },
    //         photo:
    //         {
    //             required:true,
    //             extension: "jpeg|jpg|bmp|png",
    //             filesize: 5000000 // 5 mb
    //         },
    //     };
    // }
    // else
    // {
        var ownerRules = {
            full_name: {
                required: true
            }
            // },
            // mobile:{
            //         required: true,
            //         digits: true,
            //         maxlength: 10,
            //         minlength: 10,
            //     },
            // email_id:{
            //     required: true,
            //     email:true
            // },
            // photo:
            // {
            //     extension: "jpeg|jpg|bmp|png",
            //     filesize: 5000000 // 5 mb
            // },
            // dob:{
            //     required: true
            // }, 
            // gender: {
            //     required: true
            // },
            // facebook_url:{
            //     url: true
            // },
            // twitter_url:{
            //     url: true
            // },
            // linkedin_url:{
            //     url: true
            // },
            // instagram_url:{
            //     url: true
            // },
            // photo:
            // {
            //     extension: "jpeg|jpg|bmp|png",
            //     filesize: 5000000 // 5 mb
            // },
        };
    // }

    var FromEndDate = new Date();
    $('#birthdate').datepicker({
        format: 'yyyy-mm-dd',
        endDate: FromEndDate,
        autoclose: true
    });
    
    $("#addowner").validate({
        ignore: "",
        rules: ownerRules,
        messages: {
            full_name: {
                required: "<?php echo trans('labels.namerequired');?>"
            }
        },
        errorPlacement: function(error, element) {
            if(element.attr("name") == "gender") 
            {
                $('.gender_error').append(error)
            } 
            else 
            {
                error.insertAfter(element);
            }
        }            
    });
    
});

$('.add_children').click(function() {

    var html = '<div class="form-group"><label for="add_children" class="col-sm-2 control-label"></label><div id="add_children"><div class="col-sm-7"><input  class="form-control" type="text" placeholder=""  id="" name="add_children_name[]" value=""></div><div class="col-sm-1"><button type="button" class="btn bg-purple remove_children" onClick="delete_children(this)">-</button></div></div></div>';

    $('#add_more_children').append(html);
});

function remove_children(id)
{
    $('#children_'+id).remove()
    $('#deleted_children').append('<input type="hidden" name="deleted_children[]" value="'+id+'">');
}

function delete_children(val)
{
    $(val).parent().parent().parent().remove();
}

$('.add_activity').click(function() {

    var html = '<div class="form-group"><label for="activity_title" class="col-sm-2 control-label"></label><div id="activity_title"><div class="col-sm-7"><input  class="form-control" type="text" placeholder=""  id="" name="add_activity_title[]" value=""></div><div class="col-sm-1"><button type="button" class="btn bg-purple remove_activity" onClick="delete_activity(this)">-</button></div></div></div>';

    $('#add_more_activities').append(html);
});

function remove_activity(id)
{
    $('#activity_'+id).remove()
    $('#deleted_activities').append('<input type="hidden" name="deleted_activities[]" value="'+id+'">');
}

function delete_activity(val)
{
    $(val).parent().parent().parent().remove();
}

</script>
@stop