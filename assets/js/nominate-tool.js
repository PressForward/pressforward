console.log('Nominate This Setup');

if (null !== document.getElementById('pressforward-nt')) {
	pfnt_deactivate();
}
//Coming in:
//var d=document,
//w=window,
//e=w.getSelection,
//k=d.getSelection,
//x=d.selection,
//s=(e?e():(k)?k():(x?x.createRange().text:0)),
//l=d.location,
//e=encodeURIComponent,
//

//https://docs.ckeditor.com/ckeditor5/latest/builds/guides/quick-start.html#classic-editor

window.pfnt = {};
window.pfnt.windows = {};
window.pfnt.windows.links = [];
window.pfnt.windows.scripts = [];
window.pfnt.windows.styleBlocks = [];
window.pfnt.styles = {};
window.pfnt.tools = {};
window.pfReadability = {};

function textareaBuilder(contents) {
	var editorContainer = document.createElement('div');
	editorContainer.setAttribute('id', 'wp-nomthis-editor-container');
	editorContainer.setAttribute('class', 'wp-editor-container');

	var toolbar = document.createElement('div');
	toolbar.setAttribute('id', 'qt_nomthis_toolbar');
	toolbar.setAttribute('class', 'quicktags-toolbar');

	var nomTextArea = document.createElement('textarea');
	nomTextArea.setAttribute('id', 'nominateText');
	nomTextArea.setAttribute('class', 'wp-editor-area');
	nomTextArea.setAttribute('name', 'post_content');
	nomTextArea.setAttribute('style', 'height:375px;');
	nomTextArea.setAttribute('autocomplete', 'off');
	nomTextArea.setAttribute('cols', '40');
	nomTextArea.innerHTML = contents;
	// editorContainer.appendChild(toolbar);
	editorContainer.appendChild(nomTextArea);
	window.pfnt.windows.editorContainer = editorContainer;
	return editorContainer;
}

function linkElement(linkUrl) {
	//var linkUrl = 'https://cdn.quilljs.com/1.0.0/quill.snow.css';
	var linkE = document.createElement('link');
	linkE.setAttribute('rel', 'stylesheet');
	linkE.setAttribute('href', linkUrl);
	linkE.setAttribute('type', 'text/css');
	linkE.setAttribute('media', 'all');
	var headTag = document.getElementsByTagName('head')[0];
	window.pfnt.windows.links.push(linkE);
	headTag.prepend(linkE);
	return linkE;
}

function scriptElement(scriptUrl) {
	//var linkUrl = 'https://cdn.quilljs.com/1.0.0/quill.snow.css';
	var scriptE = document.createElement('script');
	//scriptE.setAttribute('src', scriptUrl);
	scriptE.src = scriptUrl;
	window.pfnt.windows.scripts.push(scriptE);
	return scriptE;
}

function stylesAndScripts() {

	var jsScript = scriptElement(window.pfSiteData.plugin_url + 'Libraries/tinymce/js/tinymce/tinymce.min.js');
	jsScript.onload = function () { console.log("init nt wysiwyg"); window.initEditor(); };
	var headTag = document.getElementsByTagName('head')[0];
	headTag.prepend(jsScript);
	var styleE = document.createElement('style');
	styleE.innerHTML = '#wp-nomthis-editor-container { width: 60%; }' + " \n " +
		'.pressforward-nt__inputfield { width:60%; display:block; }';
	window.pfnt.windows.styleBlocks.push(styleE);
	headTag.prepend(styleE);
	// linkElement('https://cdn.quilljs.com/1.0.0/quill.snow.css')
}

