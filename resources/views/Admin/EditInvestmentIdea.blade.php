@extends('Admin.Master')

@section('content')
<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.investment_opportunities')}}
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.investment_opportunity')}}</h3>
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
                <form id="addinvestment" class="form-horizontal" method="post" action="{{ url('admin/investmentideas/save') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo (isset($data) && !empty($data)) ? $data->user_id : Config::get('constant.SUPER_ADMIN_ROLE_ID') ?>">
                   
                    
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('title'))
                                $title = old('title');
                            elseif (isset($data))
                                $title = $data->title;
                            else
                                $title = '';
                            ?>
                            <label for="title" class="col-sm-2 control-label">{{trans('labels.title')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="title" name="title" placeholder="{{trans('labels.title')}}" value="{{$title}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('category_id'))
                                $category_id = old('category_id');
                            elseif (isset($data))
                                $category_id = $data->category_id;
                            else
                                $category_id = '';
                            ?>
                            <label for="category_id" class="col-sm-2 control-label">{{trans('labels.categoryid')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <select name="category_id" data="" class="form-control">
                                    <option value="">Select {{trans('labels.parentcategory')}}</option>
                                    @forelse($parentCategories as $category)
                                        <option class="type_parent_cat cat_type" value="{{$category->id}}" {{( $category_id == $category->id) ? 'selected' : ''}}>
                                            {{$category->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

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
                               <input type="text" class="form-control" id="description" name="description" placeholder="{{trans('labels.description')}}" value="{{$description}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('investment_amount_start'))
                                $investment_amount_start = old('investment_amount_start');
                            elseif (isset($data))
                                $investment_amount_start = $data->investment_amount_start;
                            else
                                $investment_amount_start = '';

                            if (old('investment_amount_end'))
                                $investment_amount_end = old('investment_amount_end');
                            elseif (isset($data))
                                $investment_amount_end = $data->investment_amount_end;
                            else
                                $investment_amount_end = '';
                            ?>
                            <label for="investment_amount_start" class="col-sm-2 control-label">{{trans('labels.investment_amount')}}<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" name="investment_amount_start" placeholder="{{trans('labels.investment_amount_start')}}" value="{{$investment_amount_start}}">
                            </div>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" name="investment_amount_end" placeholder="{{trans('labels.investment_amount_end')}}" value="{{$investment_amount_end}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('project_duration'))
                                $project_duration = old('project_duration');
                            elseif (isset($data))
                                $project_duration = $data->project_duration;
                            else
                                $project_duration = '';
                            ?>
                            <label for="project_duration" class="col-sm-2 control-label">{{trans('labels.project_duration')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="project_duration" name="project_duration" placeholder="{{trans('labels.project_duration')}}" value="{{$project_duration}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('location'))
                                $location = old('location');
                            elseif (isset($data))
                                $location = $data->location;
                            else
                                $location = '';
                            ?>
                            <label for="location" class="col-sm-2 control-label">{{trans('labels.location')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="location" placeholder="{{trans('labels.location')}}" value="{{$location}}" onFocus="initializeAutocomplete()" id="locality"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('city'))
                                $city = old('city');
                            elseif (isset($data))
                                $city = $data->city;
                            else
                                $city = '';

                            ?>
                            <label for="city" class="col-sm-2 control-label">{{trans('labels.city')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <select name="city" data="" class="form-control">
                                    <option value="">Select {{trans('labels.city')}}</option>
                                    @forelse($cities as $val)
                                        <option class="type_parent_cat cat_type" value="{{$val->name}}" {{( $city == $val->name) ? 'selected' : ''}}>
                                            {{$val->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="image">
                            <label for="images" class="col-sm-2 control-label">{{trans('labels.images')}}</label>
                            <div class="col-sm-4">
                                <input type="file" id="investment_images" name="investment_images[]" multiple >
                            </div>                            
                        </div>

                        @if(isset($data) && !empty($data))
                            @if(!empty($data->investmentIdeasFiles))
                                <div class="form-group" id="product_images">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-4">
                                            @forelse($data->investmentIdeasFiles as $file)
                                                @if($file->file_type == 1)
                                                    @if(Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH').$file->file_name) > 0) 
                                                        <div class="business_img" id="investment_img_{{$file->id}}">
                                                        <i class="fa fa-times-circle" aria-hidden="true" onclick="return deleteInvestmentImage({{$file->id}});"></i>
                                                            <img src="{{ Storage::url(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_IMAGES_PATH').$file->file_name) }}" width="50" height="50"/>
                                                        </div>
                                                    @endif                                                
                                                @endif                                                
                                            @empty
                                           @endforelse
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="form-group" id="image">
                            <label for="docs" class="col-sm-2 control-label">{{trans('labels.documents')}}</label>
                            <div class="col-sm-4">
                                <input type="file" id="investment_docs" name="investment_docs[]" multiple >
                            </div>                            
                        </div>

                        @if(isset($data) && !empty($data))
                            @if(!empty($data->investmentIdeasFiles))
                                <div class="form-group" id="product_images">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-4">
                                            @forelse($data->investmentIdeasFiles as $file)
                                                @if($file->file_type == 3)
                                                    @if(Storage::size(Config::get('constant.INVESTMENT_IDEAS_FILE_ORIGINAL_DOCS_PATH').$file->file_name) > 0) 
                                                        <div class="business_img" id="investment_img_{{$file->id}}">
                                                            <i class="fa fa-times-circle" aria-hidden="true" onclick="return deleteInvestmentImage({{$file->id}});"></i>
                                                            <i class="fa fa-file-pdf-o" style="font-size: 33px" aria-hidden="true" style="font-size: 30px;"></i> 
                                                        </div> 
                                                    @endif                                                
                                                @endif                                                
                                            @empty
                                           @endforelse
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if(isset($data) && isset($data->investmentIdeasFiles) && !empty($data->investmentIdeasFiles))
                            <?php $videos = $data->investmentIdeasFiles->where('file_type','2'); $cnt = 0; ?>
                            @forelse($videos as $key=>$file)

                                <div class="form-group" id="video_{{$file->id}}">
                                    @if($cnt == 0)
                                        <label for="investment_video" class="col-sm-2 control-label">{{trans('labels.videos')}}</label>
                                    @else
                                        <label for="investment_video" class="col-sm-2 control-label"></label>
                                    @endif
                                    
                                    <div id='investment_video'>
                                        <div class="col-sm-7">
                                            <input  class="form-control" type="text" placeholder=""  id="" name="update_investment_video[]" value="{{$file->file_name}}">
                                            <input  class="form-control" type="hidden" placeholder=""  id="" name="update_investment_id[]" value="{{$file->id}}">
                                        </div>
                                        @if($key > 0)
                                        <div class="col-sm-1">
                                            <button type="button" class="btn bg-purple" onclick="remove_video({{$file->id}})">-</button>   
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <?php  $cnt++; ?>
                            @empty
                            @endforelse
                            <div class="form-group">
                                <label for="investment_videos" class="col-sm-2 control-label"></label>
                                <div id='investment_videos'>
                                    <div class="col-sm-7">
                                        <input  class="form-control" type="text" placeholder=""  id="" name="add_investment_video[]" value="">
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn bg-purple add_video">+</button>   
                                    </div>
                                </div>
                            </div>
                            @else
                                <div class="form-group">
                                    <label for="investment_videos" class="col-sm-2 control-label">{{trans('labels.videos')}}</label>
                                    <div id='investment_videos'>
                                        <div class="col-sm-7">
                                            <input  class="form-control" type="text" placeholder=""  id="" name="add_investment_video[]" value="">
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn bg-purple add_video">+</button>   
                                        </div>
                                    </div>
                                </div>
                            @endif
                        
                        
                        
                        <div id="add_more_videos"></div>
                        <div id="deleted_videos"></div>

                        <div class="form-group">
                            <?php
                            if (old('member_name'))
                                $member_name = old('member_name');
                            elseif (isset($data))
                                $member_name = $data->member_name;
                            else
                                $member_name = '';
                            ?>
                            <label for="member_name" class="col-sm-2 control-label">{{trans('labels.member_name')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="member_name" name="member_name" placeholder="{{trans('labels.member_name')}}" value="{{$member_name}}">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <?php
                            if (old('member_email'))
                                $member_email = old('member_email');
                            elseif (isset($data))
                                $member_email = $data->member_email;
                            else
                                $member_email = '';
                            ?>
                            <label for="member_email" class="col-sm-2 control-label">{{trans('labels.member_email')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="member_email" name="member_email" placeholder="{{trans('labels.member_email')}}" value="{{$member_email}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('member_phone'))
                                $member_phone = old('member_phone');
                            elseif (isset($data))
                                $member_phone = $data->member_phone;
                            else
                                $member_phone = '';
                            ?>
                            <label for="member_phone" class="col-sm-2 control-label">{{trans('labels.member_phone')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="member_phone" name="member_phone" placeholder="{{trans('labels.member_phone')}}" value="{{$member_phone}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('offering_percent'))
                                $offering_percent = old('offering_percent');
                            elseif (isset($data))
                                $offering_percent = $data->offering_percent;
                            else
                                $offering_percent = '';
                            ?>
                            <label for="offering_percent" class="col-sm-2 control-label">{{trans('labels.offering_percent')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="offering_percent" name="offering_percent" placeholder="{{trans('labels.offering_percent')}}" value="{{$offering_percent}}">
                            </div>
                        </div>

                        
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/investmentideas') }}">{{trans('labels.cancelbtn')}}</a>
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
<!-- <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places"></script> -->
<script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyDKPmdN1OngFz0KzAVH_ahBrK6JIjFlMNc"></script>
<!-- Include tags in input box -->
<script src="{{ asset('js/jquery.caret.min.js') }}"></script>
<script src="{{ asset('js/jquery.tag-editor.js') }}"></script>

<script type="text/javascript">
    $('.add_video').click(function() {

        var html = '<div class="form-group"><label for="investment_videos" class="col-sm-2 control-label"></label><div id="investment_videos"><div class="col-sm-7"><input  class="form-control" type="text" placeholder=""  id="" name="add_investment_video[]" value=""></div><div class="col-sm-1"><button type="button" class="btn bg-purple remove_video" onClick="return delete_video(this);">-</button></div></div></div>';

        $('#add_more_videos').append(html);
    });

    function remove_video(id)
    {
        $('#video_'+id).remove()
        $('#deleted_videos').append('<input type="hidden" name="deleted_videos[]" value="'+id+'">');
    }

    function delete_video(val)
    {
        $(val).parent().parent().parent().remove();
    }
    function initializeAutocomplete(){
        var input = document.getElementById('locality');
        var options = {}

        var autocomplete = new google.maps.places.Autocomplete(input, options);

        google.maps.event.addListener(autocomplete, 'place_changed', function() {
          var place = autocomplete.getPlace(); console.log(place);
          var lat = place.geometry.location.lat();
          var lng = place.geometry.location.lng();
          var placeId = place.place_id;
          // to set city name, using the locality param
          var componentForm = {
            locality: 'short_name',
          };
          for (var i = 0; i < place.address_components.length; i++) {
            var addressType = place.address_components[i].types[0];
            if (componentForm[addressType]) {
                var val = place.address_components[i][componentForm[addressType]];
                $("#address_city").val(val).trigger('change');
            }
            var addr = place.address_components[i];
            var getCountry;
            if (addr.types[0] == 'country') {
                getCountry = addr.long_name;
                $("#address_country").val(getCountry).trigger('change');
            }
            var getState;
            if (addr.types[0] == 'administrative_area_level_1') {
                getState = addr.long_name;
                $("#address_state").val(getState).trigger('change');
            }
            var getPostalCode;
            if (addr.types[0] == 'postal_code') {
                getPostalCode = addr.long_name;
                $("#address_pincode").val(getPostalCode);
            }
            var getLocality;
            if (addr.types[0] == 'locality') {
                getLocality = addr.long_name;
                $("#address_locality").val(getLocality);
            }
            var getStreetAddress;
            if (addr.types[0] == 'route') {
                getStreetAddress = addr.long_name;
                $("#street_address").val(getStreetAddress);
            }

            }
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;
            // document.getElementById("location_id").value = placeId;
            $('#addressExtraFields').show();
        });
    }
</script>
<script>

$(document).ready(function () {
   
    
    //CKEDITOR.replace('description');
    $.validator.addMethod('filesize', function (value, element, param) {
        return this.optional(element) || (element.files[0].size <= param)
    }, 'File size must be less than 5 MB');
    var id = <?php echo (isset($data) && !empty($data)) ? $data->id : '0'; ?>;
    
    var investmentRules = {
        title: {
            required: true
        },
        category_id:{
            required: true,
        },
        description:{
            required: true,
        },
        investment_amount_start:
        {
            required: true,
            number: true
        },
        investment_amount_end:
        {
            required: true,
            number: true
        },
        project_duration:{
            required: true
        }, 
        location: {
            required: true
        },
        city:{
            required: true
        },
        member_name:{
            required: true
        }
    };
    
    
    $("#addinvestment").validate({
        ignore: "",
        rules: investmentRules,
        messages: {
            title: {
                required: "<?php echo trans('labels.titlerequired'); ?>"
            },
            category_id:{
                required: "<?php echo trans('labels.categoryrequired'); ?>"
            },
            description:{
                required: "<?php echo trans('labels.descriptionrequired'); ?>"
            },
            investment_amount_start:
            {
                required: "<?php echo trans('labels.startamountrequired'); ?>"
            },
            investment_amount_end:
            {
                required: "<?php echo trans('labels.endamountrequired'); ?>,"
            },
            project_duration:{
                required: "<?php echo trans('labels.projectdurationrequired'); ?>"
            }, 
            location: {
                required: "<?php echo trans('labels.locationrequired'); ?>"
            },
            city:{
                required: "<?php echo trans('labels.cityrequired'); ?>"
            },
            member_name:{
                required: "<?php echo trans('labels.membernamerequired'); ?>"
            }    
        }
                    
    });
});


</script>
@stop