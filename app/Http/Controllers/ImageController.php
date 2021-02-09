<?php

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Str;
use App\Classes\MethodsImage;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Helpers\CustomHelper;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use ImagickKernel;
class ImageController extends Controller
{
    //propiedades de $_FILES  (name,type,tmp_name, error, size)
    //Tipos de error de $_FILES["imagen"] :

        //(El error 0 o UPLOAD_ERR_OK  es el estándar de subida satisfactoria)
        /*
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        */
    protected $methods;

    public function __construct(){

        $this->methods=new MethodsImage();

    }
    //todas las imágenes
    public function totalIndex(){

    }
    public function index(Request $request)
    {        
        if($request->post("api_token")){

            $api_token=$request->post("api_token");
            $email=$request->post("email");

            
            $user=User::where("api_token",$api_token)->where("email",$email)->first();
            if(!$user){
                //return ...
            }
            //si existe total está destinado para el efecto composite, tb se //podría filtrar para no incluir el elemento que contiene la imagen
            //principal pero se realiza en el frontend con computed
            if($request->post("total")){
                $image=Image::orderBy("id","DESC")->where("user_id",$user->id)->whereNotNull("thumb")->get();
                return response()->json(["images"=>$image]);
            }
            $image=Image::orderBy("id","DESC")->where("user_id",$user->id)->paginate(10);

        //En caso eliminar una imagen se asigna la misma página en la que se está
        //eliminando, pero si es la última de la página que reste una página y se 
        //vaya a la anterior, siempre y cuando no sea la primera página(current_page)

        //Para acceder a una de las propiedades protected como current_page es necesario lo siguiente:
            $current_page=json_decode($image->toJSON())->current_page;
            //si no quedan resultados en esa página redirige a la página 
            //anterior, siempre que no sea la página 0
            if(count($image)==0 && $current_page != 0){
                $page=Request()->page;
                //cambiamos el page (que trae la url) a la página anterior si en esa 
                //página no quedan resultados
                Request()->merge(["page"=>$page-1]);
                //y volvemos a realizar la consulta con el page cambiado
                $image=Image::orderBy("id","DESC")->where("user_id",$user->id)->paginate(10);
            }
            //$ima=json_decode($image);
        
        return $image;    
        }

        
        
    }
    
    public function create()
    {
        //    
    }
    
