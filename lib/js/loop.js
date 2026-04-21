/**
 * Handles looping at set intervals for various appliction needs.
 *
 * Javascript doesn't have a native way to set a looping sequence with
 * a delayed time in between each call.  `setTimeout()` only executes
 * one time at the desired delay.  This simple class gets that problem
 * solved.
 *
 * @class
 *
 * @param {string} name - The name of the loop being created (for logging/debugging)
 * @param {function} func - The function that will be executed on each loop interval
 * @param {number} wait - The milliseconds (_1000 default_) delay between each scheduled execution
 * @param {number} maxLoop - The maximum number of executions (_1000 default_)
 */
export class Loop {

	constructor(name, func, wait=1000, maxLoop=1000)
	{
		this.log       = false;
		this.name      = name;
		this.loopCount = 1;
		this.maxLoop   = maxLoop;
		this.wait      = wait;
		this.func      = func;
		const self     = this;
		this.loopId    = setTimeout(function(){
			self._loop(func, wait, self.loopId);
		}, 0);
	}

	_loop()
	{
		if(this.loopId == null || this.loopCount > this.maxLoop) {
			this.clear();
			return;
		}
		if(this.log == true) {console.log('START:'+this.name+' LOOP:'+this.loopCount+' MAX LOOP:'+this.maxLoop)};
		this.func();
		if(this.log == true) {console.log('END:'+this.name)};
		this.loopCount++;
		setTimeout(this._loop.bind(this), this.wait);
	}

	/**
	 * Clears out the loop's future executions from the point called forward
	 *
	 * @public
	 */
	clear()
	{
		this.loopId = null;
	}
}
