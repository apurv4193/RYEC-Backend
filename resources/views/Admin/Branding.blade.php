@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.branding')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <form id="addbrandingManagement" class="form-horizontal" method="post" action="{{ url('/admin/savebranding/') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    
                    <div class="box-body">
                        <div class="form-group">
                            <label for="country_id" class="col-sm-2 control-label">
                                {{ trans('labels.type') }}
                            </label>
                            <div class="col-sm-6">
                                <select name="type" class="form-control" id="filetype">
                                    <option value="">Select type</option>
                                    <option value="1">Image</option>
                                    <option value="2">Video</option>
                                    <option value="3">Text</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="display: none;" id="image-part">
                            <label for="image" class="col-sm-2 control-label">
                                {{ trans('labels.image') }}
                            </label>
                            <div class="col-sm-6">
                                <input type="file" name="image" style="margin: 0px; width: 520px; height: 48px;"/>
                            </div>
                        </div>
                        <div class="form-group" style="display: none;" id="video-part">
                            <label for="video" class="col-sm-2 control-label">
                                {{ trans('labels.video') }}
                            </label>
                            <div class="col-sm-6">
                                <input type="text" name="video" class="form-control" placeholder="youtube url" />
                            </div>
                        </div>
                        <div class="form-group" style="display: none;" id="text-part">
                            <label for="text" class="col-sm-2 control-label">
                                {{ trans('labels.text') }}
                            </label>
                            <div class="col-sm-6">
                                <textarea name="text" cols="83" rows="2"></textarea> 
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="country_id" class="col-sm-2 control-label">
                           
                        </label>
                        
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->

                    <div class="box-footer">
                        @if(count($brandingDetail) > 0)
                            @if($brandingDetail->type == 1)
                                @if(file_exists('images/branding_image.png'))
                                    <label for="country_id" class="col-sm-2 control-label">
                                        Image url
                                    </label>
                                    <div class="col-sm-8">
                                        <image src="{{url('images/branding_image.png')}}" hight="30" width="30"/>
                                        {{url('images/branding_image.png')}}
                                        <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deletebranding') }}">
                                            <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                        </a>
                                    </div>
                                @endif
                            @elseif($brandingDetail->type == 2)
                                <label for="country_id" class="col-sm-2 control-label">
                                        Video url
                                </label>
                                <div class="col-sm-8">
                                    {{$brandingDetail->name}}
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deletebranding') }}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>
                                </div>
                            @else
                                <label for="country_id" class="col-sm-2 control-label">
                                        Text
                                </label>
                                <div class="col-sm-8">
                                    {{$brandingDetail->name}}
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deletebranding') }}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>
                                </div>
                            @endif
                        @endif

                        
                    </div><!-- /.box-footer -->
                </form>
                
            </div>
            <!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script>
    $('#filetype').change(function(){

        if($('#filetype').val() == 1)
        {
            $('#image-part').show();
            $('#video-part').hide();
            $('#text-part').hide();
        }
        else if($('#filetype').val() == 2)
        {
            $('#video-part').show();
            $('#image-part').hide();
            $('#text-part').hide();
        }
        else if($('#filetype').val() == 3)
        {
            $('#text-part').show();
            $('#video-part').hide();
            $('#image-part').hide();
        }
    });
    $("#addbrandingManagement").validate({
        ignore: "",
        rules: {
            type: {
                required: true,
            }
        },
    }); 
</script>
@stop