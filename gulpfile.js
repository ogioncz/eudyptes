const gulp = require('gulp');
const less = require('gulp-less');
const autoprefix = require('gulp-autoprefixer');
const csso = require('gulp-csso');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const notify = require("gulp-notify");
const bower = require('gulp-bower');

const notifyAboutError = notify.onError(function(error) {
	console.log(error);
	return "Error: " + error.message;
});

const config = {
	assets: {
		vendor: './assets/vendor',
		less: './assets/less',
		javascript: './assets/javascript'
	},
	public: {
		javascript: './www/javascript',
		css: './www/css'
	}
}

gulp.task('bower', function() {
	return bower()
	.pipe(gulp.dest(config.assets.vendor));
});

gulp.task('css', gulp.series('bower', function() {
	return gulp.src(config.assets.less + '/screen.less')
	.pipe(less({
		paths: [
			config.assets.less,
			config.assets.vendor + '/bootstrap/less',
		]
	}).on("error", notifyAboutError))
	.pipe(csso())
	.pipe(autoprefix('last 2 versions'))
	.pipe(gulp.dest(config.public.css));
}));

gulp.task('javascript', gulp.series('bower', gulp.parallel(function javascriptApp() {
	return gulp.src([
		config.assets.javascript + '/**/*.js'
	])
	.pipe(babel({
		compact: false,
		presets: ['es2015']
	}).on("error", notifyAboutError))
	.pipe(uglify())
	.pipe(gulp.dest(config.public.javascript));
}, function javascriptDependencies() {
	return gulp.src([
		config.assets.vendor + '/jquery/dist/jquery.js',
		config.assets.vendor + '/bootstrap/dist/js/bootstrap.js',
		config.assets.vendor + '/nette-forms/src/assets/netteForms.js',
		config.assets.vendor + '/smalot-bootstrap-datetimepicker/js/bootstrap-datetimepicker.js',
		config.assets.vendor + '/smalot-bootstrap-datetimepicker/js/locales/bootstrap-datetimepicker.cs.js',
		config.assets.vendor + '/nextras-forms/js/nextras.datetimepicker.init.js',
		config.assets.vendor + '/nextras-forms/js/nextras.netteForms.js',
		config.assets.vendor + '/typeahead.js/dist/bloodhound.js',
		config.assets.vendor + '/typeahead.js/dist/typeahead.jquery.js',
		config.assets.vendor + '/nette.ajax.js/nette.ajax.js',
	])
	.pipe(uglify())
	.pipe(gulp.dest(config.public.javascript));
})));

gulp.task('watch', function() {
	gulp.watch(config.assets.less + '/**/*.less', gulp.series('css'));
	gulp.watch(config.assets.javascript + '/**/*.js', gulp.series('javascript'));
});

gulp.task('default', gulp.parallel('css', 'javascript'));
