export type Product={id:number;name:string;sku:string;category_id:number;category_name:string;facts:string;description:string;price_cents:number;stock:number;low_stock_threshold:number;active:boolean|number};
export type Category={id:number;name:string};
export type Customer={id:number;name:string;email:string;phone:string|null;address:string|null};
export type Order={id:number;customer_name:string;customer_email:string;customer_id:number;total_cents:number;status:string;created_at:string};
export type Payment={id:number;order_id:number;outcome:string;amount_cents:number;reference:string;created_at:string};
export type Movement={id:number;product_name:string;delta:number;stock_after:number;reason:string;created_at:string};
export type Data={products:Product[];categories:Category[];customers:Customer[];orders:Order[];payments:Payment[];movements:Movement[];ai_provider:string};
export type Detail={order:Order;items:{id:number;name:string;sku:string;quantity:number;unit_price_cents:number}[];payments:Payment[]};
export const money=(c:number)=>new Intl.NumberFormat('en-AU',{style:'currency',currency:'AUD'}).format(c/100);
export const orderNo=(id:number)=>'CD-'+String(id).padStart(5,'0');

