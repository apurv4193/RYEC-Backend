<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;
use App\Category;
use Cviebrock\EloquentSluggable\Sluggable;

class Business extends Model
{
    use SoftDeletes, CascadeSoftDeletes, Sluggable;

    protected $table = 'business';
    
    protected $fillable = ['user_id',  'created_by', 'name', 'business_slug', 'description', 'category_id', 'parent_category', 'category_hierarchy', 'business_logo', 'phone','country_code','mobile', 'latitude','longitude','metatags','promoted','membership_type','address','street_address','locality','country','state','city','taluka','district','pincode','establishment_year','email_id','website_url','facebook_url','twitter_url','linkedin_url','instagram_url','approved','suggested_categories','agent_user'];
    
    protected $dates = ['deleted_at'];
    

    protected $cascadeDeletes = ['products','services','businessImages','businessWorkingHours','businessActivities','getBusinessRatings','owners','business_address', 'getChats'];

    /**
     * Insert and Update Business\
     */

    protected $appends = ['categories'];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'business_slug' => [
                'source' => 'name'
            ]
        ];
    }
    
    public function getCategoriesAttribute() 
    {
        $arrCategoryIds = explode(",", $this->attributes['category_id']);
        $categories = Category::whereIn('id', $arrCategoryIds)->pluck('name');
        if(count($categories)) {
            return implode(",", $categories->toArray());
        } else {
            return '';
        }
    }

    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Business::where('id', $data['id'])->update($updateData);
        } else {
            $data['created_by'] = Auth::id();
            return Business::create($data);
        }
    }

    /**
     * get all Business for admin
     */
    public function getAll($filters = array(), $paginate = false)
    { 
        $business = Business::whereNull('deleted_at');
        
        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['agent_user']) && $filters['agent_user'] != '')
            {
                $business->where('agent_user', $filters['agent_user']);
            }
            if(isset($filters['offset']) && $filters['offset'] >= 0 && isset($filters['take']) && $filters['take'] > 0)
            {
                $business->skip($filters['offset'])->take($filters['take']);
            }
            if(isset($filters['city']) && $filters['city'] != '')
            {
                $business->where('city', $filters['city']);
            }
            if(isset($filters['searchText']) && $filters['searchText'] != '')
            {
                //$business->where('approved', 1);

                $business->where(function($business) use ($filters){

                    $business->orWhere('name', 'like', '%'.$filters['searchText'].'%');
                    
                    $business->orWhereRaw("FIND_IN_SET('".$filters['searchText']."', metatags)");

                    $business->orWhere('mobile', 'like', '%'.$filters['searchText'].'%');
                    
                    $business->orWhere(function($business) use ($filters){
                        $business->whereHas('user', function($query) use ($filters){
                            $query->where('name', 'like', '%'.$filters['searchText'].'%');
                        });    
                    });

                    $business->orWhere(function($business) use ($filters){
                        $business->whereExists(function ($query) use ($filters){
                            $query->from('categories')
                                  ->whereRaw("FIND_IN_SET(categories.id, business.category_id)")
                                  ->where('categories.name', 'like', '%'.$filters['searchText'].'%');
                        });    
                    });

                    $business->orWhere(function($business) use ($filters){
                        $business->whereHas('businessParentCategory', function($query) use ($filters){
                            $query->where('name', 'like', '%'.$filters['searchText'].'%');
                        });
                    });

                    $business->orWhere(function($business) use ($filters){
                        $business->whereHas('owners', function($query) use ($filters){
                            $query->where('full_name', 'like', '%'.$filters['searchText'].'%');
                        });    
                    });
                });
            }
            if(isset($filters['approved'])) {
                $business->where('approved', $filters['approved']);
            }
            if(isset($filters['created_by'])) {
                $business->where('created_by', $filters['created_by'])->where('user_id', '!=', $filters['created_by']);
            }
            if(isset($filters['promoted'])) {
                $business->where('promoted', $filters['promoted']);
            }
            if(isset($filters['membership_type'])) {
                if(isset($filters['membership_premium_lifetime_type'])) {
                    $business->where('membership_type', '<>', 0);
                }
                else
                {
                    $business->where('membership_type', $filters['membership_type']);
                }
            }
            
            if(isset($filters['user_id'])) {
                $business->where('user_id', $filters['user_id']);
            }
            if(isset($filters['skip']) && isset($filters['take']) && !empty($filters['take'])) 
            {
                $business->skip($filters['skip'])->take($filters['take']);
            }
            if(isset($filters['sortBy']) && !empty($filters['sortBy']))
            {
                if($filters['sortBy'] == 'popular')
                {
                    $business->orderBy('membership_type', 'DESC')->orderBy('visits', 'DESC');
                }
                elseif ($filters['sortBy'] == 'ratings') 
                {
                    $business->selectRaw('*, (SELECT AVG(business_ratings.rating) FROM business_ratings WHERE business_ratings.business_id = business.id) As average_rating');
                    $business->orderBy('membership_type', 'DESC')->orderBy('average_rating', 'DESC');
                }
                elseif($filters['sortBy'] == 'AtoZ')
                {
                    $business->orderBy('membership_type', 'DESC')->orderBy('name', 'ASC');
                }
                elseif($filters['sortBy'] == 'ZtoA')
                {
                    $business->orderBy('membership_type', 'DESC')->orderBy('name', 'DESC');
                }
                elseif($filters['sortBy'] == 'promoted')
                {
                    $business->orderBy('id', 'DESC');
                    $business->orderBy('promoted', 'DESC');
                }
                elseif($filters['sortBy'] == 'membership_type')
                {
                    $business->orderBy('membership_type', 'DESC');
                }
                elseif($filters['sortBy'] == 'nearMe' && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                {
                    $business->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    if(isset($filters['radius']) && !empty ($filters['radius'])){
                        $business->having('distance', '<', $filters['radius']);
                    }

                   //$business->orderBy('distance', 'ASC');
                   $business->orderBy(\DB::raw('-`distance`'), 'DESC');
                }
                elseif($filters['sortBy'] == 'relevance' && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                {
                    $business->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    if(isset($filters['radius']) && !empty ($filters['radius'])){
                        $business->having('distance', '<', $filters['radius']);
                    }

                   $business->orderBy('membership_type', 'DESC')->orderBy(\DB::raw('-`distance`'), 'DESC');
                }
                else
                {
                    $business->orderBy('id', 'DESC');
                }
            }
            else
            {
                $business->orderBy('id', 'DESC');
            }
        }
        else
        {
            $business->orderBy('id', 'DESC');
        }

        if(isset($paginate) && $paginate == true) {
            return $business->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $business->get();
        }
    }

    public function businessFilter($postData,$pagination=false,$search=false)
    {
        if($search == true)
        {
            $business = Business::has('user');
        }
        else
        {
            $business = Business::has('user')->where('agent_user', Auth::id())->orderBy('id', 'DESC');
        }

        if(isset($postData['fieldtype']) && $postData['fieldtype'] != '' && isset($postData['isNull']) && $postData['isNull'] != '')
        {
            if($postData['isNull'] == 0) 
            {
                $business->whereNull($postData['fieldtype']);
            }                                
            else
            {
                $business->whereNotNull($postData['fieldtype']);
            }
        }
        if(isset($postData['searchText']) && $postData['searchText'] != '')
        {
            $business->where(function($business) use ($postData){

                $business->orWhere('name', 'like', '%'.$postData['searchText'].'%');
                
                $business->orWhereRaw("FIND_IN_SET('".$postData['searchText']."', metatags)");

                $business->orWhere('mobile', 'like', '%'.$postData['searchText'].'%');
                
                $business->orWhere(function($business) use ($postData){
                    $business->whereHas('user', function($query) use ($postData){
                        $query->where('name', 'like', '%'.$postData['searchText'].'%');
                    });    
                });

                $business->orWhere(function($business) use ($postData){
                    $business->whereExists(function ($query) use ($postData){
                        $query->from('categories')
                              ->whereRaw("FIND_IN_SET(categories.id, business.category_id)")
                              ->where('categories.name', 'like', '%'.$postData['searchText'].'%');
                    });    
                });

                $business->orWhere(function($business) use ($postData){
                    $business->whereHas('businessParentCategory', function($query) use ($postData){
                        $query->where('name', 'like', '%'.$postData['searchText'].'%');
                    });
                });

                $business->orWhere(function($business) use ($postData){
                    $business->whereHas('owners', function($query) use ($postData){
                        $query->where('full_name', 'like', '%'.$postData['searchText'].'%');
                    });    
                });
            });
        }
        if(isset($postData['type']) && $postData['type'] != '')
        {
            
            if($postData['type'] == 'created_by')
            {
                $business->where('created_by', Auth::id());
            }

            if($postData['type'] == 'assign_to')
            {
                $business->where('created_by','<>',Auth::id());
            }
        }

        if($pagination == true)
        {
            return $business->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        }
        else
        {
            return $business->get();
        }
        

    }
    
    public function businessMembershipPlans()
    {
        return $this->hasMany('App\Membership');
    }

    public function businessCreatedBy()
    {
        return $this->belongsTo('App\User','created_by');
    }

    public function businessParentCategory()
    {
        return $this->belongsTo('App\Category','parent_category');
    }
  
    public function business_category()
    {
        return $this->belongsTo('App\Category','category_id');
    }
    
    public function business_category_hierarchy()
    {
        return $this->belongsTo('App\Category','category_hierarchy');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function services()
    {
        return $this->hasMany('App\Service');
    }

    public function businessImages()
    {
        return $this->hasMany('App\BusinessImage');
    }
    
    public function getBusinessRatings()
    {
        return $this->hasMany('App\BusinessRatings', 'business_id');
    }
    
    public function businessImagesById()
    {
        return $this->hasOne('App\BusinessImage', 'business_id');
    }
    
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function businessWorkingHours()
    {
        return $this->hasOne('App\BusinessWorkingHours');
    }

    public function businessActivities()
    {
        return $this->hasMany('App\BusinessActivities','business_id');
    }
    
    public function owners()
    {
        return $this->hasMany('App\Owners', 'business_id');
    }
    
    public function business_address()
    {
        return $this->hasOne('App\BusinessAddressAttributes', 'business_id');
    }
    
    public function getChats()
    {
        return $this->hasMany('App\Chats', 'business_id');
    }
    
    public function getBusinessListingByCategoryId($cId, $filters = array(),$cIds = array()) 
    {
        $whereStr = '';
        $whereArr = [];

        $whereArr[] = "FIND_IN_SET(".$cId.", category_id)";

        if(!empty($cIds))
        {
            foreach ($cIds as $id) {
                $whereArr[]  = "FIND_IN_SET(".$id.", category_id)";
            }
        }

        if (!empty($whereArr)) 
        {
            $whereStr = implode(' OR ', $whereArr);
        }
        
        $getData = Business::whereRaw($whereStr);
        
        if(isset($filters) && !empty($filters))
        { 
            if(isset($filters['offset']) && $filters['offset'] >= 0 && isset($filters['take']) && $filters['take'] > 0)
            {
                $getData->skip($filters['offset'])->take($filters['take']);
            }
            if(isset($filters['approved'])) 
            {
                $getData->where('approved', $filters['approved']);
            }
            if(isset($filters['sortBy']) && !empty($filters['sortBy']))
            {
                if($filters['sortBy'] == 'popular')
                {
                    $getData->orderBy('membership_type', 'DESC')->orderBy('visits', 'DESC');
                }
                elseif ($filters['sortBy'] == 'ratings') 
                {
                    $getData->selectRaw('*, (SELECT AVG(business_ratings.rating) FROM business_ratings WHERE business_ratings.business_id = business.id) As average_rating');
                    $getData->orderBy('membership_type', 'DESC')->orderBy('average_rating', 'DESC');
                }
                elseif ($filters['sortBy'] == 'promoted') 
                {
                    $getData->orderBy('promoted', 'DESC');
                }
                elseif($filters['sortBy'] == 'membership_type')
                {
                    $getData->orderBy('membership_type', 'DESC');
                }
                elseif($filters['sortBy'] == 'AtoZ')
                {
                    $getData->orderBy('membership_type', 'DESC')->orderBy('name', 'ASC');
                }
                elseif($filters['sortBy'] == 'ZtoA')
                {
                    $getData->orderBy('membership_type', 'DESC')->orderBy('name', 'DESC');
                }
                elseif($filters['sortBy'] == 'nearMe' && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                { 
                    $getData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    if(isset($filters['radius']) && !empty ($filters['radius'])){
                        $getData->having('distance', '<', $filters['radius']);
                    }

                   //$getData->orderBy('distance', 'ASC');
                   $getData->orderBy(\DB::raw('-`distance`'), 'DESC');
                }
                elseif($filters['sortBy'] == 'relevance' && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                { 
                    $getData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    if(isset($filters['radius']) && !empty ($filters['radius'])){
                        $getData->having('distance', '<', $filters['radius']);
                    }

                   //$getData->orderBy('membership_type', 'DESC')->orderBy('distance', 'ASC');
                   $getData->orderBy('membership_type', 'DESC')->orderBy(\DB::raw('-`distance`'), 'DESC');
                }
                else
                {
                    $getData->orderBy('id', 'DESC');
                }
            }
            else
            {
                $getData->orderBy('id', 'DESC');
            }
        }
        else
        {
            $getData->orderBy('id', 'DESC');
        }
        return $getData->get();
    }

    public function getBusinessesByRating($filters = array())
    {
        $skip = (isset($filters['skip']) && !empty($filters['skip'])) ? $filters['skip'] : 0;
        $take = (isset($filters['take']) && !empty($filters['take'])) ? $filters['take'] : Config::get('constant.WEBSITE_RECORD_PER_PAGE');
        $getData = Business::where('approved', 1);
        if(isset($filters) && !empty($filters))
        { 
            if(isset($filters['sortBy']) && !empty($filters['sortBy']))
            {
                if(isset($filters['promoted'])) 
                {
                    $getData->where('promoted', $filters['promoted']);
                }
                if(isset($filters['skip']) && isset($filters['take']) && !empty($filters['take'])) 
                {
                    $business->skip($skip)->take($take);
                }
                if ($filters['sortBy'] == 'ratings') 
                {
                    $getData->selectRaw('*, (SELECT AVG(business_ratings.rating) FROM business_ratings WHERE business_ratings.business_id = business.id) As average_rating');
                    $getData->orderBy('membership_type', 'DESC')->orderBy('average_rating', 'DESC');
                }
                else
                {
                    $getData->orderBy('id', 'DESC');
                }
            }
            else
            {
                $getData->orderBy('id', 'DESC');
            }
        }
        else
        {
            $getData->orderBy('id', 'DESC');
        }
        return $getData->get();
    }
    
    public function getBusinessesByNearMe($filters = array()) 
    {
        $getData = Business::whereNull('deleted_at')->limit(200);
        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['approved'])) {
                $getData->where('approved', $filters['approved']);
            }
            if(isset($filters['promoted'])) 
            {
                $getData->where('promoted', $filters['promoted']);
            }
            if(isset($filters['skip']) && isset($filters['take']) && !empty($filters['take'])) 
            {
                $getData->skip($filters['skip'])->take($filters['take']);
            }
            if(isset($filters['sortBy']) && !empty($filters['sortBy']))
            {
                if($filters['sortBy'] == 'nearMe' && isset($filters['radius']) && !empty ($filters['radius']) && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                {
                    // 6371, 111.045, 3959
                    $getData->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');
                    $getData->having('distance', '<', $filters['radius']);

                   //$getData->orderBy('distance', 'ASC');
                   $getData->orderBy(\DB::raw('-`distance`'), 'DESC');
                }
            }
            else
            {
                $getData->orderBy('id', 'DESC');
            }
        }
        else
        {
            $getData->orderBy('id', 'DESC');
        }

        if(isset($paginate) && $paginate == true) {
            return $getData->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $getData->get();
        }
    }
    
    public static function userVendorData($uId)
    {
        $response = Business::where('user_id', $uId)->get();
        return $response;
    }

   

}
