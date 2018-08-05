@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.memberships')}}
        @if(Auth::user()->agent_approved == 0)
        <div class="pull-right">
            <a href="{{ url('admin/user/business/membership/add') }}/{{Crypt::encrypt($businessId)}}" class="btn bg-purple"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn').' '.trans('labels.membership')}}</a>
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
                        <li><a href="{{url('admin/users')}}"><i class="fa fa-users"></i> Users </a></li>
                        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}">{{$businessDetails->user->name}}</a></li>
                        <li>{{$businessDetails->name}} {{trans('labels.business')}} - {{trans('labels.memberships')}}</li>
                    </ol>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="membership">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.plantype')}}</th>
                                <th>{{trans('labels.startdate')}}</th>
                                <th>{{trans('labels.enddate')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessDetails->businessMembershipPlans as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->subscriptionPlan->name}}    
                                </td>
                                <td>
                                    {{$value->start_date}}       
                                </td>
                                <td>
                                    {{$value->end_date}}    
                                </td>
                                <td>
                                    <a href="{{ url('admin/user/business/membership/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" {{(Auth::user()->agent_approved == 0) ? 'data-original-title=Edit' : 'data-original-title=View'}} class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    @if(Auth::user()->agent_approved == 0)
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/user/business/membership/delete') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>
                                    @endif
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
        $('#product').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop