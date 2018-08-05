@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.businesses')}}
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                @if(Auth::user()->agent_approved == 0)
                <div class="box-header">
                    <form id="formSearch" class="form-horizontal" method="post" action="{{ url('/admin/allbusiness') }}">
                        <div class="col-md-4">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="search" value="1">
                            <select class="form-control" name="fieldtype">
                                <option value=''>{{trans('labels.selectfieldname')}}</option>
                                <option value='category_id' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'category_id')?'selected':''}}>{{trans('labels.category')}}</option>
                                <option value='city' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'city')?'selected':''}}>{{trans('labels.city')}}</option>
                                <option value='state' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'state')?'selected':''}}>{{trans('labels.state')}}</option>
                                <option value='country' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'country')?'selected':''}}>{{trans('labels.country')}}</option>
                                <option value='address' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'address')?'selected':''}}>{{trans('labels.address')}}</option>
                                <option value='latitude' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'latitude')?'selected':''}}>{{trans('labels.latitude')}}</option>
                                <option value='longitude' {{(isset($postData['fieldtype']) && $postData['fieldtype'] == 'longitude')?'selected':''}}>{{trans('labels.longitude')}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="fieldcheck">
                                <option value=''>{{trans('labels.selectfieldtype')}}</option>
                                <option value='0' {{(isset($postData['fieldcheck']) && $postData['fieldcheck'] == '0')?'selected':''}}>{{trans('labels.null')}}</option>
                                <option value='1' {{(isset($postData['fieldcheck']) && $postData['fieldcheck'] == '1')?'selected':''}}>{{trans('labels.notnull')}}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="submit" class="btn bg-purple" name="searchBtn" id="searchBtn" value="{{trans('labels.search')}}"/>
                            <a href="{{ url('/admin/allbusiness') }}">
                                <input type="button" class="btn bg-purple" name="clearBtn" id="clearBtn" value="{{trans('labels.clear')}}"/>
                            </a>
                        </div>
                    </form>
                </div>
                @endif
                @if(Auth::user()->agent_approved == 1)
                    <div class="box-header">
                        <form id="formSearch" class="form-horizontal" method="post" action="{{ url('/admin/allbusiness') }}">
                            <div class="col-md-4">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="search" value="1">
                                <input type="text" value="{{(isset($postData['searchtext'])) ? $postData['searchtext'] : ''}}" name="searchText" class="form-control" placeholder="search text">
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="type">
                                    <option value=''>{{trans('labels.selecttype')}}</option>
                                    <option value='created_by' {{(isset($postData['type']) && $postData['type'] == 'created_by')?'selected':''}}>{{trans('labels.createdby')}}</option>
                                    <option value='assign_to' {{(isset($postData['type']) && $postData['type'] == 'assign_to')?'selected':''}}>{{trans('labels.assignto')}}</option>
                                    <option value='all' {{(isset($postData['type']) && $postData['type'] == 'all')?'selected':''}}>{{trans('labels.all')}}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="submit" class="btn bg-purple" name="searchBtn" id="searchBtn" value="{{trans('labels.search')}}"/>
                                <a href="{{ url('/admin/allbusiness') }}">
                                    <input type="button" class="btn bg-purple" name="clearBtn" id="clearBtn" value="{{trans('labels.clear')}}"/>
                                </a>
                            </div>
                        </form>
                    </div>
                @endif
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="business">
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.user')}}</th>
                                <th>{{trans('labels.category')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.approvalstatus')}}</th>
                                @if(Auth::user()->agent_approved == 1)
                                    <th>{{trans('labels.status')}}</th>
                                @endif
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>
                                <td>
                                    {{$value->name}}
                                </td>
                                <td>
                                    @if(isset($value->user) && $value->user != '')
                                        <a href="{{ url('/admin/edituser') }}/{{Crypt::encrypt($value->user->id)}}" target="_blank">
                                            {{$value->user->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    {{$value->categories}}
                                </td>
                                <td>
                                    @if($value->country_code)
                                        ({{$value->country_code}}){{$value->mobile}} 
                                    @else
                                        {{$value->mobile}} 
                                    @endif
                                    
                                </td>
                                <td>
                                    @if($value->approved == 0)
                                        <div class="business_approve">
                                            <span class="label label-danger" onclick="approved({{$value->id}})" style="cursor: pointer;">
                                                {{trans('labels.pending')}}
                                            </span>
                                        </div>
                                    @else
                                        <span class="label label-success">{{trans('labels.approved')}}</span>
                                    @endif
                                </td>
                                @if(Auth::user()->agent_approved == 1)
                                    <td>
                                        @if($value->created_by == Auth::id())
                                            <span class="label label-success">Created By</span>
                                        @elseif($value->created_by != Auth::id())
                                            <span class="label label-success">Assign to</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ url('admin/user/business/edit') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" data-original-title="Edit" class='glyphicon glyphicon-edit'></span>
                                    </a>&nbsp;&nbsp;
                                    @if(Auth::user()->agent_approved == 0)
                                        <a onclick="return confirm('Are you sure you want to delete ?')" href="{{ url('/admin/user/business/delete') }}/{{Crypt::encrypt($value->id)}}">
                                            <span data-toggle="tooltip" data-original-title="Delete" class='glyphicon glyphicon-remove'></span>
                                        </a>&nbsp;&nbsp;
                                    @endif

                                    <a href="{{ url('/admin/user/business/service') }}/{{Crypt::encrypt($value->id)}}">
                                        <span  class="badge bg-light-blue" data-toggle="tooltip" data-original-title="Manage Service" style="margin-bottom: 3px;">S</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/product') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Product" style="margin-bottom: 3px;">P</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/owner') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Owner" style="margin-bottom: 3px;">O</span>
                                    </a>&nbsp;&nbsp;
                                    <a href="{{ url('/admin/user/business/membership') }}/{{Crypt::encrypt($value->id)}}">
                                        <span data-toggle="tooltip" title="" class="badge bg-light-blue" data-original-title="Manage Membership" style="margin-bottom: 3px;">M</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                    @if(Auth::user()->agent_approved == 1)
                        <div class="pull-right">
                            <?php echo $businessList->render(); ?>
                        </div>
                    @endif
                </div><!-- /.box-body -->
            </div>
            <!-- /.box -->
            
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@stop
@section('script')
<script type="text/javascript">
    <?php if(Auth::user()->agent_approved == 0) { ?>
    $(document).ready(function() {
        $('#business').DataTable({
           "aaSorting": []
        });
    });
    <?php } ?>

    function approved(businessId)
    {
        var token = '<?php echo csrf_token() ?>';
        $.ajax({
            headers: { 'X-CSRF-TOKEN': token },
            type: "POST",
            url: '/admin/user/business/approved',
            data: {businessId: businessId},
            success: function( data ) {
                $('.business_approve').html('<span class="label label-success">Approved</span>');
            }
        });
    }
</script>
@stop