function Finances() {
    this.interest = 0;
    this.total = 0;
}

Finances.prototype.roundToDecimal = function(number, decimals) {
    return Math.round(number * Math.pow(10, decimals)) / Math.pow(10,decimals);
};

Finances.prototype.calculateInterest = function(principal, rate, months) {
    this.interest = principal * (rate * 0.01 / 12) * months;
    return this.interest;
};

Finances.prototype.finalCapital = function(principal, rate, months) {
    this.calculateInterest(principal, rate, months);
    this.total = parseFloat(principal) + parseFloat(this.interest);
    return this.roundToDecimal(this.total, 4);
};

Finances.prototype.getTotal = function() {
    return this.roundToDecimal(this.total, 4);
}

function CompoundInterest() {
    Finances.call(this);
}

CompoundInterest.prototype = new Finances();

CompoundInterest.prototype.calculateInterest = function(principal, rate, months) {
    var tempRate = this.roundToDecimal(1 + (rate/12/100), 4);
    console.log(tempRate)
    var interestRate = this.roundToDecimal(Math.pow(tempRate, months), 4);
    console.log(interestRate);
    this.interest = principal * interestRate;
    return this.interest;
};

