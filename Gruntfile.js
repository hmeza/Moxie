'use strict';

module.exports = function(grunt) {

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),
    jasmine : {
      all: {
      // Your project's source files
      src : ['js/jquery.js', 'js/common.js', 'js/Finances/*.js'],
      options: {
        // Jasmine spec files
        specs : 'test/js/spec/*Spec.js'
        // Spec helper files
       // helpers : 'test/js/spec/helpers/*.js'
      }
    }
    }
  });

  // Register tasks.
  grunt.loadNpmTasks('grunt-contrib-jasmine');

  // Default task.
  grunt.registerTask('default', 'jasmine');
};

//var mock = require('jasmine-mocks').mock;
