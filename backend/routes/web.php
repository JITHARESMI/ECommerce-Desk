<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\AiController;
Route::prefix('api/v1')->group(function(){
 Route::get('health',fn()=>['status'=>'ok','service'=>'Commerce Desk']);
 Route::get('csrf',fn()=>['token'=>csrf_token()]);
 Route::post('login',[AuthController::class,'login'])->middleware('throttle:login');
 Route::middleware(['auth','throttle:admin'])->group(function(){
  Route::get('me',fn()=>request()->user());
  Route::post('logout',[AuthController::class,'logout']);
  Route::get('data',[CommerceController::class,'data']);
  Route::get('products',fn()=>Illuminate\Support\Facades\DB::table('products')->orderByDesc('id')->paginate(25));
  Route::get('categories',fn()=>Illuminate\Support\Facades\DB::table('categories')->orderBy('name')->paginate(25));
  Route::get('customers',fn()=>Illuminate\Support\Facades\DB::table('customers')->orderBy('name')->paginate(25));
  Route::get('orders',fn()=>Illuminate\Support\Facades\DB::table('orders')->orderByDesc('id')->paginate(25));
  Route::get('payments',fn()=>Illuminate\Support\Facades\DB::table('payments')->orderByDesc('id')->paginate(25));
  Route::post('categories',[CommerceController::class,'category']);
  Route::put('categories/{id}',[CommerceController::class,'category'])->whereNumber('id');
  Route::delete('categories/{id}',[CommerceController::class,'removeCategory'])->whereNumber('id');
  Route::post('customers',[CommerceController::class,'customer']);
  Route::put('customers/{id}',[CommerceController::class,'customer'])->whereNumber('id');
  Route::post('products',[CommerceController::class,'product']);
  Route::put('products/{id}',[CommerceController::class,'product'])->whereNumber('id');
  Route::post('products/{id}/stock',[CommerceController::class,'stock'])->whereNumber('id');
  Route::post('orders',[CommerceController::class,'order']);
  Route::get('orders/{id}',[CommerceController::class,'detail'])->whereNumber('id');
  Route::post('orders/{id}/{action}',[CommerceController::class,'action'])->whereNumber('id')->whereIn('action',['payment','cancel','fulfill','refund']);
  Route::post('ai/description',AiController::class)->middleware('throttle:ai');
 });
});
// API is session-authenticated and CSRF protected. Serve the built SPA from the same origin.
Route::get('/{path?}',function(){
 if(file_exists(public_path('index.html'))) return response()->file(public_path('index.html'));
 return response('Commerce Desk API is running. Start the frontend on http://127.0.0.1:5173 or build it into public/.',200);
})->where('path','^(?!api(?:/|$)).*');
