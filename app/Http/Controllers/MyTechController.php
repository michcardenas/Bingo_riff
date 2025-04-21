<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller; // Asegurar la importación correcta

class MyTechController extends Controller
{
    public function index()
    {
        return view('mytech');
    }
}

