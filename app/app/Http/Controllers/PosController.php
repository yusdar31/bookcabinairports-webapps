<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Outlet;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $menus   = Menu::where('is_available', true)->orderBy('name')->get();

        return view('pos.index', compact('outlets', 'menus'));
    }
}
