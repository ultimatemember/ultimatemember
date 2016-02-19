/*
  Ultimate Member dependencies
*/
  var gulp = require('gulp');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');

gulp.task('scripts', function() {
	
    // Concat and Minify
    gulp.src([
        'assets/js/um-select.js',
        'assets/js/um-modal.js',
        'assets/js/um-jquery-form.js',
        'assets/js/um-fileupload.js',
        'assets/js/pickadate/picker.js',
        'assets/js/pickadate/picker.date.js',
        'assets/js/pickadate/picker.time.js',
        'assets/js/pickadate/legacy.js',
        'assets/js/um-raty.js',
        'assets/js/um-scrollto.js',
        'assets/js/um-scrollbar.js',
        'assets/js/um-crop.js',
        'assets/js/um-tipsy.js',
        'assets/js/um-functions.js',
        'assets/js/um-responsive.js',
        'assets/js/um-conditional.js',
        'assets/js/um-scripts.js',
        'assets/js/um-members.js',
        'assets/js/um-profile.js',
        'assets/js/um-account.js'
        ])
        .pipe(concat("um.min.js"))
        .pipe(uglify())
        .pipe(gulp.dest("assets/js"));

   
});

// The default task (called when you run `gulp`)
gulp.task('default', function() {
  gulp.start('scripts');
});