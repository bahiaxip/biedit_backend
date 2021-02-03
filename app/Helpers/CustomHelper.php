<?php
	
namespace App\Helpers;

class CustomHelper{


	//estos métodos ninguno equivale a la lectura realizada con JavaScript que 
	//curiosamente coincide con la lectura que realiza con mi Debian al comprobar la 
	//medida de los archivos
	
	/*public static function bytesHuman($bytes){
		$units=["B","KiB","MiB","GiB","TiB","PiB"];

		for($i=0;$bytes>1024;$i++){
			$bytes /=1024;
		}

		//ceil($bytes) o round ($bytes,2)
		return ceil($bytes);
		//return round($bytes,2);
	}
	*/
	/*
	public static function formatBytes($bytes, $precision = 2) { 
    	$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    	$bytes = max($bytes, 0); 
    	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    	$pow = min($pow, count($units) - 1); 

	    // Uncomment one of the following alternatives
	    $bytes /= pow(1024, $pow);

	    //esta opción calcula mal el TB
	    // $bytes /= (1 << (10 * $pow)); 

    	return round($bytes, $precision) . ' ' . $units[$pow]; 
    	//return "hola";
	}
	*/
	/* opción más rápida que la anterior (por comprobar)*/
	/*
	public static function formatBytes($bytes, $precision=2){
    	$unit_list = array
	    (
	        'B',
	        'KB',
	        'MB',
	        'GB',
	        'TB',
	    );

	    $bytes = max($bytes, 0);
	    $index = floor(log($bytes, 2) / 10);
	    $index = min($index, count($unit_list) - 1);
	    $bytes /= pow(1024, $index);

    	return round($bytes, $precision) . ' ' . $unit_list[$index];
	}
	*/
	/*opción más ŕápida (por comprobar)*/
	public static function formatBytes($bytes, $precision=2){
	    $unit_list = array
	    (
	        'B',
	        'KB',
	        'MB',
	        'GB',
	        'TB',
	    );

	    $index_max = count($unit_list) - 1;
	    $bytes = max($bytes, 0);

	    for ($index = 0; $bytes >= 1024 && $index < $index_max; $index++)
	    {
	        $bytes /= 1024;
	    }

    	return round($bytes, $precision) . ' ' . $unit_list[$index];
	}
}


?>