$(function() {
	var localStorageEnabled = 'localStorage' in window && window['localStorage'] !== null;
	var scrollDown = function(chat) {
		chat.find('.chat-messages li').last()[0].scrollIntoView(false);
	};

	var refreshChat = function(chat, scroll) {
		if (this.lastRefresh) {
			this.lastRefresh.abort();
		}
		var lastMessage = chat.find('.chat-messages li').last().attr('data-message-id');
		this.lastRefresh = $.get(chat.attr('data-chat-refresh'), {'chat-id': lastMessage}, function(data) {
			chat.find('.chat-messages ul').append(data);

			if (scroll) {
				scrollDown(chat);
			}
		});
	}

	var chatVisibilities = {};
	$('.chat').each(function() {
		var chat = $(this);
		var chatBody = chat.find('.chat-body');
		var chatName = chat.attr('data-chat-name');
		var toggleChat = $('<button type="btn"><span class="glyphicon glyphicon-chevron-down"></span></button>');

		if (localStorageEnabled && localStorage.getItem('chat-' + chatName)) {
			chat.addClass('chat-open');
			chatVisibilities[chatName] = true;
		} else {
			chat.addClass('chat-closed');
			chatVisibilities[chatName] = false;
			toggleChat.find('span').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
		}

		toggleChat.click((function(chat, chatBody, chatName) {
			return function(e) {
				chat.toggleClass('chat-open');
				chat.toggleClass('chat-closed');
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

		chat.on('click', '.chat-reply', (function(chat, chatBody, chatName) {
			return function(e) {
				var mid = $(this).parent().data('message-id');
				chat.find('textarea').insert5('{#' + mid + '}\n');
			}
		})(chat));

		$(document).ready((function(chat) {
			return function(e) {
				scrollDown(chat);
			}
		})(chat));

		chat.find('form').submit(function(e) {
			e.preventDefault();
			var textarea = $(this).find('textarea');
			$.post($(this).attr('action'), $(this).serialize(), function(data) {
				refreshChat(chat, true);
				textarea.val('');
			});
		});

		setInterval(function() {
			refreshChat(chat);
		}, 10000);
	});

	$('.chat').on('keypress', 'textarea', function(e) {
		if (e.which === 13 && !e.shiftKey) {
			e.preventDefault();
			$(this).closest('form').submit();
		}
	});

	$('.chat-active-count').popover({
		html: true,
		placement: 'auto top',
		container: 'body',
		content: function() {
			return $(this).parents('header').parent().next('.chat-active-list').html();
		}
	});

	$('body').on('click', function (e) {
		$('.chat-active-count').each(function () {
			if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
				$(this).popover('hide');
			}
		});
	});
});
