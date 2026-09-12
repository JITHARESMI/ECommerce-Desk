<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
Artisan::command('admin:create {email}',function(){
 $email=$this->argument('email');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('Enter a valid email.');return 1; }
 if(User::where('email',$email)->exists()) { $this->error('That administrator already exists.');return 1; }
 $name=$this->ask('Administrator name');
 $password=$this->secret('Password (at least 12 characters)');
 if(!$name||strlen($password??'')<12) { $this->error('Name and a password of at least 12 characters are required.');return 1; }
 User::create(['name'=>$name,'email'=>$email,'password'=>Hash::make($password)]);
 $this->info('Administrator created.');
});

