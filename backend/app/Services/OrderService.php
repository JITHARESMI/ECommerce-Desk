<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class OrderService {
 private function fail(string $message):never { throw ValidationException::withMessages(['order'=>$message]); }
 public function create(array $data,int $userId):object {
  usort($data['items'],fn($a,$b)=>$a['product_id']<=>$b['product_id']);
  $hash=hash('sha256',json_encode([$data['customer_id'],$data['items']]));
  return DB::transaction(function()use($data,$userId,$hash){
   // Serialize requests for this customer; then lock products in a stable order.
   $customer=DB::table('customers')->where('id',$data['customer_id'])->lockForUpdate()->first();
   if(!$customer) $this->fail('Customer not found.');
   if($old=DB::table('orders')->where('request_key',$data['request_key'])->first()){
    if($old->request_hash!==$hash) $this->fail('This request key was already used for a different order.');
    return $old;
   }
   $items=[];$total=0;
   foreach($data['items'] as $line){
    $p=DB::table('products')->where('id',$line['product_id'])->lockForUpdate()->first();
    if(!$p||!$p->active) $this->fail('One of these products is unavailable.');
    if($p->stock<$line['quantity']) $this->fail("Insufficient stock for {$p->name}.");
    $total+=$p->price_cents*$line['quantity'];
    $items[]=[$p,$line['quantity']];
   }
   $id=DB::table('orders')->insertGetId(['request_key'=>$data['request_key'],'request_hash'=>$hash,'customer_id'=>$customer->id,'customer_name'=>$customer->name,'customer_email'=>$customer->email,'total_cents'=>$total,'status'=>'pending','currency'=>'AUD','created_at'=>now(),'updated_at'=>now()]);
   foreach($items as [$p,$qty]){
    DB::table('order_items')->insert(['order_id'=>$id,'product_id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'quantity'=>$qty,'unit_price_cents'=>$p->price_cents]);
    DB::table('products')->where('id',$p->id)->update(['stock'=>$p->stock-$qty,'updated_at'=>now()]);
    $this->movement($p->id,-$qty,$p->stock-$qty,'Order reservation',$userId,$id);
   }
   return DB::table('orders')->find($id);
  },3);
 }
 public function movement(int $product,int $delta,int $after,string $reason,int $user,?int $order=null):void {
  DB::table('inventory_movements')->insert(['product_id'=>$product,'delta'=>$delta,'stock_after'=>$after,'reason'=>$reason,'user_id'=>$user,'order_id'=>$order,'created_at'=>now()]);
 }
 private function restock(int $id,int $user):void {
  foreach(DB::table('order_items')->where('order_id',$id)->orderBy('product_id')->get() as $item){
   $p=DB::table('products')->where('id',$item->product_id)->lockForUpdate()->first();
   DB::table('products')->where('id',$p->id)->update(['stock'=>$p->stock+$item->quantity,'updated_at'=>now()]);
   $this->movement($p->id,$item->quantity,$p->stock+$item->quantity,'Order released',$user,$id);
  }
 }
 public function action(int $id,string $action,int $user,?string $key=null,?string $outcome=null):object {
  return DB::transaction(function()use($id,$action,$user,$key,$outcome){
   $o=DB::table('orders')->where('id',$id)->lockForUpdate()->first();abort_unless($o,404);
   if($action==='payment'){
    if($old=DB::table('payments')->where('request_key',$key)->first()){
     if($old->order_id!==$id||$old->outcome!==$outcome) $this->fail('Payment request key conflict.');
     return $o;
    }
    if($o->status!=='pending') $this->fail('Only pending orders accept simulated payments.');
    DB::table('payments')->insert(['order_id'=>$id,'request_key'=>$key,'outcome'=>$outcome,'amount_cents'=>$o->total_cents,'reference'=>'SIM-'.Str::uuid(),'created_at'=>now()]);
    $status=$outcome==='success'?'paid':'pending';
   } elseif($action==='fulfill'){
    if($o->status!=='paid') $this->fail('Only paid orders can be fulfilled.');
    $status='fulfilled';
   } elseif($action==='cancel'){
    if($o->status!=='pending') $this->fail('Only pending orders can be cancelled.');
    $this->restock($id,$user);$status='cancelled';
   } elseif($action==='refund'){
    if($o->status!=='paid') $this->fail('Only paid, unfulfilled orders can be refunded in this demo.');
    DB::table('payments')->insert(['order_id'=>$id,'request_key'=>(string)Str::uuid(),'outcome'=>'refund','amount_cents'=>$o->total_cents,'reference'=>'SIM-'.Str::uuid(),'created_at'=>now()]);
    $this->restock($id,$user);$status='refunded';
   } else { $this->fail('Unknown action.'); }
   DB::table('orders')->where('id',$id)->update(['status'=>$status,'updated_at'=>now()]);
   return DB::table('orders')->find($id);
  },3);
 }
}

