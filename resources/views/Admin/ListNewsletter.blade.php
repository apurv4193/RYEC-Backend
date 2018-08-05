@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.newsletter')}}
        <a href="{{ url('admin/newsletter/create') }}" class="btn bg-purple pull-right"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.newsletter')}}</a>

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
                    <table id="newsLetterList" class="table table-bordered table-striped">                        
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.title')}}</th>
                                <th>{{trans('labels.headerstatus')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @forelse($newsletterList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->title}}
                                </td>
                                <td>
                                    @if($value->notify_subscribers == 1)
                                        <span class="label label-warning">{{trans('labels.pending')}}</span>
                                    @elseif($value->notify_subscribers == 2)
                                        <span class="label label-success">{{trans('labels.sent')}}</span>
                                    @else
                                        <span class="label label-danger">{{trans('labels.draft')}}</span>
                                    @endif
                                </td>                                
                                <td>
                                    <a href="{{ url('/admin/newsletter/edit') }}/{{Crypt::encrypt($value->id)}}">
                                       <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/newsletter/delete') }}/{{Crypt::encrypt($value->id)}}">
                                        <span class="glyphicon glyphicon-remove" data-toggle="tooltip" data-original-title="Delete" onClick="return confirm(&#39;{{trans('labels.confirmdeletemsg')}}&#39;)"></span>
                                    </a>&nbsp;&nbsp;
                                    @if($value->notify_subscribers == 0)
                                        <a href="{{ url('/admin/newsletter/savesend') }}/{{Crypt::encrypt($value->id)}}">
                                            <span class="glyphicon glyphicon-send" data-toggle="tooltip" data-original-title="Send Newsletter"></span>
                                       </a>
                                    @else
                                    @endif
                                </td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>                       
                    </table>
                </div><!-- /.box-body -->
            </div>
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#newsLetterList').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop