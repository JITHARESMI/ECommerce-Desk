export function cents(value:string):number {
 if(!/^\d+(\.\d{1,2})?$/.test(value))throw new Error('Enter a price with at most two decimal places.');
 const [whole,fraction='']=value.split('.');
 const result=Number(whole)*100+Number(fraction.padEnd(2,'0'));
 if(!Number.isSafeInteger(result)||result>100000000)throw new Error('Price is too large.');
 return result;
}

