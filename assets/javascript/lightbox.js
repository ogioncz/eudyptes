$(document).ready(function(){
	var overlay = $(document.createElement('div'));
	overlay.addClass('lightbox-overlay');
	overlay.click(function(e) {
		e.preventDefault();
		hide();
	});

	var container = $(document.createElement('div'));
	container.addClass('lightbox-container');
	container.click(function(e) {
		e.preventDefault();
		hide();
	});
	$('body').append(overlay).append(container);
	$('body').on('keydown', function(e) {
		if(e.which == 27) {
			hide();
		}
	});
	
	function load(url) {
		if(container.is('.loading')) {return;}
		container.addClass('loading');
		container.text('Loading…');
		var img = new Image();
		img.onload = function() {
			img.style.display = 'none';
			img.style.background = 'white';
			var maxWidth = $(window).width() - 2*20 - 100;
			var maxHeight = $(window).height() - 2*20 - 100;
			if(img.width > maxWidth || img.height > maxHeight) {
				var ratio = img.width / img.height;
				if(img.height >= maxHeight) {
					img.height = maxHeight;
					img.width = maxHeight * ratio;
				} else {
					img.width = maxWidth;
					img.height = maxWidth / ratio;
				}
			}
			container.animate({'width': img.width,'height': img.height, 'top': Math.round(($(window).height() - img.height - 2*20) / 2) + 'px', 'left': Math.round(($(window).width() - img.width - 2*20) / 2) + 'px'}, function(){
				container.text('');
				container.append(img);
				$(img).show();
				container.removeClass('loading');
			});
		};
		img.src = url;
	}
	
	function hide() {
		overlay.hide();
		container.removeClass('loading');
		container.children().remove();
		container.hide();
	}

	$('a[data-lightbox=true]').click(function(e) {
		e.preventDefault();
		overlay.show();
		load($(this).attr('href'));
		container.show();
	});
});
