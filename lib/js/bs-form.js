export class BSForm
{
	constructor({selector} = {})
	{
		this.selector   = selector;
		this.components = [];
		this.name       = $(selector).attr('name');
		let self        = this;
		$(this.selector).on('submit', function(e){
			e.preventDefault();

			let formData   = $(this).serialize();

			$.ajax({
				 url: $(this).attr('action') || ''
				,method: $(this).attr('method') || 'POST'
				,data: formData
				,success: (response) => {self.formSubmitSuccess(response)}
				,error: function(error) { console.error('Error:', error); }
			});
		});

		// By default, show a message box with a summary of the errors, when received.
		// This alerts a user to the fact that there are issues with the form submission, in
		// the event that the elements in violation are outside of the view port.
		//
		// Disable this for smaller forms, or for modals using
		this.showMBoxOnError = true;
	}

	addComponent({name, sourceURL, targetSelector})
	{
		this.components[name] = {name:name, sourceURL:sourceURL, targetSelector:targetSelector};
	}


	postRender()
	{
		let self = this;

		this.applyMoneyFormat();
		this.applyPercentFormat();
		this.applyDatePicker();
		this.applyShowIfYes();
		this.applyShowIfSelected();
		this.applyCollapsingLabels();
		this.applyCheckboxMultiFormat();
		this.applyShortLink();
		// Delay this slightly for form loading to complete.  This is not the ideal method, but it solves the immediate bug.
		setTimeout(() => {
			self.applyEditNotices();
		}, 1000);


		// Bind toggles
		// let self = this;
		$('div[data-form-toggles]').each(function(){
			let targetId = $(this).attr('data-form-toggles');

			self.toggleComponent({
				 targetElement: $(`div#${targetId}`)
				,toggleElement: $(this).children(`div[data-${self.name}-form-type="element-toggle"]`)
				,isVisibleOnStart:false
			})
		});

		tippy('[data-tippy-content]', {
			placement: 'top',
			animation: 'shift-away',
			theme: 'light',
			delay: [200, 100],
			allowHTML: true,
		});
		return;
	}

	postRedraw()
	{
		this.applyCheckboxMultiFormat();
	}


	showMessageBoxOnError(flag)
	{
		this.showMBoxOnError = flag;
	}


	toggleComponent({targetElement, toggleElement, hiddenText='(show details)', visibleText='(hide details)', isVisibleOnStart=false})
	{
		toggleElement.data('visibleLabel', visibleText);
		toggleElement.data('hiddenLabel', hiddenText);
		toggleElement.removeClass('d-none');
		if(isVisibleOnStart) {
			toggleElement.html(toggleElement.data('visibleLabel'));
			targetElement.removeClass('d-none');
		}
		else {
			toggleElement.html(toggleElement.data('hiddenLabel')).add;
			targetElement.addClass('d-none');
		}

		let toggleFunc = function() {
			let isVisible = !targetElement.hasClass('d-none');
			if(isVisible) {
				toggleElement.html(toggleElement.data('hiddenLabel')).add;
				targetElement.addClass('d-none');
			}
			else {
				toggleElement.html(toggleElement.data('visibleLabel'));
				targetElement.removeClass('d-none');
			}
		}

		toggleElement.on('click', toggleFunc).css('cursor', 'pointer');
	}


	applyShowIfYes()
	{
		let self = this;

		$('div[data-show-if-yes]').each(function(){
			let target = $(this).attr('data-show-if-yes');
			let selectYN = $(this).find('select');

			let toggleFunc = function(){
				let selected = $(this).val();
				if(selected === 'yes') {
					$(`.show-if-${target}-is-yes`).removeClass('d-none').find(':input').prop('disabled', false);
				}
				else {
					$(`.show-if-${target}-is-yes`).addClass('d-none').find(':input').prop('disabled', true);
				}
			};

			selectYN.on('change', toggleFunc);
			selectYN.trigger('change');
		});
	}


	applyShowIfSelected()
	{
		$('div[data-show-if-selected]').each(function(){
			let target   = $(this).attr('data-show-if-selected');
			let selector = $(this).find('select');

			let selectionFunc = function(){
				let values = $(this).val();
				$(`.show-if-selected[data-if-seleted-key="${target}"]`).each(function(){
					if(values.includes($(this).attr('data-if-selected-value'))) {
						$(this).removeClass('d-none');
						$(this).find(':input').prop('disabled', false);
					}
					else {
						$(this).addClass('d-none');
						$(this).find(':input').prop('disabled', true);
					}
				});
			}

			selector.on('change', selectionFunc);
			selector.trigger('change');
		});
	}


	applyMoneyFormat()
	{
		// Apply any money formatting
		let selectors = {
			 [`input[data-form="${this.name}"][data-money-type="text-money-usd-0000"]`]   :4
			,[`input[data-form="${this.name}"][data-money-type="text-money-usd-000"]`]    :3
			,[`input[data-form="${this.name}"][data-money-type="text-money-usd-00"]`]     :2
			,[`input[data-form="${this.name}"][data-money-type="text-money-usd-nocent"]`] :0
		}

		for(let selector in selectors) {
			const $elements = $(selector);
			const decimalPlaces = selectors[selector];

			$elements.each(function() {
				const an = AutoNumeric.getAutoNumericElement(this);
				if (an) {
					an.remove();    // ← removes listeners, drops the instance
					an.unformat();  // if you want raw number before re-init
					// or:        an.wipe();      // clears value to empty string
				}
			});

			if($(selector).length > 0) {
				AutoNumeric.multiple(selector, {
					currencySymbol: '$',
					decimalPlaces: decimalPlaces,
					digitGroupSeparator: ',',
					decimalCharacter: '.',
					emptyInputBehavior: 'zero',
					allowDecimalPadding: true
				});
			}
		}
	}

	applyPercentFormat()
	{
		let selectors = {
			 [`input[data-form="${this.name}"][data-percent-type="text-percent-000"]`]   :3
			,[`input[data-form="${this.name}"][data-percent-type="text-percent-00"]`]    :2
			,[`input[data-form="${this.name}"][data-percent-type="text-percent-0"]`]     :1
			,[`input[data-form="${this.name}"][data-percent-type="text-percent-whole"]`] :0
		}

		for(let selector in selectors) {
			const $elements = $(selector);
			const decimalPlaces = selectors[selector];

			$elements.each(function() {
				const an = AutoNumeric.getAutoNumericElement(this);
				if (an) {
					an.remove();    // ← removes listeners, drops the instance
					an.unformat();  // if you want raw number before re-init
					// or:        an.wipe();      // clears value to empty string
				}
			});

			if($(selector).length > 0) {
				AutoNumeric.multiple(selector, {
					 currencySymbol: '%'
					,currencySymbolPlacement: 's'  // 's' for suffix (e.g., 12.34 %); use 'p' for prefix if preferred
					,rawValueDivisor: '100'        // Critical for clean percentage handling
					,decimalPlaces: decimalPlaces
					,digitGroupSeparator: ','
					,decimalCharacter: '.'
				});
			}
		}
	}

	applyDatePicker()
	{
		let selector = `input[data-form="${this.name}"][data-datetime-type="text-date"]`;

		$(selector).datepicker({
			 startView: 1
			,format: 'yyyy-mm-dd'
			,autoclose: true
			,todayHighlight: true
			,language: 'en-GB' // if using a locale
		});
	}




	applyCollapsingLabels()
	{
		let selCollapseAll     = `form#form-${this.name} [data-all-collapse="true"]`;
		let selCollapeElements = `form#form-${this.name} div[data-is-collapsable="true"]`;
		let self               = this;

		let collapser = function({target, collapse=null} = {}){
			let label = $(target).children('div.form-hblock-label');
			let icon  = label.children('span.material-icons');
			let isCollapsed;
			if(collapse === true || (collapse === null && icon.text() == 'remove_circle_outline')) {
				icon.text('add_circle_outline')
				isCollapsed = true;
			}
			else {
				icon.text('remove_circle_outline');
				isCollapsed = false;
			}

			let elements = $(target).children().not('div.form-hblock-label');
			elements.each(function(){
				let element = $(this);
				// if(element.hasClass('d-none')) {
				if(isCollapsed === false) {
					element.removeClass('d-none');

					// Apply any sizing/adjustments when the display is no longer none;
					self.postRedraw();
				}
				else {
					element.addClass('d-none');
				}
			})
		}

		$(selCollapeElements).each(function(){
			let label     = $(this).children('div.form-hblock-label');
			let elements  = $(this).children().not('div.form-hblock-label');
			let labelText = label.text();
			label.empty().text(labelText).append('<span class="material-icons fs-6 m-0 ps-2 text-primary"></span>');

			if($(this).hasClass('collapsed')) {
				label.children('span.material-icons').text('add_circle_outline');
				elements.addClass('d-none');
			}
			else {
				label.children('span.material-icons').text('remove_circle_outline');
				elements.removeClass('d-none');
			}

			label.on('click', function(){
				collapser({target:$(this).parent()});
			});
		});


		$(selCollapseAll).each(function(){
			let labelText = $(this).text();
			let aCollapseAll = $('<a class="collapse-all ms-2 clickme" data-tippy-content="Collapse All"><span class="material-icons">arrow_circle_up</span></a>');
			let aExpandAll = $('<a class="expand-all ms-2 clickme" data-tippy-content="Expand All"><span class="material-icons">arrow_circle_down</span></a>');
			$(this).empty().append($('<span>').text(labelText)).append(aCollapseAll).append(aExpandAll);
			let collapseAllBtn = $(this).find('a.collapse-all');
			let expandAllBtn = $(this).find('a.expand-all');
			collapseAllBtn.on('click', function(){
				$(selCollapeElements).each(function(){
					collapser({target:$(this), collapse:true});
				});
			});
			expandAllBtn.on('click', function(){
				$(selCollapeElements).each(function(){
					collapser({target:$(this), collapse:false});
				});
			});

		})
	}



	applyCheckboxMultiFormat() {
		const selector = `form#form-${this.name} div[data-checkbox-multi="true"]`;

		$(selector).each(function () {
			const $container = $(this);
			const $labels = $container.children('label');

			// 1. Equalize label widths (only needs to run once)
			$labels.css('width', 'auto'); // reset to natural width
			const maxWidth = Math.max(...$labels.map((i, el) => $(el).width()).get());
			$labels.width(maxWidth);


			// 2. Update visual state based on current checkbox value
			$labels.each(function () {
				const $label = $(this);
				const $checkbox = $label.find('input[type="checkbox"]'); // more robust than .children()

				// Remove any previous handler (prevents stacking)
				$label.off('click.checkbox-bold');

				// Set initial bold state
				$label.toggleClass('fw-bold', $checkbox.prop('checked'));

				// Add single handler (namespaced so we can remove it cleanly later)
				$label.on('click.checkbox-bold', function () {
					// Use setTimeout(0) because checked state updates *after* click
					setTimeout(() => {
						$label.toggleClass('fw-bold', $checkbox.prop('checked'));
					}, 0);
				});
			});
		});
	}



	applyShortLink() {
		const selector = `form#form-${this.name} div[data-short-link="true"]`;
		$(selector).each(function () {
			let topRef    = $(this);
			let field     = topRef.find('input[type="hidden"');
			let code      = field.val();
			let editLabel = topRef.attr('data-short-link-edit-label');
			let buttons   = topRef.find('button');
			let btnAdd    = topRef.find('button.add-link');
			let btnEdit   = topRef.find('button.edit-link');
			let btnDelete = topRef.find('button.delete-link');
			let noLinkSet = topRef.find('span.no-link-set');
			let aLink     = topRef.find('a.short-link');
			let copyLink  = topRef.find('span.copy-link');

			code = (!code) ? '-new-' : code;

			buttons.addClass('d-none');
			noLinkSet.addClass('d-none');
			aLink.addClass('d-none');

			let buildURL = function(type){
				return topRef.attr(`data-short-link-${type}-url`).replace('phCode', code);
			}

			let buildBtnEdit = function(){
				btnEdit.off('click').on('click', function(e){
					e.preventDefault();
					MessageBox.showForm(buildURL('edit'), editLabel, e.target);
				});
			}

			let bindAdd = function(e) {
				e.preventDefault();
				MessageBox.showForm(buildURL('edit'), editLabel, e.target);
			}

			let noCodeFound = function(e) {
				btnAdd.removeClass('d-none');
				btnAdd.off('click').on('click', bindAdd);
			}

			let codeFound = function() {
				//
				$.get(buildURL('get'), function(response){
					//
					if(response['status'] == 'error') {
						noCodeFound();
					}
					else {
						buildBtnEdit();
						btnEdit.removeClass('d-none');

						btnDelete.removeClass('d-none').off('click').on('click', function(e){
							e.preventDefault();
							field.val(null).trigger('change');
							btnAdd.removeClass('d-none').off('click').on('click', bindAdd);
							btnEdit.addClass('d-none');
							btnDelete.addClass('d-none');
							noLinkSet.removeClass('d-none');
							aLink.addClass('d-none');
							copyLink.addClass('d-none');
						});

						let linkURL = response['url'];
						if (linkURL && (linkURL.startsWith('http://') || linkURL.startsWith('https://') || linkURL.startsWith('/'))) {
							aLink.attr('href', linkURL).text(linkURL).removeClass('d-none');
						} else {
							aLink.removeAttr('href').text(linkURL || '').removeClass('d-none');
						}
						copyLink.removeClass('d-none').off('click').on('click', function(){
							navigator.clipboard.writeText(linkURL).then(() => {
								// Optional: visual feedback
								const $this = $(this);
								const originalText = $this.text();
								$this.text('(copied!)').addClass('fst-italic');
								setTimeout(() => {
									$this.text(originalText).removeClass('fst-italic');
								}, 1000);
							});
						});
					}
				});
			}

			if(!code) {
				noCodeFound();
			}
			else {
				codeFound();
			}
		});
	}


	applyEditNotices()
	{
		const selector = `form#form-${this.name}`;

		$(selector).find('input, select, textarea, [contenteditable="true"]').each(function() {
			const $field = $(this);
			const $label = $(this).closest('div.ebr-form-element').find('label.ebr-form-label');
			$(this).on('change input', function(){
				// $field.addClass('form-input-is-modified');
				$label.addClass('form-label-is-modified');
			})
		});
	}




	formSubmitSuccess(response)
	{
		if('id_created' in response) {
			$(`input#form-${this.name}-id`).val(response['id_created']);
			let currentUrl = window.location.href;
			let newUrl = currentUrl.replace('-new-', response['id_created']);
			if (newUrl !== currentUrl) {  // Optional: only if it actually changes
				history.replaceState(null, '', newUrl);
			}
		}

		$(`div[data-${this.name}-form-type="element-error"]`).html(null).addClass('d-none');
		$(`label.is-invalid,is-valid`).removeClass().addClass('form-label');
		$(`div.form-${this.name}-element`).removeClass().addClass(`form-${this.name}-element`);



		for(var field in response['fields_updated']) {
			let label = $(`label#${this.name}-${field}`);

			label.removeClass('is-invalid text-danger fst-italic').removeClass('form-label-is-modified').addClass('text-success fw-bold');
			setTimeout(function(){label.removeClass('text-success fw-bold')}, 1000);
		}


		let mbErrors = [];
		for(var field in response['fields_with_error']) {


			let errorMessage   = response['fields_with_error'][field]['message'];
			mbErrors.push(errorMessage)

			let labelElement   = $(`label#${this.name}-${field}`);
			let formElement    = $(`div#form-element-${this.name}-${field}`);
			let messageElement = formElement.nextAll(`div[data-${this.name}-form-type="element-error"]`).first();

			//

			labelElement.addClass('is-invalid text-danger fw-bold fst-italic');
			formElement.addClass('border border-danger');
			messageElement.removeClass('d-none').text(errorMessage);
		}

		if(response['status'] == 'error') {
			$(`span#${this.name}-error-message`).text(response['message']).removeClass('d-none');

			if(this.showMessageBoxOnError === true) {
				MessageBox.showError(mbErrors);
			}
		}
		else {
			$(`span#${this.name}-error-message`).html(null).addClass('d-none');

			// Remove all is-modified that had no change to the database record
			$(`form#form-${this.name} label.form-label-is-modified`).removeClass('form-label-is-modified');
		}
	}
}









