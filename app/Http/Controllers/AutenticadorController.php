<?php

namespace App\Http\Controllers;
use App\Autenticador;
use Illuminate\Http\Request;

class AutenticadorController extends Controller
{
    public function AuthQr(){

		$Authenticator =new Autenticador;
		$secret = $Authenticator->generateRandomSecret();
		session(['secret' => $secret]);
        
		$qrCodeUrl = $Authenticator->getQR('capacitacion_dth', $secret);
        // dd($qrCodeUrl);
        // exit();
		return view('autenticador', compact('secret','qrCodeUrl'));	
	}

	public function CheckQr(){

		$Authenticator = new Autenticador();
		$value = session('secret');
        // dd($value);
        // exit();
		$checkResult = $Authenticator->verifyCode($value, $_POST['code'], 2);    // 2 = 2*30sec clock tolerance

		var_dump($checkResult);
		
	}
}
