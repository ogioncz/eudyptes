const gulp = require('gulp');
const less = require('gulp-less');
const autoprefix = require('gulp-autoprefixer');
const csso = require('gulp-csso');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const notify = require("gulp-notify");
const bower = require('gulp-bower');

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
	}).on("error", notify.onError(function(error) {
		console.log(error);
		return "Error: " + error.message;
	})))
	.pipe(csso())
	.pipe(autoprefix('last 2 versions'))
	.pipe(gulp.dest(config.public.css));
}));

gulp.task('javascript', gulp.series('bower', function() {
	return gulp.src([
		config.assets.vendor + '/jquery/dist/jquery.js',
		config.assets.vendor + '/bootstrap/dist/js/bootstrap.js',
		config.assets.vendor + '/jquery-details/jquery.details.js',
		config.assets.vendor + '/nette-forms/src/assets/netteForms.js',
		config.assets.vendor + '/smalot-bootstrap-datetimepicker/js/bootstrap-datetimepicker.js',
		config.assets.vendor + '/smalot-bootstrap-datetimepicker/js/locales/bootstrap-datetimepicker.cs.js',
		config.assets.vendor + '/nextras-forms/js/nextras.datetimepicker.init.js',
		config.assets.vendor + '/nextras-forms/js/nextras.netteForms.js',
		config.assets.vendor + '/nette.ajax.js/nette.ajax.js',
		config.assets.javascript + '/**/*.js'
	])
	.pipe(uglify())
	.pipe(gulp.dest(config.public.javascript));
}));

gulp.task('watch', function() {
	gulp.watch(config.assets.less + '/**/*.less', gulp.series('css'));
	gulp.watch(config.assets.javascript + '/**/*.js', gulp.series('javascript'));
});

gulp.task('default', gulp.parallel('css', 'javascript'));
