@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.otp')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <table class='table table-bordered table-striped' id="otpList">
                        <thead>
                            <tr>
                                <th>{{trans('labels.phone')}}</th>
                                <th>{{trans('labels.otp')}}</th>
                                <th>{{trans('labels.date')}}</th>
                                <th>
                                    {{trans('labels.headeraction')}} 
                                    <a href="{{ url('/admin/sendotp')}}/0/all" class="btn bg-green"  data-toggle="tooltip" data-original-title="Send OTP To All" style="float: right;">
                                        <i class="fa fa-check"><b>SEND OTP</b></i> 
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($otpList as $key=>$value)
                            <tr>
                                <td>
                                    ({{$value->country_code}})-{{$value->phone}}
                                </td>
                                <td>
                                    {{$value->otp}}
                                </td>
                                <td>
                                    {{$value->created_at}}
                                </td>
                                <td>
                                    <a href="{{ url('/admin/editotp') }}/{{Crypt::encrypt($value->id)}}" title="Edit">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/sendotp') }}/{{Crypt::encrypt($value->id)}}/single" title="Edit">
                                        <i class="fa fa-check" data-toggle="tooltip" data-original-title="Send OTP"></i> 
                                    </a>&nbsp;&nbsp;
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
        $('#otpList').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop