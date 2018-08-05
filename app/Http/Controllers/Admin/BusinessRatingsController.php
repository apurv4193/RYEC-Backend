<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Helpers;
use Config;
use Image;
use File;
use DB;
use Input;
use Redirect;
use App\BusinessRatings;
use App\Business;
use App\User;
use Crypt;
use Response;
use Carbon\Carbon;
use Mail;
use Session;
use Cache;
use Illuminate\Contracts\Encryption\DecryptException;

class BusinessRatingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        $this->objBusinessRatings = new BusinessRatings();
        $this->objUser = new User();
        $this->objBusiness = new Business();
        $this->catgoryTempImage = Config::get('constant.CATEGORY_TEMP_PATH');
        
        $this->controller = 'BusinessRatingsController';
    }
 
    public function save(Request $request)
    {
        
    }
    
}
