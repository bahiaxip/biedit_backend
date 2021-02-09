<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Image;
use Faker\Generator as Faker;

$factory->define(Image::class, function (Faker $faker) {
	$extensions=array("jpg","png","gif");
    $space_color=array("rgb","cmyk","srgb");
    return [
        "title" => $faker->name,
        "detail" => $faker->sentence(5,true),
        "width" => $faker->numberBetween(0,1920),
        "height" => $faker->numberBetween(0,1920),
        "path" => $faker->url,
        "random_name" => $faker->name,
        "thumb" =>$faker->name,
	//opción con shuffle
        "ext" => $extensions[shuffle($extensions)],
        "size" => $faker->numberBetween(0,1920),
        "space_color"=>$space_color[shuffle($space_color[$space_color])],
    //opción con array_rand
        //"ext" => $extensions[array_rand($extensions)],
        "user_id"=>rand(1,30)
    ];
});
