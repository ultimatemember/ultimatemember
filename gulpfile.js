var gulp        = require('gulp'),
    gutil       = require('gulp-util'),
    sass        = require('gulp-ruby-sass'),
    uglify      = require('gulp-uglify'),
    watch       = require('gulp-watch'),
    concat      = require('gulp-concat'),
    notify      = require('gulp-notify'),
    cssnano     = require('gulp-cssnano'),
    sourcemaps  = require('gulp-sourcemaps');

var watch_changes_in_paths = {
    scripts: ['assets/**/*', '!assets/vendor/**/*'],
};

gulp.task('scripts', function() {

        	  //= Copy library = //
            //Pickadate Javascript 
        	  gulp.src([
              'assets/vendor/pickadate/lib/legacy.js',
              'assets/vendor/pickadate/lib/picker.js',
              'assets/vendor/pickadate/lib/picker.date.js',
              'assets/vendor/pickadate/lib/picker.time.js',
              ])
          	  .pipe(gulp.dest('assets/js/pickadate/'));

            gulp.src([
              'assets/vendor/pickadate/lib/translations/*.js',
              ])
              .pipe(gulp.dest('assets/js/pickadate/translations/'));

            // Pickadate CSS
            gulp.src([
              'assets/vendor/pickadate/lib/themes/*.css',
              ])
              .pipe(gulp.dest('assets/css/pickadate/'));

            // Minify js files
            gulp.src([
                './assets/vendor/select2/dist/js/select2.full.js',
                './assets/vendor/pickadate/lib/picker.js',
                './assets/vendor/pickadate/lib/legacy.js',
                './assets/vendor/pickadate/lib/picker.date.js',
                './assets/vendor/pickadate/lib/picker.time.js',
                './assets/js/um-account.js', 
                './assets/js/um-conditional.js', 
                './assets/js/um-crop.js', 
                './assets/js/um-fileupload.js', 
                './assets/js/um-functions.js', 
                './assets/js/um-jquery-form.js', 
                './assets/js/um-masonry.js', 
                './assets/js/um-members.js', 
                './assets/js/um-modal.js', 
                './assets/js/um-profile.js', 
                './assets/js/um-raty.js', 
                './assets/js/um-responsive.js', 
                './assets/js/um-scripts.js', 
                './assets/js/um-scrollbar.js', 
                './assets/js/um-scrollto.js', 
                './assets/js/um-tipsy.js'
                ])
                //.pipe(uglify())
                .pipe(concat("um.min.js"))
                .pipe(gulp.dest('assets/js'));

            gulp.src([
                './assets/vendor/select2/dist/css/select2.css',
                './assets/css/um-misc.css',
                './assets/css/um-account.css', 
                './assets/css/um-crop.css',
                './assets/css/um-fileupload.css',
                './assets/css/um-fonticons-fa.css',
                './assets/css/um-fonticons-ii.css',
                './assets/css/um-members.css',
                './assets/css/um-modal.css',
                './assets/css/um-profile.css',
                './assets/css/um-raty.css',
                './assets/css/um-responsive.css',
                './assets/css/um-scrollbar.css',
                './assets/css/um-tipsy.css',
                './assets/css/um-styles.css'
                 ])
                .pipe(cssnano())
                .pipe(concat("um.min.css"))
                .pipe(gulp.dest('assets/css'));
  
});


// Watch for anychanges
/*gulp.task('watch', function() {
  gulp.watch(watch_changes_in_paths.scripts, ['scripts']);
});*/

// The default task (called when you run `gulp`)
gulp.task('default', ['scripts'/*,'watch'*/] );