<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Admin;

class AdminUserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    public function index($user)
      {
          
          return $is_admin = Admin::where('id','=',$user->id)->where('role_type','=','super_admin')->where('is_delete','0')->count();
         
//          if(count($is_admin))
//          {
//              return TRUE;
//          }
//          else{
//              return FALSE;
//          }
      }
}
