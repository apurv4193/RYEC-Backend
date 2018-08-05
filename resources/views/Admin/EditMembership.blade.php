@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.memberships')}}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{url('admin/users')}}"><i class="fa fa-users"></i> Users </a></li>
        <li><a href="{{url('admin/user/business')}}/{{Crypt::encrypt($businessDetails->user->id)}}">{{$businessDetails->user->name}}</a></li>
        <li><a href="{{url('admin/user/business/membership')}}/{{Crypt::encrypt($businessDetails->id)}}">{{$businessDetails->name}} {{trans('labels.business')}}</a></li>
        <li class="active">{{trans('labels.membership')}}</li>
    </ol>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">

        <!-- right column -->
        <div class="col-md-12">
            <!-- Horizontal Form -->
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.membership')}}</h3>
                </div><!-- /.box-header -->
                @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <strong>{{trans('labels.whoops')}}</strong> {{trans('labels.someproblems')}}<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form id="addmembership" class="form-horizontal" method="post" action="{{ url('admin/user/business/membership/save') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <input type="hidden" name="business_id" value="<?php echo $businessId; ?>">
                    
                    
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('subscription_plan_id'))
                                $subscription_plan_id = old('subscription_plan_id');
                            elseif (isset($data))
                                $subscription_plan_id = $data->subscription_plan_id;
                            else
                                $subscription_plan_id = '';
                            ?>
                            <label for="name" class="col-sm-2 control-label">{{trans('labels.plan')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <select name="subscription_plan_id" data="" class="form-control">
                                    <option value="">Select {{trans('labels.plan')}}</option>
                                    @forelse($planList as $plan)
                                        <option  value="{{$plan->id}}" {{($subscription_plan_id == $plan->id)? 'selected' : ''}}>
                                            {{$plan->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('start_date'))
                                $start_date = old('start_date');
                            elseif (isset($data))
                                $start_date = $data->start_date;
                            else
                                $start_date = '';
                            ?>
                            <label for="mobile" class="col-sm-2 control-label">{{trans('labels.startdate')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="startDate" name="start_date" placeholder="{{trans('labels.startdate')}}" value="{{$start_date}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('end_date'))
                                $end_date = old('end_date');
                            elseif (isset($data))
                                $end_date = $data->end_date;
                            else
                                $end_date = '';
                            ?>
                            <label for="end_date" class="col-sm-2 control-label">{{trans('labels.enddate')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="endDate" name="end_date" placeholder="{{trans('labels.enddate')}}" value="{{$end_date}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('actual_payment'))
                                $actual_payment = old('actual_payment');
                            elseif (isset($data))
                                $actual_payment = $data->actual_payment;
                            else
                                $actual_payment = '';
                            ?>
                            <label for="actual_payment" class="col-sm-2 control-label">{{trans('labels.actualpayment')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="actual_payment" name="actual_payment" placeholder="{{trans('labels.actualpayment')}}" value="{{$actual_payment}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('agent_commision '))
                                $agent_commision = old('agent_commision');
                            elseif (isset($data))
                                $agent_commision = $data->agent_commision;
                            else
                                $agent_commision = '';
                            ?>
                            <label for="agent_commision" class="col-sm-2 control-label">{{trans('labels.agentcommision')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="agent_commision" name="agent_commision" placeholder="{{trans('labels.agentcommision')}}" value="{{$agent_commision}}">
                            </div>
                        </div>

                       <div class="form-group">
                            <?php
                            if (old('net_payment '))
                                $net_payment = old('net_payment');
                            elseif (isset($data))
                                $net_payment = $data->net_payment;
                            else
                                $net_payment = '';
                            ?>
                            <label for="net_payment" class="col-sm-2 control-label">{{trans('labels.netpayment')}}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="net_payment" name="net_payment" placeholder="{{trans('labels.netpayment')}}" value="{{$net_payment}}" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <?php
                            if (old('comments'))
                                $comments = old('comments');
                            elseif (isset($data))
                                $comments = $data->comments;
                            else
                                $comments = '';
                            ?>
                            <label for="comments" class="col-sm-2 control-label">{{trans('labels.comments')}}</label>
                            <div class="col-sm-8">
                                <textarea  class="form-control" id="comments" name="comments" placeholder="{{trans('labels.comments')}}">{{$comments}}</textarea>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            @if(Auth::user()->agent_approved == 0)
                                <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                                <a class="btn btn-default" href="{{ url('/admin/user/business/membership') }}/{{Crypt::encrypt($businessId)}}">{{trans('labels.cancelbtn')}}</a>
                            @else
                                <a class="btn btn-default" href="{{ url('/admin/user/business/membership') }}/{{Crypt::encrypt($businessId)}}">{{trans('labels.backbtn')}}</a>
                            @endif
                        </div>
                    </div><!-- /.box-footer -->
                </form>
            </div>
        </div>
    </div>
</section><!-- /.content -->
@stop
@section('script')
<script src="{{asset('plugins/ckeditor/ckeditor.js')}}"></script>
<script src="{{ asset('js/jquery.mask.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
<script>

$(document).ready(function () {
    $.validator.addMethod('lessThan', function(value, element, param) {
        return this.optional(element) || parseInt(value) <= parseInt($(param).val());
    }, "The value {0} must be less than {1}");
   
    var membershipRules = 
    {
        subscription_plan_id: {
            required: true
        },
        start_date:{
            required: true
        },
        end_date:{
            required: true
        },
        actual_payment:{
            number: true,
            required: true
        },
        agent_commision:{
            lessThan:'#actual_payment'
        }
    };
    

    $('#startDate').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    $('#endDate').datepicker({
        format: 'yyyy-mm-dd',
        startDate: $('#startDate').val(),
        autoclose: true
    });
    
    $('#actual_payment').keyup(function(){
        if($('#agent_commision').val() != ''){
            $('#net_payment').val(parseInt($('#actual_payment').val())-parseInt($('#agent_commision').val()));
        }
        else
        {
            $('#net_payment').val($('#actual_payment').val());
        }
        
    });

    $('#agent_commision').keyup(function(){
        $('#net_payment').val(parseInt($('#actual_payment').val())-parseInt($('#agent_commision').val()));
    });

    $("#addmembership").validate({
        ignore: "",
        rules: membershipRules,
        messages: {
            subscription_plan_id: {
                required: "<?php echo trans('labels.planrequired');?>"
            },
            start_date: {
                required: "<?php echo trans('labels.startdaterequired'); ?>"
            },
            end_date:{
                required: "<?php echo trans('labels.enddaterequired');?>"
            },
            actual_payment: {
                required: "<?php echo trans('labels.actualpaymentrequired'); ?>"
            },
            agent_commision:{
                lessThan:"<?php echo trans('labels.invalidcommision'); ?>"
            }
        },
    });
    
});


</script>
@stop