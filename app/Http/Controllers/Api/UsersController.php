<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UsersRequest;
use App\Models\Image;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    //
    public function store(UsersRequest $request)
    {
        $verifyData = Cache::get($request->verification_key);
//        dd($verifyData);
        if(!$verifyData){
            return $this->response->error('验证码已失效',422);
        }
        if(!hash_equals($verifyData['code'],$request->verification_code)){
            // 返回401
            return $this->response->errorUnauthorized('验证码错误');
        }

        $user = User::create([
            'name'=>$request->name,
            'phone'=>$verifyData['phone'],
            'password'=>bcrypt($request->password)
        ]);
//        dd($user);
        Cache::forget($request->verification_key);

        return $this->response->item($user, new UserTransformer())
            ->setMeta([
                'access_token' => \Auth::guard('api')->fromUser($user),
                'token_type' => 'Bearer',
                'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
            ])
            ->setStatusCode(201);
    }

    public function me()
    {
        return $this->response->item($this->user(), new UserTransformer());
    }

    public function update(UsersRequest $request)
    {
        $user = $this->user();

        $attributes = $request->only(['name','email','introduction']);
//        dd([$user,$attributes]);
        if($request->avatar_image_id){
            $image = Image::find($request->avatar_image_id);
            $attributes['avatar'] = $image->path;
        }
        $user->update($attributes);

        return $this->response->item($user,new UserTransformer());
    }
}
