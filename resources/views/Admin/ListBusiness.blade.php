@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.businesses')}}
        <?php $isVendor = Helpers::userIsVendorOrNot($userDetail->id); ?>
        @if(!$isVendor)
            <div class="pull-right">
                <a href="{{ url('admin/user/business/add') }}/{{Crypt::encrypt($userId)}}" class="btn bg-purple">
                    <i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.business')}}
                </a>
            </div>
        @endif
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <ol class="breadcrumb">
                        <li><a href="{{ url('admin/users') }}"><i class="fa fa-users"></i> Users</a></li>
                        <li class="active"> {{$userDetail->name}} - {{trans('labels.businesses')}}</li>
                    </ol>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="business">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.category')}}</th>
                                <th>{{trans('labels.email')}}</th>
                                <th>{{trans('labels.websiteurl')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.promoted')}}</th>
                                <th>{{trans('labels.approvalstatus')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userDetail->businesses as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    {{$value->categories}}
                                </td>
                                <td>
                                    {{$value->email_id}}
                                </td>
                                <td>
                                    {{$value->website_url}}
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->mobile}} 
                                    @else
                                        {{$value->mobile}} 
                                    @endif
                                    
                                </td>
                                <td>
                                    @if($value->promoted == 1)
                                        <span class="label label-success">{{trans('labels.promoted')}}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($value->approved == 0)
                                        <div class="business_approve">
                                            <span class="label label-danger" onclick="approved({{$value->id}})" style="cursor: pointer;">
                                                {{trans('labels.pending')}}
                                            </span>
                                        </div>
                                    @else
                                        <span class="label label-success">{{trans('labels.approved')}}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ url('admin/user/business/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/user/business/delete') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/service') }}/{{Crypt::encrypt($value->id)}}">
                                        <span  class="badge bg-light-blue" data-toggle="tooltip" data-original-title="Manage Service" style="margin-bottom: 3px;">S</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/product') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Product" style="margin-bottom: 3px;">P</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/owner') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Owner" style="margin-bottom: 3px;">O</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/membership') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Membership" style="margin-bottom: 3px;">M</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
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
<script type="text/javascript">
    $(document).ready(function() {
        $('#business').DataTable({
           "aaSorting": []
        });
    });

    function approved(businessId)
    {
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "POST",
            url: '/admin/user/business/approved',
            data: {businessId: businessId},
            success: function( data ) {
                $('.business_approve').html('<span class="label label-success">Approved</span>');
            }
        });
    }
</script>
@stop