    public function store(Request $request)
    {   
    // opcion 1 para obtener Bearer Token (contiene el 'Bearer' delante)
        //if($request->header('Authorization')){
    //opción2 para obtener Bearer Token (solo token)
        if($request->bearerToken() && $request->post("email")){
//opción con Storage de Laravel
            $api_token=$request->bearerToken();
            //comprobamos el usuario con el api token y también el email
            
            $email=$request->post("email");
            

            $user=User::where("api_token",$api_token)->where("email",$email)->first();
            //si existe imagen se sube al 
            if($request->file("images")){
                $images=$request->file("images");
                //obtenemos nombre con File Storage de Laravel
                $name=$images[0]->getClientOriginalName();                
                //obtenemos extensión con File Storage de Laravel
                $ext=$images[0]->extension();
                //
                //obtenemos titulo, como laravel genera automáticamente un nombre
                //aleatorio no es necesario comprobar si existe más de un punto
                //en el nombre de la imagen, necesario para poder dividir de 
                //forma segura con explode. Por tanto nos ahorramos esa comprobación
                //con array_pop eliminamos el último elemento del array creado con explode y con implode volvemos a convertir en string
                $title=explode(".",$name);
                array_pop($title);
                $title=implode("",$title);
                //obtenemos title
                if($request->post("title")){
                    $title=$request->post("title");
                }
                //obtenemos detail
                $detail=NULL;
                if($request->post("detail")){
                    $detail=$request->post("detail");
                }
        //opción 1 subida de imágenes al servidor con put()
        //put y store generan un nombre aleatorio y obtiene automáticamente la extensión
        //Para asignar nombre método putFileAs o método storeAs
                //$path_image=Storage::disk("public")->put("",$images[0]);
                //$path_image=Storage::putFileAs("img/bahiaxip2@hotmail.com",$images[0],"nombre.jpg");
        //opción 2 subida de imágenes al servidor con store()
        //si no se incluye segundo parámetro al método store se asigna el 
        //disco por defecto
                //$path_image=$images[0]->storeAs("img/bahiaxip2@hotmail.com","nombre.jpg","public");
                $path_image=$images[0]->store("img/".$email,"public");
                $size=Storage::disk("public")->size($path_image);
        //opción 1 obtener datos
        //obtener datos de imagen mediante public_path("storage") y $path_image
                list($width,$height,$image_type)=getimagesize(public_path("storage")."/".$path_image);
                //obteniendo datos de imagen para db
        //opción 2 obtener datos
        //obtener datos de imagen mediante public_path() y $url
                //$url=Storage::url($path_image);
                //list($width,$height,$image_type)=getimagesize(public_path().$url);
                
        //obtenemos los tipos de rutas necesarios para la db
                $path="img/".$email."/";
                $random_name=str_replace("img/".$email."/", "",$path_image);
                $rand=Str::random(40);
                //$imagin=Storage::disk("public")->copy($path_image,$path.$rand."."jpg");
                //$data2=Storage::disk("public")->getVisibility($path."hola.jpg");

                //chmod(public_path("storage")."/".$path."hola.jpg",777);
                $thumb=NULL;
                $space_color=NULL;
                if($im=new Imagick(public_path("storage")."/".$path.$random_name)){
                    $space_color_int=$im->getImageColorspace();
                    $values=["UNDEFINED","RGB","GRAY","TRANSPARENT","OHTA","LAB","XYZ","YCBCR","YCC","YIQ","YPBPR","YUV","CMYK","SRGB","HSB","HSL","HWB","REC601LUMA","REC601YCBCR","REC709LUMA","REC709YCBCR","LOG","CMY","LUV","HCL","LCH","LMS","LCHAB","LCHUV","SCRGB","HSI","HSV","HCLP","YDBDR",];
                    $space_color=$values[$space_color_int];
                    $im->thumbnailImage(100,100,true,true);
                    $im->writeImage(public_path("storage")."/".$path.$rand.".".$ext);
                    $thumb=$rand.".".$ext;
                    
                }
                //$datas=public_path("storage")."/".$path."hola.jpg";
                    
        //creamos registro en la db
                $image=Image::create([
                    "title" =>  $title,
                    "detail"=>  $detail,
                    "width" =>  $width,
                    "height"=>  $height,
                    "path"  =>  $path,
                    "random_name"=>$random_name,
                    "thumb" => $thumb,
                    "ext"   =>  $ext,
                    "size"  => $size,
                    "space_color"=>$space_color,
                    "user_id"=> $user->id
                ]);

                return response()->json(["image"=>$image]);
            

/* anulado la asignación en PHP y sustituido por Laravel (Storage)
//opción en PHP 
            //$width=$request->post("width");
            //$height=$request->post("height");
    //opcion 1 para obtener Bearer Token
            //$api_token=$request->header('Authorization');
    //opción2 para obtener Bearer Token
            $api_token=$request->bearerToken();
            //para asignar el campo user_id asignamos al usuario con ese api_token
            $user=User::where("api_token",$api_token)->first();
            if(is_dir(public_path("storage")."/img/".$user->email)){
                //este foreach permite seleccionar imágenes múltiples (array de imágenes), sin embargo, para esta aplicación no es necesaria y siempre se aplica a una imagen
                foreach($_FILES["images"]["error"] as $key => $error){
                //UPLOAD_ERR_OK -> Error 0 ( No hay error , fichero subido con éxito)
                    if($error == UPLOAD_ERR_OK){
                        $full_name=$_FILES["images"]["name"][$key];
                        $size=$_FILES["images"]["size"][$key];
                //opcion 1 (no-utilizada) (separar nombre y extensión de imagen)
                        //como las opciones de extensión siempre son 4 caracteres ( el punto y la extensión jpg,png,gif) podemos restar 4 caracteres al total y obtener el nombre sin la extensión

                    //contamos los caracteres del titulo de la imagen
                        //$totalChar=strlen($title);

                    //Restamos 4 caracteres al string title
                        //$new_title=substr($title,0,($totalChar-4));

                //opcion 2(utilizada) (separar nombre y extensión de imagen)
                        //Creamos un array separando por punto y el último array es la extensión y el elemento o resto de elementos del array anteriores son el nombre de la imagen
                                
                                //opción directa a variables 
                                //list($t,$ext)=explode(".",$title);

                    //dividimos en array
                        $split=explode(".",$full_name);

                        //si la cuenta del array es mayor a 2, significa que el nombre de la imagen tiene más de un punto, si es así, hay dos opciones para dividir:
                            //1(anulada) Volvemos a concatenar los elementos del array menos el último.
                    //2(utilizada) Restamos al titulo el último array y el punto final                         
                        //con array_pop() obtenemos el último elemento del array en formato cadena y además modifica el array pasado eliminando ese último elemento
                //Extensión
                        $ext=array_pop($split);

                        //para obtener el formato de un valor gettype
                        //$a=gettype($ext);
                        
                        //Si no existen más puntos (de el que divide la extensión de la 
                        //imagen), el array ($split)solo se compone de 2 elementos(primero //nombre y segundo extensión), por tanto, convertimos el primer 
                        //elemento del array en string y al comprobar el total de elementos 
                        //(con count()) del array evitamos entrar en el for.
                //$name es el nombre sin la extensión, la extensión ha sido 
                //eliminada anteriormente del array $split mediante array_pop, 
                //que además de almacenar el último elemento, lo elimina del array pasado
                        $name=implode($split);
                        $count=count($split);
                        $name_tmp="";
                        //si existe más de un punto en el nombre de la imagen (poco común), 
                        //al utilizar el punto para dividir con explode y almacenarlo en un array es necesario volver a reconstruir la ruta como estaba.
                        if($count>1){
                        
                        //quitamos 1 al count para no concatenar el último array (extensión de imagen)
                            for($i=0;$i<$count;$i++){
                                //Para no añadir punto al inicio del título comprobamos si es vacío y de esa manera el primer punto no lo incluye, el resto vuelve a concatenar el punto
                                ($name_tmp=="") ?
                                    $name_tmp=$split[$i] :                                
                                    $name_tmp=$name_tmp.".".$split[$i];
                            }
                            //nombre de imagen sin extensión (en el caso de que haya más puntos en el nombre de la imagen original)
                            $name=$name_tmp;
                        }
                        //nombre de imagen aleatorio
                        $rand=Str::random(15);
                        $title="";

                        //si existe un detail en la petición se asigna si no null
                        $detail=$request->post("detail") ?: null;

                        //si title viene vacío se asigna el nombre de la imagen sin extensión
                        if(!$request->post("title") || $request->post("title")==""){
                            $title=$name;
                        }else{
                            $title=$request->post("title");
                        }
                        //asignamos a una variable la ruta del imagen
                        $path="img/".$user->email."/";
                        $random_name=$rand.".".$ext;
                       //almacenamos imagen en servidor
                        
                        move_uploaded_file($_FILES["images"]["tmp_name"][$key],public_path("storage")."/".$path.$random_name);                        
                        
                        list($width,$height)=getimagesize(public_path("storage")."/".$path.$random_name);
                        //para convertir a otro dato (string,integer...) en PHP: settype()
                        //settype($widthInitial,'string');

                        //Creamos el registro en la db
                        
                        $image=Image::create([
                            "title" =>  $title,
                            "detail"=>  $detail,
                            "width" =>  $width,
                            "height"=>  $height,
                            "path"  =>  $path,
                            "random_name"=>$random_name,
                            "ext"   =>  $ext,
                            "size"  => $size,
                            "user_id"=> $user->id
                        ]);                        
                        

*/
                    //anulado, se realiza en el Frontend                        
    /*                      
                            //se calcula ancho y alto de la imagen final
                            $x_ratio = $width/$widthInitial;
                            $y_ratio = $height/$heightInitial;
                    //Con el siguiente fragmento de código, calculamos el ancho y alto que 
                    //tendrá la imagen final. Importante destacar que se guardan las 
                    //proporciones de la imagen original.
                    //Si el ancho y el alto de la imagen no superan los máximos,
                    //ancho final y alto final son los que tiene actualmente  
                            if(($widthInitial<=$width) && ($heightInitial<=$height)){
                                $width_final=$widthInitial;
                                $height_final=$heightInitial;
                            }
                    //si proporción horizontal*alto mayor que el alto máximo,
                    //alto final es alto por la proporción horizontal
                    //es decir, le quitamos al ancho, la misma proporción que le quitamos al alto
                            elseif(($x_ratio*$heightInitial)<$width){
                                $height_final=ceil($x_ratio*$heightInitial);
                                $width_final=$width;
                            }
                    //Igual que la anterior pero a la inversa
                            else{
                                $width_final=ceil($y_ratio*$widthInitial);
                                $height_final=$height;
                            }
                    //Ya tenemos el ancho final y el alto final
    */
                        
 /*   
                        return response()->json(["image" => "correcto"]);    
                        
                    }
                
                }    
                //una sola imagen
                    //$file=$_FILES["images"];
                    //return response()->json(["data" => $file]);
*/
            }else{
                return response()->json(["message" => "No existe directorio de imágenes"]);
            }
            
            
            //$image = Image::create($request->all());
            //return response()->json($image,201);    
        }else{
            return response()->json(["message" => "No se pudo subir la imagen"]);
        }   
        
    }
    public function resizeImage(Request $request){
        try{
            if($request){
                $src=$request->post("src");
                $width=$request->post("width");
                $height=$request->post("height");
                $email=$request->post("email");
                $freeResize=null;
                //realizamos 2 consultas a la db para obtener el registro de la imagen y así poder extraer la extensión y poder crear un nuevo nombre aleatorio a la imagen redimensionada añadiendo la misma extensión, en lugar de extraerla con split o similar.
                //con el user aseguramos de que el usuario se encuentra en la db, aunque se podría realizar tan solo 1 consulta buscando solo por el nombre de la imagen(al ser aleatorio no puede existir ninguno igual)
                $user=User::where("email",$email)->first();
                if($user!=null){
                    $image_db=Image::where("random_name",$src)->where("user_id",$user->id)->first();
                    if($image_db!=null){
                        //la ruta funciona con ./ al comienzo y sin él.
                        //$path="./img/".$email."/";
                        $path="img/".$email."/";
                        $path_file=public_path("storage")."/".$path.$src;
                        $rand=Str::random(15);
                        $new_path_file=public_path("storage")."/".$path.$rand.".".$image_db->ext;
                        if($request->post("freeResize"))
                            $freeResize=true;
                //redimensionar() realiza el proceso de redimensión subiendo
                //la imagen al servidor
                        $resizedImage = $this->methods->redimensionar($path_file,$width,$height,$new_path_file,$freeResize);
                        //en caso de error manejamos la excepción lanzada desde el método redimensionar para devolverla como error
                        if(!file_exists($resizedImage[0])){
                            return response()->json(["error"=>$resizedImage]);
                        }
                        $size=filesize($resizedImage[0]);
                        Image::create([
                                "title"=> "resized_".$image_db->title,
                                "detail"=>Null,
                                "width"=>$resizedImage[1],
                                "height"=>$resizedImage[2],
                                "path"=>$path,
                                "random_name"=>$rand.".".$image_db->ext,
                                "thumb" => NULL,
                                "ext"=>$image_db->ext,
                                "size"=>$size,
                                "space_color"=>$image_db->space_color,
                                "user_id"=>$user->id
                            ]);
                        return response()->json(["message" => $resizedImage[0]]);     
                        //return $resizedImage;
                        //return response()->json(["error"=>$resizedImage]);    
                    }else{
                        return response()->json(["error"=>"No existe esa imagen en la base de datos: ".$src]);
                    }
                    
                }else{
                    return response()->json(["error"=>"No existe ese usuario en la base de datos"]);
                }       
                
                
                
            }
        }catch(Exception $t){
            return response()->json(["error"=>"Error de redimensión en resizeImage: ".$t->getMessage()]);
            
        }
    }
    
