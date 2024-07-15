/**
 * Grunt configuration for the Compass course format.
 * This may require to install grunt-cli on your local machine
 * npm install -g grunt-cli
 * Use 'grunt sass' to compile the scss files into styles.css
 * Use 'grunt watch' to run the sass task whenever a file change is detected in the scss folder
 *
 * @copyright  2023 KnowledgeOne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function (grunt) {
  grunt.initConfig({
      sass: {
          dist: {
              options: {
                  implementation: require('sass'),
                  style: 'compressed'
              },
              files: [{
                  expand: true,
                  cwd: 'scss/',
                  src: ['styles.scss'],
                  dest: '',
                  ext: '.css'
              }]
          }
      },
      watch: {
        scss: {
          files: ['scss/**/*.scss'],
          tasks: ['sass'],
          options: {
            spawn: false,
          },
        },
      }
  });
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.registerTask('default', ['sass']);
};