@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.cms')}}
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.cms')}}</h3>
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
                <form id="addtemplate" class="form-horizontal" method="post" action="{{ url('/admin/savecms') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('title'))
                                $title = old('title');
                            elseif (isset ($data) && !empty ($data->title))
                                $title = $data->title;
                            else
                                $title = '';
                            ?>
                            <label for="inputEmail3" class="col-sm-2 control-label">{{trans('labels.title')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="title" name="title" placeholder="{{trans('labels.title')}}" value="{{$title}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            if (old('slug'))
                                $slug = old('slug');
                            elseif (isset ($data) && !empty ($data->slug))
                                $slug = $data->slug;
                            else
                                $slug = '';
                            ?>
                            <label for="inputEmail3" class="col-sm-2 control-label">{{trans('labels.slug')}}</label>
                            <div class="col-sm-8">
                                <input type="text" readonly="true" class="form-control" id="slug" name="slug" placeholder="{{trans('labels.slug')}}" value="{{$slug}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            if (old('body'))
                                $body = old('body');
                            elseif (isset ($data) && !empty ($data->body))
                                $body = $data->body;
                            else
                                $body = '';
                            ?>
                            <label for="inputEmail3" class="col-sm-2 control-label">{{trans('labels.formlblbody')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <!--<textarea name="body" id="body">{{$body}}</textarea>-->
                                <textarea id="body" name="body" class="form-control" cols="5"  rows="5" placeholder="">{{$body}}</textarea>
                            <div class="descriptionerror"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <?php
                            if (old('type'))
                                $type = old('type');
                            elseif (isset ($data) && !empty ($data->type))
                                $type = $data->type;
                            else
                                $type = '';
                            ?>
                            <label for="inputEmail3" class="col-sm-2 control-label">{{trans('labels.type')}}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <select name="type" data="" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="terms" {{$type=='terms' ? 'selected' : ''}}>Terms</option>
                                    <option value="privacy" {{$type=='privacy' ? 'selected' : ''}}>Privacy</option>
                                </select>    
                            <div class="descriptionerror"></div>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('admin/cms') }}">{{trans('labels.cancelbtn')}}</a>
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
<?php if (empty($data)){ ?>
<script type="text/javascript">
    $('#title').keyup(function () {
        var str = $(this).val();
        str = $.trim(str);
        str = str.replace(/[^a-zA-Z0-9\s]/g, "");
        str = str.toLowerCase();
        str = str.replace(/\s/g, '-');
        $('#slug').val(str);
    });
</script>
<?php } ?>
<script>
CKEDITOR.replace('body');
$(document).ready(function () {

    $.validator.addMethod("emptyetbody", function(value, element) {
        var body_data = CKEDITOR.instances['body'].getData();
        return body_data != '';
    }, "<?php echo trans('validation.bodyrequired')?>");

    jQuery.validator.addMethod("lettersonly", function(value, element) {
        return this.optional(element) || /^[a-z]+$/i.test(value);
    }, "<?php echo trans('validation.lettersonly')?>");

    var signupRules = {
        title: {
            required: true
        },
        body: {
            emptyetbody: true
        }
    };

    $("#addtemplate").validate({
        ignore: "",
        rules: signupRules,
        messages: {
            title: {
                required: "<?php echo trans('labels.templatenamerequired')?>"
            },
            body: {
                emptyetbody: "<?php echo trans('labels.bodyrequired')?>"
            }
        },
        errorPlacement: function(error, element) {
            if (element.attr("name") == "body" )
                error.insertAfter(".descriptionerror");
            else
                error.insertAfter(element);
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