function Finance() {
    this.interest = 0;
    this.total = 0;
}

Finance.prototype.roundToDecimal = function(number, decimals) {
    return Math.round(number * Math.pow(10, decimals)) / Math.pow(10,decimals);
};

Finance.prototype.calculateInterest = function(principal, rate, months) {
    this.interest = principal * (rate * 0.01 / 12) * months;
    return this.interest;
};

Finance.prototype.finalCapital = function(principal, rate, months) {
    this.calculateInterest(principal, rate, months);
    this.total = parseFloat(principal) + parseFloat(this.interest);
    return this.roundToDecimal(this.total, 4);
};

Finance.prototype.getTotal = function() {
    return this.roundToDecimal(this.total, 4);
}

function CompoundInterest() {
    Finance.call(this);
}

CompoundInterest.prototype = new Finance();

CompoundInterest.prototype.calculateInterest = function(principal, rate, months) {
    let tempRate = this.roundToDecimal(1 + (rate/12/100), 4);
    let interestRate = this.roundToDecimal(Math.pow(tempRate, months), 4);
    this.interest = principal * interestRate;
    return this.interest;
};

