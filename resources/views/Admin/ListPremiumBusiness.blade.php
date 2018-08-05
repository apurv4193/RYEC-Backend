@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.premiumbusinesses')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="business">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.user')}}</th>
                                <th>{{trans('labels.category')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.type')}}</th>
                                <th>Expity Date</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    @if(isset($value->user) && $value->user != '')
                                        <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($value->user->id)}}" target="_blank">
                                            {{$value->user->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    {{$value->categories}}
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->mobile}} 
                                    @else
                                        {{$value->mobile}} 
                                    @endif
                                    
                                </td>
                                <td>
                                    @if($value->membership_type == 1)
                                        Premium
                                    @elseif($value->membership_type == 2)
                                        Lifetime Premium
                                    @endif
                                </td>
                                <!-- <td>
                                    @if($value->approved == 0)
                                        <div class="business_approve">
                                            <span class="label label-danger" onclick="approved({{$value->id}})" style="cursor: pointer;">
                                                {{trans('labels.pending')}}
                                            </span>
                                        </div>
                                    @else
                                        <span class="label label-success">{{trans('labels.approved')}}</span>
                                    @endif
                                </td> -->
                                <td>
                                <?php 
                                    $plans = $value->businessMembershipPlans;
                                    if (count($plans) > 0) {
                                        $mostRecent= 0;
                                        foreach($plans as $plan)
                                        {
                                            $curDate = strtotime($plan->end_date);
                                            if ($curDate > $mostRecent) {
                                                $mostRecent = $curDate;
                                            }
                                        }
                                        if($mostRecent != 0)
                                            echo Date('Y-m-d',$mostRecent);
                                    }     
                                ?>
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