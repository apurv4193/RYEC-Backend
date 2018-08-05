@extends('Admin.Master')

@section('content')
<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        &nbsp;
        Edit / {{$data->name}} / {{trans('labels.business')}}
        <a class="pull-left" href="{{ url('/admin/user/business') }}/{{Crypt::encrypt($userId)}}" title="Back">
            <i class="fa fa-arrow-circle-left" aria-hidden="true"></i>
        </a>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('admin/users') }}"><i class="fa fa-users"></i> Users</a></li>
        <li class="active"> <a href="{{ url('/admin/user/business') }}/{{Crypt::encrypt($userId)}}"> {{$userDetail->name}} </a></li>
        <li class="active">{{$data->name .' '. trans('labels.business')}}</li>
    </ol>
   
</section>
<!-- Main content -->
<section class="content">
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
    <div class="row">
        <!-- right column -->
        <div class="col-md-12">
             
        </div>
    </div>
    <div class="row">
        <!-- right column -->
        <div class="col-md-6">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{trans('labels.business')}} Information</h3>
                </div><!-- /.box-header -->
                <form id="addbusiness" class="form-horizontal" method="post" action="{{ url('admin/user/business/savebusinessinfo') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="old_membership_type" value="<?php echo (isset($data) && !empty($data)) ? $data->membership_type : '0'; ?>">
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
                                {{trans('labels.email')}}
                             </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="email_id" name="email_id" placeholder="{{trans('labels.emailid')}}" value="{{$email_id}}">
                            </div>
                        </div>

                        <div class="form-group" id="image">
                            <label for="images" class="col-sm-2 control-label">Images<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="file" id="business_images" name="business_images[]" class="business_image_array" multiple >
                            </div>                            
                        </div>
                        @if(isset($data) && !empty($data))
                            @if(!empty($businessImages))
                                <div class="form-group" id="business_images">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-8">
                                        
                                            @forelse($businessImages as $image)
                                                @if(Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$image['image_name']) > 0) 
                                                <div class="business_img" id="business_img_{{$image['id']}}">
                                                    <i class="fa fa-times-circle" aria-hidden="true" onclick="return deleteBusinessImage({{$image['id']}});"></i>
                                                    <img src="{{ Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$image['image_name']) }}" width="50" height="50"/>
                                                </div>
                                                @endif                                                
                                            @empty
                                           @endforelse
                                      
                                    </div>
                                </div>
                            @endif
                        @endif

                        <input type="hidden" name="old_business_logo" value="<?php echo (isset($data) && !empty($data) && $data->business_logo != '') ? $data->business_logo : '' ?>">
                        <div class="form-group">
                            <?php
                            if (old('business_logo'))
                                $business_logo = old('business_logo');
                            elseif (isset($data))
                                $business_logo = $data->business_logo;
                            else
                                $business_logo = '';
                            ?>
                            <label for="business_logo" class="col-sm-2 control-label">{{trans('labels.logo')}}<span class="star_red">*</span></label>
                            <div class="col-sm-4">
                                <input type="file" id="business_logo" name="business_logo">
                            </div>                            
                        </div>
                        @if(isset($data) && !empty($data))
                            <div class="form-group" id="business_images">
                                <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                <div class="col-sm-8">
                                    @if($data->business_logo != '' && Storage::size(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$data->business_logo) > 0)
                                        <img src="{{ Storage::url(Config::get('constant.BUSINESS_THUMBNAIL_IMAGE_PATH').$data->business_logo) }}" width="50" height="50"/>
                                    @else
                                        <img src="{{ url(Config::get('constant.DEFAULT_IMAGE')) }}" width="50" height="50"/>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="form-group">
                            <?php
                            if (old('establishment_year'))
                                $establishment_year = old('establishment_year');
                            elseif (isset($data))
                                $establishment_year = ($data->establishment_year)?$data->establishment_year:'';
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
                            if (old('promoted'))
                                $promoted = old('promoted');
                            elseif (isset($data))
                                $promoted = $data->promoted;
                            else
                                $promoted = '';
                            ?>
                            <label for="promoted" class="col-sm-2 control-label">{{trans('labels.promoted')}}</label>
                            <div class="col-sm-8">
                                <input type="checkbox" class="check_box_set" name="promoted" {{($promoted == 1)?'checked':''}} {{(Auth::user()->agent_approved == 1) ? 'disabled' : ''  }}>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('approved'))
                                $approved = old('approved');
                            elseif (isset($data))
                                $approved = $data->approved;
                            else
                                $approved = '';
                            ?>
                            <label for="approved" class="col-sm-2 control-label">{{trans('labels.status')}}</label>
                            <div class="col-sm-8">
                                <select name="approved" class="form-control" style="cursor: pointer;" {{(Auth::user()->agent_approved == 1) ? 'disabled' : ''  }}>
                                    <option value="1" {{($approved == 1)?'selected': ''}}>Approved</option>
                                    <option value="0" {{($approved == 0)?'selected': ''}}>Not approved</option>
                                </select>
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
                                <select name="membership_type" class="form-control" style="cursor: pointer;" id="checkMembership" {{(Auth::user()->agent_approved == 1) ? 'disabled' : ''  }}>
                                    <option value="1" {{($membership_type == 1)?'selected': ''}}>Premium</option>
                                    <option value="0" {{($membership_type == 0)?'selected': ''}}>Basic</option>
                                    <option value="2" {{($membership_type == 2)?'selected': ''}}>Lifetime</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('agent_user'))
                                $agent_user = old('agent_user');
                            elseif (isset($data))
                                $agent_user = $data->agent_user;
                            else
                                $agent_user = '';
                            ?>
                            <label for="membership_type" class="col-sm-2 control-label">{{trans('labels.agentuser')}}</label>
                            <div class="col-sm-8">
                                <select name="agent_user" class="form-control" style="cursor: pointer;" id="checkMembership" {{(Auth::user()->agent_approved == 1) ? 'disabled' : ''  }}>
                                    <option value="">Select {{trans('labels.user')}}</option>
                                    @forelse($agentUsers as $user)
                                        <option class="type_parent_cat cat_type" value="{{$user->user_id}}" {{($agent_user == $user->user_id)?'selected': ''}}>
                                            {{$user->user->name." - ".$user->city}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{trans('labels.hoursofoperationtimezone')}}</h3>
                </div><!-- /.box-header -->
               <form id="addbusinessworlinghrs" class="form-horizontal" method="post" action="{{ url('admin/user/business/saveworkinghours') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <!-- <input type="hidden" name="old_business_images" value="<?php echo (isset($businessImages) && !empty($businessImages)) ? '': '' ?>"> -->
                   
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            
                            if (old('timezone'))
                                $timezone = old('timezone');
                            elseif (isset($data) && isset($data->businessWorkingHours))
                                $timezone = $data->businessWorkingHours->timezone;
                            else
                                $timezone = 'Asia/Kolkata';

                            $timezones = Helpers::getTimezone();
                            ?>
                            <label for="website_url" class="col-sm-2 control-label">Timezone</label>
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
                           
                            <div class="col-sm-12">

                            <input type="hidden" name="working_hours_id" value="<?php echo (isset($data) && !empty($data->businessWorkingHours->id)) ? $data->businessWorkingHours->id : '0' ?>">
                               
                                <table class="table open_hours_table" border=0>
                                    <tr style="font-weight: bold;">
                                        <td style="width: 100px;">Days</td>
                                        <td style="width: 100px;">Open/Close</td>
                                        <td style="width: 150px;">Start time</td>
                                        <td style="width: 150px;">End time</td>
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
                                            
                                            $day_start_time = $data->businessWorkingHours->$day_start_time;
                                            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_start_time,$data->businessWorkingHours->timezone);
                                            $day_start_time = $day_time['time'];
                                            $day_start_am_pm = $day_time['am_pm'];

                                           
                                            
                                            $day_end_time = $data->businessWorkingHours->$day_end_time;
                                            $day_time = Helpers::getReverseServerDateTimeIntoGivenTimezone($day_end_time,$data->businessWorkingHours->timezone);        
                                            $day_end_time =  $day_time['time'];
                                            $day_end_am_pm = $day_time['am_pm'];

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
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Business Activities</h3>
                </div><!-- /.box-header -->
                <form id="addbusinessactivities" class="form-horizontal" method="post" action="{{ url('admin/user/business/savesocialactivities') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <!-- <input type="hidden" name="old_business_images" value="<?php echo (isset($businessImages) && !empty($businessImages)) ? '': '' ?>"> -->
                   
                    <div class="box-body">
                         @if(isset($data) && isset($data->businessActivities) && !empty($data->businessActivities->toArray()))
                            @forelse($data->businessActivities->toArray() as $key=>$activity)
                                <div class="form-group" id="activity_{{$activity['id']}}">
                                    @if($key == 0)
                                        <label for="activity_title" class="col-sm-2 control-label">{{trans('labels.activities')}}</label>
                                    @else
                                        <label for="activity_title" class="col-sm-2 control-label"></label>
                                    @endif
                                    <div id='activity_title'>
                                        <div class="col-sm-7">
                                            <input  class="form-control" type="text" placeholder=""  id="" name="update_activity_title[]" value="{{$activity['activity_title']}}">
                                            <input  class="form-control" type="hidden" placeholder=""  id="" name="update_activity_id[]" value="{{$activity['id']}}">
                                        </div>
                                        @if($key > 0)
                                        <div class="col-sm-1">
                                            <button type="button" class="btn bg-purple" onclick="remove_activity({{$activity['id']}})">-</button>   
                                        </div>
                                        @endif
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
                                <label for="activity_title" class="col-sm-2 control-label">{{trans('labels.title')}}</label>
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

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
        </div>
        <!-- right column -->
        <div class="col-md-6">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Category <span data-toggle="tooltip" data-original-title="Edit Category" class="glyphicon glyphicon-edit" style="margin-left:10px;cursor: pointer;" id="editCategory"></span>
                    </h3>
                </div><!-- /.box-header -->
                <form id="addbusinesscategory" class="form-horizontal" method="post" action="{{ url('admin/user/business/savecategryhierarchy') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    
                   <?php
                        if (old('category_hierarchy'))
                            $category_hierarchy = old('category_hierarchy');
                        elseif (isset($data))
                            $category_hierarchy = $data->category_id;
                        else
                            $category_hierarchy = '';
                    ?>

                        <div class="form-group">
                            <label for="category_id_display" class="col-sm-2 control-label" >
                                {{trans('labels.lblcategories')}}
                            </label>
                            <div class="col-sm-8" style="margin-top:-12px;padding-bottom: 10px;" id="category_id_display">
                                @if($category_hierarchy != '')
                                    <?php  $explodeCategories = explode(',',$data->category_id); ?>
                                    @foreach($explodeCategories as $category)
                                        @if($category > 0)
                                            <?php  $categories = Helpers::getCategoryReverseHierarchy($category); ?><br>
                                            @if(!empty($categories))
                                                <?php
                                                    $tempArr = [];
                                                    foreach($categories as $categoryTemp){
                                                        $tempArr[] = '<span class="label label-success">'.$categoryTemp['name'].'</span>';
                                                    }

                                                    echo implode(" > ", array_reverse($tempArr));
                                                ?>
                                            @endif
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('metatags'))
                                $metatags = old('metatags');
                            elseif (isset($data))
                                $metatags = $data->metatags;
                            else
                                $metatags = '';

                                $explodetags = array_filter(explode(',',$metatags));
                            ?>
                            <label for="metatags" class="col-sm-2 control-label">Metatags</label>
                            <div class="col-sm-8">

                                    @forelse($explodetags as $tags)
                                        <span class="label label-success">{{$tags}}</span>
                                    @empty
                                        -
                                    @endforelse
                            </div>
                        </div>
                    
                    <div id="edit_cat" style="display:inline;">
                        <div class="form-group">
                            <?php
                            if (old('category_id'))
                                $category_id = old('category_id');
                            elseif (isset($data)){
                                $category_id = $data->category_id;
                            }
                            else
                                $category_id = '';

                            $categorySelectedId = '';
                            if($data->category_hierarchy)
                            {
                                $categorySelectedId = substr($data->category_hierarchy, 0, 1); 
                            }
                            ?>
                            <label for="category_id" class="col-sm-2 control-label">{{(isset($data)) ? trans('labels.category') : trans('labels.category') }}</label>
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
                            <label for="description" class="col-sm-2 control-label">&nbsp;</label>   
                            <div id="selected_subcat" class="col-sm-8">
                                <?php 
                                    $categoryArray = $data->category_id; 
                                    $explodeCategory = explode(',',$categoryArray);
                                ?>
                                    @if($explodeCategory)
                                    
                                        @foreach($explodeCategory as $category)
                                       
                                            <?php    
                                                $catId = $category;
                                                $categoryHierarchy = array_reverse(Helpers::getCategoryReverseHierarchy($catId));
                                            ?>
                                            
                                            @if(!empty($categoryHierarchy))

                                                <div>
                                                    <input type="hidden" value="{{$catId}}" name="category_id[]"/>
                                                        <ol class="breadcrumb" style="margin-bottom: 5px;">
                                                            @forelse($categoryHierarchy as $cat)
                                                                <li class="active">{{$cat['name']}}</li>
                                                            @empty
                                                            @endforelse
                                                            <span style="cursor: pointer;" class="pull-right badge bg-red" onclick="remove_subcat(this,{{$catId}});"><i class="fa fa-remove"></i></span>
                                                        </ol>
                                                </div>
                                            @endif

                                        @endforeach

                                    @endif
                            </div>
                        </div>

                        <div  id="business_meta" class="business_meta" style="display: inline;">
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
                            <?php
                            if (old('metatags'))
                                $metatags = old('metatags');
                            elseif (isset($data))
                                $metatags = $data->metatags;
                            else
                                $metatags = '';
                            ?>
                            <label for="metatags" class="col-sm-2 control-label">Metatags</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="metatags" name="metatags" value="{{$metatags}}">
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="pull-right">
                                <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            </div>
                        </div><!-- /.box-footer -->
                    </div>
                </form>
            </div>
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Contact Details
                    </h3>
                </div><!-- /.box-header -->
                <form id="addbusinesscontact" class="form-horizontal" method="post" action="{{ url('admin/user/business/savecontactinfo') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <!-- <input type="hidden" name="old_business_images" value="<?php echo (isset($businessImages) && !empty($businessImages)) ? '': '' ?>"> -->
                   
                    <div class="box-body">
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
                            <div class="col-sm-4">
                                <?php $countryCodes = Helpers::getCountries(); ?>
                                <select name="country_code" data="" class="form-control select2" id="country_code">
                                    <option value="">Country Code</option>
                                    @forelse($countryCodes as $codes)
                                        <option value="{{$codes->country_code}}" {{($country_code == $codes->country_code)?'selected':''}}>{{$codes->name}} {{$codes->country_code}} </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-sm-4">
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

                        <div class="form-group">
                            <?php
                                $hidden_latitude = "";
                                if (old('latitude'))
                                    $latitude = old('latitude');
                                elseif (isset($data))
                                    $hidden_latitude = $latitude = $data->latitude;
                                else
                                    $latitude = '';
                            ?>  
                            <label for="latitude" class="col-sm-2 control-label">{{trans('labels.latitude')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="latitude" placeholder="{{trans('labels.latitude')}}" value="{{$latitude}}"  id="latitude"/>
                            </div>
                        </div>

                        <div class="form-group">
                              
                            <?php
                                $hidden_longitude = "";
                                if (old('longitude'))
                                    $longitude = old('longitude');
                                elseif (isset($data))
                                    $hidden_longitude = $longitude = $data->longitude;
                                else
                                    $longitude = '';
                            ?>
                            <label for="longitude" class="col-sm-2 control-label">{{trans('labels.longitude')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="longitude" placeholder="{{trans('labels.longitude')}}" value="{{$longitude}}"  id="longitude"/>
                            </div>
                        </div>

                        <input type="hidden" name="hidden_latitude" id="hidden_latitude" value="{{$hidden_latitude}}"/>
                        <input type="hidden" name="hidden_longitude" id="hidden_longitude" value="{{$hidden_longitude}}"/>

                        <div class="form-group">
                            <?php
                            if (old('street_address'))
                                $street_address = old('street_address');
                            elseif (isset($data))
                                $street_address = $data->street_address;
                            else
                                $street_address = '';
                            ?>
                            <label for="street_address" class="col-sm-2 control-label">{{trans('labels.streetaddress')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="street_address" placeholder="{{trans('labels.streetaddress')}}" value="{{$street_address}}" id="street_address"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('locality'))
                                $locality = old('locality');
                            elseif (isset($data))
                                $locality = $data->locality;
                            else
                                $locality = '';
                            ?>
                            <label for="locality" class="col-sm-2 control-label">{{trans('labels.locality')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="locality" placeholder="{{trans('labels.locality')}}" value="{{$locality}}" id="address_locality"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('country'))
                                $country = old('country');
                            elseif (isset($data))
                                $country = $data->country;
                            else
                                $country = '';
                            ?>
                            <label for="country" class="col-sm-2 control-label">{{trans('labels.country')}}</label>
                            <div class="col-sm-8">
                                <?php $countries = Helpers::getCountries(); ?>
                                    <select name="country" id="address_country" class="form-control select2">
                                        <option value="">Select {{trans('labels.country')}}</option>
                                        @forelse($countries as $con)
                                            <option class="type_parent_cat cat_type" value="{{$con->name}}" {{($country == $con->name)?'selected': ''}}>
                                                {{$con->name}}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('state'))
                                $state = old('state');
                            elseif (isset($data))
                                $state = $data->state;
                            else
                                $state = '';
                            ?>
                            <label for="state" class="col-sm-2 control-label">{{trans('labels.state')}}</label>
                            <div class="col-sm-8">
                                <?php $states = Helpers::getStates(); ?>
                                 <select name="state" id="address_state" class="form-control select2">
                                    <option value="">Select {{trans('labels.state')}}</option>
                                    @forelse($states as $st)
                                        <option class="type_parent_cat cat_type" value="{{$st->name}}" {{($state == $st->name)?'selected':''}}>
                                            {{$st->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
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
                            <label for="city" class="col-sm-2 control-label">{{trans('labels.city')}}</label>
                            <div class="col-sm-8">
                                <?php $cities = Helpers::getCities(); ?>
                                    <select id="address_city" name="city" class="form-control select2">
                                        <option value="">Select {{trans('labels.city')}}</option>
                                        @forelse($cities as $ct)
                                            <option class="type_parent_cat cat_type" value="{{$ct->name}}" {{($city == $ct->name)?'selected':''}}>
                                                {{$ct->name}}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('taluka'))
                                $taluka = old('taluka');
                            elseif (isset($data))
                                $taluka = $data->taluka;
                            else
                                $taluka = '';
                            ?>
                            <label for="taluka" class="col-sm-2 control-label">{{trans('labels.taluka')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="taluka" placeholder="{{trans('labels.taluka')}}" value="{{$taluka}}"  id="address_taluka"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('district'))
                                $district = old('district');
                            elseif (isset($data))
                                $district = $data->district;
                            else
                                $district = '';
                            ?>
                            <label for="district" class="col-sm-2 control-label">{{trans('labels.district')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="district" placeholder="{{trans('labels.district')}}" value="{{$district}}"  id="address_district"/>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <?php
                            if (old('pincode'))
                                $pincode = old('pincode');
                            elseif (isset($data))
                                $pincode = $data->pincode;
                            else
                                $pincode = '';
                            ?>
                            <label for="pincode" class="col-sm-2 control-label">{{trans('labels.pincode')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="pincode" placeholder="{{trans('labels.pincode')}}" value="{{$pincode}}"  id="address_pincode"/>
                            </div>
                        </div>

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

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Social Profiles
                    </h3>
                </div><!-- /.box-header -->
                <form id="addbusinessprofile" class="form-horizontal" method="post" action="{{ url('admin/user/business/savesocialprofiles') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <!-- <input type="hidden" name="old_business_images" value="<?php echo (isset($businessImages) && !empty($businessImages)) ? '': '' ?>"> -->
                   
                    <div class="box-body">
                         <div class="form-group">
                            <?php

                            if (old('facebook_url'))
                                $facebook_url = old('facebook_url');
                            elseif (isset($data))
                                $facebook_url = $data->facebook_url;
                            else
                                $facebook_url = '';
                            ?>
                            <label for="facebook_url" class="col-sm-2 control-label">{{trans('labels.facebook')}}</label>
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
                            <label for="twitter_url" class="col-sm-2 control-label">{{trans('labels.twitter')}}</label>
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
                            <label for="website_url" class="col-sm-2 control-label">{{trans('labels.linkedin')}}</label>
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
                            <label for="instagram_url" class="col-sm-2 control-label">{{trans('labels.instagram')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="instagram_url" name="instagram_url" placeholder="{{trans('labels.instagramurl')}}" value="{{$instagram_url}}">
                            </div>
                        </div>

                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
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
<!-- Include tags in input box -->

<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>

<script type="text/javascript">
    //$('#mobile').mask('9999999999');
    $('.select2').select2();
    $('#metatags').tagEditor({
        placeholder: 'Enter Metatags ...',
    });
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
           // $("#address_city").val(val).trigger('change');
        }
        var addr = place.address_components[i];
        var getCountry;
        if (addr.types[0] == 'country') {
            getCountry = addr.long_name;
           // $("#address_country").val(getCountry).trigger('change');
        }
        var getState;
        if (addr.types[0] == 'administrative_area_level_1') {
            getState = addr.long_name;
            //$("#address_state").val(getState).trigger('change');
        }
        var getPostalCode;
        if (addr.types[0] == 'postal_code') {
            getPostalCode = addr.long_name;
          //  $("#address_pincode").val(getPostalCode);
        }
        var getLocality;
        if (addr.types[0] == 'locality') {
            getLocality = addr.long_name;
           // $("#address_locality").val(getLocality);
        }
        var getStreetAddress;
        if (addr.types[0] == 'route') {
            getStreetAddress = addr.long_name;
           // $("#street_address").val(getStreetAddress);
        }

        }
      document.getElementById("latitude").value = lat;
      document.getElementById("longitude").value = lng;
      // document.getElementById("location_id").value = placeId;

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

<script type="text/javascript">

$(document).ready(function () {

    //CKEDITOR.replace('description');
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
        // description:{
        //     required: true
        // },
        email_id: {
            email: true
        },
        'business_images[]':
        {
            extension: "jpeg|jpg|bmp|png",
            filesize: 5000000 // 5 mb
        },
        business_logo:{
            extension: "jpeg|jpg|bmp|png",
            filesize: 5000000 // 5 mb
        },
        establishment_year:{
           // required: true,
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
    $("#addbusinessprofile").validate({
        ignore: "",
        rules: {
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
        },
    });
    $("#addbusiness").validate({
        ignore: "",
        rules: businessRules,
        messages: {
            name: {
                required: "<?php echo trans('labels.namerequired')?>"
            },
            // description:{
            //     required: "<?php echo trans('labels.descriptionrequired')?>"
            // },
            email_id: {
                email_id: "<?php echo trans('labels.invalidemail') ?>"
            },
            establishment_year:{
                //required: "<?php echo trans('labels.yearrequired') ?>",
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

        }            
    });

    $("#addbusinesscontact").validate({
        ignore: "",
        rules: {
            phone:{
                maxlength: 15
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
        },
        messages: {
            phone: {
                //required: "<?php echo trans('labels.phonerequired') ?>",
                //digits: "<?php echo trans('labels.digitsrequired') ?>",
            },
            mobile: {
                required: "<?php echo trans('labels.mobilerequired') ?>",
                // maxlength: "<?php echo trans('labels.mobilelengthrequired') ?>",
                // minlength: "<?php echo trans('labels.mobilelengthrequired') ?>"
            },
            address:{
                required: "<?php echo trans('labels.addressrequired') ?>"
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
    alert('1');
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

$('#editCategory').click(function(){
    $('#edit_cat').toggle();
})

var prev_val;
$('#checkMembership').focus(function() {
    prev_val = $(this).val();
}).change(function(){
    $(this).unbind('focus');
    var conf = confirm('Are you sure want to change membership type ?');

    if(conf == true){
        //your code
    }
    else{
        $(this).val(prev_val);
        $(this).bind('focus');
        return false;
    }
});

</script>
@stop