var deleteMessage = "test confirm redirect";
var deleteUrl = 'test/';

describe("Confirm deletion", function() {
	var testRedirector = moxieRedirector;
	beforeEach(function() {
		spyOn(moxieRedirector, 'redirect').and.returnValue(true);
	});

	it("User confirms redirect", function() {
		spyOn(window, 'confirm').and.returnValue(true);
		confirmDelete(1);
		expect(testRedirector.redirect).toHaveBeenCalledWith('test/1');
	});

	it("User cancels", function() {
		spyOn(window, 'confirm').and.returnValue(false);
		confirmDelete(1);
		expect(testRedirector.redirect).not.toHaveBeenCalled();
	});
});
