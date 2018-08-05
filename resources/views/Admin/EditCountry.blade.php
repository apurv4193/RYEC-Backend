@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.country')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.country')}}</h3>
                </div>  <!-- .box-header -->
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
                <form id="addCountryManagement" class="form-horizontal" method="post" action="{{ url('/admin/savecountry/') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="old_flag" value="<?php echo (isset($data) && !empty($data) && $data->flag != '') ? $data->flag : '' ?>">

                    <div class="box-body">

                        <div class="form-group">
                            <?php
                                if (old('name'))
                                    $name = old('name');
                                elseif (isset($data) && !empty ($data->name))
                                    $name = $data->name;
                                else
                                    $name = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.name')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="name" name="name" value="{{$name}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                                if (old('country_code'))
                                    $country_code = old('country_code');
                                elseif (isset($data) && !empty ($data->country_code))
                                    $country_code = $data->country_code;
                                else
                                    $country_code = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.countrycode')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="country_code" name="country_code" value="{{$country_code}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                                if (old('flag'))
                                    $flag = old('flag');
                                elseif (isset($data) && !empty ($data->flag))
                                    $flag = $data->flag;
                                else
                                    $flag = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.flag')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="file" id="flag" name="flag">
                            </div>
                        </div>

                        @if(isset($data) && !empty($data))
                            @if($data->flag != '')
                                <div class="form-group" id="country_flag">
                                    <label for="media_images" class="col-sm-2 control-label">&nbsp;</label>
                                    <div class="col-sm-8">
                                        @if(Storage::size(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$data->flag) > 0) 
                                            <img src="{{ Storage::url(Config::get('constant.COUNTRY_FLAG_IMAGE_PATH').$data->flag) }}" width="50" height="50"/>
                                        @endif                                                
                                    </div>
                                </div>
                            @endif
                        @endif
                        
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/country') }}">{{trans('labels.cancelbtn')}}</a>
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
        </div>
    </div>
</section><!-- /.content -->
@stop
@section('script')
<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.0/additional-methods.js"></script>

<!-- Include tags in input box -->
<script src="{{ asset('js/jquery.caret.min.js') }}"></script>
<script src="{{ asset('js/jquery.tag-editor.js') }}"></script>
<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
    


<script type="text/javascript">
    var id = <?php echo (isset($data) && !empty($data)) ? $data->id : '0'; ?>;
    if(id == 0)
    {
        var validateRules = {
            name: {
                required: true,
    //          letterswithbasicpunc: true
            },
            country_code: {
                required: true,
            },
            flag:
            {
                required: true,
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }
        };
    }
    else
    {
        var validateRules = {
            name: {
                required: true,
    //          letterswithbasicpunc: true
            },
            country_code: {
                required: true,
            },
            flag:
            {
                extension: "jpeg|jpg|bmp|png",
                filesize: 5000000 // 5 mb
            }
        };
    }
    $("#addCountryManagement").validate({
        ignore: "",
        rules: validateRules,
        messages: {
            name:{
                required: "<?php echo trans('labels.countrynamerequired'); ?>"
            },
            country_code:{
                required: "<?php echo trans('labels.countrycoderequired'); ?>"
            },
            flag:{
                required: "<?php echo trans('labels.flagrequired'); ?>",
                extension: "<?php echo trans('labels.flagvalidextension'); ?>",
            }
        }
    });
</script>


@stop
