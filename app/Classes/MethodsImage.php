<?php
namespace App\Classes;
use Exception;
class MethodsImage{
	
	function create_image($path,$type){
		switch($type){
            case 1:
                $res =imagecreatefromgif($path);
                break;
            case 2:
                $res= imagecreatefromjpeg($path);
                break;
            case 3:
                $res = imagecreatefrompng($path);
                break;
            default:
                return "";
                break;
        }
        return $res;
	}

    function export_image($res,$path,$type){
        switch($type){
            case 1:
                $im =imagegif($res,$path);
                break;
            case 2:
                $im= imagejpeg($res,$path);
                break;
            case 3:
                $im = imagepng($res,$path);
                break;        
        }
    }

    public function testFormatImg($im)
    {
        switch (exif_imagetype($im))
        {
            case IMAGETYPE_JPEG:
                return "jpeg";            
                break;
            case IMAGETYPE_PNG:
                return "png";            
                break;
            case IMAGETYPE_GIF:
                return "gif";
                break;
        }
    }

    public function redimensionar($rutaImagen,$nuevoAncho,$nuevoAlto,$rutaNueva,$freeResize=null)
    {
        try{


            $rutaNueva2=array(2);
            $rutaNueva2[0]=$rutaNueva;

            //Ruta de la imagen original    
            $image=$rutaImagen;
            
            ////Creamos una variable imagen a partir de la imagen original
            //esta versión de comprobación es viejo hay que cambiarlo por el de crearImagen y exportarImagen

            switch($this->testFormatImg($image))
            {
                case "jpeg":  
                    $imagen= imagecreatefromjpeg($image);
                    break;
                case "png":
                    $imagen= imagecreatefrompng($image);
                    break;
                case "gif":
                    $imagen= imagecreatefromgif($image);
                    break;
            }
            //$img_original=imagecreatefromjpeg($rutaImagenOriginal);

            //Se define el máximo ancho y alto que tendrá la imagen final
            
            $max_ancho=$nuevoAncho;
            $max_alto=$nuevoAlto;

            //Ancho y alto de la imagen original
            list($ancho,$alto)=getimagesize($image);

            //Se calcula ancho y alto de la imagen final
            $x_ratio=$max_ancho/$ancho;
            $y_ratio=$max_alto/$alto;

        //Nota: Con el siguiente fragmento de código, calculamos el ancho y alto que 
        //tendrá la imagen final. Importante destacar que se guardan las proporciones 
        //de la imagen original.
            //si se ha activado la redimensión libre asignamos el ancho y alto tal como vienen
            if($freeResize==true){
                $ancho_final=$nuevoAncho;
                $alto_final=$nuevoAlto;
            }
            //Si el ancho y el alto de la imagen no superan los máximos,
            //ancho final y alto final son los que tiene actualmente
            elseif(($ancho<=$max_ancho) && ($alto<=$max_alto))
            {
                $ancho_final=$ancho;
                $alto_final=$alto;
            }
            //si proporción horizontal*alto mayor que el alto máximo,
            //alto final es alto por la proporción horizontal
            //es decir, le quitamos al ancho, la misma proporción que le quitamos al alto

            elseif(($x_ratio*$alto) < $max_alto)
            {
                $alto_final=ceil($x_ratio * $alto);
                $ancho_final=$max_ancho;
            }

            //Igual que antes pero a la inversa

            else
            {
                $ancho_final=ceil($y_ratio*$ancho);
                $alto_final=$max_alto;//
            }

            //Ya tenemos el tamaño final de la imagen, ahora tenemos que redimensionar 
            //la imagen proporcionalmente

            //Creamos una imagen en blanco de tamaño $ancho_final * $alto_final
            $rutaNueva2[1]=intval($ancho_final);
            $rutaNueva2[2]=intval($alto_final);
            $tmp=imagecreatetruecolor($ancho_final,$alto_final);

            //Copiamos $img_original sobre la imagen que acabamos de crear en blanco ($tmp)
            imagecopyresampled($tmp,$imagen,0,0,0,0,$ancho_final,$alto_final,$ancho,$alto);
            //Se destruye variable $img_original para liberar memoria
            //imagedestroy($img_original);

            //Por último, tenemos que decidir si queremos mostrar la imagen por pantalla o 
            //si queremos guardar la imagen en un directorio.

            //Definimos la calidad de la imagen final

            $calidad=95;
            switch($this->testFormatImg($image))
            {
                case "jpeg":  
                    imagejpeg($tmp,$rutaNueva2[0],$calidad);
                    break;
                case "png":
                    imagepng($tmp,$rutaNueva2[0],8);
                    break;
                case "gif":
                    imagegif($tmp,$rutaNueva2[0]);
                    break;
            }
            //Se crea la imagen final en el directorio indicado


            //Mostrar imagen por pantalla
            //Header("Content-type:image/jpeg");
            //imagejpeg($tmp);
            return $rutaNueva2;
        }//este Exception es el objeto Exception de Laravel importado arriba
        catch(Exception $t){
            return "Error en la redimensión: ".$t->getMessage();

        }
    }
}
?>