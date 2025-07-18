window.pf = window.pf || {};

var pf_setup = {
	location: 'pf',
	toggler: function (evt, elem, functionOne, functionTwo) {
		evt.preventDefault();
		var toggleState = jQuery(elem).attr('data-toggle-state');
		if (undefined !== toggleState && 'toggled' === toggleState) {
			functionTwo(evt, elem, toggleState);
			jQuery(elem).attr('data-toggle-state', 'off');
		} else {
			functionOne(evt, elem, toggleState);
			jQuery(elem).attr('data-toggle-state', 'toggled');
		}
	},
	endpoint: function () {
		return wp.api.utils.getRootUrl() + 'wp-json/pf/v1/';
	},
	stats: {},
	onWindowLoader: function (callbackName) {
		if (window.attachEvent) {
			window.attachEvent('onload', callbackName);
		} else {
			if (window.onload) {
				var curronload = window.onload;
				var newonload = function (evt) {
					curronload(evt);
					callbackName(evt);
				};
				window.onload = newonload;
			} else {
				window.onload = callbackName;
			}
		}
	},
	metaEdit: function (button) {
		console.log('metaEdit', button);
		console.log(button.getAttribute('data-post-id'));
		var element = jQuery(button);
		var postID = button.getAttribute('data-post-id');
		console.log(jQuery(button.getAttribute('data-post-id') + '-meta-form'));
		var metadata = {};
		jQuery("#meta_form_modal_" + postID + " .meta-inputs input").each(function (index) {
			metadata[this.name] = this.value;
		});
		var sendObject = {};
		sendObject.action = 'pf_ajax_update_meta_fields';
		sendObject.post_id = postID
		sendObject.nonce = jQuery('#meta_form_' + postID + '_nonce_wpnonce').attr('value');
		sendObject.metadata = metadata;
		console.log(sendObject);
		jQuery.post(ajaxurl, sendObject,
			function (response) {
				console.log(response);
				jQuery('#meta_form_modal_' + postID).modal('hide');
				//jQuery('.loading-' + itemID).hide();
				// jQuery(".nominate-result-"+itemID).html(response);
				// alert(response);
				// jQuery("#test-div1").append(data);
			});
	}
};

const LoadingEffect = {
  minDuration: 500, // Minimum time in ms for the effect

  start(container) {
    if (!container) {
			return;
		}

    const startTime = Date.now();
    container.dataset.loadingStartTime = startTime; // Store start time in memory for end calculation
    container.classList.add('request-in-progress');
  },

  stop(container) {
    if (!container) {
			return;
		}

    const startTime = parseInt(container.dataset.loadingStartTime || '0', 10);
    const elapsedTime = Date.now() - startTime;
    const delay = Math.max(this.minDuration - elapsedTime, 0);

    setTimeout(() => {
      container.classList.remove('request-in-progress');
      delete container.dataset.loadingStartTime; // Clean up memory
    }, delay);
  },
};

pf_setup.LoadingEffect = LoadingEffect;

window.pf = Object.assign(window.pf, pf_setup);
