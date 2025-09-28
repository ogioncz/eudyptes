$(function() {
	$('.pin-spoiler').popover({trigger: 'click', placement: 'top'});
	$('#pinslist img').popover({trigger: 'hover', container: 'body'});
	$('input.nospam').val('nospam');
	$('.nospam').css('display', 'none');
	$('.stamp').popover({trigger: 'hover', html: true, container: 'body'});
	$('.mail-subject').popover({trigger: 'focus', container: 'body'});

	$.nette.init();
});
