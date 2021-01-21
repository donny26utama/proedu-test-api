<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use Illuminate\Http\Request;

class SeminarController extends Controller
{
    public function index()
    {
        return response()->json(['seminar' => Seminar::all()]);
    }

    public function show($id)
    {
        return response()->json(['seminar' => Seminar::where(['uuid' => $id])->first()]);
    }
}
