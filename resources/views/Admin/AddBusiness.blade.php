@extends('Admin.Master')

@section('content')
<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.business')}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-users"></i> {{$userDetail->name}}</a></li>
        <li class="active">{{trans('labels.business')}}</li>
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
                    <h3 class="box-title">Add {{trans('labels.business')}}</h3>
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
                <form id="addbusiness" class="form-horizontal" method="post" action="{{ url('admin/user/business/save') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="approved" value="1">
                    <!-- <input type="hidden" name="old_business_images" value="<?php echo (isset($businessImages) && !empty($businessImages)) ? '': '' ?>"> -->
                   
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
                        
<!--                        <div class="form-group">
                            <label for="business_slug" class="col-sm-2 control-label">{{trans('labels.businessslug')}}</label>
                            <div class="col-sm-8">
                                <input type="text" readonly="true" class="form-control" id="business_slug" name="business_slug" placeholder="{{trans('labels.businessslug')}}" value="">
                            </div>
                        </div>-->

                        <?php
                            if (old('category_hierarchy'))
                                $category_hierarchy = old('category_hierarchy');
                            elseif (isset($data))
                                $category_hierarchy = $data->category_id;
                            else
                                $category_hierarchy = '';
                        ?>
                        @if($category_hierarchy != '')
                            <div class="form-group">
                                <label for="category_id" class="col-sm-2 control-label" >
                                    {{trans('labels.lblcategories')}}
                                </label>
                                <div class="col-sm-8" style="height:35px !important;">
                                    <?php  $explodeCategories = explode(',',$category_hierarchy); ?>
                                    
                                        @foreach($explodeCategories as $category)
                                            @if($category > 0)
                                                <?php  $categoryDetail = Helpers::getCategoryById($category); ?>
                                                @if(!empty($categoryDetail))
                                                    <span class="label label-success">{{$categoryDetail['name']}}</span> 
                                                @endif 
                                            @endif
                                        @endforeach
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
                            <label for="category_id" class="col-sm-2 control-label">{{(isset($data)) ? trans('labels.changecategory') : trans('labels.category') }}</label>
                            <div class="col-sm-8">
                                <select name="parent_category" data="" class="form-control" onchange="return getParentSubCategory(this.value,1);">
                                    <option value="">Select {{trans('labels.parentcategory')}}</option>
                                    @forelse($parentCategories as $category)
                                        <option class="type_parent_cat cat_type" value="{{$category->id}}">
                                            {{$category->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>
                        
                        <div id="subcategory_0" class="subcategory_class" style="display: none;"></div>

                        <div id="subcategory_1" class="subcategory_class"></div>

                        <div class="form-group">
                            <label for="selected_subcat" class="col-sm-2 control-label">&nbsp;</label>   
                            <div id="selected_subcat" class="col-sm-8">
                                
                            </div>
                        </div>

                        <div  id="business_meta" class="business_meta" style="display: none;">
                            <div class="form-group">
                                <label for="categoty_tags" class="col-sm-2 control-label"></label>
                                <div class="col-sm-8">
                                    <select name="categoty_tags" data="" class="form-control select2" id="categoty_tags" onchange="addTagsToBusiness(this)">
                                        <option value="">Select Metatags</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="metatags" class="col-sm-2 control-label">Metatags</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="metatags" name="metatags" value="">
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
                               <textarea type="text" class="form-control" id="description" name="description" placeholder="{{trans('labels.description')}}">{{$description}}</textarea>
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
                            <label for="email_id" class="col-sm-2 control-label">
                                {{trans('labels.emailid')}}
                            </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="email_id" name="email_id" placeholder="{{trans('labels.emailid')}}" value="{{$email_id}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('business_logo'))
                                $business_logo = old('business_logo');
                            elseif (isset($business_logo))
                                $business_logo = $data->business_logo;
                            else
                                $business_logo = '';
                            ?>
                            <label for="business_logo" class="col-sm-2 control-label">{{trans('labels.logo')}}<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="file" id="business_logo" name="business_logo" class="form-control">
                            </div>
                        </div>

                        <div class="form-group" id="image">
                            <label for="images" class="col-sm-2 control-label">Images<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="file" id="business_images" name="business_images[]" class="form-control business_image_array" multiple >
                            </div>                            
                        </div>
                       

                        <div class="form-group">
                             <?php
                            if (old('phone'))
                                $phone = old('phone');
                            elseif (isset($data))
                                $phone = $data->phone;
                            else
                                $phone = '';
                            ?>
                            <label for="phone" class="col-sm-2 control-label">{{trans('labels.landline')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="{{trans('labels.phone')}}" value="{{$phone}}">
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
                            <label for="mobile" class="col-sm-2 control-label">{{trans('labels.mobile')}}<span class="star_red">*</span></label>
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
                            if (old('address'))
                                $address = old('address');
                            elseif (isset($data))
                                $address = $data->address;
                            else
                                $address = '';
                            ?>
                            <label for="address" class="col-sm-2 control-label">{{trans('labels.address')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="address" placeholder="{{trans('labels.address')}}" value="{{$address}}" onFocus="initializeAutocomplete()" id="locality"/>
                            </div>
                        </div>

                        <div class="form-group clearfix" id="addressExtraFields" style="display:inline;">
                            <!-- <div class="row"> -->
                                 <div class="col-sm-4 col-sm-offset-2">
                                    {{trans('labels.latitude')}}<input type="text" name="latitude" class="form-control" value="" id="latitude" />
                                </div>
                                <div class="col-sm-4">
                                    {{trans('labels.longitude')}}<input type="text" name="longitude" class="form-control" value="" id="longitude"/>
                                </div>
                                <label for="address" class="col-sm-2 control-label"></label>                        
                                <div class="col-sm-4 col-sm-offset-2">
                                    {{trans('labels.streetaddress')}}<input type="text" class="form-control" name="street_address" value="" id="street_address"/>
                                </div>
                                <div class="col-sm-4">
                                    {{trans('labels.locality')}}<input type="text" class="form-control" value="" name="locality" id="address_locality">
                                </div>
                            <!-- </div> -->
                            <div class=" country_outer">
                                <div class="col-sm-4 col-sm-offset-2">
                                    <?php $countries = Helpers::getCountries(); ?>
                                    {{trans('labels.country')}}
                                    <select name="country" id="address_country" class="form-control select2">
                                        <option value="">Select {{trans('labels.country')}}</option>
                                        @forelse($countries as $country)
                                            <option class="type_parent_cat cat_type" value="{{$country->name}}">
                                                {{$country->name}}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <?php $states = Helpers::getStates(); ?>
                                    {{trans('labels.state')}}
                                    <select name="state" id="address_state" class="form-control select2">
                                        <option value="">Select {{trans('labels.state')}}</option>
                                        @forelse($states as $state)
                                            <option class="type_parent_cat cat_type" value="{{$state->name}}">
                                                {{$state->name}}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                           <!--  <div class="row"> -->
                                <div class="col-sm-4 col-sm-offset-2">
                                    <?php $cities = Helpers::getCities(); ?>
                                    {{trans('labels.city')}}
                                    <select id="address_city" name="city" class="form-control select2">
                                        <option value="">Select {{trans('labels.city')}}</option>
                                        @forelse($cities as $city)
                                            <option class="type_parent_cat cat_type" value="{{$city->name}}">
                                                {{$city->name}}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    {{trans('labels.taluka')}}<input type="text" name="taluka" class="form-control" value="" id="address_taluka"/>
                                </div>
                           <!--  </div> -->
                           <!--  <div class="row"> -->
                                <div class="col-sm-4 col-sm-offset-2">
                                    {{trans('labels.district')}}<input type="text" name="district" class="form-control" value="" id="address_district"/>
                                </div>
                                <div class="col-sm-4">
                                    {{trans('labels.pincode')}}<input type="text" name="pincode" class="form-control" value="" id="address_pincode"/>
                                </div>
                            <!-- </div> -->
                            <!--  <div class="row"> -->
                               
                            <!-- </div> -->
                        </div>
                        
                        <!-- <input type="hidden" name="latitude" id="latitude" value=""/>
                        <input type="hidden" name="longitude" id="longitude" value=""/> -->
                        <input type="hidden" name="hidden_latitude" id="hidden_latitude" value=""/>
                        <input type="hidden" name="hidden_longitude" id="hidden_longitude" value=""/>

                        <div class="form-group">
                            <?php

                            if (old('website_url'))
                                $website_url = old('website_url');
                            elseif (isset($data))
                                $website_url = $data->website_url;
                            else
                                $website_url = '';
                            ?>
                            <label for="website_url" class="col-sm-2 control-label">{{trans('labels.websiteurl')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="website_url" name="website_url" placeholder="{{trans('labels.websiteurl')}}" value="{{$website_url}}">
                            </div>
                        </div>

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

                            if (old('establishment_year'))
                                $establishment_year = old('establishment_year');
                            elseif (isset($data))
                                $establishment_year = $data->establishment_year;
                            else
                                $establishment_year = '';
                            ?>
                            <label for="website_url" class="col-sm-2 control-label">{{trans('labels.establishmentyear')}}</label>
                            <div class="col-sm-8">
                                <input  class="form-control" type="text" placeholder="click to show datepicker"  id="establishment_year" name="establishment_year" value="{{$establishment_year}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php

                            if (old('timezone'))
                                $timezone = old('timezone');
                            else
                                $timezone = 'Asia/Kolkata';
                            
                                $timezones = Helpers::getTimezone();
                            ?>
                            <label for="website_url" class="col-sm-2 control-label">{{trans('labels.hoursofoperationtimezone')}}</label>
                            <div class="col-sm-8">
                                <select name="timezone" data="" class="form-control">
                                    <option value="">Select Timezone</option>
                                    @forelse($timezones as $zone)
                                        <option class="type_parent_cat cat_type" value="{{$zone->name}}" {{($zone->name == $timezone) ? 'selected' : ''}}>
                                            {{$zone->value}} - {{$zone->name}} 
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="website_url" class="col-sm-2 control-label"></label>
                            <div class="col-sm-8">

                            <input type="hidden" name="working_hours_id" value="<?php echo (isset($data) && !empty($data->businessWorkingHours->id)) ? $data->businessWorkingHours->id : '0' ?>">
                               
                                <table class="table open_hours_table" border=0>
                                    <tr style="font-weight: bold;">
                                        <td style="width: 100px;">Days</td>
                                        <td style="width: 100px;">Open/Close</td>
                                        <td style="width: 200px;">Start time</td>
                                        <td style="width: 200px;">End time</td>
                                    </tr>
                                    <?php 
                                        $days = Helpers::getWeekDays();
                                        $times = Helpers::getTime();
                                    ?>
                                    @foreach($days as $day)
                                    <?php 
                                        $day_start_time = $day.'_start_time';
                                        $day_end_time = $day.'_end_time';
                                        $day_open_close = $day.'_open_close';

                                        if (isset($data) && isset($data->businessWorkingHours->$day_start_time) && isset($data->businessWorkingHours->$day_end_time) && isset($data->businessWorkingHours->$day_open_close))
                                        {
                                            $day_start_am_pm = date('A',$data->businessWorkingHours->$day_start_time);
                                            $day_start_time = $data->businessWorkingHours->$day_start_time;
                                            $day_start_time = date('g:i',$day_start_time);

                                            $day_end_am_pm = date('A',$data->businessWorkingHours->$day_end_time);
                                            $day_end_time = $data->businessWorkingHours->$day_end_time;
                                            $day_end_time = date('g:i',$day_end_time);

                                            $day_open_close = $data->businessWorkingHours->$day_open_close;
                                        }
                                        else{
                                            $day_start_time = '9:00';
                                            $day_start_am_pm = '';

                                            $day_end_time = '6:00';
                                            $day_end_am_pm = 'PM';

                                            $day_open_close = '';
                                        }

                                    ?>
                                    <tr>
                                        <td>{{ucfirst($day)}}</td>
                                        <td>
                                            <select name="{{$day}}_open_close" onchange="open_close(this.value,'{{$day}}')">
                                                <option value="1" {{($day_open_close == '1')?'selected':''}}>Open</option>
                                                <option value="0" {{($day_open_close == '0' || ($day == 'sun'))?'selected':''}}>Close</option>
                                            </select>
                                        </td>
                                       
                                        <td class="{{$day}}_start_time"  style={{($day_open_close == Config::get('constant.BUSINESS_WORKING_CLOSE_FLAG') || ($day == 'sun')) ? 'display:none;' : ''}}>
                                            <select name="{{$day}}_start_time" style="width: 80px;">
                                                @foreach($times as $time)
                                                    <option value="{{$time}}" {{($day_start_time == $time) ? 'selected' : ''}}>{{$time}}</option>
                                                @endforeach
                                            </select>
                                            <select name="{{$day}}_start_time_am_pm">
                                                <option value="AM" {{($day_start_am_pm == 'AM')?'selected':''}}>AM</option>
                                                <option value="PM" {{($day_start_am_pm == 'PM')?'selected':''}}>PM</option>
                                            </select>
                                        </td>
                                        <td class="{{$day}}_end_time" style={{($day_open_close == Config::get('constant.BUSINESS_WORKING_CLOSE_FLAG') || ($day == 'sun')) ? 'display:none;' : ''}}>
                                            <select name="{{$day}}_end_time" style="width: 80px;">
                                                @foreach($times as $time)
                                                    <option value="{{$time}}" {{($day_end_time == $time) ? 'selected' : ''}}>{{$time}}</option>
                                                @endforeach
                                            </select>
                                            <select name="{{$day}}_end_time_am_pm">
                                                <option value="AM" {{($day_end_am_pm == 'AM')?'selected':''}}>AM</option>
                                                <option value="PM" {{($day_end_am_pm == 'PM')?'selected':''}}>PM</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                               
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="activity_title" class="col-sm-2 control-label">Business Activities</label>
                            <div id='activity_title'>
                                <div class="col-sm-7">
                                    <input  class="form-control" type="text" placeholder=""  id="" name="add_activity_title[]" value="">
                                </div>
                                <div class="col-sm-1">
                                    <button type="button" class="btn bg-purple add_activity">+</button>   
                                </div>
                            </div>
                        </div>
                        
                        
                        <div id="add_more_activities"></div>
                        <div id="deleted_activities"></div>

                        <div class="form-group">
                            <?php
                            if (old('promoted'))
                                $promoted = old('promoted');
                            elseif (isset($data))
                                $promoted = $data->promoted;
                            else
                                $promoted = '';
                            ?>
                            <label for="promoted" class="col-sm-2 control-label">{{trans('labels.promoted')}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="check_box_set" name="promoted"  {{($promoted == 1)?'checked':''}}>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('membership_type'))
                                $membership_type = old('membership_type');
                            elseif (isset($data))
                                $membership_type = $data->membership_type;
                            else
                                $membership_type = '';
                            ?>
                            <label for="membership_type" class="col-sm-2 control-label">{{trans('labels.membershiptype')}}</label>
                            <div class="col-sm-8">
                                <select name="membership_type" data="" class="form-control">
                                    <option value="0">Basic</option>
                                    <option value="1">Premium</option>
                                    <option value="2">Lifetime</option>
                                </select>
                            </div>
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/user/business') }}/{{Crypt::encrypt($userId)}}">{{trans('labels.cancelbtn')}}</a>
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

<script type="text/javascript">
   <?php  if (!isset($data) && empty($data)){ ?>
    $('#name').keyup(function () 
    {
//        var str = $(this).val();
//        str = $.trim(str);
//        str = str.replace(/[^a-zA-Z0-9\s]/g, "");
//        str = str.toLowerCase();
//        str = str.replace(/\s/g, '-');
//        $('#business_slug').val(str);
    });
    
    <?php } ?>
</script>

<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
<script type="text/javascript">
    //$('#mobile').mask('9999999999');

$(document).ready(function () {

    //CKEDITOR.replace('description');
    $('.select2').select2();
    $('#metatags').tagEditor({
        placeholder: 'Enter Metatags ...',
    });
    var FromEndDate = new Date();
    $('#establishment_year').datepicker({
                    minViewMode: 'years',
                    autoclose: true,
                    format: 'yyyy',
                    endDate: FromEndDate
                });  
    $.validator.addMethod('filesize', function (value, element, param) {
        return this.optional(element) || (element.files[0].size <= param)
    }, 'File size must be less than 5 MB');
   
    
        var businessRules = 
        {
            name: {
                required: true
            },
            description:{
                required: true
            },
            email_id: {
                //required: true,
                email: true
            },
            phone:{
                maxlength: 15,
            },
            mobile: {
                required: true,
                digits: true,
                maxlength: 13,
                minlength: 6,
            },
            address:{
                required: true
            },
            'business_images[]':
            {
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            },
            business_logo:{
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            },
            establishment_year:{
              //  required: true,
                minlength:4
            },
            facebook_url:{
                url: true
            },
            twitter_url:{
                url: true
            },
            linkedin_url:{
                url: true
            },
            instagram_url:{
                url: true
            }
        };
    
    
    $("input.ipfile").each(function(){
       $(this).rules("add", {
           required:true,
           accept: "jpg|jpeg"
       });                   
    });
    $("#addbusiness").validate({
        ignore: "",
        rules: businessRules,
        messages: {
            name: {
                required: "<?php echo trans('labels.namerequired')?>"
            },
            description:{
                required: "<?php echo trans('labels.descriptionrequired')?>"
            },
            email_id: {
                email_id: "<?php echo trans('labels.invalidemail') ?>"
            },
            phone: {
                digits: "<?php echo trans('labels.digitsrequired') ?>",
            },
            mobile: {
                required: "<?php echo trans('labels.mobilerequired') ?>",
                // maxlength: "<?php echo trans('labels.mobilelengthrequired') ?>",
                // minlength: "<?php echo trans('labels.mobilelengthrequired') ?>"
            },
            address:{
                required: "<?php echo trans('labels.addressrequired') ?>"
            },
            establishment_year:{
              //  required: "<?php echo trans('labels.yearrequired') ?>",
                minlength: "<?php echo trans('labels.yearminlength') ?>"
            },
            facebook_url:{
                url: "<?php echo trans('labels.validurlrequired') ?>"
            },
            twitter_url:{
                url: "<?php echo trans('labels.validurlrequired') ?>"
            },
            linkedin_url:{
                url: "<?php echo trans('labels.validurlrequired') ?>"
            },
            instagram_url:{
                url: "<?php echo trans('labels.validurlrequired') ?>"
            },
            'business_images[]':{
                required: "<?php echo trans('labels.imagerequired') ?>"
            },
            business_logo:{
                required: "<?php echo trans('labels.logorequired') ?>"
            }
        }            
    });
    
});

    function getParentSubCategory(categoryId, level)
    {
        var categoryArray = [categoryId];
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "POST",
            url: '/admin/search/subcategory',
            data: {categoryIds: categoryArray, level: level},
            success: function( data ) {

                if(level == 1)
                {   
                    $('#subcategory_0').html(data);
                    $('#subcategory_1').html('');
                    //$('#selected_subcat').html('');
                    $('#business_meta').show();
                    
                    $.ajax({
                            headers: { 'X-CSRF-TOKEN': token },
                            type: "POST",
                            url: '/admin/search/businessmetatags',
                            data: {categoryId: categoryId},
                            success: function( metatags ) {
                                $('#categoty_tags')
                                    .find('option')
                                    .remove()
                                    .end()
                                    .append('<option value="">Select Metatags</option>');
                                    
                                $.each(metatags, function(key, value) {   
                                    $("#categoty_tags option[value='"+value+"']").remove();  
                                    $('#categoty_tags').append($("<option></option>")
                                                       .attr("value",value)
                                                       .text(value)); 
                                });
                        }
                    });  
                }

                
                if(data)
                {

                    $('#subcategory_1').html(data);
                    $('#subcategory_0').hide();

                }
                else
                {
                    $('#subcategory_0').show();
                    $('#subcategory_1').html('<input type="hidden" name="category_id[]" value="'+categoryId+'"/>'+'<span style="margin-left: 185px !important;">No Subcategory Found</span>');
                }

            }
        });
    }

    function addCategotyHierarchy(catId)
    {
        var flag = 0;
        $('input[name^="category_id"]').each(function() {
            if($(this).val() == catId)
            {
                alert('Category is already selected ');
                flag = 1;
            }
        });
        if(flag == 0)
        {
            var token = '<?php echo csrf_token() ?>';
            $.ajax({
                headers: { 'X-CSRF-TOKEN': token },
                type: "POST",
                url: '/admin/search/addCategotyHierarchy',
                data: {catId: catId},
                success: function( data ) {
                    $('#selected_subcat').append(data);
                    $('#subcategory_0').show();
                    $('#subcategory_1').html('');
                    $.ajax({
                                headers: { 'X-CSRF-TOKEN': token },
                                type: "POST",
                                url: '/admin/search/businessmetatags',
                                data: {categoryId: catId},
                                success: function( metatags ) {
                                    
                                    $.each(metatags, function(key, value) {   
                                        $("#categoty_tags option[value='"+value+"']").remove();  
                                        $('#categoty_tags').append($("<option></option>")
                                                           .attr("value",value)
                                                           .text(value)); 
                                    });
                            }
                        }); 
                    }
                });
        }
    }

    function remove_subcat(catRemove,catId)
    {
        $(catRemove).parent().parent().remove();
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
                headers: { 'X-CSRF-TOKEN': token },
                type: "POST",
                url: '/admin/search/businessmetatags',
                data: {categoryId: catId},
                success: function( metatags ) {
                    
                    $.each(metatags, function(key, val) {   
                        $("#categoty_tags option[value='"+val+"']").remove();
                    });
            }
        }); 

    }

    function addTagsToBusiness(selectval)
    {
        $('#metatags').tagEditor('addTag', selectval.value);
    }
// function getSubCategory(categoryIds, level)
// {
//     var categoryArray = [];
//     for (var i = 0; i < categoryIds.options.length; i++) 
//     {
//         if (categoryIds.options[i].selected) 
//         {
//            categoryArray.push(categoryIds.options[i].value);
//         }
//     }
    
//     var token = '<?php echo csrf_token() ?>';
//     $.ajax({
//         headers: { 'X-CSRF-TOKEN': token },
//         type: "POST",
//         url: '/admin/search/subcategory',
//         data: {categoryIds: categoryArray, level: level},
//         success: function( data ) {
//             $('#subcategory_'+level).html(data);
//             $('#subcategory_'+level).append('<input type="hidden" value="'+categoryArray.toString()+'" name="category_id[]" class="subcategory_class"/>');
//             $('#subcategory_'+level).nextAll('.subcategory_class').remove();
//             $('#subcategory_'+level).after('<div class="subcategory_class" id="subcategory_'+(level+1)+'"></div>');
//         }
//     });
// }

function deleteBusinessImage(imageId)
{
    var x = confirm("Are you sure you want to delete?");
    if (x)
        {
            var token = '<?php echo csrf_token() ?>';
            $.ajax({
                headers: { 'X-CSRF-TOKEN': token },
                type: "POST",
                url: "{{url('/admin/remove/businessimage')}}",
                data: {businessImageId: imageId},
                success: function( data ) {
                   $('#business_img_'+imageId).remove();
                }
            });
        }
    else
        return false;
    
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

function open_close(val,day)
{
    if(val == 0)
    {
        $('.'+day+'_start_time').hide();
        $('.'+day+'_end_time').hide();
    }
    else
    {
        $('.'+day+'_start_time').show();
        $('.'+day+'_end_time').show();
    }
}


</script>
@stop