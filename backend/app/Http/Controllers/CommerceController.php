<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\OrderService;
class CommerceController {
 public function data(){
  return [
   'products'=>DB::table('products')->join('categories','categories.id','=','products.category_id')->select('products.*','categories.name as category_name')->orderBy('products.id','desc')->get(),
   'categories'=>DB::table('categories')->orderBy('name')->get(),
   'customers'=>DB::table('customers')->orderBy('name')->get(),
   'orders'=>DB::table('orders')->orderByDesc('id')->get(),
   'movements'=>DB::table('inventory_movements')->join('products','products.id','=','inventory_movements.product_id')->select('inventory_movements.*','products.name as product_name')->orderByDesc('inventory_movements.id')->limit(100)->get(),
   'payments'=>DB::table('payments')->orderByDesc('id')->limit(100)->get(),
   'ai_provider'=>config('ai.provider'),
  ];
 }
 public function category(Request $r,?int $id=null){
  if($id) abort_unless(DB::table('categories')->find($id),404);
  $d=$r->validate(['name'=>['required','string','max:100',Rule::unique('categories')->ignore($id)]]);
  if($id) DB::table('categories')->where('id',$id)->update($d+['updated_at'=>now()]);
  else $id=DB::table('categories')->insertGetId($d+['created_at'=>now(),'updated_at'=>now()]);
  return DB::table('categories')->find($id);
 }
 public function removeCategory(int $id){
  abort_unless(DB::table('categories')->find($id),404);
  if(DB::table('products')->where('category_id',$id)->exists()) throw ValidationException::withMessages(['category'=>'Move products to another category before deleting.']);
  DB::table('categories')->where('id',$id)->delete();return response()->noContent();
 }
 public function customer(Request $r,?int $id=null){
  if($id) abort_unless(DB::table('customers')->find($id),404);
  $d=$r->validate(['name'=>'required|string|max:200','email'=>['required','email','max:200',Rule::unique('customers')->ignore($id)],'phone'=>'nullable|string|max:40','address'=>'nullable|string|max:1000']);
  if($id) DB::table('customers')->where('id',$id)->update($d+['updated_at'=>now()]);
  else $id=DB::table('customers')->insertGetId($d+['created_at'=>now(),'updated_at'=>now()]);
  return DB::table('customers')->find($id);
 }
 public function product(Request $r,OrderService $orders,?int $id=null){
  if($id) abort_unless(DB::table('products')->find($id),404);
  $d=$r->validate(['name'=>'required|string|max:200','sku'=>['required','string','max:80',Rule::unique('products')->ignore($id)],'category_id'=>'required|integer|exists:categories,id','facts'=>'required|string|max:5000','description'=>'nullable|string|max:10000','price_cents'=>'required|integer|min:0|max:100000000','low_stock_threshold'=>'required|integer|min:0|max:1000000','active'=>'required|boolean']);
  return DB::transaction(function()use($d,$id){
   if($id) DB::table('products')->where('id',$id)->update($d+['updated_at'=>now()]);
   else $id=DB::table('products')->insertGetId($d+['stock'=>0,'created_at'=>now(),'updated_at'=>now()]);
   return DB::table('products')->find($id);
  });
 }
 public function stock(Request $r,int $id,OrderService $orders){
  $d=$r->validate(['delta'=>'required|integer|not_in:0|min:-1000000|max:1000000','reason'=>'required|string|max:200']);
  return DB::transaction(function()use($d,$id,$r,$orders){
   $p=DB::table('products')->where('id',$id)->lockForUpdate()->first();abort_unless($p,404);
   $after=$p->stock+$d['delta'];
   if($after<0||$after>1000000) throw ValidationException::withMessages(['stock'=>'Stock must be between zero and 1,000,000.']);
   DB::table('products')->where('id',$id)->update(['stock'=>$after,'updated_at'=>now()]);
   $orders->movement($id,$d['delta'],$after,$d['reason'],$r->user()->id);
   return DB::table('products')->find($id);
  },3);
 }
 public function order(Request $r,OrderService $orders){
  $d=$r->validate(['request_key'=>'required|uuid','customer_id'=>'required|integer|exists:customers,id','items'=>'required|array|min:1|max:50','items.*.product_id'=>'required|integer|distinct|exists:products,id','items.*.quantity'=>'required|integer|min:1|max:1000']);
  // Normalize integers before hashing retries; prices are always read from the server.
  $d['customer_id']=(int)$d['customer_id'];
  $d['items']=array_map(fn($i)=>['product_id'=>(int)$i['product_id'],'quantity'=>(int)$i['quantity']],$d['items']);
  return response()->json($orders->create($d,$r->user()->id),201);
 }
 public function detail(int $id){
  $o=DB::table('orders')->find($id);abort_unless($o,404);
  return ['order'=>$o,'items'=>DB::table('order_items')->where('order_id',$id)->get(),'payments'=>DB::table('payments')->where('order_id',$id)->orderBy('id')->get()];
 }
 public function action(Request $r,int $id,string $action,OrderService $orders){
  if($action==='payment') $r->validate(['request_key'=>'required|uuid','outcome'=>'required|in:success,failed']);
  return $orders->action($id,$action,$r->user()->id,$r->input('request_key'),$r->input('outcome'));
 }
}

