(function($) {
	$.fn.insert5 = function() {
		var editable = $(this);
		var start = editable[0].selectionStart;
		var end = editable[0].selectionEnd;
		if (arguments.length == 2) {
			editable.val(editable.val().slice(0, start) + arguments[0] + editable.val().slice(start, end) + arguments[1] + editable.val().slice(end));
			editable[0].selectionStart = start + arguments[0].length;
			editable[0].selectionEnd = end + arguments[0].length;
		} else if (arguments.length == 1) {
			editable.val(editable.val().slice(0, start) + arguments[0] + editable.val().slice(end));
			editable[0].selectionStart = editable[0].selectionEnd = start + arguments[0].length;
		}
		editable.focus();
	}
}(jQuery || Zepto));
