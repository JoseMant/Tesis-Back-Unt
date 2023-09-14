<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DedicacionDocente;
class DedicacionDocenteController extends Controller
{
    public function index(){
        return DedicacionDocente::all();
    }
}
