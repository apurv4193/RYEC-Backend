@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.owners')}}
        <div class="pull-right">
            <a href="{{ url('admin/user/business/owner/add') }}/{{Crypt::encrypt($businessId)}}" class="btn bg-purple"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn').' '.trans('labels.owner')}}</a>
        </div>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <ol class="breadcrumb">
                        <li><a href="{{url('admin/users')}}"><i class="fa fa-users"></i> {{trans('labels.users')}}</a></li>
                        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}"> {{$businessDetails->user->name}}</a></li>
                        <li>{{$businessDetails->name}} {{trans('labels.business')}} - {{trans('labels.owners')}}</li>
                    </ol>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="owner">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.email')}}</th>
                                <th>{{trans('labels.photo')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessDetails->owners as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->full_name}}
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->mobile}} 
                                    @else
                                        {{$value->mobile}} 
                                    @endif
                                </td>
                                <td>
                                    {{$value->email_id}}
                                </td>
                                <td>
                                    @if($value->photo !='' && Storage::size(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$value->photo) > 0) 
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->photo), 0, -10)}}' src="{{ Storage::url(Config::get('constant.OWNER_THUMBNAIL_IMAGE_PATH').$value->photo) }}" height="40" width="40" title="{{$value->photo}}" class="img-circle" />
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->photo), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ Storage::url(Config::get('constant.OWNER_ORIGINAL_IMAGE_PATH').$value->photo) }}" style='width:100%; border-radius:5px;' title="{{$value->photo}}" />
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif 
                                </td>
                                <td>
                                    <a href="{{ url('admin/user/business/owner/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/user/business/owner/delete') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
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
        $('#owner').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop