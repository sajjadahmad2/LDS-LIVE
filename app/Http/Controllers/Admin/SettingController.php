<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
class SettingController extends Controller
{
    public function index()
    {
    $settings = Setting::pluck('value','key')->toArray();
    return view('admin.settings.index',compact('settings'));
    }

    public function save(Request $request)
    {
        foreach($request->setting ?? [] as $key=>$value){
            save_settings($key,$value);
        }
        return response()->json(['success' => true, 'message' => 'Data saved successfully']);
    }

    public function saveLogo(Request $request)
    {

    $user = auth()->user();
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);
    if ($request->hasFile('image')) {
        $settings = Setting::where('key', 'logo')->first();
        if ($settings && $settings->value) {
            $oldImagePath = asset('assets/uploads/company_logos/' . $settings->value);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        $imagePath = $request->file('image')->store('company_logos', 'public');
        if ($settings) {
            $settings->update([
                'value' => $imagePath,
                'user_id' => $user->id
            ]);
        } else {
            Setting::create([
                'key' => 'logo',
                'value' => $imagePath,
                'user_id' => $user->id
            ]);
        }
        return back()->with('success', 'Logo uploaded successfully.');
    }
    return back()->with('error', 'No image uploaded.');
}

}
