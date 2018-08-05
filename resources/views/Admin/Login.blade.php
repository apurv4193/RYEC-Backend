@extends('Admin.Master')

@section('content')

<div class="col-xs-12" style="padding-top: 15px;">
    @if (count($errors) > 0)
    <div class="alert alert-danger">
        <strong>{{trans('validation.whoops')}}</strong> {{trans('validation.someproblems')}}<br><br>
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
<div class="login-box">
    <div class="login-logo" style="color:#FFF;">
        {{trans('labels.appname')}}
    </div><!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg" style="color: #000;">{{trans('labels.startsession')}}</p>
        <form id="login_form" role="form" method="POST" action="{{ url('admin/logincheck') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="form-group has-feedback">
                <input type="text"  autocomplete="new-email" class="form-control" id="email" maxlength="50" minlength="5" name="email" placeholder="{{trans('labels.emaillbl')}} or {{trans('labels.phone')}}" value="">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password"  autocomplete="new-password" class="form-control" id="password" maxlength="20" minlength="6" name="password" placeholder="{{trans('labels.passwordlbl')}}">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-4">
                </div>
                <div class="col-xs-4">
                    <button type="submit" class="btn bg-purple btn-block">{{trans('labels.login')}}</button>
                </div><!-- /.col -->
                <div class="col-xs-4">
                </div>
            </div>
        </form>
    </div><!-- /.login-box-body -->
</div><!-- /.login-box -->
@stop
@section('script')
<script type="text/javascript">
    jQuery(document).ready(function() {
        var signupRules = {
            email: {
                required: true,
                //email  : true
            },
            password: {
                required: true
            }
        };
        $("#login_form").validate({
            rules: signupRules,
            messages: {
                email: {
                    required: '<?php echo trans('validation.emailrequired')?>'
                },
                password: {
                    required: '<?php echo trans('validation.passwordrequired')?>'
                }
            }
        });
    });
</script>

@stop