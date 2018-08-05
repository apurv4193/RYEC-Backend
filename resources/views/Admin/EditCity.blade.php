@extends('Admin.Master')

@section('content')
<link rel="stylesheet" type="text/css" href="{{ asset('css/jquery.tag-editor.css') }}" />
<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.city')}}
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
                    <h3 class="box-title"><?php echo (isset($data) && !empty($data)) ? ' Edit ' : 'Add' ?> {{trans('labels.city')}}</h3>
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
                <form id="addStateManagement" class="form-horizontal" method="post" action="{{ url('/admin/savecity/') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" value="<?php echo (isset($data) && !empty($data)) ? $data->id : '0' ?>">
                    
                    <div class="box-body">

                        <div class="form-group">
                            <?php
                            if (old('state_id'))
                                $state_id = old('state_id');
                            elseif (isset($data) && !empty ($data->state_id))
                                $state_id = $data->state_id;
                            else
                                $state_id = '';
                            ?>
                            <label for="state_id" class="col-sm-2 control-label">{{ trans('labels.state') }}<span class="star_red">*</span></label>
                            <div class="col-sm-8">
                                <select name="state_id" data="" class="form-control">
                                    <option value="">Select {{trans('labels.state')}}</option>
                                    @forelse($states as $state)
                                        <option class="type_parent_cat cat_type" value="{{$state->id}}" {{($state_id == $state->id)? 'selected' : ''}}>
                                            {{$state->name}}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                        </div>

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
                        
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn bg-purple save-btn">{{trans('labels.savebtn')}}</button>
                            <a class="btn btn-default" href="{{ url('/admin/city') }}">{{trans('labels.cancelbtn')}}</a>
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
    var validateRules = {
        name: {
            required: true,
        },
        state_id: {
            required: true
        }
    };
    $("#addStateManagement").validate({
        ignore: "",
        rules: validateRules,
        messages: {
            name:{
                required: "<?php echo trans('labels.statenamerequired'); ?>"
            },
            state_id: {
                required: "<?php echo trans('labels.staterequired'); ?>"
            }
        }
    });
</script>


@stop
