<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jorder extends Model{
    /*与模型关联的数据表*/
    protected $table='jorder';

    /*指定是否模型应该被戳记时间*/
    public $timestamps = false;

    public function getGoods(){
        return $this->hasMany('App\Models\Goods','id','gid');
    }

    public function getStatus(){
        return $this->hasOne('App\Models\OrderStatus','id','orderstatus');
    }

    public function getSite(){
        return $this->hasOne('App\Models\OrderSite','id','scid');
    }
}
