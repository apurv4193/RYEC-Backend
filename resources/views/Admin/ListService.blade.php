@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.services')}}
        <div class="pull-right">
            <a href="{{ url('admin/user/business/service/add') }}/{{Crypt::encrypt($businessId)}}" class="btn bg-purple"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn'). ' '.trans('labels.service')}}</a>
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
                        <li><a href="{{ url('admin/users') }}"><i class="fa fa-users"></i> Users</a></li>
                        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}">{{$businessDetails->user->name}}</a></li>
                        <li>{{$businessDetails->name}} {{trans('labels.business')}} - {{trans('labels.services')}}</li>
                    </ol>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="service">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.title')}}</th>
                                <th>{{trans('labels.logo')}}</th>
                                <th>{{trans('labels.description')}}</th>
                                <th>{{trans('labels.metatags')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessDetails->services as $key=>$value)
                            <tr>
                                <td>
                                   {{$value->id}}
                                </td>
                                <td>
                                   {{$value->name}}
                                </td>
                                <td>
                                   @if(($value->logo !='') && Storage::size(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$value->logo) > 0) 
                                        <img style="cursor: pointer;" data-toggle='modal' data-target='#{{$value->id.substr(trim($value->logo), 0, -10)}}' src="{{ Storage::url(Config::get('constant.SERVICE_THUMBNAIL_IMAGE_PATH').$value->logo) }}" height="40" width="40" title="{{$value->logo}}" class="img-circle" />
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id.substr(trim($value->logo), 0, -10)}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:transparent;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        <img src="{{ Storage::url(Config::get('constant.SERVICE_ORIGINAL_IMAGE_PATH').$value->logo) }}" style='width:100%; border-radius:5px;' title="{{$value->logo}}" />
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif 
                                </td>
                                <td>
                                   {{str_limit($value->description,60)}}
                                    @if(strlen($value->description) > 60)
                                        <span data-toggle='modal' data-target='#{{$value->id}}' style="cursor: pointer;color:#605ca8;font-weight: bold;">Read more</span>
                                        <div class='modal modal-centered fade image_modal' id='{{$value->id}}' role='dialog' style='vertical-align: center;'>
                                            <div class='modal-dialog modal-dialog-centered'>
                                                <div class='modal-content' style="background-color:#fff;">
                                                    <div class='modal-body'>
                                                    <center>
                                                        <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                                        {{$value->description}}
                                                    <center>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                   {{$value->metatags}}
                                </td>
                                <td>
                                    <a href="{{ url('admin/user/business/service/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/user/business/service/delete') }}/{{Crypt::encrypt($value->id)}}">
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
        $('#service').DataTable({
           "aaSorting": []
        });
        $('[data-toggle="popover"]').popover(); 
    });

</script>
@stop