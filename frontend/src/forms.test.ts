import {describe,it,expect} from 'vitest';
import {cents} from './forms';
describe('currency input',()=>{
 it('keeps decimal money exact',()=>{expect(cents('19.99')).toBe(1999);expect(cents('0.29')).toBe(29);expect(cents('12.5')).toBe(1250);});
 it('rejects fractional cents, negative and malformed prices',()=>{for(const value of ['12.999','-1','1e3','abc',''])expect(()=>cents(value)).toThrow();});
});

