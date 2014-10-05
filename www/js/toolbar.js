$(function() {
	function htmlSpecialChars(text) {
		return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
	}
	var contentArea = $('textarea');
	var quickbar = $('<div id="quickbar" class="btn-toolbar"></div>');
	contentArea.before(quickbar);

	var buttons = [
		[
			{opening: '**', closing: '**', title: 'důležitý text (ctrl+b)', body: '<span class="glyphicon glyphicon-bold"></span>', shortcut: 66},
			{opening: '*', closing: '*', title: 'zvýrazněný text (ctrl+i)', body: '<span class="glyphicon glyphicon-italic"></span>', shortcut: 73},
			{opening: '[', closing: ']()', title: 'odkaz (ctrl+l)', body: '<span class="glyphicon glyphicon-globe"></span>', shortcut: 76},
			{opening: '<spoiler>', closing: '</spoiler>', title: 'spoiler', body: '<span class="glyphicon glyphicon-eye-close"></span>'}
		],
		[
			{opening: '„', closing: '“', title: 'české uvozovky (ctrl+q)', body: '„“', shortcut: 81},
			{title: 'pomlčka', body: '–', shortcut: 189},
			{title: 'trojtečka (ctrl+.)', body: '…', shortcut: 190},
			{opening: ' ', title: 'pevná mezera (ctrl+␣)', body: '␣', shortcut: 32},
			{opening: '<mark>', closing: '</mark>', title: 'zvýraznění', body: '<span class="icon-marker"></span>'},
		],
		[
			{title: 'Celá obrazovka', body: '<span class="glyphicon glyphicon-fullscreen"></span>', action: 'fullscreen'},
		]
	];

	var shortcuts = [];
	var quickbarContent = '';
	for(var group in buttons) {
		quickbarContent += '<div class="btn-group">';
		for(var btn in buttons[group]) {
			var button = buttons[group][btn];
			// if(button.action && button.action == 'fullscreen') {
			quickbarContent += '<button class="btn btn-default" type="button"';
			if(button.action) {
				quickbarContent += ' data-action="' + htmlSpecialChars(button.action) + '"';
			}
			if(button.opening) {
				quickbarContent += ' data-opening="' + htmlSpecialChars(button.opening) + '"';
			}
			if(button.closing) {
				quickbarContent += ' data-closing="' + htmlSpecialChars(button.closing) + '"';
			}
			if(button.title) {
				quickbarContent += ' title="' + htmlSpecialChars(button.title) + '"';
			}
			if(button.shortcut) {
				shortcuts.push(button.shortcut);
				quickbarContent += ' data-shortcut="' + button.shortcut + '"';
			}
			quickbarContent += '>' + button.body + '</button>';
		}
		quickbarContent += '</div>';
	}

	quickbar.html(quickbarContent);
	$('#quickbar button').click(function(e) {
		var btn = $(this);
		var action = btn.attr('data-action');
		var opening = btn.attr('data-opening');
		var closing = btn.attr('data-closing');
		if(action == 'fullscreen') {
			contentArea.parent().toggleClass('fullscreen');
			$('body').toggleClass('nooverflow');
		} else if(closing) {
			contentArea.insert5(opening, closing);
		} else if(opening) {
			contentArea.insert5(opening);
		} else {
			contentArea.insert5(btn.text());
		}
	});

	$(document).keydown(function(e) {
		if(contentArea.is(':focus') && shortcuts.indexOf(e.which) >= 0 && e.ctrlKey && !e.altKey) {
			console.log(e.which);
			$('button[data-shortcut='+e.which+']').click();
			e.preventDefault();
		}
	});
});
