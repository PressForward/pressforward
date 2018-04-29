console.log('Nominate This Setup');
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

window.pfnt = {};
window.pfnt.windows = {};
window.pfnt.styles = {};
window.pfnt.tools = {};
window.pfReadability = {};

function textareaBuilder(contents) {
	var editorContainer = document.createElement('div');
	editorContainer.setAttribute('id', 'wp-pressthis-editor-container');
	editorContainer.setAttribute('class', 'wp-editor-container');

	var toolbar = document.createElement('div');
	toolbar.setAttribute('id', 'qt_pressthis_toolbar');
	toolbar.setAttribute('class', 'quicktags-toolbar');

	var nomTextArea = document.createElement('div');
	nomTextArea.setAttribute('id', 'nominateText');
	nomTextArea.setAttribute('class', 'wp-editor-area');
	nomTextArea.setAttribute('name', 'post_content');
	//nomTextArea.setAttribute('autocomplete', 'off');
	//nomTextArea.setAttribute('cols', '40');
	nomTextArea.innerHTML = contents;
	editorContainer.appendChild(toolbar);
	editorContainer.appendChild(nomTextArea);
	return editorContainer;
}

function ctrlBar() {
	var ctrlContainer = document.createElement('div');
	ctrlContainer.setAttribute('id', 'wp-pressthis-wrap');
	ctrlContainer.setAttribute('class', 'wp-core-ui wp-editor-wrap tmce-active');

	var editorStyle = window.pfSiteData.plugin_url + 'Libraries/SummerNote/summernote.css';
	var linkE = document.createElement('link');
	linkE.setAttribute('rel', 'stylesheet');
	linkE.setAttribute('id', 'editor-css');
	linkE.setAttribute('href', editorStyle);
	linkE.setAttribute('type', 'text/css');
	linkE.setAttribute('media', 'all');
	ctrlContainer.appendChild(linkE);

	var toolDiv = document.createElement('div');
	toolDiv.setAttribute('id', 'wp-pressthis-editor-tools');
	toolDiv.setAttribute('class', 'wp-editor-tools hide-if-no-js');
	var toolDivInner = document.createElement('div');
	toolDiv.setAttribute('class', 'wp-editor-tabs');
	var visualButton = document.createElement('button');
	visualButton.setAttribute('id', 'pressthis-tmce');
	visualButton.setAttribute('class', 'wp-switch-editor switch-tmce');
	visualButton.setAttribute('data-wp-editor-id', 'pressthis');
	visualButton.innerHTML = 'Visual';
	var textButton = document.createElement('button');
	textButton.setAttribute('id', 'pressthis-tmce');
	textButton.setAttribute('class', 'wp-switch-editor switch-tmce');
	textButton.setAttribute('data-wp-editor-id', 'pressthis');
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
	windows.mainWindow.setAttribute('class', 'wrapper');

	windows.innerWindow = document.createElement('div');

	windows.innerWindow.setAttribute('id', 'pressforward-nt__inner');
	windows.innerWindow.setAttribute('class', 'editor-wrapper');

	pf_styles.mwStyles = 'position: absolute;' +
		'width: 76%;' +
		'height: 450px;' +
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

	var pfMainWindowAppender = function () { document.getElementsByTagName('body')[0].appendChild(window.pfnt.windows.mainWindow); };
	var pfInnerWindowAppender = function () {
		window.pfnt.windows.mainWindow.appendChild(window.pfnt.windows.innerWindow);
		window.pfnt.windows.mainWindow.appendChild(ctrlBar());
		window.pfnt.windows.innerWindow.appendChild(textareaBuilder(window.pfReadability.article.content));
	};

	pfMainWindowAppender();
	pfInnerWindowAppender();

	jQuery(document).ready(function () {
		jQuery('#nominateText').summernote({
			tabsize: 2,
			minHeight: 100,             // set minimum height of editor
			maxHeight: 400,             // set maximum height of editor
			focus: true                  // set focus to editable area after initializin
		});
	});

};

pfnt_activate();