    public function show(Image $image)
    {
        //
    }
    
    public function edit(Image $image)
    {
        //
    }
    
    public function update(Request $request, Image $image)
    {
        
    }
    
    public function destroy($id,Request $request)
    {
        //falta comprobar si la imagen pertenece al mismo user_id
        $page=null;
        if($request->post()){            
            $page=$request->post("dato");
        }
        if($id!=null){            
            $image=Image::where("id",$id)->first();

            //La devolución de laravel si la imagen existe en la db es 1,
            //si no existe devuelve 0. comprobamos si existe $image o es distinto a 0 
            //para eliminar del servidor
            
            
            if($image){
                $image->delete();
                //para unlink es necesario permisos 777 a los directorios
               //unlink(public_path()."/".$image->path.$image->random_name);
                //Manejando con Storage se puede proteger contra escritura mediante 
                //la propiedad del archivo que se genera: www-data, de esa forma
                //permite asignar permisos más restrictivos
                //Storage::delete("/public/".$image->path.$image->random_name);
                Storage::disk("public")->delete($image->path.$image->random_name);
                //$image=Storage::disk("local");
            }
            return response()->json(["message" => "La imagen se ha eliminado correctamente","page"=>$page]);   
            //return response()->json(["message" => $image]);   
            
        }
        
    }

    public function getImage($image){
        //falta comprobar usuario y comprobar la imagen de otra forma, y comprobar en el servidor, por ejemplo con file_exists o similar
        if($image){
            $ima;            
            $ima_mainimage=Image::where("random_name",$image)->first();
            $ima_thumb=Image::where("thumb",$image)->first();
            if($ima_mainimage )
                $ima=$ima_mainimage;
            if($ima_thumb)
                $ima=$ima_thumb;
            
    //es necesario añadir public o public_path a la ruta si se solicita desde apache,
    //con live server de Laravel no da ningún error
            //return response()->file('public/'.$ima["path"].$ima["random_name"]);
            //return response()->file(public_path("storage").'/'.$ima["path"].$ima["random_name"]);    
            return response()->file(public_path("storage").'/'.$ima["path"].$image);    
            //return response()->json(["data"=>$image]);    
        }
    }

