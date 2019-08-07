<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Common\LdapHelper;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // overwrite the trait controller
    public function login(Request $req)
    {
      $this->validate($req, [
            'staff_no' => 'required', 'password' => 'required',
      ]);

      $logresp = LdapHelper::doLogin($req->staff_no, $req->password, 1);

      // dd($logresp);

      if($logresp['message'] == 'failed'){
        return view('auth.login', ['loginerror' => 'Invalid Credential', 'type' => 'warning']);
      }

      return redirect()->intended(route('home'));


    }

}
