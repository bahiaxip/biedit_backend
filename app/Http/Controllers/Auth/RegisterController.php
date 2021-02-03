<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    
    use RegistersUsers;
    
    protected $redirectTo = RouteServiceProvider::HOME;
    
    public function __construct()
    {
        $this->middleware('guest');
    }

    
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    
    protected function create(array $data)
    {
        //sustituida la creación de directorio en PHP por sistema de archivos Storage 
        //de laravel, no es necesario, ya que funciona bien con PHP pero Storage crea
        //un directorio con propietario www-data y por tanto es protegido contra 
        //escritura mientras que en PHP es accesible a cualquier usuario, aunque para 
        //imágenes, al llevar nombre aleatorio tampoco es imprescindible , solo por probar

        /*
        //se crea la carpeta de usuario con el nombre del email en PHP
        //debe existir la carpeta img
        if(!is_dir("img/".$data["email"])){
            mkdir("img/".$data["email"]."/");            
        }
        */
        //Con el método store de laravel utilizándolo en el controlador para
        //almacenar los archivos por primera vez ya crea el directorio, por tanto,
        //esta creación del directorio con el nombre del correo no es necesaria
        
        $directory=Storage::disk("public")->directories("img/".$data["email"]);
        if(!$directory){
            Storage::disk("public")->makeDirectory("img/".$data["email"]);            
        }
        
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        
    }

    //métodos para la autenticación en api
    public function register(Request $request)
    {
        $email=$request->email;

        //se comprueba si ya existe un email igual
        $sameEmail=User::where("email",$email)->first();
        if(!$sameEmail){
            $this->validator($request->all())->validate();
            
            event(new Registered($user = $this->create($request->all())));

            $this->guard()->login($user);
        
            return $this->registered($request,$user) ?: redirect($this->redirectPath());
        }else{
                
            return response()->json(["message" => "El email ya existe"]);
        }

        
    }


    protected function registered(Request $request, $user){
        $user->generateToken();

        return response()->json(['data' => $user->toArray()],201);
    }

    
}