    public function cropImage(Request $request){
        try{
            //este método a diferencia del proyecto original es 
            //necesario redimensionar la imagen primero y luego recortar, ya 
            //que las imágenes se guardan sin redimensionar.
            if($request->bearerToken() && $request->post("data")){
                $api_token = $request->bearerToken();
                $data = json_decode($request->post("data"));
                $email=$data->email;
                //En esta realizamos doble comprobación para obtener el usuario: comprobamos el api_token y tb el email
                $user=User::where("email",$email)->where("api_token",$api_token)->first();
                $original_image=Image::where("random_name",$data->src)->where("user_id",$user->id)->first();
                //obtenemos ancho, alto y el tipo para el método crearImagen()
                $path_image=public_path("storage")."/".$original_image->path.$original_image->random_name;
                list($width,$height,$image_type)=getimagesize($path_image);
                //obtenemos el ancho y el alto asignado para el panel de recorte
                $resize_width=$data->resizeWidth;
                $resize_height = $data->resizeHeight;
                $rand=Str::random(40);
                //redimensionamos imagen original
                $resized_image=$this->methods->redimensionar($path_image,$resize_width,$resize_height,public_path("storage")."/".$original_image->path.$rand.".".$original_image->ext);

                //recortamos imagen
                $canvas = imagecreatetruecolor($data->width,$data->height);
                //con imagecolorallocate asignamos un color a una imagen
                $negro=imagecolorallocatealpha($canvas,0,0,0,127);
                //con imagecolortransparent la hacemos transparente
                imagecolortransparent($canvas,$negro);
                //establece el modo de mezcla y deshabilita la mezcla alfa //imagealphablending es necesario para imagesavealpha
                imagealphablending($canvas, false);
                //configura el indicador para guardar la información del canal alfa para imágenes png
                imagesavealpha($canvas, true);
                $image = $this->methods->create_image($resized_image[0],$image_type);
                //imagecopy a diferencia de imagecopyresampled recorta pero no redimensiona

                imagecopy($canvas,$image,0,0,$data->x,$data->y,$data->width,$data->height);
                $this->methods->export_image($canvas,$resized_image[0],$image_type);
                Image::create([
                    "title"=>"crop_".$original_image->title,
                    "detail"=>NULL,
                    "width" =>$data->width,
                    "height" =>$data->height,
                    "path" => $original_image->path,
                    "random_name"=>$rand.".".$original_image->ext,
                    "thumb" => NULL,
                    "ext" => $original_image->ext,
                    "size" =>$original_image->size,
                    "space_color"=>$original_image->space_color,
                    "user_id" => $user->id
                ]);
                return response()->json(["message" => "La imagen se ha recortado y ha sido almacenada en el álbum"]);
            }else{
                return response()->json(["error" => "Faltan datos en el envío"]);
            }
        }catch(Exception $e){
            return response()->json(["error"=>"Error al procesar el recorte de la imagen en cropImage: ".$e->getMessage()]);
        }
    }

    public function download(Request $request){
                
        if($request->get("image") && $request->get("name") && $request->get("id")){
            $id=$request->get("id");
            //$id=2;
            $image=$request->get("image");
            $name=$request->get("name");
            $path=$request->get("path");
            $test_image=Image::where("random_name",$image)->where("user_id",$id)->first();
            if(!$test_image){                
                return response()->json(["message"=>"El usuario no tiene acceso a esta imagen"]);
            }
            $headers=[
                "Content-Type"=>'image/jpeg',
                "Content-Disposition"=>" attachment; filename=$image"

            ];
            return response()->download(public_path("storage")."/".$path.$image,$name,$headers);
            
        }else{
            return response()->json(["message"=>"Faltan datos en la solicitud"]);
        }
    }

    public function setFilter(Request $request){
        //recomendable añadir try-catch
        if($request->post("name") && $request->post("email")){
            $name=$request->post("name");
            $email=$request->post("email");
            $filter=$request->post("filter");
            //obtenemos el user
            $user=User::where("email",$email)->first();
            //obtenemos la imagen comprobando si coincide el user y el nombre de la imagen
            $test=Image::where("user_id",$user->id)->where("random_name",$name)->first();
            if(!$test)
                return response()->json(["message"=>"No existe esa imagen o no pertenece a ese usuario"]);
            //obtenemos datos
            $path_image=public_path("storage")."/".$test->path.$test->random_name;

            list($width,$height,$image_type)=getimagesize($path_image);
            $im=$this->methods->create_image($path_image,$image_type);
            //están configurados para evitar cambios bruscos
            if($filter)
            {
                if($filter=="grayscale")
                {
                    if($im && imagefilter($im,IMG_FILTER_GRAYSCALE)){}
                }
                else if($filter=="sepia")
                {
                    if($im && imagefilter($im,IMG_FILTER_GRAYSCALE))
                    {
                        imagefilter($im,IMG_FILTER_COLORIZE,30,25,0);
                        imagefilter($im,IMG_FILTER_CONTRAST,-15);
                    }
                }
                else if($filter=="brightness")
                {
                    imagefilter($im,IMG_FILTER_BRIGHTNESS,50);
                    imagefilter($im,IMG_FILTER_CONTRAST,-25);
                }
                else
                {
                    imagefilter($im,IMG_FILTER_CONTRAST,-30);
                }
            }
            $rand=Str::random(40);
            $path_new_image=public_path("storage")."/".$test->path.$rand.".".$test->ext;
            //$path_image=$test->path.$rand.'.'.$test->ext;
            $this->methods->export_image($im,$path_new_image,$image_type);
            $size=filesize($path_image);
            
            //insertar en la db
            $image=Image::create([
                "title"=>"filtered_".$test->title,
                "detail"=>NULL,
                "width"=>$width,
                "height"=>$height,
                "path"=>$test->path,
                "random_name"=>$rand.'.'.$test->ext,
                "thumb" => NULL,
                "ext"=>$test->ext,
                "size"=>$size,
                "space_color"=>$test->space_color,
                "user_id"=>$test->user_id
            ]);
            
            //en lugar de utilizar el helper CustomHelper se asignan los bytes en 
            //crudo y después en JavaScript se muetra en modo legible para humanos 
            //igual que el el método store, después con el método prettyBytes de 
            //JavaScript devuelve un resultado idéntico que en Debian.
            //$dato=CustomHelper::bytesHuman($size);
            //$dato=CustomHelper::formatBytes($size);

            return response()->json(["image"=>$image]);
        }
    }
    
