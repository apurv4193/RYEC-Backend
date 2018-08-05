<div class="form-group">
    <label for="category_id" class="col-sm-2 control-label" ></label>
    <div class="col-sm-8">
        <!-- <select name="categoryArray[]" id="subcategory" class="form-control" onchange="return getSubCategory(this, {{$level+1}});" multiple>
            @forelse($categoryArray as $category)
                <option class="type_parent_cat cat_type" value="{{$category->id}}" >{{$category->name}}</option>
            @empty
            @endforelse
        </select> -->
        <ol class="breadcrumb">
            @forelse($categoryHierarchy as $cat)
                <li class="active"><a href="javascript:getParentSubCategory({{$cat['id']}}, {{$level+1}})">{{$cat['name']}}</a></li>
            @empty
            @endforelse
        </ol>
        <table class="table table-condensed">
            <tbody>
            @forelse($categoryArray as $category)
                <tr>
                    @if($category->childCategroyData->count() > 0)
                        <td>{{$category->name}}</td>
                        <td>
                            <span  title="Sub Category" style="cursor: pointer;" class="pull-right badge bg-light-blue" onclick="return getParentSubCategory({{$category->id}}, {{$level+1}});">
                                    <i class="fa fa-arrow-right"></i>
                            </span>
                        </td>
                    @else
                        <td>{{$category->name}}</td>
                        <td>
                            <span  title="Select Category" style="cursor: pointer;" class="pull-right badge bg-green" onclick="return  addCategotyHierarchy({{$category->id}});"><i class="fa fa-check"></i></span>
                        </td>
                    @endif
                </tr>
            @empty
            @endforelse
            </tbody>
        </table>
    </div>
</div>


