$(function() {
	var localStorageEnabled = 'localStorage' in window && window['localStorage'] !== null;
	var scrollDown = function(chat) {
		chat.find('li:nth-last-child(2)')[0].scrollIntoView(false);
	};

	var chatVisibilities = {};
	$('.chat').each(function() {
		var chat = $(this);
		var chatBody = chat.find('.chat-body');
		var chatName = chat.attr('data-chat-name');
		var toggleChat = $('<button type="btn"><span class="glyphicon glyphicon-chevron-down"></span></button>');

		if (localStorageEnabled && localStorage.getItem('chat-' + chatName)) {
			chatBody.show();
			chatVisibilities[chatName] = true;
		} else {
			chatBody.hide();
			chatVisibilities[chatName] = false;
			toggleChat.find('span').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
		}

		toggleChat.click((function(chat, chatBody, chatName) {
			return function(e) {
				chatBody.toggle('500ms');
				if (localStorageEnabled) {
					if (chatVisibilities[chatName]) {
						localStorage.removeItem('chat-' + chatName);
					} else {
						localStorage.setItem('chat-' + chatName, true);
					}
				}

				chatVisibilities[chatName] = !chatVisibilities[chatName];

				$(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
			}
		})(chat, chatBody, chatName));
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
	// $.nette.ext('scrollOnSubmit', {
	// 	success: function() {
	// 		console.log(arguments[3].nette.el);
	// 		if(arguments[3].type === 'post' && arguments[3].nette.form && $(arguments[3].nette.el[0]).prop('data-target').length > 0) {
	// 			scrollDown($(arguments[3].nette.form[0]).parent('.chat'));
	// 		}
	// 	}
	// });
});
