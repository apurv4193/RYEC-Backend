@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.states')}}
        <a href="{{ url('admin/addstate') }}" class="btn bg-purple pull-right"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.state')}}</a>

    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <table class='table table-bordered table-striped' id="stateList">
                        <thead>
                            <tr>
                                <th>{{trans('labels.country')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stateList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->country->name}}
                                </td>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    <a href="{{ url('/admin/editstate') }}/{{Crypt::encrypt($value->id)}}" title="Edit">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deletestate') }}/{{$value->id}}">
                                        <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div><!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#stateList').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop