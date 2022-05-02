<?php

namespace App\Models;
//use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

//Trait for sending notifications in laravel
use Illuminate\Notifications\Notifiable;

//Notification for Seller
use App\Notifications\AdminResetPasswordNotification;
use App\RoleMaster;
use App\RoleHasPermission;
use App\PermissionMaster;

class Admin extends Authenticatable
{
	
	use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password','first_name','last_name',
    ];

    //protected $table = 'admins';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
   /* protected $hidden = [
        'password', 'remember_token',
    ];*/
	
	public function sendPasswordResetNotification($token)
	{
      $this->notify(new AdminResetPasswordNotification($token));
    }
    
    protected $appends = ['Name','secret'];

    public function getNameAttribute()
    {
        return 'demo Support Team';
    }

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

}
