@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.investment_opportunities')}}
        <div class="pull-right">
            <a href="{{ url('admin/investmentideas/add') }}" class="btn bg-purple"><i class="fa fa-plus"></i>&nbsp;{{trans('labels.addbtn').' '.trans('labels.investmentopportunity')}}</a>
        </div>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body table-responsive">
                    <table id="investmentOpportunities" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.creator_name')}}</th>
                                <th>{{trans('labels.title')}}</th>
                                <th>{{trans('labels.description')}}</th>
                                <th>{{trans('labels.investment_amount')}}</th>
                                <th>{{trans('labels.project_duration')}}</th>
                                <th>{{trans('labels.member_name')}}</th>
                                <th>{{trans('labels.member_email')}}</th>
                                <th>{{trans('labels.member_phone')}}</th>
                                <th>{{trans('labels.offering_percent')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($investmentDetail as $key=>$value)      
                                
                            <tr>
                                <td>
                                    {{(isset($value->getUsersDetails) && !empty($value->getUsersDetails->name)) ? $value->getUsersDetails->name : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->title) && !empty($value->title)) ? $value->title : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->description) && !empty($value->description)) ? $value->description : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->investment_amount_start) && isset($value->investment_amount_end)) ? $value->investment_amount_start.' - '.$value->investment_amount_end: '-'}}
                                </td>
                                <td>
                                    {{(isset($value->project_duration) && !empty($value->project_duration)) ? $value->project_duration : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->member_name) && !empty($value->member_name)) ? $value->member_name : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->member_email) && !empty($value->member_email)) ? $value->member_email : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->member_phone) && !empty($value->member_phone)) ? $value->member_phone : '-'}}
                                </td>
                                <td>
                                    {{(isset($value->offering_percent) && !empty($value->offering_percent)) ? $value->offering_percent : '-'}}
                                </td>
                                <td>
                                    <a href="{{ url('/admin/investmentideas/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    <a onclick="return confirm('Are you sure you want to delete ?')" href="">
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
        $('#investmentOpportunities').DataTable({
           'aaSorting': []
        });
    });
</script>
@stop