<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Image extends Model
{
    protected $fillable= [
    	"title","detail","width","height","path","random_name","thumb","ext","size","space_color","user_id"
    ];

    public function user(){
    	$this->belongsTo(User::class,"id");
    }

}
