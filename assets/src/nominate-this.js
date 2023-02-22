import { Readability } from '@mozilla/readability'
import DOMPurify from 'dompurify'

import 'url-search-params-polyfill'
import 'whatwg-fetch'

(function(){
	const params = new URLSearchParams( document.location.search )

	addEventListener(
		'DOMContentLoaded',
		() => {
			const url = params.get( 'u' )

			if ( url ) {
				fetchUrlData( url )
			}
		}
	)

	const fetchUrlData = ( url ) => {
		const fetchUrl = ajaxurl + '?action=pf_fetch_url_content&url=' + encodeURIComponent( url )

		setIsLoading( true )

		window.fetch( fetchUrl )
			.then( response => response.json() )
			.then( ( responseJSON ) => {
				// todo - failure to fetch
				// todo isProbablyRenderable

				// DOM object is necessary for Readability as well as other parsing.
				const domObject = new DOMParser().parseFromString( responseJSON.data.body, 'text/html' )

				// Readability object will provide post content and author.
				const readabilityObj = new Readability( domObject ).parse()

				// Post content. Overwrite only if no selection is passed.
				const selection = params.get( 's' )
				if ( ! selection ) {
					const cleanContent = DOMPurify.sanitize( readabilityObj.content )
					const contentEditor = tinymce.get( 'content' )
					if ( contentEditor ) {
						contentEditor.setContent( cleanContent )
					}
				}

				// Post author.
				const authorField = document.getElementById( 'item_author' )
				if ( authorField ) {
					authorField.value = DOMPurify.sanitize( readabilityObj.byline, { ALLOWED_TAGS: [] } )
				}

				const tagsField = document.getElementById( 'post_tags' )
				if ( tagsField ) {
					const keywords = getKeywords( domObject )
					tagsField.value = DOMPurify.sanitize( keywords.join( ', ' ), { ALLOWED_TAGS: [] } )
				}

				// Featured images.
				const itemFeatImgField = document.getElementById( 'item_feat_img' )
				if ( itemFeatImgField ) {
					const sourceUrl = new URL( url )
					const imageUrl = ensureAbsoluteUrl( getImageUrl( domObject ), sourceUrl.protocol + '//' + sourceUrl.hostname )
					itemFeatImgField.value = DOMPurify.sanitize( imageUrl, { ALLOWED_TAGS: [] } )
				}
			} )
	}

	const setIsLoading = ( isLoading ) => {

	}

	/**
	 * Gets keywords out of a DOM object.
	 *
	 * As in the PFOpenGraph library,
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @returns {array}
	 */
	const getKeywords = ( domObject ) => {
		const metaTags = domObject.querySelectorAll( 'meta' )

		const isKeywordTag = ( tag ) => {
			// Prefer the 'name' attribute if available.
			const tagName = tag?.name
			const tagProperty = tag.getAttribute( 'property' )
			const tagIdentifier = tagName.length > 0 ? tagName : tagProperty

			if ( ! tagIdentifier ) {
				return false
			}

			return 'article:tag' === tagIdentifier
		}

		let keywords = [ 'via bookmarklet' ]
		for ( const metaTag of metaTags ) {
			if ( isKeywordTag( metaTag ) ) {
				const tagKeywords = metaTag.content.split( ',' )
				tagKeywords.map( tagKeyword => keywords.push( tagKeyword ) )
			}
		}

		return keywords
	}

	/**
	 * Gets image out of a DOM object.
	 *
	 * The order of preference is copied from the PFOpenGraph library:
	 * 1. og:image
	 * 2. <link rel="image_src">
	 * 3. twitter:image
	 * 4. First 'img' tag in the body text
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @returns {array}
	 */
	const getImageUrl = ( domObject ) => {
		const ogImageTag = domObject.querySelector( 'meta[property="og:image"]' )
		if ( ogImageTag ) {
			return ogImageTag.content
		}

		const linkImageSrc = domObject.querySelector( 'link[rel="image_src"]' )
		if ( linkImageSrc ) {
			// el.href converts to absolute using the WP domain, so we get the raw attribute.
			return linkImageSrc.getAttribute( 'href' )
		}

		const twitterImageTag = domObject.querySelector( 'property[name="twitter:image"]' ) ?? domObject.querySelector( 'meta[name="twitter:image"]' )
		if ( twitterImageTag ) {
			return twitterImageTag.content
		}

		const firstBodyImg = domObject.querySelector( 'body img' )
		if ( firstBodyImg ) {
			// el.href converts to absolute using the WP domain, so we get the raw attribute.
			return firstBodyImg.getAttribute( 'src' )
		}

		return ''
	}

	/**
	 * Ensures that URL is relative.
	 *
	 * @param {string} url  URL to check.
	 * @param {string} base URL base.
	 * @return {string}
	 */
	const ensureAbsoluteUrl = ( url, base ) => {
		if ( '/' !== url.substr( 0, 1 ) ) {
			return url
		}

		return base + url
	}
})()
