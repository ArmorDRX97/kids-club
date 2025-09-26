<?php
namespace App\Http\Controllers;


use App\Models\User;
use App\Services\ShiftManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q',''));
        $users = User::when($q, function($qq) use ($q){
            $qq->where('name','like',"%$q%")
                ->orWhere('email','like',"%$q%")
                ->orWhere('phone','like',"%$q%");
        })->orderBy('name')->paginate(20)->withQueryString();
        return view('users.index', compact('users','q'));
    }


    public function create(){ return view('users.create'); }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:150','unique:users,email'],
            'phone' => ['nullable','string','max:40'],
            'password' => ['required','string','min:8','confirmed'],
            'role' => ['required', Rule::in([User::ROLE_RECEPTIONIST])],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => bcrypt($data['password']),
        ]);
        $user->syncRoles([$data['role']]);
        if ($user->hasRole(User::ROLE_RECEPTIONIST)) {
            $this->shiftManager->getSetting($user);
        }
        return redirect()->route('users.index')->with('success','Пользователь создан');
    }


    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }


    public function update(Request $request, User $user)
    {
        $isAdminTarget = $user->hasRole(User::ROLE_ADMIN);

        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:150', Rule::unique('users','email')->ignore($user->id)],
            'phone' => ['nullable','string','max:40'],
            'password' => ['nullable','string','min:8','confirmed'],
            'role' => $isAdminTarget
                ? ['nullable']
                : ['required', Rule::in([User::ROLE_RECEPTIONIST])],
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if(!empty($data['password'])) $user->password = bcrypt($data['password']);
        $user->save();
        if ($isAdminTarget) {
            $user->syncRoles([User::ROLE_ADMIN]);
        } else {
            $user->syncRoles([$data['role']]);
            if ($user->hasRole(User::ROLE_RECEPTIONIST)) {
                $this->shiftManager->getSetting($user);
            }
        }
        return redirect()->route('users.index')->with('success','Изменения сохранены');
    }


    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) return back()->with('error','Нельзя удалить самого себя');
        $user->delete();
        return back()->with('success','Удалено');
    }
}
