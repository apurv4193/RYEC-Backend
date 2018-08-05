@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.otp')}}
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.otp')}}</h3>
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
                <form id="addOtpManagement" class="form-horizontal" method="post" action="{{ url('/admin/saveotp/') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                                if (old('phone'))
                                    $phone = old('phone');
                                elseif (isset($data) && !empty ($data->phone))
                                    $phone = $data->phone;
                                else
                                    $phone = '';

                                if (old('country_code'))
                                    $country_code = old('country_code');
                                elseif (isset($data) && !empty ($data->country_code))
                                    $country_code = $data->country_code;
                                else
                                    $country_code = '';
                            ?>
                            <label for="phone" class="col-sm-2 control-label">{{trans('labels.phone')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="phone" name="phone" value="({{$country_code}})-{{$phone}}" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                                if (old('otp'))
                                    $otp = old('otp');
                                elseif (isset($data) && !empty ($data->otp))
                                    $otp = $data->otp;
                                else
                                    $otp = '';
                            ?>
                            <label for="otp" class="col-sm-2 control-label">{{trans('labels.otp')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="otp" name="otp" value="{{$otp}}">
                            </div>
                        </div>
                        
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <button type="submit" class="btn bg-purple save-btn" name="action" value="sendotp">{{trans('labels.saveresendotpbtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/otp') }}">{{trans('labels.cancelbtn')}}</a>
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
    $('#otp').mask('999999');
    var validateRules = {
        otp: {
            required: true,
            number:true,
            maxlength: 6,
            minlength: 6,

        }
    };
    $("#addOtpManagement").validate({
        ignore: "",
        rules: validateRules,
        messages: {
            otp:{
                required: "<?php echo trans('labels.otprequired'); ?>"
            }
            
        }
    });
</script>


@stop
