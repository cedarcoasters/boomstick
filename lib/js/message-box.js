$(function() {


window.MessageBox = {
	 selectorBase:      'message-above-all-center'
	,selectorEscapeKey: 'message-box-escape'
	,selectScreen:      null
	,selectContainer:   null
	,selectContent:     null
	,selectUL:          null
	,selectLI:          null
	,screen:            null
	,container:         null
	,content:           null
	,list:              null
	,listItem:          null
	,count:             0
	,zIndex:            99
	,debugDiv:          null
	,boxes:             []




	,init: function() {
		MessageBox.selectScreen    = 'div#'+MessageBox.selectorBase+'-screen';
		MessageBox.selectContainer = 'div#'+MessageBox.selectorBase+'-container';
		MessageBox.selectContent   = 'div#'+MessageBox.selectorBase+'-content';
		MessageBox.screen          = $(MessageBox.selectScreen);
		MessageBox.container       = $(MessageBox.selectContainer);
		MessageBox.content         = $(MessageBox.selectContent);
		MessageBox.selectUL        = 'ul#'+MessageBox.selectorBase+'-list';
		MessageBox.selectLI        = MessageBox.selectUL+'>li';
		MessageBox.list            = $(MessageBox.selectUL);
		MessageBox.listItem        = $(MessageBox.selectLI);
		MessageBox.debugDiv        = $('<div></div>');

		$(document).keyup(function(e) {
			if(e.key == 'Escape') {
				MessageBox.remove('.'+MessageBox.selectorEscapeKey);
			}
		});
	}

	,bindClose: function(screenId) {
		$('span.'+MessageBox.selectorBase+'-close').click(function() {
			MessageBox.remove();
		});

		$(screenId).click(function() {
			MessageBox.remove();
		});
	}

	,remove: function(addClass='') {
		MessageBox.boxes.forEach(function (boxId, index) {
			$('div#'+boxId+addClass).fadeOut(200).remove();
		});
		MessageBox.boxes = [];

		if(typeof MessageBox.callbackFunc == 'function') {
			MessageBox.callbackFunc();
		}
	}

	,show: function(messages, type='success', heading='Alert', bindClose=true, autoCloseSeconds=null, callbackFunc=null, eTarget=null) {

		MessageBox.callbackFunc = callbackFunc;

		if(autoCloseSeconds != null) {
			setTimeout(()=> {
				MessageBox.remove()
			}
			,(autoCloseSeconds*1000));
		}

		++MessageBox.count; // Increment the id

		// Clone and rename the Content
		var content = MessageBox.content.clone();
		content.attr('id', MessageBox.selectorBase+'-content-box-'+(MessageBox.count));
		content.find('span#'+MessageBox.selectorBase+'-heading').text(heading);


		// Clone and rename the Container
		var container = MessageBox.container.clone();
		var containerId = MessageBox.selectorBase+'-container-box-'+(MessageBox.count);
		content.attr('id', containerId);
		if (eTarget) {
			container.data('originator', eTarget);
			//
		}
		MessageBox.boxes.push(containerId);

		// Clone and rename the Screen Overlay
		var screen   = MessageBox.screen.clone();
		screen.addClass('message-box-escape message-above-all-center-screen');
		var screenId = MessageBox.selectorBase+'-screen-box-'+(MessageBox.count);
		screen.attr('id', screenId);
		MessageBox.boxes.push(screenId);

		if (messages instanceof jQuery) {
			messages.appendTo(content).removeClass('d-none');
		}
		else if (Array.isArray(messages)) {
			// Add the messages to the list
			var list = MessageBox.list.clone().html('');
			messages.forEach(function (message, index) {
				var item = MessageBox.listItem.clone();
				if(message instanceof jQuery) {
					item.empty().append(message).appendTo(list);
				}
				else {
					item.text(message).appendTo(list)
				}
			});

			// Append the list to the content
			list.appendTo(content).removeClass('d-none');
		}

		// Append the content to the container

		content.appendTo(container).removeClass('d-none').addClass('message-type-'+type);

		// Prepare the screen and container for showing
		screen.hide().removeClass('d-none').css('z-index', (MessageBox.zIndex++)).appendTo($('body'));
		container.hide().removeClass('d-none').css('z-index', (MessageBox.zIndex++)).appendTo($('body'));

		// Bind the closing actions to the newly created elements
		if(bindClose == true) {
			$('span#message-box-bind-close-x').removeClass('d-none');
			MessageBox.bindClose('div#'+screenId);
		}
		else {
			content.removeClass(MessageBox.selectorEscapeKey);
			screen.removeClass(MessageBox.selectorEscapeKey);
			container.removeClass(MessageBox.selectorEscapeKey);
			$('span#message-box-bind-close-x').addClass('d-none');
		}

		screen.fadeIn(200);
		container.fadeIn(300);
	}

	,showSuccess: function(messages, heading='Success', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'success', heading, bindClose, null, callbackFunc);
	}
	,showError: function(messages, heading='Error', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'danger', heading, bindClose, null, callbackFunc);
	}
	,showWarning: function(messages, heading='Warning', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'warning', heading, bindClose, null, callbackFunc);
	}
	,showInfo: function(messages, heading='Info', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'info', heading, bindClose, null, callbackFunc);
	}
	,showNote: function(messages, heading='Note', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'dark', heading, bindClose, null, callbackFunc);
	}

	,showSuccessAutoClose(autoCloseSeconds, messages, heading='Success', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'success', heading, bindClose, autoCloseSeconds, callbackFunc);
	}
	,showErrorAutoClose(autoCloseSeconds, messages, heading='Error', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'danger', heading, bindClose, autoCloseSeconds, callbackFunc);
	}
	,showWarningAutoClose(autoCloseSeconds, messages, heading='Warning', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'warning', heading, bindClose, autoCloseSeconds, callbackFunc);
	}
	,showInfoAutoClose(autoCloseSeconds, messages, heading='Info', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'info', heading, bindClose, autoCloseSeconds, callbackFunc);
	}
	,showNoteAutoClose(autoCloseSeconds, messages, heading='Note', callbackFunc=null, bindClose=true) {
		MessageBox.show(messages, 'dark', heading, bindClose, autoCloseSeconds, callbackFunc);
	}


	// ,showForm(sourceURL, heading, eTarget=null, callbackFunc=null) {
	// 	$.get(sourceURL, function(response){
	// 		MessageBox.show($(response), 'info', heading, true, null, callbackFunc, eTarget);
	// 	})
	// }
	,showForm: function(sourceURL, heading, eTarget=null, callbackFunc=null) {
		$.get(sourceURL, function(response){
			// Pass eTarget along to the callback
			MessageBox.show(
				$(response),
				'form',
				heading,
				true,
				null,
				function() {
					if (typeof callbackFunc === 'function') {
						callbackFunc(eTarget);   // ← now callback receives the element
					}
				},
				eTarget
			);
		});
	}
}});
