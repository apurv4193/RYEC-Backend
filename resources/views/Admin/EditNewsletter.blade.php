@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.newsletter')}}
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.newsletter')}}</h3>
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
                <form id="addNewsletter" class="form-horizontal" method="post" action="{{ url('/admin/newsletter/save') }}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('title'))
                                $title = old('title');
                            elseif ($data)
                                $title = $data->title;
                            else
                                $title = '';
                            ?>
                            <label for="title" class="col-sm-2 control-label">{{trans('labels.title')}}<span class="star_red">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="title" name="title" placeholder="{{trans('labels.title')}}" value="{{$title}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            if (old('body'))
                                $body = old('body');
                            elseif ($data)
                                $body = $data->body;
                            else
                                $body = '';
                            ?>
                            <label for="body" class="col-sm-2 control-label">{{trans('labels.body')}}<span class="star_red">*</span></label>
                            <div class="col-sm-10">
                                <textarea id="body" name="body" class="form-control" cols="5"  rows="5" placeholder="">{{$body}}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            if (old('publish_status'))
                                $publish_status = old('publish_status');
                            elseif ($data)
                                $publish_status = $data->publish_status;
                            else
                                $publish_status = '';
                            ?>
                            <label for="publish_status" class="col-sm-2 control-label">{{trans('labels.publish_status')}}<span class="star_red">*</span></label>
                            <div class="col-sm-10">
                                <select name="publish_status" id="publish_status" class="form-control">
                                    <option value="0">Draft</option>
                                    <option value="1">Publish</option>
                                </select>
                            </div>
                        </div>
                        
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" name="save" value="save" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <button type="submit" name="save_send" value="save_send"  class="btn bg-purple save-btn">{{trans('labels.savesendbtn')}}</button>
                            <a class="btn btn-default" href="{{ url('admin/newsletter') }}">{{trans('labels.cancelbtn')}}</a>
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
<script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.0/additional-methods.js"></script>

<script type="text/javascript">
    
CKEDITOR.replace('body');
$(document).ready(function () {
   
    <?php if(isset($data->id) && $data->id > 0) { ?>
        var signupRules = {
            title: {
                required: true
            },
            body: {
                emptyetbody: true
            },
            publish_status: {
                required: true
            }
        };
    <?php }else{ ?>
        var signupRules = {
            title: {
                required: true
            },
            body: {
                emptyetbody: true
            },
            publish_status: {
                required: true
            }
        };
    <?php } ?>

    $("#addNewsletter12").validate({
        ignore: "",
        rules: signupRules,
        messages: {
            title: {
                required: "<?php echo trans('labels.titlerequired')?>"
            },
            body: {
                emptyetbody: "<?php echo trans('labels.bodyrequired')?>"
            },
            publish_status: {
                emptyetbody: "<?php echo trans('labels.publish_statusrequired')?>"
            }
        },
        submitHandler: function(form) {
          // do other things for a valid form
            $('.save-btn').prop('disabled', true);
            form.submit();
        }

    });
});
</script>
@stop