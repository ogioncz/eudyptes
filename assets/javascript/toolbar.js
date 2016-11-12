$(function() {
	let activeContentArea = null;

	function htmlSpecialChars(text) {
		return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
	}

	$('body').append(
		`<div class="modal fade" id="link-insertion-dialog" tabindex="-1" role="dialog" aria-labelledby="link-insertion-dialog-label" data-keyboard="false">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Zavřít"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="link-insertion-dialog-label">Vložení odkazu na stránku</h4>
					</div>
					<div class="modal-body">
						<form>
							<div class="form-group">
								<label for="link-target" class="control-label">Cíl odkazu:</label>
								<input type="text" class="form-control" id="link-target" autocomplete="off">
							</div>
							<div class="form-group">
								<label for="link-text" class="control-label">Text:</label>
								<input type="text" class="form-control" id="link-text">
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Zrušit</button>
						<button type="button" class="btn btn-primary" id="link-insert">Vložit odkaz</button>
					</div>
				</div>
			</div>
		</div>`
	);

	$.getJSON($('body').attr('data-basepath') + '/page/titles', (data) => {
		var titles = new Bloodhound({
			datumTokenizer: Bloodhound.tokenizers.whitespace,
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			local: data
		});
		$("#link-target").typeahead({
			hint: false,
			highlight: true,
			minLength: 1
		},
		{
			name: 'titles',
			source: titles
		});
	});

	$('#link-insertion-dialog').on('shown.bs.modal', () => $('#link-target').focus());
	$('#link-insertion-dialog').on('hidden.bs.modal', () => {
		$('#link-target').typeahead('val', '');
		$('#link-text').val('');
	});

	$('#link-target, #link-text').on('keydown', (e) => {
		const typeahead_open = $('#link-target ~ .tt-menu').is('.tt-open');
		if (!typeahead_open) {
			if (e.which == 13) { // ENTER_KEY
				submitLinkDialog();
			} else if (e.which == 27) { // ESCAPE_KEY
				$('#link-insertion-dialog').modal('hide');
			}
		}
	});

	$('#link-insert').on('click', submitLinkDialog);

	function submitLinkDialog() {
		const target = $('#link-target').typeahead('val').trim();
		const text = $('#link-text').val().trim();

		if (!target) {
			alert('Cíl odkazu je nutné zadat.');
			$('#link-target').focus();
			return;
		}

		let link = null;
		if (target.match(/^((http|ftp)s?:\/\/|mailto:)/)) {
			if (!text || text === target) {
				link = `${target}`;
			} else {
				link = `[${text}](${target})`;
			}
		} else {
			if (!text || text === target) {
				link = `[[${target}]]`;
			} else {
				link = `[[${target}|${text}]]`;
			}
		}

		activeContentArea.insert5(link);
		$('#link-insertion-dialog').modal('hide');
	}

	$('textarea.editor').each(function() {
		var contentArea = $(this);
		var quickbar = $('<div class="quickbar" class="btn-toolbar"></div>');
		contentArea.before(quickbar);

		var buttons = [
			[
				{opening: '**', closing: '**', title: 'důležitý text (ctrl+b)', body: '<span class="glyphicon glyphicon-bold"></span>', shortcut: 66},
				{opening: '*', closing: '*', title: 'zvýrazněný text (ctrl+i)', body: '<span class="glyphicon glyphicon-italic"></span>', shortcut: 73},
				{toggle: 'modal', target: '#link-insertion-dialog', title: 'odkaz na stránku (ctrl+o)', body: '<span class="glyphicon glyphicon-link"></span>', shortcut: 79},
				{opening: '[', closing: ']()', title: 'odkaz (ctrl+l)', body: '<span class="glyphicon glyphicon-globe"></span>', shortcut: 76},
				{opening: '¡¡¡\n', closing: '\n!!!', title: 'spoiler (ctrl+s)', body: '<span class="glyphicon glyphicon-eye-close"></span>', shortcut: 83}
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
		for (var group in buttons) {
			quickbarContent += '<div class="btn-group">';
			for (var btn in buttons[group]) {
				var button = buttons[group][btn];
				quickbarContent += '<button class="btn btn-default" type="button"';
				if (button.action) {
					quickbarContent += ' data-action="' + htmlSpecialChars(button.action) + '"';
				}
				if (button.opening) {
					quickbarContent += ' data-opening="' + htmlSpecialChars(button.opening) + '"';
				}
				if (button.closing) {
					quickbarContent += ' data-closing="' + htmlSpecialChars(button.closing) + '"';
				}
				if (button.toggle) {
					quickbarContent += ' data-toggle="' + htmlSpecialChars(button.toggle) + '"';
				}
				if (button.target) {
					quickbarContent += ' data-target="' + htmlSpecialChars(button.target) + '"';
				}
				if (button.title) {
					quickbarContent += ' title="' + htmlSpecialChars(button.title) + '"';
				}
				if (button.shortcut) {
					shortcuts.push(button.shortcut);
					quickbarContent += ' data-shortcut="' + button.shortcut + '"';
				}
				quickbarContent += '>' + button.body + '</button>';
			}
			quickbarContent += '</div>';
		}

		quickbarContent = $(quickbarContent);

		quickbar.html(quickbarContent);
		quickbarContent.find('button').click((function(ca) {
			return function(e) {
				activeContentArea = ca;
				var btn = $(this);
				var action = btn.attr('data-action');
				var opening = btn.attr('data-opening');
				var closing = btn.attr('data-closing');
				if (action == 'fullscreen') {
					ca.parent().toggleClass('fullscreen');
					$('body').toggleClass('nooverflow');
				} else if (closing) {
					ca.insert5(opening, closing);
				} else if (opening) {
					ca.insert5(opening);
				} else {
					ca.insert5(btn.text());
				}
			};
		})(contentArea));

		contentArea.keydown((function(ca) {
			return function(e) {
				if (ca.is(':focus') && shortcuts.indexOf(e.which) >= 0 && e.ctrlKey && !e.altKey) {
					ca.parent().find('button[data-shortcut='+e.which+']').click();
					e.preventDefault();
				}
			};
		})(contentArea));
	});
});