export class EBRFormRender
{
	constructor()
	{
		this.forms = [];
	}

	addForm(form)
	{
		this.forms[form['name']] = form;
	}

	render({names, callback=null} = {})
	{
		this._render({names:this._parseNames(names), callback:callback, callPostRender:true});
	}

	redraw({names, callback=null} = {})
	{
		this._render({names:this._parseNames(names), callback:callback, callPostRender:false});
	}

	_parseNames(input)
	{

		let options;

		if (typeof input === 'string') {
			options = { names: [input] };
		} else if (Array.isArray(input)) {
			options = { names: input };
		} else if (input && typeof input === 'object') {
			options = { names: input.names || [] };
		} else {
			options = { names: [] };
		}
		const { names = [] } = options;

		return names;
	}

	async _render({names, callPostRender=true, callback=null}) {
		const promises      = [];  // Collect promises for all insertions
		const redrawObjects =  [];
		// for(const formObj of this.forms) {

		for(const formName in this.forms) {
			const formObj = this.forms[formName];

			for(const name in formObj.components) {

				const component = formObj.components[name];
				if (names.length > 0 && !names.includes(component.name)) {
					continue;
				}
				redrawObjects.push(formObj);

				const url = component.sourceURL();

				try {
					const content = await $.get(url);

					// Insert content and wait for the DOM update to be "committed"
					const insertPromise = new Promise(resolve => {
						$(component.targetSelector).html(content);
						// setTimeout(0) pushes resolution to next microtask queue
						setTimeout(resolve, 0);
					});

					promises.push(insertPromise);
				} catch (err) {
					console.error(`Failed to load ${url}`, err);
				}
			}
		}

		// Wait for all DOM insertions to complete
		await Promise.all(promises);

		for(const formObj of redrawObjects) {
			if (callPostRender === true) {
				formObj.postRender();
			}
			else {
				formObj.postRedraw();
			}
		}

		if(typeof callback == 'function') {
			return callback();
		}
		return;
	}
}