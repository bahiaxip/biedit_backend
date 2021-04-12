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

            //Límites máximos
    //Se podrían asignar los límites desde el Global.js en el FrontEnd
            $max_ancho=1920;
            $max_alto=1280;

            //Ancho y alto de la imagen original
            list($ancho,$alto)=getimagesize($image);

    //Establecer límites máximos

            $ancho_final=$nuevoAncho;
            $alto_final=$nuevoAlto;    
            
            if($nuevoAncho > $max_ancho){
                //establecemos el ancho máximo y obtenemos proporcion
                $ancho_final=$max_ancho;
                $alto_final=round(($max_ancho*$alto)/$ancho);

            }
            else if($nuevoAlto > $max_alto){
                $alto_final=$max_alto;
                $ancho_final=round(($max_alto*$ancho)/$alto);
                //establecemos el alto máximo y obtenemos proporción
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