<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\InvestmentIdeasFiles;
use App\InvestmentIdeas;
use Auth;
use DB;
use Config;

class InvestmentIdeasInterest extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'investment_ideas_interest';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['idea_id', 'user_id', 'description'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $primaryKey = 'id';


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
            return InvestmentIdeasInterest::where('id', $data['id'])->update($updateData);
        } else {
            return InvestmentIdeasInterest::create($data);
        }
    }

    public function getInvestmentIdeas()
    {
        return $this->belongsTo('App\InvestmentIdeas','idea_id');
    }

    public function getUsers()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function getInvestmentIdeasFiles()
    {
        return $this->belongsTo('App\InvestmentIdeasFiles', 'idea_id');
    }

    /**
     * get all Show Interest On Investment Ideas Ideas for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $investmentIdeasInterest = InvestmentIdeasInterest::orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) {
            if(isset($filters['approved'])) {
                $investmentIdeasInterest->where('approved', $filters['approved']);
            }
            if(isset($filters['user_id'])) {
                $investmentIdeasInterest->where('user_id', $filters['user_id']);
            }
            if(isset($filters['idea_id'])) {
                $investmentIdeasInterest->where('idea_id', $filters['idea_id']);
            }
        }
        if(isset($paginate) && $paginate == true) {
            return $investmentIdeasInterest->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $investmentIdeasInterest->get();
        }
    }


    public function getShowInterestOnInvestmentIdeas($offset=0)
    {
        $response = InvestmentIdeasInterest::skip($offset)->take(Config::get('constant.API_RECORD_PER_PAGE'))->get();
        return $response;
    }

}
