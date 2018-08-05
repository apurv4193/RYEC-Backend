@extends('Admin.Master')

@section('content')

<!-- Content Wrapper. Contains page content -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        {{trans('labels.trending_categories')}}
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
                                <th>{{trans('labels.lblcategoryname')}}</th>
                                <th>{{trans('labels.noofbusiness')}}</th>
                                <th>{{trans('labels.headerstatus')}}</th>
                                <th>{{trans('labels.headeraction')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($getTrendingCategories as $key=>$value)
                            <tr>
                                <td>{{$value->id}}</td>

                                <td>
                                    @if($value->parent == 0)
                                        {{$value->name}}
                                    @else
                                        {{$value->parentCatData->name}} - {{$value->name}}
                                    @endif

                                </td>
                                
                                <td>
                                    <?php   $businesses = Helpers::categoryHasBusinesses($value->id); ?>
                                    @if($businesses->count() != 0)
                                        <a href="{{ url('/admin/allbusiness') }}/{{Crypt::encrypt($value->id)}}" target="_blank">
                                            {{$businesses->count()}}
                                        </a>
                                    @else
                                        0
                                    @endif
                                </td>
                                
                                <td id="trendingStatus_{{$value->id}}">
                                    @if ($value->trending_category == 1)
                                        <span class="label label-success">Trending</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="trendingCategories[]" id="trendingCategory_{{$value->id}}" onclick="updateTrendingCategory(this.value)" value="{{$value->id}}" <?php echo ($value->trending_category == 1) ? 'checked' : '' ?>>
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
        table = $('#trendingCategoriesListing').DataTable({
           'aaSorting': [],
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
    
    function updateTrendingCategory(categroyId)
    {
        var checkBox = document.getElementById("trendingCategory_"+categroyId);
        var trendingCategory = 0
        if (checkBox.checked == true)
        {
            trendingCategory = 1;            
        }  
        $.ajax({
            type: 'post',
            url: '{{ url("admin/updateTrendingCategory") }}',
            data: {
                categoryId: categroyId,
                trendingCategory:trendingCategory
            },
            success: function (response)
            {
                if(response !== '' && response != 0)
                {
                    if(trendingCategory == 1) {
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
