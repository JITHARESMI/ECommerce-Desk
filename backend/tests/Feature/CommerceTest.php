<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;
class CommerceTest extends TestCase {
 use RefreshDatabase;
 private function ready():void {
  $this->seed();
  $this->actingAs(User::create(['name'=>'Test Admin','email'=>'admin@example.com','password'=>'not-a-real-password']));
 }
 private function orderBody(int $quantity=2):array {
  return ['request_key'=>(string)Str::uuid(),'customer_id'=>DB::table('customers')->value('id'),'items'=>[['product_id'=>DB::table('products')->value('id'),'quantity'=>$quantity]]];
 }
 public function test_private_api_requires_sign_in():void {
  $this->getJson('/api/v1/data')->assertUnauthorized();
  $this->postJson('/api/v1/orders',[])->assertUnauthorized();
 }
 public function test_password_login_and_logout():void {
  User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>'test-password-123']);
  $this->postJson('/api/v1/login',['email'=>'admin@example.com','password'=>'wrong'])->assertUnprocessable();
  $this->postJson('/api/v1/login',['email'=>'admin@example.com','password'=>'test-password-123'])->assertOk()->assertJsonMissingPath('password');
  $this->getJson('/api/v1/me')->assertOk();
  $this->postJson('/api/v1/logout')->assertOk();
  $this->getJson('/api/v1/me')->assertUnauthorized();
 }
 public function test_order_total_uses_server_prices_and_retries_do_not_reserve_twice():void {
  $this->ready();$body=$this->orderBody();$p=DB::table('products')->find($body['items'][0]['product_id']);
  $body['total_cents']=1;$body['items'][0]['unit_price_cents']=1;
  $a=$this->postJson('/api/v1/orders',$body)->assertCreated()->assertJsonPath('total_cents',$p->price_cents*2);
  $this->postJson('/api/v1/orders',$body)->assertCreated()->assertJsonPath('id',$a->json('id'));
  $this->assertDatabaseCount('orders',1);$this->assertDatabaseHas('products',['id'=>$p->id,'stock'=>$p->stock-2]);
 }
 public function test_insufficient_stock_rolls_back_every_line():void {
  $this->ready();$products=DB::table('products')->orderBy('id')->get();
  $body=$this->orderBody(1);$body['items'][]=['product_id'=>$products[1]->id,'quantity'=>999];
  $this->postJson('/api/v1/orders',$body)->assertUnprocessable();
  $this->assertDatabaseCount('orders',0);
  $this->assertDatabaseHas('products',['id'=>$products[0]->id,'stock'=>$products[0]->stock]);
 }
 public function test_payment_retry_and_refund_release_stock_once():void {
  $this->ready();$body=$this->orderBody();$p=DB::table('products')->find($body['items'][0]['product_id']);
  $id=$this->postJson('/api/v1/orders',$body)->json('id');
  $pay=['request_key'=>(string)Str::uuid(),'outcome'=>'success'];
  $this->postJson("/api/v1/orders/$id/payment",$pay)->assertOk()->assertJsonPath('status','paid');
  $this->postJson("/api/v1/orders/$id/payment",$pay)->assertOk();
  $this->assertDatabaseCount('payments',1);
  $this->postJson("/api/v1/orders/$id/refund")->assertOk();
  $this->postJson("/api/v1/orders/$id/refund")->assertUnprocessable();
  $this->assertDatabaseHas('products',['id'=>$p->id,'stock'=>$p->stock]);
 }
 public function test_failed_payment_does_not_mark_order_paid():void {
  $this->ready();$id=$this->postJson('/api/v1/orders',$this->orderBody())->json('id');
  $this->postJson("/api/v1/orders/$id/payment",['request_key'=>(string)Str::uuid(),'outcome'=>'failed'])->assertOk()->assertJsonPath('status','pending');
  $this->postJson("/api/v1/orders/$id/fulfill")->assertUnprocessable();
  $this->postJson("/api/v1/orders/$id/cancel")->assertOk();
  $this->postJson("/api/v1/orders/$id/cancel")->assertUnprocessable();
 }
 public function test_stock_cannot_become_negative():void {
  $this->ready();$id=DB::table('products')->value('id');
  $this->postJson("/api/v1/products/$id/stock",['delta'=>-999,'reason'=>'Count correction'])->assertUnprocessable();
 }
 public function test_ai_requires_consent_returns_draft_and_records_tokens():void {
  $this->ready();config(['ai.provider'=>'openai','ai.key'=>'fake-test-key','ai.model'=>'gpt-4o-mini']);
  Http::preventStrayRequests();
  Http::fake(['api.openai.com/*'=>Http::response(['choices'=>[['finish_reason'=>'stop','message'=>['content'=>json_encode(['description'=>'A cotton tote with an internal pocket.'])]]],'usage'=>['prompt_tokens'=>80,'completion_tokens'=>12]])]);
  $body=['name'=>'Tote','facts'=>'Cotton canvas with an internal pocket.'];
  $this->postJson('/api/v1/ai/description',$body)->assertUnprocessable();
  Http::assertNothingSent();
  $this->postJson('/api/v1/ai/description',$body+['confirm_external'=>true])->assertOk()->assertJsonPath('provider','openai');
  $this->assertDatabaseHas('ai_runs',['input_tokens'=>80,'output_tokens'=>12]);
  $this->assertDatabaseCount('products',4);
 }
 public function test_template_is_labelled_and_does_not_call_provider():void {
  $this->ready();Http::preventStrayRequests();
  $this->postJson('/api/v1/ai/description',['name'=>'Lamp','facts'=>'USB-C power and adjustable brightness.'])->assertOk()->assertJsonPath('provider','template');
  Http::assertNothingSent();
 }
}

