window.pf = window.pf || {};

window.pf.ga = {
	ua: 'UA-107079913-1',
	site_name: 'WordPress',
    site_url: location.host,
    init: function(){
        //Global Site Tag (gtag.js) - Google Analytics
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function(){window.dataLayer.push(arguments);}
        this.track = window.gtag;
		this.site_name = document.getElementById('wp-admin-bar-site-name').innerText.trim();
        this.track('js', new Date());
        this.track('config', this.ua, {
            'page_title': document.title.replace( window.pf.ga.site_name, "" ).replace("‹  — WordPress", "").trim(),
            'page_location': 'http://pressforward.org/pf-admin',
            'page_path': location.pathname+location.search
        } );
        this.track('event', 'pageview');
    },
	activate: function(){
		var tag = document.createElement("script");
		tag.src = "//www.googletagmanager.com/gtag/js?"+window.pf.ga.ua;
		tag.setAttribute('onload', "window.pf.ga.init()");
		var docHead = document.getElementsByTagName("head");
		window.addEventListener("load", function () {
			docHead[0].appendChild(tag);
		});
	}
};

if ( pf_analytics.active ){
	window.pf.ga.activate();
}
