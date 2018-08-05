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
                <form id="sendPushNotification" class="form-horizontal" method="post" action="{{ url('/admin/sendPushNotification/') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="box-body">
                        
                        <div class="form-group">                            
                            <label for="title" class="col-sm-2 control-label">{{trans('labels.title')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="title" name="title">
                            </div>
                        </div>
                                                 
                        <div class="form-group">
                            <label for="description" class="col-sm-2 control-label">{{ trans('labels.description') }}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <textarea name="description" id="description" cols="113" rows="2"></textarea> 
                            </div>
                        </div>
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.sendbtn')}}</button>
                        </div>
                    </div><!-- /.box-footer -->
                    
                </form>
                
            </div>
            <!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function () 
    {
        var topicPushNotificationRules = {
            title: {
                required: true
            },
            description:{
                 required: true
            },
        };
        $("#sendPushNotification").validate({
            ignore: "",
            rules: topicPushNotificationRules,
            messages: 
            {
                title: {
                    required: "<?php echo trans('labels.titlerequired')?>"
                },
                description: {
                    required: "<?php echo trans('labels.descriptionrequired')?>"
                }
            }            
        });
    });
</script>
@stop