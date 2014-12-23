$(function(){
	$('[data-content]').popover({
		trigger: 'focus',
		container: 'body'
	});
	$('input.nospam').val('nospam');
	$('.nospam').css('display', 'none');
	$('details').details();

	$.nette.init();
});
