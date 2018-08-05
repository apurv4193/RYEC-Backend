<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\InvestmentIdeasFiles;
use Auth;
use DB;
use Config;
use Cviebrock\EloquentSluggable\Sluggable;

class InvestmentIdeas extends Model
{
    use SoftDeletes, CascadeSoftDeletes, Sluggable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'investment_ideas';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'category_id', 'title', 'title_slug', 'description','location','city', 'latitude','longitude','investment_amount_start', 'investment_amount_end', 'project_duration', 'member_name', 'member_email', 'member_phone', 'offering_percent'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    protected $primaryKey = 'id';
    
    protected $cascadeDeletes = ['investmentIdeasFiles', 'investmentIdeasInterest'];
    
//  protected $maps = ['investment_amount_start' => 'start_price', 'investment_amount_end' => 'end_price'];
    
    protected $appends = ['descriptions', 'start_price', 'end_price'];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'title_slug' => [
                'source' => 'title'
            ]
        ];
    }
    
    public function getStartPriceAttribute()
    {
        return $this->attributes['investment_amount_start'];
    }
    
    public function getEndPriceAttribute()
    {
        return $this->attributes['investment_amount_end'];
    }
    
    public function getDescriptionsAttribute() 
    {
        return $this->attributes['description'];
    }
    
    /**
     * Insert and Update Investment Ideas
     */    
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) 
        {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return InvestmentIdeas::where('id', $data['id'])->update($updateData);
        } else {
            return InvestmentIdeas::create($data);
        }
    }

    /**
     * get all Investment Ideas for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
//      $investmentIdeas = InvestmentIdeas::orderBy('id', 'DESC');
        $investmentIdeas = InvestmentIdeas::whereNull('deleted_at');
        
        if(isset($filters) && !empty($filters)) 
        {
            if(isset($filters['ids'])) {
                $investmentIdeas->whereIn('id',$filters['ids']);
            }
            if(isset($filters['approved'])) {
                $investmentIdeas->where('approved', $filters['approved']);
            }
            if(isset($filters['user_id'])) {
                $investmentIdeas->where('user_id', $filters['user_id']);
            }
            if(isset($filters['offset'])) {
                if(isset($filters['limit']))
                    $investmentIdeas->skip($filters['offset'])->take($filters['limit']);
                else
                    $investmentIdeas->skip($filters['offset'])->take(Config::get('constant.API_RECORD_PER_PAGE'));
            }
            if(isset($filters['min_price']) && isset($filters['max_price'])) {
                $investmentIdeas->where(function($investmentIdeas) use ($filters){
                    $investmentIdeas->whereBetween('investment_amount_start', [$filters['min_price'],$filters['max_price']]); 
                    $investmentIdeas->orWhereBetween('investment_amount_end', [$filters['min_price'],$filters['max_price']]);
                    
                    $investmentIdeas->orWhere(function($investmentIdeas) use ($filters){
                        $investmentIdeas->where('investment_amount_end', '>=' ,$filters['min_price']);
                        $investmentIdeas->where('investment_amount_start', '<=' ,$filters['min_price']);
                    });

                    $investmentIdeas->orWhere(function($investmentIdeas) use ($filters){
                        $investmentIdeas->where('investment_amount_end', '>=' ,$filters['max_price']);
                        $investmentIdeas->where('investment_amount_start', '<=' ,$filters['max_price']);
                    });                                        
                });
            }
            if(isset($filters['category_id'])) {
                $investmentIdeas->whereIn('category_id', $filters['category_id']); 
            }
            if(isset($filters['location'])) {
                $investmentIdeas->whereIn('city', $filters['location']); 
            }
            if(isset($filters['price_range']) && !empty($filters['price_range'])) 
            {
                if($filters['price_range'] == 'price_low_high')
                {
//                  $investmentIdeas->orderBy(DB::raw('CAST(investment_amount_start AS SIGNED)'), 'ASC');
                    $investmentIdeas->orderBy('investment_amount_start', 'ASC');
                }
                elseif($filters['price_range'] == 'price_high_low')
                {
                    $investmentIdeas->orderBy('investment_amount_end', 'DESC');
                }
                else
                {
                    $investmentIdeas->orderBy('id', 'DESC');
                }
            }
            if(isset($filters['sortBy']) && !empty($filters['sortBy']))
            {
                if($filters['sortBy'] == 'popular')
                {
                    $investmentIdeas->orderBy('visits', 'DESC');
                }
                elseif($filters['sortBy'] == 'recentlyAdded')
                {
                    $investmentIdeas->orderBy('id', 'DESC');
                }
                elseif($filters['sortBy'] == 'nearMe' && isset($filters['latitude']) && !empty ($filters['latitude']) && isset($filters['longitude']) && !empty ($filters['longitude']))
                {

                    $investmentIdeas->selectRaw('*, ( 6371 * acos( cos( radians(' . $filters['latitude'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $filters['longitude'] . ') ) + sin( radians(' . $filters['latitude'] . ') ) * sin( radians( latitude ) ) ) ) AS distance');

                    if(isset($filters['radius']) && !empty ($filters['radius'])){
                        $investmentIdeas->having('distance', '<', $filters['radius']);
                    }

                   $investmentIdeas->orderBy('distance', 'ASC');
                }
            }
            else
            {
                $investmentIdeas->orderBy('id', 'DESC');
            }
        }
        else
        {
            $investmentIdeas->orderBy('id', 'DESC');
        }
        if(isset($paginate) && $paginate == true) {
            return $investmentIdeas->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $investmentIdeas->get();
        }
    }
    
    public function investmentIdeasFiles()
    {
        return $this->hasMany('App\InvestmentIdeasFiles', 'investment_id');
    }
    
    public function investmentIdeasImage()
    {
        return $this->hasOne('App\InvestmentIdeasFiles', 'investment_id');
    }
    
    public function investmentIdeasInterest()
    {
        return $this->hasMany('App\InvestmentIdeasInterest', 'idea_id');
    }
    
    public function getCategoryDetails()
    {
        return $this->belongsTo('App\Category','category_id');
    }
    
    public function getUsersDetails()
    {
        return $this->belongsTo('App\User','user_id');
    }
    
    public function getInvestmentIdeasDetails($ideaId) 
    {
        $response = InvestmentIdeas::find($ideaId); 
        return $response;
    }

    /*
    ** get investment filters
    */
    public function getInvestmentMaxMinAmountFilters() 
    {
        $response = InvestmentIdeas::selectRaw('min(investment_amount_start) AS min_amount,max(investment_amount_end) AS max_amount')->first(); 

        return $response;
    }
}
