'use strict';

var gulp = require( 'gulp' );
const { series, parallel } = require('gulp');
var sass = require('@selfisekai/gulp-sass');
var uglify = require('gulp-uglify');
var extrep = require('gulp-ext-replace');
var cleanCSS = require('gulp-clean-css');
var sourcemaps = require('gulp-sourcemaps');
var babel = require('gulp-babel');

gulp.task('sass', function () {
	return gulp.src('assets/sass/**/*.scss')
		.pipe(sass.sync().on('error', sass.logError))
		.pipe(gulp.dest('assets/css/'));
});

// Watch task
gulp.task('default', function () {
	gulp.watch('assets/sass/**/*.scss', ['sass']);
	gulp.watch('**/*.js', ['minify']);
	gulp.watch('**/*.css', ['minify-css']);
});

gulp.task('minify-core-js', function() {
	return gulp.src(['assets/js/*.js', '!assets/js/*.min.js'])
		.pipe(babel({
			presets: [ '@babel/preset-env' ]
		}))
		.pipe(uglify().on('error', console.error))
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('assets/js'));
});

gulp.task( 'minify-jquery-fullscreen', function() {
	return gulp.src(['Libraries/jquery-fullscreen/*.js', '!Libraries/jquery-fullscreen/*.min.js'])
		.pipe(babel())
		.pipe(uglify().on('error', console.error).on('error', console.error))
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/jquery-fullscreen/'));
});

gulp.task( 'minify-alertbox', function() {
	return gulp.src(['Libraries/AlertBox/assets/js/*.js', '!Libraries/AlertBox/assets/js/*.min.js'])
		.pipe(uglify().on('error', console.error))
		.pipe(babel())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/AlertBox/assets/js/'));
});

gulp.task( 'minify-bootstrap', function() {
	return gulp.src(['Libraries/twitter-bootstrap/js/*.js', '!Libraries/twitter-bootstrap/js/*.min.js'])
		.pipe(babel({
			presets: [ '@babel/preset-env' ]
		}))
		.pipe(uglify().on('error', console.error))
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/twitter-bootstrap/js/'));
});


gulp.task( 'minify-readability', function() {
	return gulp.src(['Libraries/MozillaReadability/*.js', '!Libraries/MozillaReadability/*.min.js'])
		.pipe(uglify().on('error', console.error))
		.pipe(babel())
		.pipe(uglify().on('error', console.error))
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/BookmarkletReadability/'));
});

gulp.task( 'minify-js', parallel( 'minify-core-js', 'minify-jquery-fullscreen', 'minify-alertbox', 'minify-bootstrap', 'minify-readability' ) );

gulp.task( 'minify-core-css', () => {
	return gulp.src(['./assets/css/*.css', '!./assets/css/*.min.css'])
		.pipe(sourcemaps.init())
		.pipe(cleanCSS())
		.pipe(sourcemaps.write())
		.pipe(extrep('.min.css'))
		.pipe(gulp.dest('./assets/css/'));

});

gulp.task('minify-bootstrap-css', () => {
	return gulp.src(['./Libraries/twitter-bootstrap/css/*.css', '!./Libraries/twitter-bootstrap/css/*.min.css'])
		.pipe(sourcemaps.init())
		.pipe(cleanCSS())
		.pipe(sourcemaps.write())
		.pipe(extrep('.min.css'))
		.pipe(gulp.dest('./Libraries/twitter-bootstrap/css/'));
});

gulp.task( 'minify-css', parallel( 'minify-core-css', 'minify-bootstrap-css' ) );

gulp.task( 'minify', parallel( 'minify-js', 'minify-css' ) );
