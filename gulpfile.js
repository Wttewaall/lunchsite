'use strict';

// settings
var minifyCSS	= false;
var minifyJS	= false;
var addCSSMaps	= false;
var addJSMaps	= false;

// dependencies
var gulp		= require('gulp');
var path		= require('path');
var del			= require('del');
var merge		= require('merge-stream');
var concat		= require('gulp-concat');
var rename		= require('gulp-rename');
var jshint		= require('gulp-jshint');
var less		= require('gulp-less');
//var sass		= require('gulp-sass');
var twig		= require('gulp-twig');
var sourcemaps	= require('gulp-sourcemaps');
var autoprefixer	= require('gulp-autoprefixer');
var minify		= require('gulp-minify-css');
var uglify		= require('gulp-uglify');
var uncss		= require('gulp-uncss');

// Dependecies
gulp.task('deps', function () {

	var assets = gulp.src([
		'assets/**/*',
	]).pipe(gulp.dest('web/'));

	var images = gulp.src([
		'assets/img/**/*',
	]).pipe(gulp.dest('web/img/'));

	var fonts = gulp.src([
		'bower_components/bootstrap/dist/fonts/**/*',
		'bower_components/bootstrap-material-design/dist/fonts/**/*',
		'assets/fonts/**/*',
	]).pipe(gulp.dest('web/fonts/'));

	var vendorsCSS = gulp.src([
		'bower_components/form.validation/dist/css/formValidation.css',
		'bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css',
		'bower_components/dropdown.js/jquery.dropdown.css',
	]).pipe(concat('vendors.css'))
	.pipe(gulp.dest('web/css/'));
	
	if (minifyCSS) {
		vendorsCSS = vendorsCSS.pipe(minify())
		.pipe(rename({ extname: '.min.css' }))
		.pipe(gulp.dest('web/css/'));
	}
	
	var vendorsJS = gulp.src([
		'bower_components/jquery/dist/jquery.min.js',
		'bower_components/bootstrap/dist/js/bootstrap.js',
		'bower_components/moment/min/moment-with-locales.js',
		'bower_components/form.validation/dist/js/formValidation.js',
		'bower_components/form.validation/dist/js/framework/bootstrap.js',
		'bower_components/form.validation/dist/js/language/nl_NL.js',
		'bower_components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js',
		'bower_components/bootbox/bootbox.js',
		'bower_components/bootstrap-material-design/dist/js/material.js',
		'bower_components/bootstrap-material-design/dist/js/ripples.js',
		'bower_components/dropdown.js/jquery.dropdown.js'
	])
	.pipe(concat('vendors.js'))
	.pipe(gulp.dest('web/js/'));
	
	if (minifyJS) {
		vendorsJS = vendorsJS.pipe(uglify())
		.pipe(rename({ extname: '.min.js' }))
		.pipe(gulp.dest('web/js/'));
	}
	
	var cssMaps = gulp.src((addCSSMaps) ? [
		'bower_components/bootstrap/dist/css/*.map',
		'bower_components/bootstrap-material-design/dist/css/*.map',
	] : []).pipe(gulp.dest('web/css/'));
	
	var jsMaps = gulp.src((addJSMaps) ? [
		'bower_components/jquery/dist/*.map',
	] : []).pipe(gulp.dest('web/js/'));
	
	var scripts = gulp.src([
		'src/js/**/*.js',
	]).pipe(concat('lunchsite.js'))
	.pipe(gulp.dest('web/js/'));
	
	var index = gulp.src([
		'src/php/index.php',
	]).pipe(gulp.dest('web/'));
	
	// ---- merge ----

	return merge(
		assets, images, fonts,
		vendorsCSS, vendorsJS,
		cssMaps, jsMaps,
		scripts, index
	);
});

// Less
gulp.task('less', function () {
	var lessTask = gulp.src('src/less/lunchsite.less')
		.pipe(less())
		.pipe(sourcemaps.init())
		.pipe(autoprefixer())
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest('web/css/'));
		
	if (minifyCSS) {
		lessTask = lessTask.pipe(minify())
		.pipe(rename({ extname: '.min.css' }))
		.pipe(gulp.dest('web/css/'));
	}
	
	return lessTask;
});

// JS
gulp.task('js', function () {
	var jsTask = gulp.src([
		'src/js/**/*.js'
	])
	.pipe(sourcemaps.init())
	.pipe(concat('lunchsite.js'))
	.pipe(sourcemaps.write('.'))
	.pipe(gulp.dest('web/js/'));
	
	if (minifyJS) {
		jsTask = jsTask.pipe(uglify())
		.pipe(rename({ extname: '.min.js' }))
		.pipe(gulp.dest('web/js/'));
	}
	
	return jsTask;
});

// Twig
gulp.task('twig', function () {
	return gulp.src([
		'src/twig/templates/*.html.twig'
	]).pipe(twig())
	.pipe(rename({ extname: '' }))
	.pipe(gulp.dest('web/'));
});

// Watchers
gulp.task('watch:less', function () {
	gulp.watch('src/less/**/*.less', ['less']);
});

gulp.task('watch:js', function () {
	gulp.watch('src/js/**/*.js', ['js']);
});

gulp.task('watch:twig', function () {
	gulp.watch('src/twig/**/*.twig', ['twig']);
});

gulp.task('watch', function () {
	gulp.watch('src/less/**/*.less', ['less']);
	gulp.watch('src/js/**/*.js', ['js']);
	//gulp.watch('src/twig/**/*.twig', ['twig']);
});

gulp.task('uncss:vendors', function() {
	return gulp.src('web/css/vendors.css')
		.pipe(uncss({
			html: ['http://lunchsite.localhost']
		}))
		.pipe(rename({ extname: '.un.css' }))
		.pipe(gulp.dest('web/css'));
});

// also see: https://www.npmjs.com/package/gulp-closure-compiler
gulp.task('uncss', function() {
	return gulp.src('web/css/lunchsite.css')
		.pipe(uncss({
			html: ['http://lunchsite.localhost']
		}))
		.pipe(rename({ extname: '.un.css' }))
		.pipe(gulp.dest('web/css'));
});

gulp.task('clear', function() {
	del(['web/.htaccess', 'web/*']);
});

gulp.task('minify:all', function() {
	
	// remove old minified files
	del([
		'web/css/*.min.css',
		'web/js/*.min.js'
	]);
	
	var minCSS = gulp.src('web/css/*.css')
		.pipe(minify())
		.pipe(rename({ extname: '.min.css' }))
		.pipe(gulp.dest('web/css/'));
	
	var minJS = gulp.src('web/js/*.js')
		.pipe(uglify())
		.pipe(rename({ extname: '.min.js' }))
		.pipe(gulp.dest('web/js/'));
		
	return merge(minCSS, minJS);
});

// Default
gulp.task('default', ['less', 'js']);
gulp.task('production', ['deps', 'less', 'js', 'minify:all']);