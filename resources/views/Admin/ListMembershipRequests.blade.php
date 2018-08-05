@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.membershiprequest')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
                    <strong>{{trans('labels.whoops')}}</strong> {{trans('labels.someproblems')}}<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="box-header">
                    <form id="formSearch" class="form-horizontal" method="post" action="{{ url('/admin/membershiprequest') }}">
                        <div class="col-md-4">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="search" value="1">
                            <select class="form-control" name="status">
                                <option value=''>{{trans('labels.selectstatus')}}</option>
                                <option value='1' {{(isset($postData['status']) && $postData['status'] == 1)?'selected':''}}>Approve</option>
                                <option value='2' {{(isset($postData['status']) && $postData['status'] == 2)?'selected':''}}>Reject</option>
                                <option value='0' {{(isset($postData['status']) && $postData['status'] == 0)?'selected':''}}>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="submit" class="btn bg-purple" name="searchBtn" id="searchBtn" value="{{trans('labels.search')}}"/>
                            <a href="{{ url('/admin/membershiprequest') }}">
                                <input type="button" class="btn bg-purple" name="clearBtn" id="clearBtn" value="{{trans('labels.clear')}}"/>
                            </a>
                        </div>
                    </form>
                   <!--  <div class="dataTables_info" id="business_info" role="status" aria-live="polite" style="float:right;">
                        @if($membershipRequests->count() == 1)
                            Showing {{$membershipRequests->count()}} entry
                        @else
                            Showing {{$membershipRequests->count()}} entries
                        @endif
                    </div> -->
                </div>
                <div class="box-body table-responsive">
                    <table id="trendingCategoriesListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>Membership Plan</th>
                                <th>{{trans('labels.user')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.business')}}</th>
                                <th>{{trans('labels.date')}}</th>
                                <th>{{trans('labels.status')}}</th>
                                <th>{{trans('labels.action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($membershipRequests as $request)
                            <tr>
                                <td>{{$request->id}}</td>
                                <td>{{$request->subscriptionPlan->name}}</td>
                                <td>
                                    @if(isset($request->user->name) && $request->user->name != '')
                                        <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($request->user->id)}}" target="_blank">
                                            {{$request->user->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    {{(isset($request->user->phone) && $request->user->phone != '')? $request->user->phone : ''}}
                                </td>
                                <td>
                                    @if(isset($request->user->singlebusiness->name) && $request->user->singlebusiness->name != '')
                                        <a href="{{ url('admin/user/business/edit') }}/{{Crypt::encrypt($request->user->singlebusiness->id)}}" target="_blank">
                                            {{$request->user->singlebusiness->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    {{$request->created_at}}
                                </td>
                                <td>
                                   @if($request->status == 0)
                                        Pending
                                    @elseif($request->status == 1)
                                        Approved
                                    @elseif($request->status == 2)
                                        Rejected
                                    @endif
                                    @if($request->reasons != '')
                                        <span style="cursor:pointer;" data-toggle="tooltip" data-placement="bottom" data-original-title="Reason: {{$request->reasons}}">
                                            <i class="fa fa-caret-square-o-down"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="reasons" id="reason_{{$request->id}}" value="{{$request->reasons}}"/>
                                    @if($request->status == 0)
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/1" class="btn bg-green"  data-toggle="tooltip" data-original-title="Approved">
                                            <i class="fa fa-check"></i> 
                                        </a>
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/2" class="btn btn-danger" data-toggle="tooltip" data-original-title="Rejected" onclick="return confirm('Are you sure you want to reject?')">
                                            <i class="fa fa-close"></i> 
                                        </a>&nbsp;&nbsp;
                                        <i style="cursor:pointer;" onclick="pendingComment({{$request->id}})" data-toggle="tooltip" data-original-title="Add Comment" class="fa fa-comment-o"></i> 
                                    @elseif($request->status == 1)
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/2" class="btn btn-danger" data-toggle="tooltip" data-original-title="Rejected" onclick="return confirm('Are you sure you want to reject?')">
                                            <i class="fa fa-close"></i> 
                                        </a>
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/0" class="btn btn-danger" data-toggle="tooltip" data-original-title="Pending">
                                            <i class="fa fa-pause"></i> 
                                        </a>&nbsp;&nbsp;
                                        <i style="cursor:pointer;" onclick="approveComment({{$request->id}})" data-toggle="tooltip" data-original-title="Add Comment" class="fa fa-comment-o"></i> 
                                        
                                    @elseif($request->status == 2)
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/1" class="btn bg-green"  data-toggle="tooltip" data-original-title="Approved">
                                            <i class="fa fa-check"></i> 
                                        </a>
                                        <a href="{{ url('/admin/membershipapprove')}}/{{Crypt::encrypt($request->id)}}/0" class="btn btn-danger" data-toggle="tooltip" data-original-title="Pending">
                                            <i class="fa fa-pause"></i> 
                                        </a>&nbsp;&nbsp;
                                        <i style="cursor:pointer;" class="fa fa-comment-o" onclick="rejectComment({{$request->id}})" data-toggle="tooltip" data-original-title="Add Comment"></i> 
                                        
                                    @endif
                                   
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

    <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
        
        <form id="reasonForm" class="form-horizontal" method="post" action="{{ url('/admin/membershipreject')}}">

            <input type="hidden" value="{{ csrf_token()}}" name="_token">
            <input type="hidden" value="" name="request_id" id="request_id">
            <input type="hidden" value="" name="status" id="status">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" style="display:none;" id="reasonRejection">Reason for Rejection</h4>
                    <h4 class="modal-title" style="display:none;" id="reasonPending">Reason for Pending</h4>
                    <h4 class="modal-title" style="display:none;" id="commentApprove">Comment for Approve</h4>
                </div>
                <div class="modal-body">
                    <p><textarea name="reasons" id="reasons" placeholder="Enter text" cols="91" rows="5"></textarea></p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-default" data-dismiss="modal">
                        Send
                    </button>
                </div>
            </div>
        </form>
    </div>
  </div>
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#trendingCategoriesListing').DataTable({
           "aaSorting": []
        });
    });
    function pendingComment(requestId)
    {
        $('#reasonPending').show();
        $('.modal').css("display","block");
        $('.fade').css("opacity","1");
        $('#status').val(0);
        $('#request_id').val(requestId);
        $('#reasons').text($('#reason_'+requestId).val());
    }
    function approveComment(requestId)
    {
        $('#commentApprove').show();
        $('.modal').css("display","block");
        $('.fade').css("opacity","1");
        $('#status').val(1);
        $('#request_id').val(requestId);
        $('#reasons').text($('#reason_'+requestId).val());
    }
    function rejectComment(requestId)
    {
        $('#reasonRejection').show();
        $('.modal').css("display","block");
        $('.fade').css("opacity","1");
        $('#status').val(2);
        $('#request_id').val(requestId);
        $('#reasons').text($('#reason_'+requestId).val());
    }
// function rejectRequest(requestId)
// {
//     var x = confirm("Are you sure you want to reject this request?");
//     if (x)
//     {
//         $('#reasonRejection').show();
//         $('.modal').css("display","block");
//         $('.fade').css("opacity","1");
//         $('#request_id').val(requestId);
//         $('#status').val(2);
//         return false;
//     }
//     else
//     {
//         return false;
//     }
// }
$(document).ready(function(){
    $(".close").click(function(){
        $('.modal').css("display","none");
        $('.fade').css("opacity","0");
    });
});

</script>
@stop