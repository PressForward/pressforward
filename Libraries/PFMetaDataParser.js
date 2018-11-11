window.pfMetaData = {
	self: this,
	title: '',
	description: '',
	image: '',
	author: '',
	feed: '',
	canonical: '',
	keywords: [],
	openGraph: {
		title: '',
		type: 'article'
	},
	twitter: {
		title: '',
	},
	jsonLD: {},
	checkForProp: function (val) {
		if (val === undefined || val === null || val === false || val.length < 1) {
			return false;
		} else {
			return true;
		}
	},
	init: function () {
		var self = window.pfMetaData;
		self.title = document.title;
		self.processMeta();
		self.processLinks();
		self.processJSONLD();
		self.assureMajorMetadataFilled();
	},
	assureMajorMetadataFilled: function () {
		var self = window.pfMetaData;
		if (false === self.cascadeMetaValues('title')) {
			if (self.checkForProp(self.jsonLD.headline)) {
				self.title = self.jsonLD.headline;
			}
		}
		if (false === self.cascadeMetaValues('description')) {
			if (self.checkForProp(self.jsonLD.description)) {
				self.description = self.jsonLD.description;
			}
		}
		if (false === self.cascadeMetaValues('author')) {
			if (self.checkForProp(self.jsonLD.author) && self.checkForProp(self.jsonLD.author.name)) {
				self.author = self.jsonLD.author.name;
			}
		}
		if (false === self.cascadeMetaValues('image')) {
			if (self.checkForProp(self.jsonLD.image) && self.checkForProp(self.jsonLD.image[0])) {
				self.image = self.jsonLD.image[0];
			}
		}
		if (false === self.checkForProp('canonical')) {
			if (self.checkForProp(self.openGraph.url)) {
				self.canonical = self.openGraph.url;
			} else if (self.checkForProp(self.jsonLD.mainEntityOfPage) && self.checkForProp(self.jsonLD.mainEntityOfPage['@id'])) {
				self.canonical = self.jsonLD.mainEntityOfPage['@id'];
			}
		}
		if (false !== self.checkForProp(self.openGraph.section)) {
			self.keywords.push(self.openGraph.section);
		}
		if (false !== self.checkForProp(self.openGraph.tag)) {
			self.keywords = self.keywords.concat(self.openGraph.tag);
		}
		var metaKeywords = document.querySelector('meta[name="keywords"]');
		if (null !== metaKeywords && undefined !== metaKeywords) {
			var keywordsString = metaKeywords.content;
			self.keywords = self.keywords.concat(keywordsString.split(','));
		}
	},
	cascadeMetaValues: function (prop) {
		var self = window.pfMetaData;
		if (!self.checkForProp(self[prop])) {
			if (!self.checkForProp(self.openGraph[prop])) {
				if (!self.checkForProp(self.twitter[prop])) {
					return false;
				} else {
					self[prop] = self.twitter[prop];
				}
			} else {
				self[prop] = self.openGraph[prop];
			}
		}
		return true;
	},
	processOpenGraphTag: function (element, type) {
		var self = window.pfMetaData;
		var firstTriple = type.length;
		firstTriple += 1;
		var ogTags = document.querySelectorAll('meta[property="' + element + '"]');
		if (ogTags.length > 0) {
			console.log(ogTags);
			if (element === 'og:type' && ogTags[0] && ogTags[0].length && ogTags[0].hasOwnPropery('content')) {
				self.openGraph[element.substring(firstTriple)] = ogTags[0].content;
			} else if (1 === ogTags.length) {
				self.openGraph[element.substring(firstTriple)] = ogTags[0].content;
			} else if (ogTags.length > 1) {
				var elementName = element.substring(firstTriple);
				self.openGraph[elementName] = [];
				ogTags.forEach(function (element) {
					self.openGraph[elementName].push(element.content);
				});
			}
		}
	},
	processMeta: function () {
		var self = window.pfMetaData;
		var metas = document.getElementsByTagName('meta');
		var defaultMetas = [
			'author',
			'description'
		];
		defaultMetas.forEach(function (element) {
			var self = window.pfMetaData;
			if (undefined !== metas[element]) {
				self[element] = metas[element].content;
			}
		});
		var twitterMetas = [
			'twitter:card',
			'twitter:creator',
			'twitter:description',
			'twitter:image',
			'twitter:site',
			'twitter:title'
		];
		twitterMetas.forEach(function (element) {
			var self = window.pfMetaData;
			if (undefined !== metas[element]) {
				window.pfMetaData.twitter[element.substring(8)] = metas[element].content;
			}
		});
		var ogPropertyMetas = [
			'og:title',
			'og:site_name',
			'og:description',
			'og:url',
			'og:locale',
			'og:type',
			'og:image'
		];
		ogPropertyMetas.forEach(function (element) {
			var self = window.pfMetaData;
			self.processOpenGraphTag(element, 'og');
		});
		var ogPropertyTypeMetas = [
			self.openGraph.type + ':published_time',
			self.openGraph.type + ':author',
			self.openGraph.type + ':publisher',
			self.openGraph.type + ':section',
			self.openGraph.type + ':tag',
			self.openGraph.type + ':image',
		];
		ogPropertyTypeMetas.forEach(function (element) {
			var self = window.pfMetaData;
			self.processOpenGraphTag(element, self.openGraph.type);
		});
	},
	processLink: function (selector) {
		var aTag = document.head.querySelector('link[' + selector + ']');
		if (null !== aTag) {
			return aTag.href;
		} else {
			return false;
		}
	},
	processLinks: function () {
		var self = window.pfMetaData;
		// var links = document.getElementsByTagName('link');
		self.canonical = self.processLink('rel="canonical"');
		self.feed = self.processLink('type="application/rss+xml"');
	},
	processJSONLD: function () {
		var self = window.pfMetaData;
		var JSONLDTag = document.head.querySelector('script[type="application/ld+json"]');
		if (null !== JSONLDTag) {
			self.jsonLD = JSON.parse(JSONLDTag.innerHTML);
		}
	}
};
window.pfMetaData.init();
