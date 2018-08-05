<div>
    <input type="hidden" value="{{$catId}}" name="category_id[]"/>
    <ol class="breadcrumb" style="margin-bottom: 5px;">
        @forelse($categoryHierarchy as $cat)
            <li class="active">{{$cat['name']}}</li>
        @empty
        @endforelse
        <span style="cursor: pointer;" class="pull-right badge bg-red" onclick="remove_subcat(this,{{$catId}});"><i class="fa fa-remove"></i></span>
    </ol>
</div>