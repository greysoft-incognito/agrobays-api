<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Greysoft\Charts;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{

    public function charts($type = 'pie')
    {

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  'success',
            'response_code' => 200,
            'charts' => [
                "pie" => (new Charts)->getPie('admin'),
                "bar" => (new Charts)->getBar('admin'),
                "transactions" => ""
            ],
        ]);
    }
}
