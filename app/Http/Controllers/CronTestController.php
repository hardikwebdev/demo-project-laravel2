<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\DisputeOrder;
use App\User;
use App\AffiliateEarning;
use App\Specialaffiliatedusers;
use App\BuyerTransaction;
use App\SellerEarning;
use App\OrderSubscription;
use App\SubscribeUser;
use Artisan;
class CronTestController extends Controller
{
	
    function testcron($cronname)
    {
    	Artisan::call($cronname);
    }
}
