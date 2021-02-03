<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
    public function updateUser(Request $request){        
        if($request!=null && $request->name!=null && $request->email!=null){
            $name=$request->name;
            $email=$request->email;
            $sameEmail = User::where("email",$email)->first();
            if(!$sameEmail){
                return response()->json(["message" => "No existe una cuenta con ese email"]);
            }else{
                $sameEmail->update(["name" => $name]);
                return response()->json(["data" => $sameEmail ]);
            }
        }else{
            return response()->json(["message" => "Faltan datos"]);
        }
    }
}
