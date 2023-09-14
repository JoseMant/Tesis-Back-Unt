<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CategoriaSGA;
class CategoriaSGAController extends Controller
{
    public function index(){
        return CategoriaSGA::all();
    }
}
