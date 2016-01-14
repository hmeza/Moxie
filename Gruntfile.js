module.exports = function(grunt) {
  grunt.initConfig({
    jasmine : {
      // Your project's source files
      src : 'js/**/*.js',
      // Your Jasmine spec files
      specs : 'test/js/specs/**/*spec.js',
      // Your spec helper files
      helpers : 'test/js/specs/helpers/*.js'
    }
  });

  // Register tasks.
  grunt.loadNpmTasks('grunt-jasmine-runner');

  // Default task.
  grunt.registerTask('default', 'jasmine');
};
