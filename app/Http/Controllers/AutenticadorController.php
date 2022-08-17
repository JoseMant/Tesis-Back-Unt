<?php

namespace App\Http\Controllers;
use App\Autenticador;
use Illuminate\Http\Request;

class AutenticadorController extends Controller
{
    public function index(){

		$Authenticator =new Autenticador;
		$secret = $Authenticator->generateRandomSecret();

		session(['auth_secret' => $secret]);
		var_dump($secret);
        
		$QRcode = $Authenticator->getQR('API_TRAMITES', $secret);
        
		return view('autenticador', compact('secret','QRcode'));	
	}

	public function store(){

		$Authenticator = new Autenticador();
		$value = session('auth_secret');
        // exit();
		$checkResult = $Authenticator->verifyCode($value, $_POST['code'], 2);    // 2 = 2*30sec clock tolerance
		
        dd($value, $_POST['code'], $checkResult);
		var_dump($checkResult);
		
	}
}