    public function setPolygon(Request $request){
        //recomendable añadir try-catch
        if($request->post("name") && $request->post("email")){
            $name=$request->post("name");
            $email=$request->post("email");
            $polygon=$request->post("polygon");
            //obtenemos el user
            $user=User::where("email",$email)->first();
            //obtenemos la imagen comprobando si coincide el user y el nombre de la imagen
            $test=Image::where("user_id",$user->id)->where("random_name",$name)->first();
            if(!$test)
                return response()->json(["message"=>"No existe esa imagen o no pertenece a ese usuario"]);

            $path_image=public_path("storage")."/".$test->path.$test->random_name;
            //obtenemos datos de imagen  con el objeto Image que trae $test
            list($width,$height,$image_type)=getimagesize($path_image);
            //Creamos recurso de imagen
            $im=$this->methods->create_image($path_image,$image_type);
            //pathinfo para obtener datos de imagen aunque se pueden obtener de la
            //db ya que la estructura de la tabla images incorpora todos estos datos.

                            //pathinfo proporciona información de una imagen:
                            //dirname,basename,extension,filename
            $info = pathinfo($path_image);

            $widthlongerside=false;
            if($width>$height){
                $widthlongerside=true;
            }
            //asignamos identificador de imagen de una nueva imagen con fondo negro
            $img = imagecreatetruecolor($width,$height);
            //copiamos la imagen en el recurso de imagen $im
            imagecopy($img,$im,0,0,0,0,$width,$height);
            //establecemos el modo de mezcla alfa de la imagen a false
            //(que es necesario para imagesavealpha)
            //almacenamos el canal alfa
            imagealphablending($img, false);
            imagesavealpha($img, true);
            //rellenamos el recurso con un fondo haciendo uso de 
            //imagecolorallocatealpha para poder mantener
            //el canal alpha
            $background = imagecolorallocatealpha($img, 255, 255, 255, 127);
            imagefill($img,0,0,$background);

            //pasamos fórmula para obtener los puntos de un polígono regular 
            //que podemos variar en función de los lados que nosostro indiquemos
            $points=array();
            $sides=$polygon;
            $radius;
            $space;
            if(!$widthlongerside){
                $radius=$width/2;
                $space=($height-$width)/2;
            }else{
                $radius=$height/2;
                $space=($width-$height)/2;
            }

            if($sides!=0){
                //14 representa la estrella de 7 puntas (14lados)
                if($sides==14){
                    $cont=0;
                    //con spikness modificamos el paso (por defecto estaba en 0.5)
                    $spikness=0.38;
                    for($i=0;$i<=360;$i += 360/($sides)){
                        $cont++;
                        if($cont % 2 == 0){
                            $points[]=$width/2+($radius * $spikness) * cos(deg2rad($i));
                            $points[]=$height/2+($radius * $spikness) * sin(deg2rad($i));
                        }else{
                            $points[]=$width/2+$radius * cos(deg2rad($i));
                            $points[]=$height/2+$radius * sin(deg2rad($i));
                        }
                    }
                }else{
                    for($i=0;$i<=360;$i += 360/$sides){
                        $points[] = $width/2+$radius * cos(deg2rad($i));
                        $points[] = $height/2 + $radius * sin(deg2rad($i));
                    }
                }
            }
            
            //creamos un segundo recurso de imagen donde asignaremos el polígono con sus puntos de coordenadas.
            $img2=imagecreatetruecolor($width,$height);
            //establecemos el modo de mezcla alfa de la imagen a false            
            //(que es necesario para imagesavealpha)
            //almacenamos el canal alfa           
            imagealphablending($img2, false);
            imagesavealpha($img2,true);
            //rellenamos el recurso
            imagefill($img2,90,0,$background);
            //imagefill($img2,0,0,$background);
            //asignamos fondo transparente
            $transparent=imagecolortransparent($img2,imagecolorallocate($img2,255,1,254));
            if($sides==0){
                imagefilledellipse($img2, $width/2, $height/2, $height, $height, $transparent);
            }else{
                //copiamos parte de la imagen del recurso $img2 al recurso $img
                imagefilledpolygon($img2, $points, $sides, $transparent);
            }
            
            //rotación del recurso $img2
            $img2=imagerotate($img2,90,0);

            //copiamos parte de la imagen del recurso $img2 al recurso $img
            if(!$widthlongerside){
                imagecopy($img,$img2,0,$space,$space,0,$width,$height);
                $img3=imagecreatetruecolor($width,$width);
            }else{
                imagecopy($img,$img2,$space,0,0,$space,$width,$height);
                $img3=imagecreatetruecolor($height,$height);
            }
            imagealphablending($img3, false);
            imagesavealpha($img3, true);
            imagefill($img3,0,0,$background);

            //copiamos parte de la imagen del recurso $img al recurso $img3
            if(!$widthlongerside)
                imagecopyresampled($img3,$img,0,0,0,$space,$width,$height,$width,$height);
            else
                imagecopyresampled ($img3, $img, 0, 0, $space, 0, $width, $height, $width, $height);

            $rand=Str::random(40);
            $path_newimage=public_path("storage")."/".$test->path.$rand.'.png';

            imagepng($img3,$path_newimage);
            list($new_width,$new_height)=getimagesize($path_newimage);
            $size=filesize($path_newimage);

            $image=Image::create([
                "title"=>"shape_".$test->title,
                "detail"=>NULL,
                "width"=>$new_width,
                "height"=>$new_height,
                "path"=>$test->path,
                "random_name"=>$rand.'.png',
                "thumb" => NULL,
                "ext"=>"PNG",
                "size"=>$size,
                "space_color"=>$test->space_color,
                "user_id"=>$test->user_id
            ]);
            /*
            imagedestroy($img);
            imagedestroy($img2);
            imagedestroy($img3);
            */

            return response()->json(["image"=>$image]);
        }

    }

