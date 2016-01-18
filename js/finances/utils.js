function Finances() {
    this.interest = 0;
}

Finances.prototype.roundToDecimal = function(number, decimals) {
    return Math.round(number * Math.pow(10, decimals)) / Math.pow(10,decimals);
};

Finances.prototype.finalCapital = function(principal, rate, months) {
    this.interest = principal * (rate * 0.01 / 12) * months;
    var total = principal + this.interest;
    return this.roundToDecimal(total, 4);
};

