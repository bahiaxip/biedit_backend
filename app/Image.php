<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Image extends Model
{
    protected $fillable= [
    	"title","detail","width","height","path","random_name","ext","size","user_id"
    ];

    public function user(){
    	$this->belongsTo(User::class,"id");
    }

}
