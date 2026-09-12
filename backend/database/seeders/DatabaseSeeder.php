<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder {
 public function run():void {
  // Sample catalogue only; never create or reset an administrator password.
  DB::transaction(function(){
   foreach(['Desk essentials','Audio','Accessories'] as $name) DB::table('categories')->insertOrIgnore(['name'=>$name,'created_at'=>now(),'updated_at'=>now()]);
   $category=DB::table('categories')->pluck('id','name');
   foreach([
    ['Arc desk lamp','ARC-001','Desk essentials',6900,18,'Adjustable arm. USB-C power. Three brightness settings. Matte black finish.'],
    ['Studio headphones','STU-002','Audio',12900,4,'Over-ear fit. Wired 3.5 mm connection. Detachable cable. Foldable design.'],
    ['Everyday tote','TOT-003','Accessories',2900,32,'Cotton canvas. Internal pocket. Natural colour. 38 x 42 cm.'],
    ['Grid notebook','GRD-004','Desk essentials',1800,7,'A5 size. 160 grid pages. Hardcover. Elastic closure.']
   ] as [$name,$sku,$cat,$price,$stock,$facts]){
    if(!DB::table('products')->where('sku',$sku)->exists()){
     $id=DB::table('products')->insertGetId(['name'=>$name,'sku'=>$sku,'category_id'=>$category[$cat],'facts'=>$facts,'description'=>$facts,'price_cents'=>$price,'stock'=>$stock,'low_stock_threshold'=>5,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
     DB::table('inventory_movements')->insert(['product_id'=>$id,'delta'=>$stock,'stock_after'=>$stock,'reason'=>'Sample opening stock','created_at'=>now()]);
    }
   }
   foreach([['Alex Morgan','alex@example.com'],['Jamie Taylor','jamie@example.com']] as [$name,$email]) DB::table('customers')->insertOrIgnore(['name'=>$name,'email'=>$email,'created_at'=>now(),'updated_at'=>now()]);
  });
 }
}

