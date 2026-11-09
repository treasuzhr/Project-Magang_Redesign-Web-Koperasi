import test from 'node:test';
import assert from 'node:assert/strict';
import { calculateLoanPreview } from '../../resources/js/modules/loan-calculator.js';

test('default example uses flat monthly interest on the initial principal', () => {
    assert.deepEqual(calculateLoanPreview(10000000, 12), {
        monthlyPrincipal: 833333, monthlyInterest: 100000, monthlyPayment: 933333,
    });
});

test('changing tenor changes principal installments, not flat monthly interest', () => {
    const short = calculateLoanPreview(10000000, 6);
    const long = calculateLoanPreview(10000000, 36);
    assert.equal(short.monthlyInterest, long.monthlyInterest);
    assert.equal(short.monthlyPayment, 1766667);
    assert.equal(long.monthlyPayment, 377778);
});

test('preview endpoints calculate and round rupiah correctly', () => {
    assert.equal(calculateLoanPreview(1000000, 6).monthlyPayment, 176667);
    assert.equal(calculateLoanPreview(20000000, 36).monthlyPayment, 755556);
});

test('invalid amounts or tenors cannot produce misleading estimates', () => {
    for (const value of [0, -1, NaN, Infinity, 1.5]) {
        assert.throws(() => calculateLoanPreview(value, 12), RangeError);
        assert.throws(() => calculateLoanPreview(1000000, value), RangeError);
    }
});
