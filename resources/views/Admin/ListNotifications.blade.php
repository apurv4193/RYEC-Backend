@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.branding_notifications')}}
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

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.notifications')}}
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
                <div class="box-body">
                    <table id="trendingCategoriesListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>Notification</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Notification to Rajput customers to register their business</td>
                                <td>
                                    <a href="{{ url('/admin/send/rajputbusinessregister/notification') }}" class="btn bg-green ">
                                        <i class="fa fa-check"></i> Send Notification
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>Notification to members to upgrade membership plan to Premium</td>
                                <td>
                                    <a href="{{ url('/admin/send/upgradetopremium/notification') }}" class="btn bg-green ">
                                        <i class="fa fa-check"></i> Send Notification
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