    public function setEffect(Request $request){

        if($request->post("name")){

            $name=$request->post("name");
            $email=$request->post("email");
            $effect=$request->post("effect");
            //obtenemos el user
            $user=User::where("email",$email)->first();
            //obtenemos la imagen comprobando si coincide el user y el nombre de la imagen
            $test=Image::where("user_id",$user->id)->where("random_name",$name)->first();
            if(!$test)
                return response()->json(["message"=>"No existe esa imagen o no pertenece a ese usuario"]);
            $path_image=public_path("storage").'/'.$test->path.$test->random_name;
            list($width,$height,$image_type)=getimagesize($path_image);
            $rand=Str::random(40);
            //modificamos extensión para el efecto esquinas
            $ext=$test->ext;
            if($effect=="esquinas"){
                $ext="png";
            }
            $path_newimage=public_path("storage").'/'.$test->path.$rand.".".$ext;
            //obtener versión Imagick instalada
            //$version_imagick=Imagick::getVersion

            if($im=new Imagick($path_image)){
           
                //mejor llamar con $this por si se traslada la función.
                //self::setEffectToImage($im,$width,$height,$path_newimage,$effect);

                $this->setEffectToImage($path_image,$width,$height,$path_newimage,$effect);
            }
            
            $size=filesize($path_newimage);

            list($newwidth,$newheight,$newimage_type)=getimagesize($path_newimage);

            $image=Image::create([
                "title"=>"effect_".$test->title,
                "detail"=>NULL,
                "width"=>$newwidth,
                "height"=>$newheight,
                "path"=>$test->path,
                "random_name"=>$rand.".".$ext,
                "thumb" => NULL,
                "ext"=>$ext,
                "size"=>$size,
                "space_color"=>$test->space_color,
                "user_id"=>$test->user_id
            ]);
            return response()->json(["image"=>$size]);    
        }
        //if($im=new Imagick())
        
    }
    
    public function setCompression(Request $request){
        if($request->post("image") && $request->post("email") && $request->post("range")){
            $image=$request->post("image");
            $email=$request->post("email");
            $range=$request->post("range");
            if($range<1 || $range>100)
                return response()->json(["message"=>"El rango no es válido"]);
            //obtenemos el user
            $user=User::where("email",$email)->first();
            //obtenemos la imagen comprobando si coincide el user y el nombre de la imagen
            $test=Image::where("user_id",$user->id)->where("random_name",$image)->first();
            if(!$test)
                return response()->json(["message"=>"No existe esa imagen o no pertenece a ese usuario"]);
            
            $path_image=public_path("storage")."/".$test->path.$image;
            $rand=Str::random(40);
            
            
            if($test->ext=="jpg" || strtolower($test->ext)=="jpg" || $test->ext=="jpeg" ||strtolower($test->ext)=="jpeg" ){

                $ext="jpg";
                $path_newimage=public_path("storage").'/'.$test->path.$rand.".".$ext;
                $im=new Imagick($path_image);
                $im->setCompression(Imagick::COMPRESSION_JPEG);
                $im->setImageCompressionQuality($range);
                $im->writeImage($path_newimage);
                

            }else if($test->ext=="png" || strtolower($test->ext)=="png"){
                $values=[16,32,64,128,255];
                $color=$values[$range];
                $ext="png";
                $path_newimage=public_path("storage").'/'.$test->path.$rand.".".$ext;
                //exec("convert ".$im." -colors 16 ".$new_path);
                //el strip deja fuera ciertos metadatos como fecha y hora de la imagen,
                //modelo de cámara y lente,nombre del programa que creó la imagen,etc...
                exec("convert ".$path_image." -colors ".$color." ".$path_newimage);
                
            }
            
            $size=filesize($path_newimage);
            list($width,$height,$image_type)=getimagesize($path_newimage);    
            $image=Image::create([
                "title"=>"effect_".$test->title,
                "detail"=>NULL,
                "width"=>$width,
                "height"=>$height,
                "path"=>$test->path,
                "random_name"=>$rand.".".$ext,
                "thumb" => NULL,
                "ext"=>$ext,
                "size"=>$size,
                "space_color"=>$test->space_color,
                "user_id"=>$test->user_id
            ]);
            
            return response()->json(["image"=>$image]);

        }
        //si es jpeg un método 
        //test jpg
        //tipos de compresiones
            $im->setCompression(Imagick::COMPRESSION_JPEG);
                    //$im->setCompression(Imagick::COMPRESSION_ZIP);
                    //$im->setCompression(Imagick::COMPRESSION_UNDEFINED);

                //compresiones muy poco efectivas para png               
                    //opción para versiones anteriores
                            //$im->setCompressionQuality(25);

        //opción de compression con setImageCompressionQuality
                   $im->setImageCompressionQuality(quality);

                    //opción de compressión con setOption rango (0-9)
                            //$im->setOption("png:compression-level",1);

                    //otra fórmula más larga para compression
                            //$imagick=new Imagick();
                            //$imagick->setCompression(Imagick::COMPRESSION_JPEG);
                            //$imagick->setCompressionQuality(25);
                            //$imagick->newPseudoImage(
                                //$im->getImageWidth(),
                                //$im->getImageHeight(),
                                //"canvas:white"
                            //);
                            //$imagick->compositeImage(
                                //$im,Imagick::COMPOSITE_ATOP,
                                //0,
                                //0
                            //);                        
                            //$imagick->setFormat("jpg");

            
        //si es png otro método

               //la única efectiva de compresion (png8), exceptuando el método compressImage(), creado más abajo. 
                            //$im->writeImage('png8:'.$new_path);
            //(compresión) efectivo comando exec para comprimir imagen png
                //exec("convert ".$im." -colors 16 ".$new_path);
    }
    

