<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/5
 * Time: 17:45
 */
namespace App\Http\Controllers\Admin;


use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller{
    public function index(Request $request){
        if($request->ajax()){
            $data=$request->all();
            $rules=['username'=>'required', 'password'=>'required'];
            $validator=Validator::make($data,$rules);
            if($validator->passes()){
                $where['username']=$data['username'];
                $where['password']=md5($data['password']);
                $info=Admin::where($where)->first();
                if(empty($info)){
                    return response(['code'=>2,'info'=>'用户名或密码错误！']);
                }
                if($info['status'] != 1){
                    return response(['code'=>2,'info'=>'用户被停权！']);
                }
                $request->session()->put('aid',$info['id']);
                $condition['lasttime']=time();
                $condition['ip']=$request->getClientIp();
                $info->save($condition);
                /*$info->lasttime=time();
                $info->ip=$request->getClientIp();
                $info->save();*/
                return response(['code'=>1,'info'=>'登录成功！']);
            }else{
                return response(['code'=>2,'info'=>$validator->messages()]);
            }
        }else {
            return view('login.index');
        }
    }

    public function logout(Request $request){
        $request->session()->forget('aid');
        return redirect('admin/login');
    }
}