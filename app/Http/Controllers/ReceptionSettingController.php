<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ShiftManager;
use Illuminate\Http\Request;

class ReceptionSettingController extends Controller
{
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function index()
    {
        $receptionists = User::role(User::ROLE_RECEPTIONIST)
            ->with('receptionSetting')
            ->orderBy('name')
            ->get();

        $receptionists->each(function (User $user) {
            if (!$user->receptionSetting) {
                $user->setRelation('receptionSetting', $this->shiftManager->getSetting($user));
            }
        });

        return view('reception.settings', compact('receptionists'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole(User::ROLE_RECEPTIONIST), 404);

        $data = $request->validate([
            'shift_starts_at' => ['required', 'date_format:H:i'],
            'shift_ends_at' => ['required', 'date_format:H:i'],
            'auto_close_enabled' => ['sometimes', 'boolean'],
        ]);

        $setting = $this->shiftManager->getSetting($user);
        $setting->shift_starts_at = $data['shift_starts_at'];
        $setting->shift_ends_at = $data['shift_ends_at'];
        $setting->auto_close_enabled = $request->boolean('auto_close_enabled');
        $setting->save();

        return redirect()->route('reception.settings')->with('success', 'Настройки для '.$user->name.' обновлены.');
    }
}