function ctrlBar() {
	var ctrlContainer = document.createElement('div');
	ctrlContainer.setAttribute('id', 'wp-nomthis-wrap');
	ctrlContainer.setAttribute('class', 'wp-core-ui wp-editor-wrap tmce-active');


	var toolDiv = document.createElement('div');
	toolDiv.setAttribute('id', 'wp-nomthis-editor-tools');
	toolDiv.setAttribute('class', 'wp-editor-tools hide-if-no-js');
	var toolDivInner = document.createElement('div');
	toolDiv.setAttribute('class', 'wp-editor-tabs');
	var visualButton = document.createElement('button');
	visualButton.setAttribute('id', 'nomthis-tmce');
	visualButton.setAttribute('class', 'wp-switch-editor switch-tmce');
	visualButton.setAttribute('data-wp-editor-id', 'nomthis');
	visualButton.innerHTML = 'Visual';
	var textButton = document.createElement('button');
	textButton.setAttribute('id', 'nomthis-tmce');
	textButton.setAttribute('class', 'wp-switch-editor switch-tmce');
	textButton.setAttribute('data-wp-editor-id', 'nomthis');
	textButton.innerHTML = 'Text';

	toolDivInner.appendChild(visualButton);
	toolDivInner.appendChild(textButton);
	toolDiv.appendChild(toolDivInner);

	ctrlContainer.appendChild(toolDiv);

	return ctrlContainer;
}

function pfnt_activate() {

	var pf = window.pfnt;
	var windows = window.pfnt.windows;
	var pf_styles = window.pfnt.styles;
	var pf_tools = window.pfnt.tools;
	var documentClone = document.cloneNode(true);
	window.pfReadability.article = new Readability(documentClone).parse();

	windows.mainWindow = document.createElement('div');

	windows.mainWindow.setAttribute('id', 'pressforward-nt');
	windows.mainWindow.setAttribute('class', 'nomthis-wrapper');

	windows.innerWindow = document.createElement('div');

	windows.innerWindow.setAttribute('id', 'pressforward-nt__inner');
	windows.innerWindow.setAttribute('class', 'nomthis-inner-wrapper');

	windows.titleField = document.createElement('input');
	windows.titleField.setAttribute('id', 'pressforward-nt__inputfield__title');
	windows.titleField.setAttribute('class', 'pressforward-nt__inputfield');
	windows.titleField.setAttribute('type', 'text');
	windows.titleField.setAttribute('value', window.pfReadability.article.title);

	windows.bylineField = document.createElement('input');
	windows.bylineField.setAttribute('id', 'pressforward-nt__inputfield__byline');
	windows.bylineField.setAttribute('class', 'pressforward-nt__inputfield');
	windows.bylineField.setAttribute('type', 'text');
	windows.bylineField.setAttribute('value', window.pfReadability.article.byline);

	pf_styles.mwStyles = 'position: fixed;' +
		'width: 76%;' +
		'height: 550px;' +
		'display: block;' +
		'background: #c7c7c7;' +
		'top: 0;' +
		'left: 12%;' +
		'padding: 1px;' +
		'box-sizing: border-box;' +
		'z-index: 10000;';

	pf_styles.iwStyles = 'position: relative;' +
		'width: 100%;' +
		'height: 100%;' +
		'background: white;' +
		'border: #afceaf 3px solid;' +
		'box-sizing: border-box;';

	windows.mainWindow.setAttribute('style', pf_styles.mwStyles);

	windows.innerWindow.setAttribute('style', pf_styles.iwStyles);

	var pfMainWindowAppender = function () { document.getElementsByTagName('body')[0].prepend(window.pfnt.windows.mainWindow); };
	var pfInnerWindowAppender = function () {
		window.pfnt.windows.mainWindow.appendChild(window.pfnt.windows.innerWindow);
		window.pfnt.windows.innerWindow.appendChild(window.pfnt.windows.titleField);
		window.pfnt.windows.innerWindow.appendChild(window.pfnt.windows.bylineField);
		window.pfnt.windows.innerWindow.appendChild(textareaBuilder(window.pfReadability.article.content));
	};

	pfMainWindowAppender();
	pfInnerWindowAppender();

	window.initEditor = function () {
		window.ntEditor = tinymce.init({ selector: '#nominateText' });
	};

	stylesAndScripts();

};

pfnt_activate();

function clearInner(node) {
	while (node.hasChildNodes()) {
		clear(node.firstChild);
	}
}

function clear(node) {
	while (node.hasChildNodes()) {
		clear(node.firstChild);
	}
	node.parentNode.removeChild(node);
	console.log(node, "cleared!");
}


function pfnt_deactivate() {
	clearInner(window.pfnt.windows.mainWindow);
	window.pfnt.windows.mainWindow.remove();
}
