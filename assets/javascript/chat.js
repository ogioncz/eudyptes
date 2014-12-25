$(function() {
	var scrollDown = function(chat) {
		chat.find('li:nth-last-child(2)')[0].scrollIntoView(false);
	};

	$('.chat').each(function() {
		var chat = $(this);
		var toggleChat = $('<button type="btn"><span class="glyphicon glyphicon-chevron-down"></span></button>');
		toggleChat.click((function(chat) {
			return function(e) {
				chat.find('.chat-body').toggle('500ms');
				$(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
			}
		})(chat));
		chat.find('header .chat-toolbar').append(toggleChat);

		scrollDown(chat);

		setInterval(function() {
			chat.find('.refresh-chat').click();
		}, 5000);
	});

	$('.chat').on('keypress', 'textarea', function(e) {
		if (e.which === 13 && !e.shiftKey) {
			e.preventDefault();
			$(this).submit();
		}
	});

	$.nette.ext('scrollOnSubmit', {
		success: function() {
			if (arguments[3].type === 'post') {
				scrollDown($('#chat'));
			}
		}
	});
});