    public function setEffectToImage($im,$w,$h,$new_path,$type){


        //compositeImage: dificil (mezcla entre imagen1, imagen2, imagen_opacidad)
        //getPixelIterator
        //getPixelRegionIterator
        //rotateImage
        //separateImageChannel
        //setCompressionQuality (1-100) (2 opciones )
                
        //setImageClipMask: recorte sin efecto y el resto de imagen con un efecto
        //shearImage:inclinación de X y de Y y color de fondo, al probar mostrar
            //vista previa con transform:skew de CSS no es equivalente y transform con canvas dificil de redimensionar, además el 
            //shearImage genera error en las pruebas con número 180 y otros...
        //textureImage:repetición de imágenes
        //transformImageColorSpace: Espacio de color(RGB,CMYK,CMY,SRGB...), canales(1,2,3,black,alfa)
        //separateImageChannel
        switch($type){
            case "polaroid":
            /*
                $im->polaroidImage(new ImagickDraw(),15);
                //en scaleImage el tercer parámetro (booleano) si es false 
                //permite //mantener la misma medida, si es true toma como 
                //referencia el ancho o alto más pequeño, pero queda mejor
                $im->scaleImage($w,$h,true);
                $im->writeImage($new_path);
            */
                //$im->setImageOrientation(Imagick::ORIENTATION_LEFTTOP);
                //$imagick=Imagick::getCharacter();
                //SILUETA edge 
            /* 
                $kernel= ImagickKernel::fromBuiltIn(Imagick::KERNEL_OCTAGON,"3");
                $im->morphology(Imagick::MORPHOLOGY_EDGE,1, $kernel);
            */
                //SILUETA smooth
            /*
                $kernel= ImagickKernel::fromBuiltIn(Imagick::KERNEL_OCTAGON,"3");
                $im->morphology(Imagick::MORPHOLOGY_SMOOTH,1, $kernel);
            */
                //SILUETA edge-in borde interior
            /* 
                $kernel= ImagickKernel::fromBuiltIn(Imagick::KERNEL_OCTAGON,"3");
                $im->morphology(Imagick::MORPHOLOGY_EDGE_IN,1, $kernel);
            */
                //ROTATE
            /*
                $originalWidth = $im->getImageWidth();
                $originalHeight = $im->getImageHeight();
                //la rotación a 90 invierte las dimensiones, el ancho pasa a tener la dimensión del alto y viceversa.
                $im->rotateImage("rgb(0,0,0)",90);
     
                //si se añade el siguiente recorte se mantiene la dimensión más 
                //corta independientemente si es el ancho o el alto, el problema 
                //es que la imagen queda recortada, pero se sabe la dimensión 
                //que va a tener, por ello puede ser muy útil en algunos casos.
                //opción anulada, más interesante sin el recorte para rotación a 
                //90 o 270 ya que la rotación es exacta en dimensiones y no se recorta
                //    $im->setImagePage(
                //      $im->getimageWidth(),
                //        $im->getimageHeight(),
                //        0,
                //        0
                //    );
             
                //    $im->cropImage(
                //        $originalWidth,
                //        $originalHeight,
                //        ($im->getimageWidth() - $originalWidth) / 2,
                //        ($im->getimageHeight() - $originalHeight) / 2
                //    );
                
            */
                

            //Transformar a RGB o CMYK
    //$im->transformImageColorSpace(Imagick::COLORSPACE_RGB);//return 13
    //$im->transformImageColorSpace(Imagick::COLORSPACE_CMYK);//return 12
            //Separar canales de imagen CHANNEL_RED,CHANNEL_GREEN,CHANNEL_BLUE,
                //CHANNEL_ALPHA,CHANNEL_CYAN,CHANNEL_MAGENTA,CHANNEL_YELLOW,CHANNEL_BLACK, CHANNEL_ALL,..., más en página oficial de PHP de constantes:
                //https://www.php.net/manual/es/imagick.constants.php
                //$im->separateImageChannel(Imagick::CHANNEL_YELLOW);
            //compression
            //exec("convert ".$im." -colors 256 ".$new_path);
            //comprobación de colores
                $data=exec("convert ".$im." -colors 256 ".$new_path);
                //$im->separateimagechannel(Imagick::CHANNEL_BLUE);
    //$size=$im->getImageColorspace();
                //$im->writeImage($new_path);
                //return response()->json(["data"=>$size]);
                
                break;
            case "vertical":
                $im->flipImage();                       
                $im->writeImage($new_path);
                break;
            case "horizontal":
                $im->flopImage();                       
                $im->writeImage($new_path);
                break;
            case "vignette":
                //con fondo negro
                //$im->setImageBackgroundColor("black");
                $im->vignetteImage(30,20,10,20);                
                $im->writeImage($new_path);
                break;
            case "remolino":
                $im->swirlImage(90);
                $im->writeImage($new_path);
                break;
            case "oleo":
                $im->oilPaintImage(2);
                $im->writeImage($new_path);
                break;
                
            case "esquinas":
                
                //establecemos el formato a png aunque no es necesario
                $im->setImageFormat("png");

                //$im->roundCorners(50,50);
                //método alternativo a roundCorners() que es deprecated
                
                //activamos canal alpha
                $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                //creamos la máscara con el método roundCorners alternativo
                //parámetros: ancho,alto y radius
                $mask=$this->roundCorners($w,$h,100);
                //aplicamos máscara a la imagen
                $im->compositeImage($mask,Imagick::COMPOSITE_DSTIN,0,0);
                //subimos al servidor
                $im->writeImage($new_path);
                break;
                
            case "onda":
                $im->waveImage(4,20);
                $im->writeImage($new_path);
                break;
            case "rotacionI":
                $im->transposeImage();
                $im->writeImage($new_path);                      
                break;
            case "rotacionD":
                $im->transverseImage();
                $im->writeImage($new_path);
                break;
            case "tridimensional":
                $im->embossImage(3,1);
                $im->writeImage($new_path);
                break;
            case "desenfoqueG":
                $im->gaussianBlurImage(5,3);
                $im->writeImage($new_path);
                break;
            case "blur":
                $im->rotationalBlurImage(4);
                $im->writeImage($new_path);
                break;
            case "default":

                break;
        }
    }


    public function roundCorners($w,$h,$cornerRadius){
        $mask= new Imagick();
        $mask->newImage($w,$h,new ImagickPixel("transparent"),"png");
        //crear rectangulo redondeado
        $shape=new ImagickDraw();
        $shape->setFillColor(new ImagickPixel("black"));
        $shape->roundRectangle(0,0,$w,$h,$cornerRadius,$cornerRadius);
        //dibujar rectángulo
        $mask->drawImage($shape);
        return $mask;
        
    }
    //para la compresión hay que hacer un range:
    //con jpg el setImageCompressionQuality y para png el exec(convert...)
    public function compressImage($image,$new_image){
        exec("convert ".$image." -colors 64 ".$new_image);
    }
    
