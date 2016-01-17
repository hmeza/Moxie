describe('JavaScript addition operator', function () {
    it('adds two numbers together', function () {
        expect(1 + 2).toEqual(3);
    });
});

describe("Confirm deletion", function() {
  var windowObj = {location: href: ''}};

  beforeEach(mock.module(function($provide) {
     $provide.value('$window', windowObj);
  }));
  beforeEach(module('secure'));

  it("User confirms redirect", function() {
    spyOn(window, 'confirm').andReturn(true);
    expect(windowObj.location.href).toEqual('/secure/regulations/1/attachment');
  });

  it("User cancels", function() {
    spyOn(window, 'confirm').andReturn(true);
    expect(windowObj.location.href).toEqual('');
  });
});
