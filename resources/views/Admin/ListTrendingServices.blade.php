@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.trending_services')}}
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
                    <table id="trendingServicesListing" class='table table-bordered table-striped'>
                        <thead>
                            <tr>
                                <th>{{trans('labels.id')}}</th>
                                <th>{{trans('labels.lblcategoryname')}}</th>
                                <th>{{trans('labels.noofbusiness')}}</th>
                                <th>{{trans('labels.headerstatus')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($getTrendingServices as $key=>$value)
                            <tr>
                                <td>{{$value->id}}</td>

                                <td>{{$value->name}}</td>
                                
                                <td>
                                    <?php 
                                        $businesses = Helpers::categoryHasBusinesses($value->id); 
                                        echo $businesses->count();
                                    ?>
                                </td>
                                
                                <td id="trendingStatus_{{$value->id}}">
                                    @if ($value->trending_service == 1)
                                        <span class="label label-success">Trending</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="trendingServices[]" id="trendingService_{{$value->id}}" onclick="updateTrendingService(this.value)" value="{{$value->id}}" <?php echo ($value->trending_service == 1) ? 'checked' : '' ?>>
                                        </label>
                                    </div>
                                </td>
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
        table = $('#trendingServicesListing').DataTable({
           "aaSorting": [],
           'order': [[ 3, "desc" ]],
           'paging': false
        });
    });
</script>

<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    function updateTrendingService(categroyId)
    {
        var checkBox = document.getElementById("trendingService_"+categroyId);
        var trendingService = 0;
        if (checkBox.checked == true)
        {
            trendingService = 1;            
        }    
        $.ajax({
            type: 'post',
            url: '{{ url("admin/updateTrendingService") }}',
            data: {
                categoryId: categroyId,
                trendingService:trendingService
            },
            success: function (response)
            {
                if(response !== '' && response != 0)
                {
                    if(trendingService == 1) {
                        $('#trendingStatus_'+categroyId).html('<span class="label label-success">Trending</span>');
                    } else{
                        $('#trendingStatus_'+categroyId).html('-');
                    } 
                }
            }
        });
    }
</script>

@stop