    //rpuebas    
    /*
    public function setPolygon(Request $request){
        if($request->post("name") && $request->post("email")){
            $name=$request->post("name");
            $email=$request->post("email");
            $polygon=$request->post("polygon");
            //obtenemos el user
            $user=User::where("email",$email)->first();
            //obtenemos la imagen comprobando si coincide el user y el nombre de la imagen
            $test=Image::where("user_id",$user->id)->where("random_name",$name)->first();
            if(!$test)
                return response()->json(["message"=>"No existe esa imagen o no pertenece a ese usuario"]);
            //obtenemos datos de imagen  con el objeto Image que trae $test
            list($width,$height,$image_type)=getimagesize($test->path.$test->random_name);
            //Creamos recurso de imagen
            $im=$this->methods->create_image($test->path.$test->random_name,$image_type);
            //pathinfo para obtener datos de imagen aunque se pueden obtener de la
            //db ya que la estructura de la tabla images incorpora todos estos datos.

                            //pathinfo proporciona información de una imagen:
                            //dirname,basename,extension,filename
            $info = pathinfo($test->path.$test->random_name);

            $widthlongerside=false;
            if($width>$height){
                $widthlongerside=true;
            }
            //creamos identificador de imagen de una nueva imagen con fondo negro
            $img = imagecreatetruecolor($width,$height);            
            //copiamos la imagen en el nuevo identificado de imagen $img            
            imagecopy($img,$im,0,0,0,0,$width,$height);
            //establecemos el modo de mezcla alfa de la imagen a false
            //(que es necesario para imagesavealpha)
            //almacenamos el canal alfa
            imagealphablending($img, false);
            imagesavealpha($img, true);
            //rellenamos el recurso con un fondo haciendo uso de 
            //imagecolorallocatealpha para poder mantener
            //el canal alpha
            $background = imagecolorallocatealpha($img, 255, 255, 255, 127);
            imagefill($img,0,0,$background);

            //pasamos fórmula para obtener los puntos de un polígono regular 
            //que podemos variar en función de los lados que nosotros indiquemos
            $points=array();
            $sides=$polygon;
            $radius;
            $space;
            if(!$widthlongerside){
                $radius=$width/2;
                $space=($height-$width)/2;

            }else{
                $radius=$height/2;
                $space=($width-$height)/2;
            }
            $space=($height-$width)/2;
            $space=($width-$height)/2;

            if($sides>1 && $sides<9){

                
                
                for($i=0;$i<=360;$i+=360/$sides){
                    $points[]=$width/2+$radius*cos(deg2rad($i));
                    $points[]=$height/2+$radius*sin(deg2rad($i));
                }
                

            }
            //creamos un segundo recurso de imagen donde asignaremos el polígono con sus puntos de coordenadas.
            $img2=imagecreatetruecolor($width,$height);

            //establecemos el modo de mezcla alfa de la imagen a false            
            //(que es necesario para imagesavealpha)
            //almacenamos el canal alfa           
            imagealphablending($img2, false);
            imagesavealpha($img2,true);
            //rellenamos el recurso
            //imagefill($img2,90,0,$background);
            //imagefill($img2,0,0,$background);
            //asignamos fondo transparente
            $transparent=imagecolortransparent($img2,imagecolorallocate($img2,255,1,254));
            //$img2=imagerotate($img2,90,0);
            //copiamos parte de la imagen del recurso $img2 al recurso $img
            imagefilledpolygon($img2,$points,$sides,$transparent);

            //rotación del recurso $img2
            $img2=imagerotate($img2,90,0);
            

            //copiamos parte de la imagen del recurso $img2 al recurso $img
            if(!$widthlongerside){
                imagecopy($img,$img2,0,$space,$space,0,$width,$height);
                $img3=imagecreatetruecolor($width,$width);
            }else{
                //la imagen de prueba
                imagecopy($img,$img2,$space,0,0,$space,$width,$height);                
                $img3=imagecreatetruecolor($height,$height);
            } 
            imagealphablending($img3, false);
            imagesavealpha($img3, true);
            imagefill($img3,0,0,$background);

            //copiamos parte de la imagen del recurso $img al recurso $img3
            if(!$widthlongerside)
                imagecopyresampled($img3,$img,0,0,0,$space,$width,$height,$width,$height);
            else
                imagecopyresampled ($img3, $img, 0, 0, $space, 0, $width, $height, $width, $height);
            

            $rand=Str::random(15);
            $path_image=$test->path.$rand.'.png';
            imagepng($img3,$path_image);



            return response()->json(["data"=>$points]);
        }

    }
    */
    
    /*
    constantes asignadas en un array de strings
    $values=["UNDEFINED","RGB","GRAY","TRANSPARENT","OHTA","LAB","XYZ","YCBCR","YCC","YIQ","YPBPR","YUV","CMYK","SRGB","HSB","HSL","HWB","REC601LUMA","REC601YCBCR","REC709LUMA","REC709YCBCR","LOG","CMY","LUV","HCL","LCH","LMS","LCHAB","LCHUV","SCRGB","HSI","HSV","HCLP","YDBDR",];
    */

    /*  CONSTANTES COLORSPACE EN PHP */

    /* BEGIN - ALL AVAILABLE COLORSPACE CONSTANTS
      Imagick::COLORSPACE_UNDEFINED; //0
      Imagick::COLORSPACE_RGB; //1
      Imagick::COLORSPACE_GRAY; //2
      Imagick::COLORSPACE_TRANSPARENT; //3
      Imagick::COLORSPACE_OHTA; //4
      Imagick::COLORSPACE_LAB; //5
      Imagick::COLORSPACE_XYZ; //6
      Imagick::COLORSPACE_YCBCR; //7
      Imagick::COLORSPACE_YCC; //8
      Imagick::COLORSPACE_YIQ; //9
      Imagick::COLORSPACE_YPBPR; //10
      Imagick::COLORSPACE_YUV; //11
      Imagick::COLORSPACE_CMYK; //12
      Imagick::COLORSPACE_SRGB; //13
      Imagick::COLORSPACE_HSB; //14
      Imagick::COLORSPACE_HSL; //15
      Imagick::COLORSPACE_HWB; //16
      Imagick::COLORSPACE_REC601LUMA; //17
      Imagick::COLORSPACE_REC601YCBCR; //18
      Imagick::COLORSPACE_REC709LUMA; //19
      Imagick::COLORSPACE_REC709YCBCR; //20
      Imagick::COLORSPACE_LOG; //21
      Imagick::COLORSPACE_CMY; //22
      Imagick::COLORSPACE_LUV; //23
      Imagick::COLORSPACE_HCL; //24
      Imagick::COLORSPACE_LCH; //25
      Imagick::COLORSPACE_LMS; //26
      Imagick::COLORSPACE_LCHAB; //27
      Imagick::COLORSPACE_LCHUV; //28
      Imagick::COLORSPACE_SCRGB; //29
      Imagick::COLORSPACE_HSI; //30
      Imagick::COLORSPACE_HSV; //31
      Imagick::COLORSPACE_HCLP; //32
      Imagick::COLORSPACE_YDBDR; //33
      END - ALL AVAILABLE COLORSPACE CONSTANTS */
    
}
