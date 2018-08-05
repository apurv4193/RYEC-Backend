@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.countries')}}
        <a href="{{ url('admin/addcountry') }}" class="btn bg-purple pull-right"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn')}} {{trans('labels.country')}}</a>

    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <table class='table table-bordered table-striped' id="countryList">
                        <thead>
                            <tr>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.countrycode')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($countryList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    {{$value->country_code}}
                                </td>
                                <td>
                                    <a href="{{ url('/admin/editcountry') }}/{{Crypt::encrypt($value->id)}}" title="Edit">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/deletecountry') }}/{{$value->id}}">
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
        $('#countryList').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop