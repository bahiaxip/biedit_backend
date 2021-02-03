<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {

            $table->increments("id");
            $table->string("title")->nullable();
            $table->string("detail")->nullable();
            $table->string("width");
            $table->string("height");
            $table->string("path");
            $table->string("random_name");
            $table->string("ext");
            $table->string("size");
            $table->bigInteger("user_id")->unsigned();            
            

            //Relación
            $table->foreign("user_id")->references("id")->on("users")
                    ->onDelete("cascade")->onUpdate("cascade");

            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('images');
    }
}
