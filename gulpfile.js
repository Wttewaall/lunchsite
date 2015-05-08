'use strict';

var gulp		= require('gulp');
var combineCSS	= require('combine-css');
var concat		= require('gulp-concat');
var jshint		= require('gulp-jshint');
var less		= require('gulp-less');
var rename		= require('gulp-rename');
var twig		= require('gulp-twig');
var uglify		= require('gulp-uglify');
var merge		= require('merge-stream');
var path		= require('path');

gulp.copy = function(src, dest) {
	return gulp.src(src, {base: "."})
		.pipe(gulp.dest(dest));
};

/**
TODO: flag toevoegen om optioneel alleen de minified versies te pakken
TODO: alle dependencies in een enkele vendors.js/css concatten
**/

// Dependecies
gulp.task('deps', function () {

	var favicon = gulp.src([
		'assets/img/favicon.ico',
	]).pipe(gulp.dest('web/'));

	var images = gulp.src([
		'assets/img/**/*'
	]).pipe(gulp.dest('web/img/'));

	var fonts = gulp.src([
		'bower_components/bootstrap/dist/fonts/**/*',
		'bower_components/bootstrap-material-design/dist/fonts/**/*',
		'assets/fonts/**/*'
	]).pipe(gulp.dest('web/fonts/'));

	var styles = gulp.src([
		'bower_components/form.validation/dist/css/formValidation.min.css',
		'bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css',
		'bower_components/select2/dist/css/select2.min.css',
		'bower_components/bootstrap-material-design/dist/css/*.min.*'
	]).pipe(gulp.dest('web/css/'));

	var thirdpartyScripts = gulp.src([
		'bower_components/jquery/dist/jquery.min.js',
		'bower_components/bootstrap/dist/js/bootstrap.min.js',
		'bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
		'bower_components/moment/min/moment-with-locales.min.js',
		'bower_components/select2/dist/js/select2.min.js',
		'bower_components/bootbox/bootbox.js',
		'bower_components/bootstrap-material-design/dist/js/*.min.*'
	]).pipe(gulp.dest('web/js/'));

	var bootstrapValidator = gulp.src([
		'bower_components/form.validation/dist/js/framwork/bootstrap.js',
		'bower_components/form.validation/dist/js/formValidation.js',
		'bower_components/form.validation/dist/js/language/nl_NL.js'
	]).pipe(concat('formValidation.js'))
	.pipe(gulp.dest('web/js/'));
	
	// ---- merge ----

    return merge(
		favicon, images, fonts, styles,
		thirdpartyScripts, bootstrapValidator
	);
});

// Less
gulp.task('less', function () {
	return gulp.src('src/less/lunchsite.less')
		.pipe(less())
		.pipe(gulp.dest('web/css/'));
});

// JS
gulp.task('js', function () {
	return gulp.src([
		'src/js/**/*.js'
	]).pipe(concat('lunchsite.js'))
	.pipe(gulp.dest('web/js/'));
});

// Twig
gulp.task('twig', function () {
	return gulp.src([
			'src/twig/views/*.html.twig'
		])
		.pipe(twig())
		.pipe(rename(function (path) {
			path.extname = "" /* removes the .twig extension */
		}))
		.pipe(gulp.dest('web/'));
});

// Watchers
gulp.task('watchless', function () {
    gulp.watch('src/less/**/*.less', ['less']);
});

gulp.task('watchless', function () {
    gulp.watch('src/js/**/*.js', ['js']);
});

gulp.task('watchtwig', function () {
	gulp.watch('src/twig/**/*.twig', ['twig']);
});

gulp.task('watch', function () {
	gulp.watch('src/less/**/*.less', ['less']);
	gulp.watch('src/js/**/*.js', ['js']);
	gulp.watch('src/twig/**/*.twig', ['twig']);
});

// Default
gulp.task('default', ['deps', 'less', 'js', 'twig']);