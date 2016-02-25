describe("Check roundToDecimal function", function() {
    var finance = new Finance();

    it("Calculates some roundings", function() {
        expect(finance.roundToDecimal(8.3333334,4)).toEqual(8.3333);
        expect(finance.roundToDecimal(71.2424151251,2)).toEqual(71.24);
        expect(finance.roundToDecimal(71.2424151251,0)).toEqual(71);
    });
});

describe("Calculate simple interest", function() {
    var finance = new Finance();

    // example from ING
    it("Calculates interest of 2000€ for 4 years at 5%", function() {
        expect(finance.calculateInterest(2000, 5, 48)).toEqual(400);
    });

    it("Calculates interest of 1000€ for 4 months at 2.5%", function() {
        expect(finance.calculateInterest(1000, 2.5, 4)).toEqual(8.333333333333334);
    });

    it("Calculates interest of 300.50 for 15 months at 1.2%", function() {
        expect(finance.calculateInterest(300.5, 1.2, 15)).toEqual(4.5075);
    });
});

describe("Calculate total with simple interest", function() {
    var finance = new Finance();

    // example from ING
    it("Calculates total of 2000€ for 4 years at 5%", function() {
        expect(finance.finalCapital(2000, 5, 48)).toEqual(2400);
    });

	it("Calculates total of 1000€ for 4 months at 2.5%", function() {
		expect(finance.finalCapital(1000, 2.5, 4)).toEqual(1008.3333);
	});

	it("Calculates total of 300.50 for 15 months at 1.2%", function() {
        expect(finance.finalCapital(300.5, 1.2, 15)).toEqual(305.0075);
	});
});

describe("Calculate compound interest", function() {
    var finance = new CompoundInterest();

    // example from ING
    it("Calculates interest of 2000€ for 4 years at 5%", function() {
        //expect(finance.calculateInterest(2000, 5, 48)).toEqual(441.83);
    });
});
