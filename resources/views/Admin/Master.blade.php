<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{trans('labels.appname')}}</title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- Bootstrap 3.3.5 -->
        <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css')}}">
        <link rel="stylesheet" href="{{ asset('css/bootstrap.css')}}">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css')}}">
        <!-- Ionicons -->
        <link rel="stylesheet" href="{{ asset('css/ionicons.min.css')}}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{ asset('css/AdminLTE.min.css')}}">
        <link rel="stylesheet" href="{{ asset('css/skins/skin-purple-light.min.css')}}">
        <link rel="stylesheet" href="{{ asset('css/jquery-ui.css')}}">
        <link rel="stylesheet" href="{{ asset('css/custom.css')}}">
        <link rel="stylesheet" href="{{ asset('plugins/datepicker/datepicker3.css')}}">
        <!-- Datatables style -->
        <link rel="stylesheet" href="{{ asset('plugins/datatables/dataTables.bootstrap.css')}}">
        <link rel="stylesheet" href="{{ asset('plugins/iCheck/all.css')}}">

        @yield('header')
    </head>
    @if (Auth::check())
    <body class="hold-transition skin-purple-light sidebar-mini">
        @else
    <body class="hold-transition login-page">
        @endif

        <div class="wrapper">
            @if (Auth::check())
            <header class="main-header">
                <!-- Logo -->
                <a href="{{ url('/')}}" class="logo">
                    <!-- mini logo for sidebar mini 50x50 pixels -->
                    <span class="logo-mini">{{trans('labels.appshortname')}}</span>
                    <!-- logo for regular state and mobile devices -->
                    <span class="logo-lg">{{trans('labels.appname')}}</span>
                </a>
                <!-- Header Navbar: style can be found in header.less -->
                <nav class="navbar navbar-static-top" role="navigation">
                    <!-- Sidebar toggle button-->
                    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                        <span class="sr-only">{{trans('labels.togglenav')}}</span>
                    </a>
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">

                            <!-- User Account: style can be found in dropdown.less -->
                            <li class="dropdown user user-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <img src="{{ asset('/images/avatar5.png')}}" class="user-image" alt="User Image">
                                    <span class="hidden-xs">{{Auth::user()->name}}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <!-- User image -->
                                    <li class="user-header">
                                        <img src="{{ asset('/images/avatar5.png')}}" class="img-circle" alt="User Image">
                                        <p>
                                            {{Auth::user()->name}}
                                        </p>
                                    </li>

                                    <li class="user-footer">
                                        <div style="text-align: center;">
                                            <a href="{{ url('admin/logout')}}" class="btn btn-default btn-flat">{{trans('labels.logout')}}</a>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <!-- Left side column. contains the logo and sidebar -->
            <aside class="main-sidebar">
                <!-- sidebar: style can be found in sidebar.less -->
                <section class="sidebar">
                    <!-- Sidebar user panel -->
                    <div class="user-panel">
                        <div class="pull-left image">
                            <img src="{{ asset('/images/avatar5.png')}}" class="img-circle" alt="User Image">
                        </div>
                        <div class="pull-left info">
                            <p>{{Auth::user()->name}}</p>
                          </div>
                    </div>

                    <!-- sidebar menu: : style can be found in sidebar.less -->
                    <ul class="sidebar-menu">
                      <?php
                       if (isset($controller) && !empty($controller));
                       else
                          $controller = '';
                      ?>

                        @if(Auth::user()->agent_approved == 1)
                            <li <?php if (in_array(Route::current()->uri(), ['admin/users', 'admin/adduser','admin/edituser/{id}'])) {echo 'class="active"';}?>>
                                <a href="{{ url('admin/users') }}">
                                    <i class="fa fa-users"></i> <span>{{trans('labels.users')}}</span>
                                </a>
                            </li>
                            <li <?php if (in_array(Route::current()->uri(), ['admin/allbusiness'])) {echo 'class="active"';}?>>
                                <a href="{{ url('admin/allbusiness') }}">
                                    <i class="fa fa-navicon"></i> <span>{{trans('labels.businesses')}}</span>
                                </a>
                            </li>

                            <li class="<?php
                        if (in_array(Route::current()->uri(), ['admin/state','admin/country','admin/city','admin/editstate/{id}','admin/addstate','admin/addcity','admin/editcity/{id}','admin/addcountry','admin/editcountry/{id}'])) {
                            echo 'active';
                        }
                        ?>  treeview">
                        <a href="">
                            <i class="fa fa-globe"></i> <span>Zones</span><i class="fa fa-angle-left pull-right"></i>
                        </a>
                            <ul class="treeview-menu">
                                <li <?php if (in_array(Route::current()->uri(), ['admin/country','admin/addcountry','admin/editcountry/{id}'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/country') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.country')}}</span>
                                    </a>
                                </li>

                                <li <?php if (in_array(Route::current()->uri(), ['admin/state','admin/editstate/{id}','admin/addstate'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/state') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.state')}}</span>
                                    </a>
                                </li>

                                <li <?php if (in_array(Route::current()->uri(), ['admin/city','admin/addcity','admin/editcity/{id}'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/city') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.city')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        @else

                            <li class="<?php if (Route::current()->uri() == 'admin/dashboard') {echo 'active';}?> treeview">
                            <a href="{{ url('admin/dashboard') }}">
                                <i class="fa fa-dashboard"></i> <span>{{trans('labels.dashboard')}}</span>
                            </a>
                        </li>
                        <li <?php if (in_array(Route::current()->uri(), ['admin/membershiprequest'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/membershiprequest') }}">
                                <i class="fa fa-bullhorn"></i> <span>{{trans('labels.membershiprequest')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/users', 'admin/adduser','admin/edituser/{id}'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/users') }}">
                                <i class="fa fa-users"></i> <span>{{trans('labels.users')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/otp','admin/editotp/{id}'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/otp') }}">
                                <i class="fa fa-users"></i> <span>{{trans('labels.otp')}}</span>
                            </a>
                        </li>

                       <li <?php if (in_array(Route::current()->uri(), ['admin/allbusiness'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/allbusiness') }}">
                                <i class="fa fa-navicon"></i> <span>{{trans('labels.businesses')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/premiumbusiness'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/premiumbusiness') }}">
                                <i class="fa fa-navicon"></i> <span>{{trans('labels.premiumbusinesses')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/getAllPromotedBusinesses'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/getAllPromotedBusinesses') }}">
                                <i class="fa fa-bullhorn"></i> <span>{{trans('labels.promoted_business')}}</span>
                            </a>
                        </li>

                        <li class="<?php if (in_array(Route::current()->uri(), ['admin/categories', 'admin/category/create','admin/category/edit/{id}', 'admin/category/subcategories/{parentId}', 'admin/category/subcategory/{parentId}/addsubcategory', 'category/subcategory/{parentId}/editsubcategory/{editId}'])) { echo 'active'; } ?> treeview">
                            <a href="{{ url('admin/categories') }}">
                                <i class="fa fa-list-ul"></i>
                                <span>{{trans('labels.lblcategorymanagement')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/getAllTrendingCategory'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/getAllTrendingCategory') }}">
                                <i class="fa fa-area-chart"></i> <span>{{trans('labels.trending_categories')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/getAllTrendingServices'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/getAllTrendingServices') }}">
                                <i class="fa fa-line-chart"></i> <span>{{trans('labels.trending_services')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/agents'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/agents') }}">
                                <i class="fa fa-user-secret"></i> <span>{{trans('labels.agentrequest')}}</span>
                                <?php $agentrequestcount = Helpers::getPendingAgent(); ?>
                                @if($agentrequestcount > 0)
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">{{$agentrequestcount}}</span>
                                </span>
                                @endif
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/subscriptions', 'admin/addsubscription','admin/editsubscription/{id}'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/subscriptions') }}">
                                <i class="fa fa-dashboard"></i> <span>{{trans('labels.membershipplans')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/templates', 'admin/addtemplate','admin/edittemplate/{id}'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/templates') }}">
                                <i class="fa fa-envelope-o"></i> <span>{{trans('labels.emailtemplates')}}</span>
                            </a>
                        </li>

                        <li <?php if (in_array(Route::current()->uri(), ['admin/investmentideas'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/investmentideas') }}">
                                <i class="fa fa-money"></i> <span>{{trans('labels.investment_opportunities')}}</span>
                            </a>
                        </li>

                        <li class="<?php
                        if (in_array(Route::current()->uri(), ['admin/state','admin/country','admin/city','admin/editstate/{id}','admin/addstate','admin/addcity','admin/editcity/{id}','admin/addcountry','admin/editcountry/{id}'])) {
                            echo 'active';
                        }
                        ?>  treeview">
                        <a href="">
                            <i class="fa fa-globe"></i> <span>Zones</span><i class="fa fa-angle-left pull-right"></i>
                        </a>
                            <ul class="treeview-menu">
                                <li <?php if (in_array(Route::current()->uri(), ['admin/country','admin/addcountry','admin/editcountry/{id}'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/country') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.country')}}</span>
                                    </a>
                                </li>

                                <li <?php if (in_array(Route::current()->uri(), ['admin/state','admin/editstate/{id}','admin/addstate'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/state') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.state')}}</span>
                                    </a>
                                </li>

                                <li <?php if (in_array(Route::current()->uri(), ['admin/city','admin/addcity','admin/editcity/{id}'])) {echo 'class="active"';}?>>
                                    <a href="{{ url('admin/city') }}">
                                        <i class="fa fa-align-justify"></i> <span>{{trans('labels.city')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li <?php if (in_array(Route::current()->uri(), ['admin/notifications'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/notifications') }}">
                                <i class="fa fa-bullhorn"></i> <span>{{trans('labels.notifications')}}</span>
                            </a>
                        </li>
                        <li <?php if (in_array(Route::current()->uri(), ['admin/cms', 'admin/addcms','admin/editcms/{id}'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/cms') }}">
                                <i class="fa fa-pencil-square-o"></i> <span>{{trans('labels.cms')}}</span>
                            </a>
                        </li>
                        <li <?php if (in_array(Route::current()->uri(), ['admin/analytics'])) {echo 'class="active"';}?>>
                            <a href="{{ url('admin/analytics') }}">
                                <i class="fa fa-bar-chart-o"></i> <span>{{trans('labels.analytics')}}</span>
                            </a>
                        </li>
                        <li class="<?php if (in_array(Route::current()->uri(), ['admin/newsletter', 'admin/newsletter/create', 'admin/newsletter/edit/{id}'])) { echo 'active'; } ?> treeview">
                            <a href="{{ url('admin/newsletter') }}">
                                <i class="fa fa-newspaper-o"></i>
                                <span>{{trans('labels.newsletter')}}</span>
                            </a>
                        </li>
                        <li class="<?php if (in_array(Route::current()->uri(), ['admin/branding'])) { echo 'active'; } ?> treeview">
                            <a href="{{ url('admin/branding') }}">
                                <i class="fa fa-image"></i>
                                <span>{{trans('labels.branding')}}</span>
                            </a>
                        </li>
                        <li class="<?php if (in_array(Route::current()->uri(), ['admin/searchterm'])) { echo 'active'; } ?> treeview">
                            <a href="{{ url('admin/searchterm') }}">
                                <i class="fa fa-navicon"></i>
                                <span>{{trans('labels.searchterm')}}</span>
                            </a>
                        </li>
                        <!-- <li class="<?php  // if (in_array(Route::current()->uri(), ['admin/getPushNotification'])) { echo 'active'; } ?> treeview">
                            <a href="{{ url('admin/getPushNotification') }}">
                                <i class="fa fa-navicon"></i>
                                <span>{{trans('labels.topic_notification')}}</span>
                            </a>
                        </li> -->


                        @endif

                    </ul>
                </section>
                <!-- /.sidebar -->
            </aside>
            @endif

            @if (Auth::check())
            <div class="content-wrapper">

                @if ($message = Session::get('success'))
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-body">
                            <div class="alert alert-success alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
                                <h4><i class="icon fa fa-check"></i> {{trans('validation.successlbl')}}</h4>
                                {{ $message }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if ($message = Session::get('error'))
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-body">
                            <div class="alert alert-error alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">X</button>
                                <h4><i class="icon fa fa-check"></i> {{trans('validation.errorlbl')}}</h4>
                                {{ $message }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @yield('content')
            </div><!-- /.content-wrapper -->
            @else
            @yield('content')
            @endif

            @if (Auth::check())
            <footer class="main-footer">
                <div class="pull-right hidden-xs">
                    {!! trans('labels.version') !!}
                </div>
                {!! trans('labels.copyrightstr') !!}
            </footer>
            @endif
            @yield('footer')
        </div>
        <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
        <script src="{{ asset('plugins/jQuery/jQuery-2.1.4.min.js')}}"></script>
        <!-- Bootstrap 3.3.5 -->
        <script src="{{ asset('js/bootstrap.min.js')}}"></script>
        <!-- SlimScroll -->
        <script src="{{ asset('plugins/slimScroll/jquery.slimscroll.min.js')}}"></script>
        <!-- FastClick -->
        <script src="{{ asset('plugins/fastclick/fastclick.min.js')}}"></script>
        <!-- Datepicker -->
        <script src="{{ asset('plugins/datepicker/bootstrap-datepicker.js')}}"></script>
        <!-- backendLTE App -->
        <script src="{{ asset('js/app.min.js')}}"></script>
        <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
        <!-- datatables -->
        <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js')}}"></script>
        <script src="{{ asset('plugins/datatables/dataTables.bootstrap.min.js')}}"></script>
        <script src="{{ asset('plugins/iCheck/icheck.min.js')}}"></script>


        @yield('script')
    </body>
    </body>
</html>
