<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AccountController extends Controller
{
    public function index(){ return view('account.index'); }


    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','string','min:8','confirmed'],
        ]);
        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password))
            return back()->with('error','Текущий пароль неверен');
        $user->password = bcrypt($data['password']);
        $user->save();
        return back()->with('success','Пароль обновлён');
    }
}
