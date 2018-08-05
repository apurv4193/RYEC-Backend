@extends('Admin.Master')

@section('content')
<!-- content push wrapper -->

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>

        {{trans('labels.searchterms')}}
       
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <table class='table table-bordered table-striped' id="searchTermList">
                        <thead>
                            <tr>
                                <th>{{trans('labels.searchterm')}}</th>
                                <th>{{trans('labels.type')}}</th>
                                <th>{{trans('labels.city')}}</th>
                                <th>{{trans('labels.count')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($searchTermList as $key=>$value)
                            <tr>
                                <td>
                                    {{$value->search_term}}
                                </td>
                                <td>
                                    @if($value->type == 1)
                                        Category
                                    @elseif($value->type == 2)
                                        Metatag
                                    @else
                                        Term
                                    @endif
                                </td>
                                <td>
                                    {{$value->city}}
                                </td>
                                <td>
                                    {{$value->count}}
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
        $('#searchTermList').DataTable({
           "aaSorting": []
        });
    });
</script>
@stop