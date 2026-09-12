<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController {
 public function login(Request $r){
  $data=$r->validate(['email'=>'required|email','password'=>'required|string']);
  if(!Auth::attempt($data)) throw ValidationException::withMessages(['email'=>'Email or password is incorrect.']);
  $r->session()->regenerate();return $r->user();
 }
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return response()->json(['message'=>'Signed out']);}
}

