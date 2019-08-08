<?php

namespace App\Common;

use Illuminate\Support\Facades\Auth;
use App\Common\CommonHelper;
use App\User;

class LdapHelper
{

  function errorHandler($errno, $errstr) {
		return CommonHelper::respond_json($errno, $errstr);
	}

  public static function doLogin($username, $password, $isweb = 0){

    // set_error_handler(array($this, 'errorHandler'));
    $errorcode = 200;
    $errm = 'success';

    $udn = "cn=$username,ou=users,o=data";
    $hostnameSSL = env('TMLDAP_HOSTNAME', 'ldaps://idssldap.tm.com.my:636');
    //	ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
    putenv('LDAPTLS_REQCERT=never');

    $con =  ldap_connect($hostnameSSL);
    if (is_resource($con)){
      if (ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3)){
        ldap_set_option($con, LDAP_OPT_REFERRALS, 0);

        // try to mind / authenticate
        try{
        if (ldap_bind($con,$udn, $password)){
          $errm = 'success';

          // insert into login access table
          // $loginacc = new LoginAccess;
          // $loginacc->STAFF_ID = $username;
          // $loginacc->FROM_IP = request()->ip();
          // $loginacc->save();

        } else {
          $errorcode = 401;
          $errm = 'Invalid credentials.';
        }} catch(Exception $e) {
          $errorcode = 500;
          $errm = $e->getMessage();
        }

      } else {
        $errorcode = 500;
        $errm = "TLS not supported. Unable to set LDAP protocol version to 3";
      }

      // clean up after done
      ldap_close($con);

    } else {
      $errorcode = 500;
      $errm = "Unable to connect to $hostnameSSL";
    }

    if($errorcode == 200){
      // $this->logs($username, 'Login', []);
      return LdapHelper::fetchUser($isweb, $username );
    }

    return CommonHelper::respond_json($errorcode, $errm);

  }

  /**
  *	get the information for the requested user
  *	to be used internally
  */
  public static function fetchUser($isweb, $username, $searchtype = 'cn'){

    // set_error_handler(array($this, 'errorHandler'));

    // do the ldap things
    $errm = 'success';
    $errorcode = 200;
    $udn= 'cn=novabillviewerldapadmin, ou=serviceAccount, o=Telekom';
    $password = 'nHQUbG9Z';
    $hostnameSSL = env('TMLDAP_HOSTNAME', 'ldaps://idssldap.tm.com.my:636');
    $retdata = [];
    //	ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
    putenv('LDAPTLS_REQCERT=never');

    $con =  ldap_connect($hostnameSSL);
    if (is_resource($con)){
      if (ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3)){
        ldap_set_option($con, LDAP_OPT_REFERRALS, 0);

        // try to bind / authenticate
        try{
        if (ldap_bind($con,$udn, $password)){

          // perform the search
          $ldres = ldap_search($con, 'ou=users,o=data', "$searchtype=$username");
          $ldapdata = ldap_get_entries($con, $ldres);
          // dd($ldapdata);
          // return $ldapdata;

          if($ldapdata['count'] == 0){
            $errorcode = 404;
            $errm = 'user not found';
          } else {
            $costcenter = $ldapdata['0']['ppcostcenter']['0'];
            $stid = $ldapdata['0']['cn']['0'];
            // $bcname = $this->findBC($costcenter);
            // $role = $this->getRole($stid);


            $retdata = [
              'STAFF_NO' => $stid,
              'NAME' => $ldapdata['0']['fullname']['0'],
              'UNIT' => $ldapdata['0']['pporgunitdesc']['0'],
              'SUBUNIT' => $ldapdata['0']['ppsuborgunitdesc']['0'],
              'DEPARTMENT' => $ldapdata['0']['pporgunit']['0'],
              'COST_CENTER' => $costcenter,
              'SAP_NUMBER' => $ldapdata['0']['employeenumber']['0'],
              // 'JOB_STATUS' => $ldapdata['0']['ppjobstatus']['0'],
              'JOB_GRADE' => $ldapdata['0']['ppjobgrade']['0'],
              // 'BC_NAME' => $bcname,
              // 'ROLE' => $role,
              'NIRC' => $ldapdata['0']['ppnewic']['0'],
              'EMAIL' => $ldapdata['0']['mail']['0'],
              'MOBILE_NO' => $ldapdata['0']['mobile']['0'],
              'SUPERIOR' => $ldapdata['0']['ppreporttoname']['0']
            ];

            $theuser = LdapHelper::createUser($retdata);

            //$retdata = $ldapdata;
            if($isweb == 1){
              Auth::loginUsingId($theuser->id, false);
              session(['ldapdata' => $retdata]);
            }
          }

        } else {
          $errorcode = 403;
          $errm = 'Invalid admin credentials.';
        }} catch(Exception $e) {
          $errorcode = 500;
          $errm = $e->getMessage();
        }

      } else {
        $errorcode = 500;
        $errm = "TLS not supported. Unable to set LDAP protocol version to 3";
      }

      // clean up after done
      ldap_close($con);

    } else {
      $errorcode = 500;
      $errm = "Unable to connect to $hostnameSSL";
    }

    return CommonHelper::respond_json($errorcode, $errm, $retdata);
  }

  // create the user if it's not exists
  private static function createUser($ldapdata){
    $user = User::where('staff_no', $ldapdata['STAFF_NO'])->first();

    if(!$user){
      $user = new User;
    }

    $user->name = $ldapdata['NAME'];
    $user->email = $ldapdata['EMAIL'];
    $user->staff_no = $ldapdata['STAFF_NO'];
    $user->superior = $ldapdata['SUPERIOR'];
    $user->password = 'ldap';
    $user->save();

    return $user;

  }


}
