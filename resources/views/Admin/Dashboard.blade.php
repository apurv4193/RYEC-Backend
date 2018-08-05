@extends('Admin.Master')

@section('content')
<!-- content   -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.dashboard')}}
        <small>Control Panel</small>
    </h1>
</section>

<section class="content">
    <div class="alert alert-success alert-dismissable" id="alert_dashboard" style="display:none;">
        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
        <h4><i class="icon fa fa-check"></i> Success!</h4>
        Business have approved successfully
    </div>
    <div class="alert alert-success alert-dismissable" id="red_alert_dashboard" style="display:none;">
        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
        <h4><i class="icon fa fa-check"></i> Rejected!</h4>
        Business have rejected successfully
    </div>
    @if(Auth::user()->agent_approved != 1)
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">List of members waiting for approval</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table id="membersForApproval" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.userId')}}</th>
                                <th>{{trans('labels.user')}}</th>
                                <th>{{trans('labels.business')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.date')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($membersForApproval as $member)
                            <tr>
                                <td>{{$member->id}}</td>
                                <td>{{(isset($member->user))?$member->user->id:""}}</td>
                                <td>
                                    @if(isset($member->user))
                                        <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($member->user->id)}}" target="_blank">
                                            {{$member->user->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ url('admin/user/business/edit') }}/{{Crypt::encrypt($member->id)}}" target="_blank">
                                        {{$member->name}}
                                    </a>
                                </td>  
                                <td>{{$member->mobile}}</td>
                                <td>{{$member->created_at}}</td>
                                <td>
                                    <div class="business_approve">
                                        <!-- <button class="btn btn-success" onclick="approved({{$member->id}})" >
                                            <i class="fa fa-check"></i>&nbsp;Approve
                                        </button> -->
                                        <a href="" class="btn bg-green"  data-toggle="tooltip" data-original-title="Approved" onclick="approved({{$member->id}})">
                                            <i class="fa fa-check"></i> 
                                        </a>
                                        <a href="#" class="btn btn-danger" data-toggle="tooltip" data-original-title="Rejected" onclick="rejected({{$member->id}})">
                                            <i class="fa fa-close"></i> 
                                        </a>
                                    </div>
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
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">List of Subscriptions Expired/ Expiring</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    To be implemented
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        Welcome to RYEC
                    </h3>
                </div>
            </div>
        </div>
    </div>
    @endif

</section>
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#membersForApproval').DataTable({
           "aaSorting": []
        });
    });

    function approved(businessId)
    {
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "POST",
            url: "{{url('/admin/user/business/approved')}}",
            data: {businessId: businessId},
            success: function( data ) {
                location.reload();
                $('#alert_dashboard').show();
            }
        });
    }
    function rejected(businessId)
    {
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "POST",
            url: "{{url('/admin/user/business/rejected')}}",
            data: {businessId: businessId},
            success: function( data ) {
                location.reload();
                $('#red_alert_dashboard').show();
            }
        });
    }
</script>
@stop
