@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.users')}}
        
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <table class="table table-bordered table-striped" id="users">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.email')}}</th>
                                <th>{{trans('labels.birthdate')}}</th>
                                <th>{{trans('labels.phone')}}</th>
                                <th>{{trans('labels.memberoragent')}}</th>
                                <th>{{trans('labels.photo')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse($userList as $key=>$value)
                            <tr>
                                <td> 
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    {{strip_tags($value->email)}}
                                </td>
                                <td>
                                    {{$value->dob}}
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->phone}}
                                    @else
                                        {{$value->phone}}
                                    @endif
                                </td>
                                <td> 
                                    <?php $isVendor = Helpers::userIsVendorOrNot($value->id); ?>
                                    @if($isVendor == 1)
                                        <span class="label label-success">{{trans('labels.vendor')}}</span>
                                    @endif

                                    @if($value->agent_approved == 1)
                                        <span class="label label-success">{{trans('labels.agent')}}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($value->profile_pic != '' && Storage::size(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$value->profile_pic) > 0)
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->profile_pic), 0, -10)}}' src="{{ Storage::url(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$value->profile_pic) }}" width="50" height="50" class="img-circle"/>
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->profile_pic), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ Storage::url(Config::get('constant.USER_ORIGINAL_IMAGE_PATH').$value->profile_pic) }}" style='width:100%; border-radius:5px;' title="{{$value->profile_pic}}" />
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
                                    <a onclick="return confirm('Are you sure you want to active this user ?')" href="{{ url('/admin/activeuser') }}/{{$value->id}}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>
                                </td>
                            </tr>
                            
                            @empty
                            <tr>
                                <th colspan="8"><center>No records found</center></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    
                </div><!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
@stop