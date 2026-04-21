/**
 * A simple base class used to control page components and rendering.
 *
 * @class
 * @abstract
 *
 * @property {Array} hideFields - App-Global fields that can be hidden when inheriting/implementing the App Class
 * @property {Array} filterParts - An array of filters used to create {@link App#filter}
 * @property {string} filter - For simple filtering, this holds a pipe delimited list of filters
 * @property {Array} loadObjects - An array of objects containing `load()` and `reload()` methods that can be collectively controlled by the App class.
 */
export class BSApp
{
	constructor()
	{
		this.hideFields = [];

		this.filterParts = [];
		this.filter = '*';

		this.loadObjects = [];

		if(this.init && typeof this.init === 'function') {
			this.init();
		}
	}


	/**
	 * Resets the basic filtering to '*' (_no filters_) and reloads the app objects in {@link App#loadObjects}
	 *
	 * @param {boolean} reload - If true (_default_) the {@link App#reload} method is called
	 *
	 * @public
	 */
	resetFilter(reload = true)
	{
		this.filterParts = [];
		this.filter = '*';
		if(reload === true) {
			this.reload();
		}
	}


	/**
	 * Adds a filter option to {@link App#filterParts} and updates {@link App#filter}
	 *
	 * @param {string} key - The name of the filter
	 * @param {string|number} value - The filter value
	 * @param {boolean} reload - If true (_default_) the {@link App#reload} method is called
	 *
	 * @public
	 */
	setFilter(key, value, reload=true)
	{
		if(this.filterParts.length < key+1) {
			for(let i=0; i<key; i++) {
				if(i in this.filterParts) {
					continue;
				}
				this.filterParts[i] = '*';
			}
		}

		if(this.filterParts[key] == value && reload === true) {
			this.filterParts[key] = '*';
		}
		else {
			this.filterParts[key] = value;
		}

		this.filter = this.filterParts.join('|');
		if(reload === true) {
			this.reload();
		}
	}


	/**
	 * Loads all of the objects in {@link App#loadObjects}
	 *
	 * @public
	 */
	load()
	{
		$('body').css('cursor', 'progress');
		Promise.all(
			this.loadObjects.map(
				tbl => tbl.load({
					withIndicator: true
				})
			)
		)
		.then(() => {
			$('body').css('cursor', 'auto');
		})
	}

	/**
	 * Reloads all objects found in {@link App.loadObjects}
	 *
	 * While this is loading, the cursor type is set to "progress"
	 * to indicate to the user that background data is being processed.
	 *
	 * @public
	 */
	reload()
	{
        $('body').css('cursor', 'progress');
        const loadPromises = [];
        for (let key in this.loadObjects) {
            loadPromises.push(this.loadObjects[key].reload());
        }
        Promise.all(loadPromises).finally(
            () => {
                $('body').css('cursor', 'auto');
            }
        )
	}

	/**
	 * Applies the logic of {@link NumberFormat.USD} and applies the `money_value` class to all css selectors passed in.
	 *
	 * @param {Array.<string>} cssSelector - A standard css selector (_i.e. `span.money`_)
	 * @param {boolean} showCents - When true (default), cents are displayed. Otherwise, only the dollar amount is rendered
	 */
	applyUSD(cssSelector, showCents = true) {
	 	$(cssSelector).each(function(){
			var amount = $(this).text();
			$(this).attr('money_value', amount)
				.addClass('money text-right')
				.text(NumberFormat.USD(amount, showCents));
		});
		NumberFormat.init();
	 }


	/**
	 * Applies the logic of {@link NumberFormat.USDK} and  applies the `money_value` class to all css selectors passed in.
	 *
	 * @param {Array.<string>} cssSelector - A standard css selector (_i.e. `span.money`_)
	 */
	applyUSDK(cssSelector) {
	 	$(cssSelector).each(function(){
			var amount = $(this).text();
			$(this).attr('money_value', amount)
				.addClass('money text-right')
				.text(NumberFormat.USDK(amount));
		});
		NumberFormat.init();
	 }

	/**
	 * Applies the logic of {@link NumberFormat.Percent0} and  applies the `percent` class to all css selectors passed in.
	 *
	 * @param {Array.<string>} cssSelector - A standard css selector (_i.e. `span.percent`_)
	 */
	applyPercent(cssSelector, decimal=0) {
		$(cssSelector).each(function(){
			var ratio = $(this).text();
			$(this).attr('performance_value', ratio)
				.addClass('percent')
				.text(NumberFormat.Percent(ratio, decimal));
		});
		NumberFormat.init();
	}

	applyPercent0(cssSelector) {
		this.applyPercent(cssSelector);
	}


	/**
	 * Applies the logic of {@link NumberFormat.Percent0} and applies the `performance` class to all css selectors passed in.
	 *
	 * @param {Array.<string>} cssSelector - A standard css selector (_i.e. `span.performance`_)
	 */
	applyPerformancePercent0(cssSelector) {
		$(cssSelector).each(function(){
			var ratio = $(this).text();
			$(this).attr('performance_value', ratio)
				.addClass('performance')
				.text(NumberFormat.Percent0(ratio));
		});
		NumberFormat.init();
	}
}
