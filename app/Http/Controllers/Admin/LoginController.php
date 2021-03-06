<?php
namespace App\Http\Controllers\Admin;

use App\Http\Requests\LoginPost;
use App\Models\Admin;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    use ThrottlesLogins;

    public $username='username';

    public $maxAttempts=10;

    /*protected function guard(){
        return Auth::guard('admin');
    }*/

    /**
     * 登录
     * @author totti_zgl
     * @date 2018/3/28 16:48
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if($this->hasTooManyLoginAttempts($request)){
                $request->session()->flash('login','登录次数过多,账号已被锁定！');

                return response()->json(['code' => 2, 'info' => '登录次数过多,账号已被锁定！']);
            }

            $data = $request->all();
            $validator = Validator::make($data, Admin::$rules, Admin::$messages, Admin::$attributeNames);
            if ($validator->passes()) {
                $check_captcha = $this->checkCaptcha($data['verify']);
                if (empty($check_captcha)) {
                    return response()->json(['code' => 2, 'info' => '验证码错误！']);
                }

                $where['username'] = $data['username'];
                $where['password'] = md5($data['password']);
                $info = Admin::where($where)->first();
                if (empty($info)) {
                    $this->incrementLoginAttempts($request);

                    return response()->json(['code' => 2, 'info' => '用户名或密码错误！']);
                }
                if ($info['status'] != 1) {
                    $this->incrementLoginAttempts($request);

                    return response()->json(['code' => 2, 'info' => '用户被停权！']);
                }

                $request->session()->put('aid', $info['id']);

                //存入redis
                //Cache::store('redis')->put('aid',$info['id'],600);

                $info->lasttime = time();
                $info->ip = $request->getClientIp();
                $info->save();
                return response()->json(['code' => 1, 'info' => '登录成功！']);
            } else {
                $error = $validator->errors()->messages();

                //获取error中第一个
                //$msg=current($error);

                if (isset($error['username'])) {
                    return response(['code' => 2, 'info' => $error['username'][0]]);
                } elseif (isset($error['password'])) {
                    return response(['code' => 2, 'info' => $error['password'][0]]);
                } else {
                    return response(['code' => 2, 'info' => $error['verify'][0]]);
                }
            }

            /*$code=Auth::guard('admin')->attempt(['username'=>$data['username']]);
            if(empty($code)){
                return response()->json(['code'=>2,'info'=>'用户名或密码错误！']);
            }else{
                return response()->json(['code'=>1,'info'=>'登录成功！']);
            }*/

        } else {
            return view('admin.login.index');
        }
    }

    /**
     * 登录请求验证器
     * @param LoginPost $request
     * @return \Illuminate\Http\JsonResponse
     * @author totti_zgl
     * @date 2018/5/21 16:54
     */
    public function store(LoginPost $request)
    {
        $data = $request->all();
        $where['username'] = $data['username'];
        $where['password'] = md5($data['password']);
        $info = Admin::where($where)->first();
        if (empty($info)) {
            return response()->json(['code' => 2, 'info' => '用户名或密码错误！'], 200);

        }
        if ($info['status'] != 1) {
            return response()->json(['code' => 2, 'info' => '用户被停权！']);
        }
        $request->session()->put('aid', $info['id']);
        $info->lasttime = time();
        $info->ip = $request->getClientIp();
        $info->save();
        return response()->json(['code' => 1, 'info' => '登录成功！']);
    }

    /**
     * 验证码
     * @return $this
     * @author totti_zgl
     * @date 2018/3/28 16:49
     */
    public function captcha()
    {
        $captcha = new CaptchaBuilder();
        $captcha->build(148, 51);
        $verify = $captcha->getPhrase();
        Session::put('verify', $verify);
        ob_clean();
        return response($captcha->output())->header('Content-type', 'image/jpeg');
    }

    /**
     * 检验验证码
     * @param $data
     * @return bool
     * @author totti_zgl
     * @date 2018/3/28 16:49
     */
    private function checkCaptcha($data)
    {
        $captcha = Session::get('verify');
        if ($captcha == $data) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 退出
     * @author totti_zgl
     * @date 2018/3/28 16:49
     */
    public function logout(Request $request)
    {
        $request->session()->forget('aid');
        return redirect('admin/login');
    }

    /*
     * 登录次数限制
     */
    protected function throttleKey(Request $request)
    {
        return Str::lower('admin_'.$request->input($this->username())).'|'.$request->ip();
    }

    protected function username()
    {
        return property_exists($this,'username') ? $this->username :  'email';
    }

    /*public function index(Request $request){
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
                $info->lasttime=time();
                $info->ip=$request->getClientIp();
                $info->save();
                return response(['code'=>1,'info'=>'登录成功！']);
            }else{
                return response(['code'=>2,'info'=>$validator->messages()]);
            }
        }else {
            return view('admin.login.index');
        }
    }*/
}