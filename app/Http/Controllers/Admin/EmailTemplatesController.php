<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplatesRequest;
use Auth;
use Input;
use Config;
use Redirect;
use App\EmailTemplates;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EmailTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->objEmailTemplates = new EmailTemplates();
    }

    public function index()
    {
        $emailTemplatesList = $this->objEmailTemplates->getAll();
        return view('Admin.ListEmailTemplates', compact('emailTemplatesList'));
    }

    public function add()
    {
        $data = [];
        return view('Admin.EditEmailTemplate', compact('data'));
    }

    public function edit($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $data = $this->objEmailTemplates->find($id);
            if($data) {
                return view('Admin.EditEmailTemplate', compact('data'));
            } else {
                return Redirect::to("admin/templates")->with('error', trans('labels.recordnotexist'));
            }
        } catch (DecryptException $e) {
            return view('errors.404');
        }
        
    }

    public function save(EmailTemplatesRequest $request)
    {
        $postData = Input::get();
        unset($postData['_token']);
        $response = $this->objEmailTemplates->insertUpdate($postData);
        if ($response) {
            return Redirect::to("admin/templates")->with('success', trans('labels.etsuccessmsg'));
        } else {
            return Redirect::to("admin/templates")->with('error', trans('labels.eterrormsg'));
        }
    }

    public function delete($id)
    {
        $data = $this->objEmailTemplates->find($id);
        $response = $data->delete();
        if ($response) {
            return Redirect::to("admin/templates")->with('success', trans('labels.etdeletesuccessmsg'));
        }
    }

}
