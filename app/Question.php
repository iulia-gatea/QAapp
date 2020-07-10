<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
	protected $fillable = ['title', 'description'];

    public function answers()
    {
    	return $this->hasMany('App\Answer');
    }
}
