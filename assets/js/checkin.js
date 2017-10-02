var SHEET = "1YCzGHTdubM37kGlubJRsBzRaZCXxmN-oVJS-dDSt0Q8";
var FORM = "1FAIpQLSfBCTuRhZ95glaa-748OyTfjYzU_uM-1q0QzS_sFZ13z5BJTg";
window.pf = window.pf || {};
//var tag = document.createElement("script");
//tag.src = "https://apis.google.com/js/api.js";
//tag.setAttribute('onload', "window.pf.checkIn.initCheckin()");
//var docHead = document.getElementsByTagName("head");
var tag = document.createElement('iframe');
var googleForm = 'http://docs.google.com/forms/d/e/'+FORM+'/formResponse';
tag.setAttribute('onload', "window.pf.checkIn.initCheckin()");
tag.setAttribute('id', 'iframe-checkin');
//tag.setAttribute('style', 'display:none;');
//tag.name('iframe-checkin');
tag.style.display = 'none';

window.pf.checkIn = {
	frame: tag,
	iteration: 0,
	values: function(){
		return {
			"entry.133052561": document.getElementById('wp-admin-bar-site-name').innerText,
			"entry.638951200": location.host,
			"entry.63627490": '1',
			"fvv": "1"
		}
	},
	submitFrame: function(parentFrame, path, params, method){
		method = method || "post"; // Set method to post by default if not specified.
		// The rest of this code assumes you are not using a library.
		// It can be made less wordy if you use one.
		var form = parentFrame.createElement("form");
		form.setAttribute("method", method);
		form.setAttribute("action", path);

		for(var key in params) {
			if(params.hasOwnProperty(key)) {
				var hiddenField = document.createElement("input");
				hiddenField.setAttribute("type", "text");
				hiddenField.setAttribute("name", key);
				hiddenField.setAttribute("value", params[key]);

				form.appendChild(hiddenField);
			 }
		}

		parentFrame.body.appendChild(form);
		form.submit();
	},
	initCheckin: function(){
		if ( 0 === this.iteration){
			var values = this.values();
			this.submitFrame(this.frame.contentDocument, googleForm, values, "POST");
			//this.frame.document.getElementsByTagName('input')[0].value = values.siteName;
			//this.frame.document.getElementsByTagName('input')[1].value = values.siteURL;
			//this.frame.document.getElementsByTagName('input')[2].value = values.scriptVer;
			//this.frame.document.querySelectorAll('[role="button"]')[1].click();
			this.iteration = this.iteration+1;
		}
	},

}

window.addEventListener("load", function () {
	document.body.appendChild(tag);
});
