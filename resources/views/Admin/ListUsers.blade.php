@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.users')}}
        <div class="pull-right">
            <a href="{{ url('admin/adduser') }}" class="btn bg-purple ">
                <i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.user')}}
            </a>
           <!--  <a href="{{ url('admin/addagent') }}" class="btn bg-purple">
                <i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.agent')}}
            </a> -->
        </div>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <form id="formSearch" class="form-horizontal" method="post" action="{{ url('/admin/users') }}">
                        <div class="col-md-4">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="search" value="1">
                            <input type="text" value="{{(isset($postData['searchtext'])) ? $postData['searchtext'] : ''}}" name="searchtext" class="form-control" placeholder="search text">
                        </div>
                        <div class="col-md-2">
                           <select class="form-control" name="usertype">
                                <option value=''>{{trans('labels.selecttype')}}</option>
                                <option value='vendor' {{(isset($postData['usertype']) && $postData['usertype'] == 'vendor')?'selected':''}}>{{trans('labels.vendor')}}</option>
                                <option value='agent' {{(isset($postData['usertype']) && $postData['usertype'] == 'agent')?'selected':''}}>{{trans('labels.agent')}}</option>
                                <option value='deactive' {{(isset($postData['usertype']) && $postData['usertype'] == 'deactive')?'selected':''}}>{{trans('labels.deactive')}}</option>
                                <option value='active' {{(isset($postData['usertype']) && $postData['usertype'] == 'active')?'selected':''}}>{{trans('labels.active')}}</option>
                                <option value='deleted' {{(isset($postData['usertype']) && $postData['usertype'] == 'deleted')?'selected':''}}>{{trans('labels.deleted')}}</option>
                           </select>
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="search" value="1">
                            <select class="form-control" name="fieldname">
                                <option value=''>{{trans('labels.selectfieldname')}}</option>
                                <option value='phone' {{(isset($postData['fieldname']) && $postData['fieldname'] == 'phone')?'selected':''}}>{{trans('labels.phone')}}</option>
                                <option value='email' {{(isset($postData['fieldname']) && $postData['fieldname'] == 'email')?'selected':''}}>{{trans('labels.email')}}</option>
                                <option value='dob' {{(isset($postData['fieldname']) && $postData['fieldname'] == 'dob')?'selected':''}}>{{trans('labels.dob')}}</option>
                                <option value='gender' {{(isset($postData['fieldname']) && $postData['fieldname'] == 'gender')?'selected':''}}>{{trans('labels.gender')}}</option>
                                <option value='isRajput' {{(isset($postData['fieldname']) && $postData['fieldname'] == 'isRajput')?'selected':''}}>{{trans('labels.rajput')}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="fieldtype">
                                <option value=''>{{trans('labels.selectfieldtype')}}</option>
                                <option value='0' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == '0')?'selected':''}}>{{trans('labels.null')}}</option>
                                <option value='1' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == '1')?'selected':''}}>{{trans('labels.notnull')}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="submit" class="btn bg-purple" name="searchBtn" id="searchBtn" value="{{trans('labels.search')}}"/>
                            <a href="{{ url('/admin/users') }}">
                                <input type="button" class="btn bg-purple" name="clearBtn" id="clearBtn" value="{{trans('labels.clear')}}"/>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="users">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.membershiptype')}}</th>
                                <th>{{trans('labels.phone')}}</th>
                                @if(Auth::user()->agent_approved == 0)
                                    <th>{{trans('labels.memberoragent')}}</th>
                                @endif
                                <th>{{trans('labels.status')}}</th>
                                <th>{{trans('labels.date')}}</th>
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
                                   <?php 
                                    if(isset($value->singlebusiness->membership_type) && count($value->singlebusiness->membership_type) > 0)
                                    {
                                        if($value->singlebusiness->membership_type == 2)
                                        {
                                           echo 'LifeTime Premium';
                                        }
                                        elseif($value->singlebusiness->membership_type == 1)
                                        {
                                            echo 'Premium';
                                        }
                                        else
                                        {
                                            echo 'Basic';
                                        }
                                    }
                                    else
                                    {
                                        echo '-';
                                    }
                                   ?>
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->phone}}
                                    @else
                                        {{$value->phone}}
                                    @endif
                                </td>
                                @if(Auth::user()->agent_approved == 0)
                                    <td>
                                        <?php $isVendor = Helpers::userIsVendorOrNot($value->id); ?>
                                        @if($isVendor == 1)
                                            <span class="label label-success">{{trans('labels.vendor')}}</span>
                                        @endif

                                        @if($value->agent_approved == 1)
                                            <span class="label label-success">{{trans('labels.agent')}}</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @if($value->deleted_at != NULL)
                                        Deleted
                                    @else
                                        @if($value->status == "1")
                                            Active
                                        @else
                                            Deactive
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    {{$value->created_at}}
                                </td>
                                <td>
                                    @if($value->deleted_at != NULL)
                                        <!-- <a href="{{ url('/admin/activate')}}/{{$value->id}}"> -->
                                            <a href="" onclick="activate({{$value->id}})" class="btn bg-green"  data-toggle="tooltip" data-original-title="Active">
                                                <i class="fa fa-check"></i> 
                                            </a>
                                            <a onclick="return confirm('Are you sure you want to hard delete ?')" href="{{ url('/admin/harddeleteuser') }}/{{$value->id}}" class="btn bg-green"  data-toggle="tooltip" data-original-title="Hard Delete">
                                                <i class="glyphicon glyphicon-remove"></i> 
                                            </a>

                                        <!-- </a> -->
                                    @else
                                        <!-- @if($value->agent_approved == 1)
                                            <a href="{{ url('/admin/editagent') }}/{{Crypt::encrypt($value->id)}}">
                                                <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                            </a>&nbsp;&nbsp;
                                        @else
                                            <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($value->id)}}">
                                                <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                            </a>&nbsp;&nbsp;
                                        @endif -->
                                        <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($value->id)}}">
                                            <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                        </a>&nbsp;&nbsp;
                                            
                                        <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deleteuser') }}/{{$value->id}}">
                                            <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                        </a>&nbsp;&nbsp;

                                        <a href="{{ url('/admin/user/business') }}/{{Crypt::encrypt($value->id)}}">
                                            <span data-toggle="tooltip" class="badge bg-light-blue" data-original-title="Manage Business" style="margin-bottom: 3px;">B</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <th colspan="9"><center>No records found</center></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if (isset($userList) && !empty($userList))
                        <div class="pull-right">
                            @if(isset($postData['searchtext']) && $postData['searchtext'] != '')
                                <?php
                                    $searchtext = $postData['searchtext'];
                                ?>
                            @else
                                <?php $searchtext = ''; ?>
                            @endif
                            @if(isset($postData['usertype']) && $postData['usertype'] != '')
                                <?php
                                    $usertype = $postData['usertype'];
                                ?>
                            @else
                                <?php $usertype = ''; ?>
                            @endif
                            @if(isset($postData['fieldname']) && $postData['fieldname'] != '')
                                <?php
                                    $fieldname = $postData['fieldname'];
                                ?>
                            @else
                                <?php $fieldname = ''; ?>
                            @endif
                            @if(isset($postData['fieldtype']) && $postData['fieldtype'] != '')
                                <?php
                                    $fieldtype = $postData['fieldtype'];
                                ?>
                            @else
                                <?php $fieldtype = ''; ?>
                            @endif
                            <?php echo $userList->appends(['searchtext' => $searchtext, 'usertype' => $usertype, 'fieldname' => $fieldname, 'fieldtype' => $fieldtype])->render(); ?>
                        </div>
                    @endif
                </div><!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
      checkboxClass: 'icheckbox_flat-green',
      radioClass   : 'iradio_flat-green'
    })

    function activate(userId)
    {
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "GET",
            url: "{{url('/admin/activate')}}/"+userId,
            success: function( data ) {
                location.reload();
                $('#alert_dashboard').show();
            }
        });
    }
</script>
@stop