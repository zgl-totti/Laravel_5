<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model{
    /*与模型关联的数据表*/
    protected $table='site';

    /*指定是否模型应该被戳记时间*/
    public $timestamps = false;

}
