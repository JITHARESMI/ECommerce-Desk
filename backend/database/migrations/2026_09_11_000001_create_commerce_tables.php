<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->rememberToken();$t->timestamps();});
  Schema::create('categories',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->timestamps();});
  Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('category_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('sku')->unique();$t->text('facts');$t->text('description')->nullable();$t->unsignedInteger('price_cents');$t->unsignedInteger('stock')->default(0);$t->unsignedInteger('low_stock_threshold')->default(5);$t->boolean('active')->default(true);$t->timestamps();});
  Schema::create('customers',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('phone')->nullable();$t->text('address')->nullable();$t->timestamps();});
  Schema::create('orders',function(Blueprint $t){$t->id();$t->uuid('request_key')->unique();$t->string('request_hash',64);$t->foreignId('customer_id')->constrained()->restrictOnDelete();$t->string('customer_name');$t->string('customer_email');$t->string('status')->default('pending');$t->unsignedBigInteger('total_cents');$t->string('currency',3)->default('AUD');$t->timestamps();});
  Schema::create('order_items',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->string('name');$t->string('sku');$t->unsignedInteger('quantity');$t->unsignedInteger('unit_price_cents');});
  Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->restrictOnDelete();$t->uuid('request_key')->unique();$t->string('outcome');$t->unsignedBigInteger('amount_cents');$t->string('reference')->unique();$t->timestamp('created_at');});
  Schema::create('inventory_movements',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();$t->integer('delta');$t->unsignedInteger('stock_after');$t->string('reason');$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->timestamp('created_at');});
  Schema::create('ai_runs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('provider');$t->string('model')->nullable();$t->unsignedInteger('input_tokens')->default(0);$t->unsignedInteger('output_tokens')->default(0);$t->timestamp('created_at');});
 }
 public function down():void {
  foreach(['ai_runs','inventory_movements','payments','order_items','orders','customers','products','categories','users'] as $table) Schema::dropIfExists($table);
 }
};

