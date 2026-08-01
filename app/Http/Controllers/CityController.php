<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::all();
        return view('cities.index', compact('cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100|unique:cities,name',
            'region' => 'nullable|max:100',
        ]);
        
        City::create([
            'name' => $request->name,
            'region' => $request->region,
            'is_active' => true,
        ]);
        
        return redirect()->route('cities.index')->with('success', 'تم إضافة المدينة بنجاح');
    }

    public function update(Request $request, City $city)
    {
        $request->validate([
            'name' => 'required|max:100|unique:cities,name,' . $city->id,
            'region' => 'nullable|max:100',
            'is_active' => 'boolean',
        ]);
        
        $city->update([
            'name' => $request->name,
            'region' => $request->region,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('cities.index')->with('success', 'تم تحديث بيانات المدينة');
    }

    public function destroy(City $city)
    {
        $city->delete();
        return redirect()->route('cities.index')->with('success', 'تم الحذف بنجاح');
    }
    
    public function destroyAll()
    {
        City::truncate();
        return redirect()->route('cities.index')->with('success', 'تم حذف جميع المدن والمناطق بنجاح');
    }
}
