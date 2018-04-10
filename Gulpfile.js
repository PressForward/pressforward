'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');
var extrep = require('gulp-ext-replace');
var cleanCSS = require('gulp-clean-css');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('sass', function () {
	return gulp.src('assets/sass/**/*.scss')
		.pipe(sass())
		.on('error', sass.logError)
		.pipe(gulp.dest('assets/css/'))
		.on('error', function (error) {
			console.log(error);
		});
});

// Watch task
gulp.task('default', function () {
	gulp.watch('assets/sass/**/*.scss', ['sass']);
	gulp.watch('**/*.js', ['minify']);
	gulp.watch('**/*.css', ['minify-css']);
});

gulp.task('minify', function () {
	gulp.src(['assets/js/*.js', '!assets/js/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('assets/js/'));

	gulp.src(['Libraries/jquery-fullscreen/*.js', '!Libraries/jquery-fullscreen/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/jquery-fullscreen/'));

	gulp.src(['Libraries/jquery-tinysort/*.js', '!Libraries/jquery-tinysort/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/jquery-tinysort/'));

	gulp.src(['Libraries/AlertBox/assets/js/*.js', '!Libraries/AlertBox/assets/js/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/AlertBox/assets/js/'));

	gulp.src(['Libraries/*.js', '!Libraries/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/'));

	gulp.src(['Libraries/twitter-bootstrap/js/*.js', '!Libraries/twitter-bootstrap/js/*.min.js'])
		.pipe(uglify())
		.pipe(extrep('.min.js'))
		.pipe(gulp.dest('Libraries/twitter-bootstrap/js/'));

});

gulp.task('minify-css', () => {
	gulp.src(['./assets/css/*.css', '!./assets/css/*.min.css'])
		.pipe(sourcemaps.init())
		.pipe(cleanCSS())
		.pipe(sourcemaps.write())
		.pipe(extrep('.min.css'))
		.pipe(gulp.dest('./assets/css/'));

	gulp.src(['./Libraries/twitter-bootstrap/css/*.css', '!./Libraries/twitter-bootstrap/css/*.min.css'])
		.pipe(sourcemaps.init())
		.pipe(cleanCSS())
		.pipe(sourcemaps.write())
		.pipe(extrep('.min.css'))
		.pipe(gulp.dest('./Libraries/twitter-bootstrap/css/'));
});
