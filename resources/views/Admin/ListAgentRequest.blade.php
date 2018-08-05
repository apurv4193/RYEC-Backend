@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{trans('labels.agentrequest')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="agentRequest">
                        <thead>
                            <tr>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.photo')}}</th>
                                <th>{{trans('labels.email')}}</th>
                                <th>{{trans('labels.phone')}}</th>
                                <th>{{trans('labels.comment')}}</th>
                                <th>{{trans('labels.action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentRequestList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->user->name}}    
                                </td>
                                <td>
                                     @if(File::exists(public_path('uploads/user/thumbnail/'.$value->user->profile_pic)) && ($value->user->profile_pic !='')) 
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->user->profile_pic), 0, -10)}}' src="{{ asset(Config::get('constant.USER_THUMBNAIL_IMAGE_PATH').$value->user->profile_pic) }}" height="40" width="40" title="{{$value->user->profile_pic}}" class="img-circle" />
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->user->profile_pic), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ asset(Config::get('constant.USER_ORIGINAL_IMAGE_PATH').$value->user->profile_pic) }}" style='width:100%; border-radius:5px;' title="{{$value->user->profile_pic}}" />
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{$value->user->email}}
                                </td>
                                <td>
                                    {{$value->user->phone}}
                                </td>
                                <td>
                                    {{str_limit($value->comment,60)}}
                                    @if(strlen($value->comment) > 60)
                                        <span data-toggle='modal' data-target='#{{$value->id}}' style="cursor: pointer;color:#605ca8;font-weight: bold;">Read more</span>
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:#fff;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        {{$value->comment}}
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($value->user->agent_approved == 0)
                                        <a onclick="return confirm('Are you sure you want to approve ?')" href="{{ url('admin/agentrequest') }}/{{Crypt::encrypt($value->id)}}">
                                            <div class="business_approve">
                                                <button class="btn btn-success" onclick="approved(20)">
                                                    <i class="fa fa-check"></i>&nbsp;Approve
                                                </button>
                                            </div>
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
        $('#agentRequest').DataTable({
           "aaSorting": []
        });
    }); 
</script>
@stop