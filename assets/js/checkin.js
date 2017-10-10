window.pf = window.pf || {};

window.pf.checkIn = {
	frame: {},
	iteration: 0,
	sheet: "1YCzGHTdubM37kGlubJRsBzRaZCXxmN-oVJS-dDSt0Q8",
	form: "1FAIpQLSfBCTuRhZ95glaa-748OyTfjYzU_uM-1q0QzS_sFZ13z5BJTg",
	values: function(){
		return {
			"entry.133052561": document.getElementById('wp-admin-bar-site-name').innerText.trim(),
			"entry.638951200": location.host,
			"entry.63627490": '1',
			"fvv": "1"
		}
	},
	submitFrame: function(parentFrame, path, params, method){
		method = method || "post"; // Set method to post by default if not specified.
		// The rest of this code assumes you are not using a library.
		// It can be made less wordy if you use one.
		var formEl = parentFrame.createElement("form");
		formEl.setAttribute("method", method);
		formEl.setAttribute("action", path);

		for(var key in params) {
			if(params.hasOwnProperty(key)) {
				var hiddenField = document.createElement("input");
				hiddenField.setAttribute("type", "text");
				hiddenField.setAttribute("name", key);
				hiddenField.setAttribute("value", params[key]);

				formEl.appendChild(hiddenField);
			 }
		}

		parentFrame.body.appendChild(formEl);
		formEl.submit();
	},
	initCheckin: function(){
		if ( 0 === this.iteration){
			var values = this.values();
			this.submitFrame(this.frame.contentDocument, this.googleForm, values, "POST");
			//this.frame.document.getElementsByTagName('input')[0].value = values.siteName;
			//this.frame.document.getElementsByTagName('input')[1].value = values.siteURL;
			//this.frame.document.getElementsByTagName('input')[2].value = values.scriptVer;
			//this.frame.document.querySelectorAll('[role="button"]')[1].click();
			this.iteration = this.iteration+1;
		}
	},
	activate: function(){
		//var tag = document.createElement("script");
		//tag.src = "https://apis.google.com/js/api.js";
		//tag.setAttribute('onload', "window.pf.checkIn.initCheckin()");
		//var docHead = document.getElementsByTagName("head");
		var tag = document.createElement('iframe');
		this.googleForm = 'http://docs.google.com/forms/d/e/'+this.form+'/formResponse';
		tag.setAttribute('onload', "window.pf.checkIn.initCheckin()");
		tag.setAttribute('id', 'iframe-checkin');
		//tag.setAttribute('style', 'display:none;');
		//tag.name('iframe-checkin');
		tag.style.display = 'none';
		this.frame = tag;
		window.addEventListener("load", function () {
			document.body.appendChild(tag);
		});
		jQuery.post(ajaxurl, {
			action: 'pf_checked_in'
		},
		function (response) {
			console.log( 'pf install logged', response );
		});
	}
};

if ( pf_checkin.active ){
	window.pf.checkIn.activate();
}
