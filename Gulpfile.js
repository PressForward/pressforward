'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('sass', function() {
    return gulp.src('assets/sass/**/*.scss')
    	.pipe(sass())
        .on('error', sass.logError)
        .pipe(gulp.dest('assets/css/'))
        .on('error', function(error) {
        	console.log(error);
		});
});

//Watch task
gulp.task('default',function() {
    gulp.watch('assets/sass/**/*.scss',['sass']);
});