<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Category;
use App\Business;
use DB;
use Config;
use Crypt;
use Cache;
//use OwenIt\Auditing\Contracts\Auditable;
use Cviebrock\EloquentSluggable\Sluggable;

class Category extends Model 
{
    use SoftDeletes, Sluggable;
//    use Sluggable;
//    use \OwenIt\Auditing\Auditable;

    protected $table = 'categories';

    protected $fillable = ['id', 'parent', 'name', 'category_slug','cat_logo', 'banner_img', 'metatags', 'service_type', 'trending_service', 'trending_category', 'created_at', 'updated_at'];

    protected $dates = ['deleted_at'];

    protected $auditInclude = ['name'];


    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'category_slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function investment_ideas()
    {
        return $this->hasMany('App\InvestmentIdeas', 'category_id');
    }
    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0)
        {
            $updateData = [];
            foreach ($this->fillable as $field) 
            {
                if (array_key_exists($field, $data)) 
                {
                    $updateData[$field] = $data[$field];
                }
            }            
            Cache::forget('apiTrendingCategories');
            Cache::forget('getTrendingServices');
            Cache::forget('apiTrendingServices');
            return Category::where('id', $data['id'])->update($updateData);
        }
        else
        {
            return Category::create($data);
        }
    }

    public function parentCatData()
    {
        return $this->belongsTo('App\Category','parent');
    }
    
    public function childCategroyData()
    {
        return $this->hasMany('App\Category','parent')->orderBy('name','ASC');
    }
    
    public function getAll($filters = array(), $paginate = false)
    {
        $getData = Category::whereNull('deleted_at');

        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['searchText']) && $filters['searchText'] != '')
            {
                $getData->where('name', 'like', '%'.$filters['searchText'].'%');
            }
            if(isset($filters['parent']))
            {
                $getData->where('parent', $filters['parent']);
            }
            
            if(isset($filters['parentIn']))
            {
                $getData->whereIn('parent', $filters['parentIn']);
            }

            if(isset($filters['idIn']))
            {
                $getData->whereIn('id', $filters['idIn']);
            }
            
            if(isset($filters['service_type']))
            {
                $getData->where('service_type', $filters['service_type']);
            }
            
            if(isset($filters['trending_service']))
            {
                $getData->where('trending_service', $filters['trending_service']);
            }

            if(isset($filters['trending_category']))
            {
                $getData->where('trending_category', $filters['trending_category']);
            }

            if(isset($filters['service_type']))
            {
                $getData->where('service_type', $filters['service_type']);
            }
            if(isset($filters['limit']) &&!empty($filters['limit']))
            {
                $getData->limit($filters['limit']);
            }
            if(isset($filters['sortBy']) && $filters['sortBy'] == 'id')
            {
                $getData->orderBy('id', 'DESC');
            }
            else
            {
               $getData->orderBy('name', 'ASC'); 
            }

        }
        else
        {
            $getData->orderBy('name', 'ASC');
        }
        if(isset($paginate) && $paginate == true) 
        {
            return $response = $getData->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } 
        else 
        {
            return $response = $getData->get();
        }
    }

    public function childAssignParent($parentId = 0, $id)
    {   
        $findChild = Category::where('parent', $id)->get();
        if($findChild && count($findChild) > 0)
        {
            foreach ($findChild as $key => $value)
            {
               $updateData = Category::where('id', $value->id)->update(['parent' => $parentId]);
            }
        }
        return 1;
    }
    
    // APIs regarding Function
   
    public function getSubCategoryWithCount($filters)
    {
        if(isset($filters['category_id']) && !empty($filters['category_id']))
        {
            $cId = $filters['category_id'];
            $response = Category::find($cId);
        }
        elseif(isset($filters['category_slug']) && !empty($filters['category_slug']))
        {
            $category_slug =  $filters['category_slug'];
            $response = Category::where('category_slug', $category_slug)->first();
        }
        return $response;
    }
    
    public function categoryHasBusinesses($categoryId) 
    {
        $getData = Business::where(function($query) use ($categoryId){
                        $query->whereRaw("FIND_IN_SET(".$categoryId.", category_id)")
                            ->orWhere('parent_category', $categoryId);
                    });
        return $getData->get();
    }
    
}
