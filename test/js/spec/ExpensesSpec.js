var deleteMessage = "test confirm redirect";

describe("Confirm deletion", function() {
    var testRedirector;
    beforeEach(function() {
        testRedirector = new moxieRedirector();
        spyOn(moxieRedirector, 'redirect').returnValue(true);
    });

  it("User confirms redirect", function() {
      confirmDelete(1);
      expect(testRedirector.redirect).toHaveBeenCalledWith('1');
  });

  it("User cancels", function() {
      console.log(moxieRedirector);
      var testRedirector = new moxieRedirector();
      spyOn(testRedirector, 'redirect').returnValue(true);
      confirmDelete(1);
  });
});
