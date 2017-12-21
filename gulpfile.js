/*
  Ultimate Member dependencies
*/

var gulp = require('gulp')
, uglify = require('gulp-uglify'),
  rename = require("gulp-rename");

// task
gulp.task( 'default', function () {
    gulp.src(['assets/js/*.js', '!assets/js/*.min.js', ]) // path to your files
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/' ) );
});

/*
       var gulp = require('gulp');
    var uglify = require('gulp-uglify');
    var concat = require('gulp-concat');
 var minifyCSS = require('gulp-minify-css');

gulp.task('scripts', function() {

    // Concat and Minify
    gulp.src([
        'assets/js/select2/select2.full.min.js',
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

gulp.task('css', function() {

    gulp.src([
        'assets/css/um-fonticons-ii.css',
        'assets/css/um-fonticons-fa.css',
        'assets/css/select2/select2.min.css',
        'assets/css/um-modal.css',
        'assets/css/um-styles.css',
        'assets/css/um-members.css',
        'assets/css/um-profile.css',
        'assets/css/um-account.css',
        'assets/css/um-misc.css',
        'assets/css/um-fileupload.css',
        'assets/css/pickadate/default.css',
        'assets/css/pickadate/default.date.css',
        'assets/css/pickadate/default.time.css',
        'assets/css/um-raty.css',
        'assets/css/um-scrollbar.css',
        'assets/css/um-crop.css',
        'assets/css/um-tipsy.css',
        'assets/css/um-responsive.css',
        ])
        .pipe(minifyCSS())
        .pipe(concat("um.min.css"))
        .pipe(gulp.dest("assets/css"));
});

// The default task (called when you run `gulp`)
gulp.task('default', function() {
  gulp.start('scripts');
  gulp.start('css');
});*/
