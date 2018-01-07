'use strict';

var gulp = require( 'gulp' );
var sass = require( 'gulp-sass' );
var uglify = require( 'gulp-uglify' );
var extrep = require('gulp-ext-replace');

gulp.task('sass', function() {
	return gulp.src( 'assets/sass/**/*.scss' )
		.pipe( sass() )
		.on( 'error', sass.logError )
		.pipe( gulp.dest( 'assets/css/' ) )
		.on('error', function(error) {
			console.log( error );
		});
});

// Watch task
gulp.task('default',function() {
	gulp.watch( 'assets/sass/**/*.scss',['sass'] );
});

gulp.task('minify', function() {
	return gulp.src('assets/js/*.js')
		.pipe( uglify() )
		.pipe( extrep( '.min.js' ) )
		.pipe( gulp.dest( 'assets/js/' ) )
});
