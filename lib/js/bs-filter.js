
export class BSFilter
{
	constructor({name, eventId='EBRFilterUpdated', tagTemplate='div#ebr-filter-template', collapsed=true})
	{
		this.formCode  = name;
		this.md5Id     = null;
		this.eventId   = eventId;
		this.formId    = 'form-filter-'+name;
		this.container = '#ebr-filter-'+name;

		this.template            = $($('template#ebr-filter')[0].content);
		this.tplRoundTo1K        = this.template.children('div#roundTo1K-container').clone();
		this.tplEBRShare        = this.template.children('div#EBRShare-container').clone();
		this.tplExcludeDailyData = this.template.children('div#excludeDailyData-container').clone();
		this.tplSubmitButon      = this.template.children('div#submitButton-container').clone();
		this.tplHiddenInput      = this.template.children('div#hiddenInput-container').clone();
		this.tplSearchbox        = this.template.children('div#searchbox-container').clone();
		this.tplCheckbox         = this.template.children('div#checkbox-container').clone();
		this.tplSelect           = this.template.children('div#select-container').clone();
		this.tplSliderRange      = this.template.children('div#sliderRange-container').clone();

		this.sourceHeaders   = {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
			'Accept':       'application/json'
		};


		this.objFilter = this.template.children(tagTemplate).clone();
		this.objFilter
			.children('form')
			.attr('id', this.formId)
			.find('div.switches')
			.html($(this.container).children('div.switches').html())
			.attr('id', '');

		$(this.container)
			.html(this.objFilter.removeClass('d-none'))
			.removeClass('d-none');

		if(typeof this.downloadURL === 'function') {
			let $this = this;
			this.objFilter
				.children('div.download')
				.removeClass('d-none')
				.find('button.download')
				.off('click') // Remove previously bound clicks
				.addClass('clickme')
				.removeClass('btn-disabled')
				.click(function(e) {
					e.preventDefault();

					$('button#download-btn>span#download-label').addClass('fst-italic');
					$('button#download-btn>span#download-save').addClass('d-none');
					$('button#download-btn>div#download-in-progress').removeClass('d-none');

					$.ajax({
						url       : $this.downloadURL(),
						method    : 'POST',
						data      : $('form#' + $this.formId).serialize(), // Serialize form data
						xhrFields : {
							responseType: 'blob' // Expect a binary response (ZIP file)
						},
						success : function (data, status, xhr) {
							// Get the filename from the Content-Disposition header
							var filename = 'downloaded_file.zip'; // Fallback filename
							var disposition = xhr.getResponseHeader('Content-Disposition');
							if (disposition && disposition.indexOf('attachment') !== -1) {
								var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
								var matches = filenameRegex.exec(disposition);
								if (matches != null && matches[1]) {
									filename = matches[1].replace(/['"]/g, ''); // Remove quotes if present
								}
							}

							// Create a downloadable link for the ZIP file
							var blob = new Blob([data], { type: xhr.getResponseHeader('Content-Type') });
							var link = document.createElement('a');
							link.href = window.URL.createObjectURL(blob);
							link.download = filename; // Use server-provided filename or fallback
							document.body.appendChild(link);
							link.click();
							document.body.removeChild(link); // Clean up
						},
						error : function (xhr, status, error) {
							console.error('Download failed:', error);
							// Optionally handle errors (e.g., show an error message)
						},
						complete : function () {
							// Hide spinner and show save icon regardless of success or failure
							$('button#download-btn>span#download-label').removeClass('fst-italic');
							$('button#download-btn>div#download-in-progress').addClass('d-none');
							$('button#download-btn>span#download-save').removeClass('d-none');
						}
					});
				});
		}

		// Filter Toggle
		let filterToggle = $(this.container+' div.filter-toggle');

		if(collapsed === false && $(this.container+' div.switches').is(':visible') === false) {
			$(this.container+' div.switches').attr('style', '');
			filterToggle.find('span.material-icons').text('expand_more');
		}

		filterToggle.on('click', function(e) {
			e.preventDefault();
			$(window.filter.container+' div.switches').slideToggle('slow', function(){
				let isVisible = $(this).is(':visible');
				console.log(filterToggle.find('span material-icons'));
				if(isVisible === true) {
					filterToggle.find('span.material-icons').text('expand_more')
				}
				else {
					filterToggle.find('span.material-icons').text('expand_less');
				}
			});
		});
	}


	showDownload()
	{
		if(typeof this.downloadURL === 'function') {
			this.objFilter.children('div.download').removeClass('d-none');
		}
	}
	hideDownload()
	{
		if(typeof this.downloadURL === 'function') {
			this.objFilter.children('div.download').addClass('d-none');
		}
	}


	populateForm(serializedData, callback=false) {
		let $this = this;
		let formData = {};
		$.each(serializedData.split('&'), function(index, pair) {
			let keyValue = pair.split('=');
			let key      = decodeURIComponent(keyValue[0]);
			let value    = decodeURIComponent(keyValue[1] || '');
			if(key != '_token') {
				// Handle arrays (e.g., for multi-select or checkboxes)
				if (formData[key]) {
					if (!Array.isArray(formData[key])) {
						formData[key] = [formData[key]];
					}
					formData[key].push(value);
				} else {
					formData[key] = value;
				}
			}
		});

		$('form#'+$this.formId).find(':input').each(function() {
			let $field    = $(this);
			let fieldName = $field.attr('name');
			// console.log({fieldName:fieldName, formData_fieldName:formData[fieldName], fieldObj:this});
			if (!formData[fieldName]) return;


			let fieldType = $field.attr('type');
			if (fieldType === 'checkbox') {
				// Handle checkboxes
				$field.prop('checked', formData[fieldName].includes($field.val()));
			} else if(fieldType === 'radio') {
				// Hanlde radios
				$field.prop('checked', formData[fieldName] === $field.val());
			} else if ($field.is('select[multiple]')) {
				// Handle multi-select
				let values = Array.isArray(formData[fieldName]) ? formData[fieldName] : [formData[fieldName]];
				$field.val(values);
			} else {
				// Handle text inputs, single-select, etc.
				$field.val(formData[fieldName]).trigger('valueChanged').trigger('change');
			}
		});

		if(typeof callback == 'function') {
			callback();
		}
	}


	serialized()
	{
		let search4md5 = /\/[A-Z0-9]{32}$/;
		let base_url   = window.location.href.replace(search4md5, '');
		let fser       = $('form#'+this.formId+' :input:not([name="_token"])').serialize();
		let fb64       = btoa(fser);
		let md5_id     = MD5(base_url+fb64).toUpperCase();
		let new_url    = base_url+'/'+md5_id;
		let allInputs  = [];

		this.md5Id = md5_id;

		$('form#'+this.formId+' :input:not([name="_token"])').each(function(){
			allInputs.push($(this).attr('name'));
		})

		let self = this;

		$.ajax({
			 url     : '/utility/filter-cache-update'
			,type    : 'POST'
			,headers : this.sourceHeaders
			,data    : {
				 code     : this.formCode
				,fb64     : fb64
				,base_url : base_url
				,md5_id   : md5_id
				,inputs   : allInputs
			}
			,success : function(response){
				history.replaceState({ page: 'filter' }, '', new_url);

				let bookmarkName   = response['bookmark-name'];
				let bookmarkList   = response['bookmark-list'];
				let bookmarkInput  = $(self.container + ' div#bookmark-name>div>input[name="bookmark-name"]');
				let bookmarkSelect = $(self.container + ' div#bookmark-select>select[name="bookmark-select"]');
				let spanDelete     = $(self.container + ' span#delete-bookmark');

				bookmarkInput.val(bookmarkName);

				bookmarkInput.on('keypress', function(e) {
					if (e.key === 'Enter' || e.keyCode === 13) {
						e.preventDefault();
						return false;
					}
				});

				// Encapuslate the UI changes when a bookmark is present
				let displayBookmark = function() {

					if(bookmarkInput.val()) {
						spanDelete.removeClass('d-none');
					}
					else {
						spanDelete.addClass('d-none');
					}
				}

				let refreshBookmarkSelect = function(bookmarkList) {
					bookmarkList = {
						 0 : "Bookmarks"
						,...bookmarkList
					};

					bookmarkSelect.empty();
					Object.entries(bookmarkList).forEach(([key, text]) => {
						$('<option>').val(key).text(text).appendTo(bookmarkSelect);
					});
				}

				spanDelete.on('click', function(){
					bookmarkInput.val(null).trigger('input');
					displayBookmark();
				})

				refreshBookmarkSelect(bookmarkList);
				displayBookmark(bookmarkName);

				bookmarkInput.on('input', self.debounce(function(){
					$.ajax({
						 url     : `/utility/filter-bookmark/save/${self.formCode}/${self.md5Id}`
						,type    : 'POST'
						,headers : self.sourceHeaders
						,data    : {
							bookmarkName : $(this).val()
						}
						,success : function(response){
							refreshBookmarkSelect(response['bookmark-list'])
							displayBookmark();
						}
						,error   : function(error) {
							console.error('Error:', error);
						}
					})
				}));

				bookmarkInput.on('blur', self.debounce(function() {
					displayBookmark()
				}));


				bookmarkSelect.off('change').on('change', function(){
					let bookmarkId = $(this).val();
					let cacheMD5Id = null;
					$.get(`/utility/filter-bookmark/cache-md5/${bookmarkId}`, function(response){
						console.log(response)
						cacheMD5Id = response;
						console.log(cacheMD5Id)
						window.location.href = `${base_url}/${cacheMD5Id}`;
					});
				})


				// console.log({fb64:fb64, base_url:base_url, md5_id:md5_id})
			}
			,error   : function(error) {
				console.error("Error:", error);
			}
		});

		return $('form#'+this.formId).serialize();
	}



	debounce(func, delay = 1000) {
		let timeout;
		return function(...args) {
			clearTimeout(timeout);
			timeout = setTimeout(() => func.apply(this, args), delay);
		};
	}



	submitButton({name, label="Search", withReset=false, onResetCallback=false, onSubmitCallback=false} = {})
	{
		let self = this;
		let sbc  = this.tplSubmitButon.clone()
			.attr('id', null)
			.addClass('filter-div-'+name)
			.removeClass('d-none');
		let submitBtn = sbc.children('button.submit').removeClass('d-none').text(label);
		let resetBtn  = sbc.children('button.reset');

		$('div#filter-'+name).append(sbc);

		submitBtn.on('click', function(e){
			e.preventDefault();
			$(document).trigger(self.eventId, {filterId:self.formId, triggerElement:$(this)});
			if(typeof onSubmitCallback == 'function') {
				onSubmitCallback();
			}
		})

		if(withReset == true) {
			resetBtn.removeClass('d-none');
			resetBtn.on('click', function(e){
				e.preventDefault();
				$('form#'+self.formId).trigger('reset', {triggerElement:$(this)});
				$(document).trigger(self.eventId, {filterId:self.formId});
				if(typeof onResetCallback == 'function') {
					onResetCallback();
				}
			});
		}
	}


	hiddenInput({name, value, onchange=null} = {})
	{
		let self = this;
		let hic  = this.tplHiddenInput.clone()
			.attr('id', null)
			.addClass('filter-div-'+name);
		$('div#filter-'+name).append(hic);

		let hiddenInput = hic.children('input[type="hidden"]')
		hiddenInput.attr({
			 name  : 'filter_'+name
			,value : value
		});

		if(typeof onchange === 'function') {
			hiddenInput.on('valueChanged', onchange); // Requires a manual .trigger('valueChanged') to be called
		}
	}


	searchbox({name, value=null, onChangeReload=true} = {})
	{
		let self = this;
		let sbc = self.tplSearchbox.clone()
			.attr('id', null)
			.addClass('filter-div-'+name)
			.removeClass('d-none');

		let searchbox = sbc.children('input[type="text"]');

		$('div#filter-'+name).append(sbc);
		searchbox.removeClass('d-none')
			.attr({
				 name  : 'filter_'+name
				,value : value
			});
		searchbox.on('keypress', function(e) {
			if (e.key === 'Enter' || e.keyCode === 13) {
				e.preventDefault();
				$(document).trigger(self.eventId, {filterId:self.formId});

				return false;
			}
		});
		searchbox.on('change', function() {
			if(onChangeReload === true) {
				$(document).trigger(self.eventId, {filterId:self.formId});
			}
		});
	}


	roundTo1K({name, label="Round to $1K", checked=false} = {})
	{
		let self = this;
		let r2c  = this.tplRoundTo1K.clone()
			.attr('id', null)
			.addClass('filter-div-'+name)
			.removeClass('d-none');
		let toggle = r2c.find('input#round-to-nearest-1k')
		toggle.attr({
				 id      : 'filter_'+name
				,checked : checked
				,name    : 'filter_'+name+'[]'
		});

		$('div#filter-'+name).append(r2c);

		toggle.on('change', function(e){
			e.preventDefault();
			$(document).trigger(self.eventId, {filterId:self.formId});
		});
	}


	ebrShare({name, label="EBR Share", checked=true} = {})
	{
		let self = this;
		let r2c  = this.tplEBRShare.clone()
			.attr('id', null)
			.addClass('filter-div-'+name)
			.removeClass('d-none');
		let toggle = r2c.find('input#ebr-share')
		toggle.attr({
				 id      : 'filter_'+name
				,checked : checked
				,name    : 'filter_'+name+'[]'
		});

		$('div#filter-'+name).append(r2c);

		toggle.on('change', function(e){
			e.preventDefault();
			$(document).trigger(self.eventId, {filterId:self.formId});
		});
	}


	excludeDailyData({name, label="Exclude Daily Data", checked=false} = {})
	{
		let self = this;
		let r2c  = this.tplExcludeDailyData.clone()
			.attr('id', null)
			.addClass('filter-div-'+name)
			.removeClass('d-none');
		let toggle = r2c.find('input#exclude-daily-data')
		toggle.attr({
				 id      : 'filter_'+name
				,checked : checked
				,name    : 'filter_'+name+'[]'
		});

		$('div#filter-'+name).append(r2c);

		toggle.on('change', function(e){
			e.preventDefault();
			$(document).trigger(self.eventId, {filterId:self.formId});
		});
	}


	checkbox({
		 name
		,options
		,selected       = null
		,checkAll       = false
		,onChangeReload = true
	} = {})
	{
		let self = this;

		if(typeof selected === 'string' || typeof seleted === 'number') {
			selected = [selected];
		}

		$.each(options, function(checkValue, title) {

			let display = (title.length > 33) ? title.slice(0, 35)+'...' : title;
			let cbc     = self.tplCheckbox.clone()
				.attr("id", null)
				.addClass('filter-div-'+name);
			let label = cbc.children('label');
			let checkbox = label.children('input');
			checkbox.attr({
				 type  : 'checkbox'
				,id    : 'filter_'+name+'_'+checkValue
				,value : checkValue
				,name  : 'filter_'+name+'[]'
				,title : title
			});
			label.attr('title', checkValue);
			label.append(document.createTextNode(display));
			$('div#filter-'+name).append(cbc);
			cbc.removeClass('d-none')
			checkbox.on('change', function() {
				if(onChangeReload === true) {
					$(document).trigger(self.eventId, {filterId:self.formId});
				}
			});
		});
	}


	select({
		 name
		,options
		,sort           = 'asc'
		,selected       = null
		,subLabel       = null
		,onChangeReload = true
		,multiple       = false
		,size           = null
		,selectAll      = false
		,emptyDefault   = true
		,allowEmpty     = true
		// ,after          = null
		,onchange       = null
		,emptyOptText        = '-- None Select --'
	} = {})
	{
		let self = this;
		let select = this.tplSelect.children('select').clone();


		select.html('');
		select.attr({
			 id   : name
			,name : 'filter_'+name+'[]'
			,multiple : multiple
		});

		if(typeof selected === 'string' || typeof selected === 'number') {
			selected = [selected];
		}

		if(multiple === true) {
			select.attr({
				 multiple : true
			});
		}

		if(typeof size == 'number' && size > 0) {
			select.attr({
				 size : size
			});
		}

		if(emptyDefault === true || allowEmpty === true) {
			let emptyOpt = $('<option>')
				.attr('value', '___skip___')
				.text(emptyOptText);
			select.append(emptyOpt);
			if(selected === null && emptyDefault === true) {
				emptyOpt.attr('selected', true);
			}
		}

		let optionsArray;
		if(sort === 'asc') {
			optionsArray = Object.entries(options).sort((a, b) => a[1].localeCompare(b[1]));
		}
		else if(sort === 'desc') {
			optionsArray = Object.entries(options).sort((a, b) => b[1].localeCompare(a[1]));
		}
		else {
			optionsArray = Object.entries(options);
		}


		for (let i = 0; i < optionsArray.length; i++) {
			let [selectValue, title] = optionsArray[i];
			let option = self.tplSelect.children('select').children('option').clone();
			option.attr('value', selectValue).text(title);
			if(selectAll === true || (selected !== null && selected.includes(selectValue))) {
				option.attr('selected', true);
			}
			select.append(option);
		}

		$('div#filter-'+name).html(select);
		select.removeClass('d-none');
		if(subLabel) {
			let label = self.tplSelect.children('label');
			label.attr('for', name).text(subLabel);
			$('div#filter-'+name).append(label);
			label.removeClass('d-none');
		}
		if(typeof onchange === 'function') {
			select.on('change', onchange);
		}
		select.on('change', function() {
			if(onChangeReload === true) {
				$(document).trigger(self.eventId, {filterId:self.formId});
			}
		});
	}



	sliderRange({
		 name
		,rangeList=null
		,onChangeReload=true
	} = {})
	{
		let self = this;
		let range = this.tplSliderRange.clone();

		let minValue = 0
		let maxValue;
		let displayValues = [];
		let formValues = [];
		let formKeysByValue = {};
		if(typeof rangeList == 'obj' && Array.isArray(rangeList)) {
			maxValue      = rangeList.length;
			displayValues = rangeList;
			formValues    = rangeList;
		}
		else {
			maxValue = Object.keys(rangeList).length - 1;
			formValues = Object.keys(rangeList);
			for(let i=0; i<formValues.length; i++) {
				displayValues[i] = rangeList[formValues[i]];
				formKeysByValue[formValues[i]] = i;
			}
		}


		let displayMinValue = $(range).children('div.row').children('div[useFor="min"]');
		let displayMaxValue = $(range).children('div.row').children('div[useFor="max"]');
		let inputSlider = $(range).find('input[useFor="slider"]');
		let inputMinValue = $(range).find('input[useFor="min"]');
		let inputMaxValue = $(range).find('input[useFor="max"]');
		let nameMD5 = MD5(name+Date.now());
		displayMinValue.attr({
			 id:'displayMinValue_'+nameMD5
		});
		displayMaxValue.attr({
			 id:'displayMaxValue_'+nameMD5
		});
		inputSlider.attr({
			id:'slider_'+nameMD5
		});
		inputMinValue.attr({
			 id:'minValue_'+nameMD5
			,name:'filter_'+name+'Min'
		});
		inputMaxValue.attr({
			 id:'maxValue_'+nameMD5
			,name:'filter_'+name+'Max'
		});

		let setSelected = function() {
			// let max = formKeysByValue[inputMaxValue.val()];
			// let min = formKeysByValue[inputMinValue.val()];

			// max = (typeof max === undefined) ? (formValues.length - 1) : max;
			// min = (typeof min === undefined) ? 0 : min;

			let min = (formKeysByValue[inputMinValue.val()] === undefined) ? 0 : formKeysByValue[inputMinValue.val()];
			let max = (formKeysByValue[inputMaxValue.val()] === undefined) ? (formValues.length - 1) : formKeysByValue[inputMaxValue.val()];

			// console.log(formKeysByValue[inputMaxValue.val()] === undefined)

			$('input#slider_'+nameMD5).slider('setValue', [min, max]);
			$('div#displayMinValue_'+nameMD5).text(displayValues[min]);
			$('div#displayMaxValue_'+nameMD5).text(displayValues[max]);
		}

		inputMinValue.on('change', setSelected);
		inputMaxValue.on('change', setSelected);



		$('div#filter-'+name).html(range);

		let slider = $('input#slider_'+nameMD5).slider({
			 range: true
			,min: minValue
			,max: maxValue
			,step: 1
			,values: [minValue, maxValue]
		});

		slider.on('slide', function(slideEvt) {
			$('div#displayMinValue_'+nameMD5).text(displayValues[slideEvt.value[0]]);
			$('div#displayMaxValue_'+nameMD5).text(displayValues[slideEvt.value[1]]);
			$("#maxValue").text('$' + slideEvt.value[1]);
			$('input#minValue_'+nameMD5).val(formValues[slideEvt.value[0]]);
			$('input#maxValue_'+nameMD5).val(formValues[slideEvt.value[1]]);

			if(onChangeReload === true) {
				$(document).trigger(self.eventId, {filterId:self.formId});
			}
		});

		// $('.slider.slider-horizontal').css('width', '100% !important;');
		// console.log($('div.slider.slider-horizontal'));

		$('div#displayMinValue_'+nameMD5).text(displayValues[0]);
		$('div#displayMaxValue_'+nameMD5).text(displayValues[Object.keys(displayValues).length - 1]);
		range.removeClass('d-none');
	}

}
