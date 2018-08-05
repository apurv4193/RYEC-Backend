<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Config;
use Auth;
use JWTAuth;
use JWTAuthException;


class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes, CascadeSoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'status','name', 'created_by', 'isRajput', 'email', 'password','country_code', 'phone', 'dob', 'occupation', 'profile_pic', 'subscription','gender','agent_approved','manual_entry','notification', 'reset_password_otp', 'reset_password_otp_date' ];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $cascadeDeletes = ['userMetaData', 'getInvestmentIdeasData', 'investmentIdeasInterest', 'getBusinessRatings','businesses','agentUser'];

    // public function setPasswordAttribute($password) {
    //     $this->attributes['password'] = bcrypt($password);
    // }

    /**
     * Insert and Update User
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return User::where('id', $data['id'])->update($updateData);
        } else {
            $data['created_by'] = Auth::id();
            return User::create($data);
        }
    }

    /**
     * get all User for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        
        $User= USer::whereHas('user_role', function ($query) {
                    $query->where('role_id', Config::get('constant.USER_ROLE_ID'));
                })->orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) 
        { 
            if(isset($filters['deleted']) && $filters['deleted'] == 'all')
            {
                $User->withTrashed();
            }
            if(isset($filters['created_by']) && isset($filters['user_ids']))
            {
                $User->where('created_by',$filters['created_by'])->orWhereIn('id',$filters['user_ids']);
            }
            
        }
        else
        {
            $User->where('status','1');
        }

        if(isset($paginate) && $paginate == true) {
            return $User->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $User->get();
        }
    }

    public function user_role()
    {
        return $this->hasOne('App\UserRole');
    }

    public function userMetaData()
    {
        return $this->hasOne('App\UserMetaData', 'user_id');
    }

    public function getInvestmentIdeasData()
    {
        return $this->hasMany('App\InvestmentIdeas','user_id');
    }

    public function investmentIdeasInterest()
    {
        return $this->hasMany('App\InvestmentIdeasInterest', 'user_id');
    }

    public function getBusinessRatings()
    {
        return $this->hasMany('App\BusinessRatings', 'user_id');
    }

    public function getActiveUserSubscription()
    {
        return User::where('subscription', '1')->get();
    }

    public function checkCurrentPassword($userId,$password)
    {
        if ($user = JWTAuth::attempt(['id' => $userId, 'password' => $password]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function businesses()
    {
        return $this->hasMany('App\Business');
    }

    public function singlebusiness()
    {
        return $this->hasOne('App\Business');
    }

    public function agentRequest()
    {
        return $this->hasOne('App\AgentRequest');
    }

    public function agentUser()
    {
        return $this->hasOne('App\AgentUser','user_id');
    }

    public function userFilter($postData)
    {
        $User = User::whereHas('user_role', function ($query) {
                    $query->where('role_id', Config::get('constant.USER_ROLE_ID'));
                })->orderBy('id', 'DESC');

        if(isset($postData['searchtext']) && $postData['searchtext'] != '')
        {
            $User->where(function($query) use ($postData){
                $query->where('name', 'like', '%'.$postData['searchtext'].'%')
                    ->orWhere('email', 'like', '%'.$postData['searchtext'].'%')
                    ->orWhere('phone', 'like', '%'.$postData['searchtext'].'%');
                    
            });
            $User->where('status','1');

        }
        if(isset($postData['usertype']) && $postData['usertype'] != '')
        {
            if($postData['usertype'] == 'vendor')
            {
                $User->whereIn('id',function($query){
                   $query->select('user_id')->from('business');
                });
                $User->where('status','1');
            }

            if($postData['usertype'] == 'customer')
            {
                $User->whereNotIn('id',function($query){
                   $query->select('user_id')->from('business');
                });
                $User->where('status','1');
            }

            if($postData['usertype'] == 'agent')
            {
                $User->where('agent_approved', 1)->where('status','1');
            }

            if($postData['usertype'] == 'deactive')
            {
                $User->where('status','0');
            }

            if($postData['usertype'] == 'active')
            {
                $User->where('status','1');
            }

            if($postData['usertype'] == 'deleted')
            {
                $User->onlyTrashed();
            }

        }
        if(isset($postData['created_by']))
        {
            $User->where('created_by',$postData['created_by']);
        }

        if(isset($postData['fieldname']) && $postData['fieldname'] != '' && isset($postData['fieldtype']) && $postData['fieldtype'] != '')
        {
            
            if($postData['fieldtype'] == 0) 
            {
                if($postData['fieldname'] == 'gender' || $postData['fieldname'] == 'isRajput')
                {
                    $User->where($postData['fieldname'],0);
                }   
                else
                {
                    $User->whereNull($postData['fieldname']);    
                }
            }                                
            else
            {
                if($postData['fieldname'] == 'gender' || $postData['fieldname'] == 'isRajput')
                {
                    $User->where($postData['fieldname'],1);
                }   
                else
                {
                    $User->whereNotNull($postData['fieldname']);   
                }
                
            }
        }

        return $User->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));

    }

}
