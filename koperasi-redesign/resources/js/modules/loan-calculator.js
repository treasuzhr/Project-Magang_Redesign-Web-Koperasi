export const PREVIEW_MONTHLY_RATE = .01;

export function calculateLoanPreview(principal, months) {
    if (!Number.isSafeInteger(principal) || principal <= 0 || !Number.isSafeInteger(months) || months <= 0) {
        throw new RangeError('Principal and months must be positive integers.');
    }
    const monthlyPrincipal = Math.round(principal / months);
    const monthlyInterest = Math.round(principal * PREVIEW_MONTHLY_RATE);
    return { monthlyPrincipal, monthlyInterest, monthlyPayment: monthlyPrincipal + monthlyInterest };
}
