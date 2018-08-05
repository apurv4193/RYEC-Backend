@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.promoted_business')}}
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
                <div class="box-body table-responsive">
                    <table id="trendingCategoriesListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.name')}}</th>
                                <th>{{trans('labels.category')}}</th>
                                <th>{{trans('labels.email')}}</th>
                                <th>{{trans('labels.websiteurl')}}</th>
                                <th>{{trans('labels.mobile')}}</th>
                                <th>{{trans('labels.approvalstatus')}}</th>
                                <th>{{trans('labels.headerstatus')}}</th>
                               <!--  <th>{{trans('labels.headeraction')}}</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessesData as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->id}}
                                </td>

                                <td>
                                    {{$value->name}}
                                </td>
                                
                                <td>
                                    {{(isset($value->business_category->name)) ? $value->business_category->name : '-'}}
                                </td>
                                
                                <td>
                                    {{$value->email_id}}
                                </td>
                                
                                <td>
                                    {{$value->website_url}}
                                </td>
                                
                                <td>
                                    {{$value->mobile}} 
                                </td>
                                
                                <td>
                                    @if($value->approved == 0)
                                        <div class="business_approve">
                                            <span class="label label-danger" onclick="approved({{$value->id}})" style="cursor: pointer;">
                                                Pending
                                            </span>
                                        </div>
                                    @else
                                        <span class="label label-success">Approved</span>
                                    @endif
                                </td>
                                                                
                                <td id="promotedStatus_{{$value->id}}">
                                    @if ($value->promoted == 1)
                                        <span class="label label-success">{{trans('labels.promoted')}}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                
                                <!-- <td>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="promotedbusinesses[]" id="promotedbusinesses_{{$value->id}}" onclick="updatePromotedBusiness(this.value);" value="{{$value->id}}" <?php echo ($value->promoted == 1) ? 'checked' : '' ?>>
                                        </label>
                                    </div>
                                </td> -->
                            </tr>
                            @empty
                            @endforelse

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
    var table;
    $(document).ready(function() {
        table = $('#trendingCategoriesListing').DataTable({
           'aaSorting': [],
           'order': [[ 6, "desc" ]]
        });
    });
</script>

<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    function updatePromotedBusiness(businessId)
    {
        var checkBox = document.getElementById("promotedbusinesses_"+businessId);
        var promotedBusiness = 0;
        if (checkBox.checked == true)
        {
            promotedBusiness = 1;            
        }
        $.ajax({
            type: 'post',
            url: '{{ url("admin/updatePromotedBusinesses") }}',
            data: {
                businessId: businessId,
                promotedBusiness:promotedBusiness
            },
            success: function (response)
            {
                if(response !== '' && response != 0)
                {
                    if(promotedBusiness == 1) 
                    {
                        $('#promotedStatus_'+businessId).html('<span class="label label-success">Promoted</span>');
                    } else{
                        $('#promotedStatus_'+businessId).html('-');
                    }  
                }              
            }
        });
    }
    
</script>

@stop
