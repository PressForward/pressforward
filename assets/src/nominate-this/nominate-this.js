import { Readability } from '@mozilla/readability'
import DOMPurify from 'dompurify'

import 'url-search-params-polyfill'
import 'whatwg-fetch'

import { __ } from '@wordpress/i18n'

(function(){
	const params = new URLSearchParams( document.location.search )
	const hasSelection = !! params.get( 's' )

	let requestIsPending = false

	addEventListener(
		'DOMContentLoaded',
		() => {
			const url = params.get( 'u' )

			if ( url ) {
				fetchUrlData( url )

				document.getElementById( 'loading-url' ).innerHTML = DOMPurify.sanitize( url, { ALLOWED_TAGS: [] } )
			}
		}
	)

	/**
	 * Fetches data about a URL from the server.
	 *
	 * @param {string} url URL.
	 */
	const fetchUrlData = ( url ) => {
		const fetchUrl = ajaxurl + '?action=pf_fetch_url_content&url=' + encodeURIComponent( url )

		// Only show the loading indicator if there's a delay of more than 2 seconds.
		const requestStartTime = Date.now()
		requestIsPending = true
		setTimeout(
			() => {
				if ( ! requestIsPending ) {
					return
				}

				setIsLoading( true )
			},
			2000
		)

		window.fetch( fetchUrl )
			.then( response => response.json() )
			.then( ( responseJSON ) => {
				requestIsPending = false

				// To avoid flashes, don't hide until at least five seconds has passed.
				const timeElapsed = Date.now() - requestStartTime
				if ( timeElapsed > 5000 ) {
					setIsLoading( false )
				} else {
					setTimeout(
						() => {
							setIsLoading( false )
						},
						5000 - timeElapsed
					)
				}

				if ( responseJSON.success ) {
					// DOM object is necessary for Readability as well as other parsing.
					const domObject = new DOMParser().parseFromString( responseJSON.data.body, 'text/html' )

					// Readability object will provide post content and author.
					const readabilityObj = new Readability( domObject.cloneNode( true ) ).parse()

					// Detect embeds in the readable content and replace with raw URLs.
					const processedReadableContent = processEmbeds( readabilityObj.content, responseJSON.data.embeds )

					// Post content. Overwrite only if no selection is passed.
					if ( ! hasSelection ) {
						const cleanContent = DOMPurify.sanitize( processedReadableContent )

						// "Visual" tab.
						const contentEditor = tinymce.get( 'content' )
						if ( contentEditor ) {
							contentEditor.setContent( cleanContent )
						}

						// "Text" tab.
						const contentTextarea = document.getElementById( 'content' )
						if ( contentTextarea ) {
							contentTextarea.innerHTML = cleanContent
						}
					}

					// Post author.
					const authorField = document.getElementById( 'item_author' )
					if ( authorField ) {
						const authorFromLD = getAuthorFromLD( domObject )

						const authorValue = authorFromLD ?? readabilityObj.byline

						authorField.value = DOMPurify.sanitize( authorValue, { ALLOWED_TAGS: [] } )
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
				} else {
					document.body.classList.add( 'is-failed-request' )

				}
			} )
	}

	/**
	 * Sets or unsets the 'is-loading' body class.
	 *
	 * @param {bool} isLoading
	 */
	const setIsLoading = ( isLoading ) => {
		if ( isLoading ) {
			document.body.classList.add( 'is-loading' )
		} else {
			document.body.classList.remove( 'is-loading' )
		}
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
		let keywords = [ __( 'via bookmarklet', 'pf' ) ]

		// Prefer linked data if available.
		const ld = getLDFromDomObject( domObject )
		if ( ld && ld.hasOwnProperty( 'keywords' ) ) {
			let keywordArray
			if ( Array.isArray( ld.keywords ) ) {
				// If the keywords are structured, we don't have a reliable way of parsing them.
				keywordArray = ld.keywords.filter( (keyword) => { return 'string' === typeof keyword } )
			} else {
				keywordArray = ld.keywords.split( ',' )
			}

			return [ ...keywords, keywordArray ]
		}

		// Next, look at 'keyword' meta tags.
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

		for ( const metaTag of metaTags ) {
			if ( isKeywordTag( metaTag ) ) {
				const tagKeywords = metaTag.content.length > 0 ? metaTag.content.split( ',' ) : null
				if ( tagKeywords ) {
					tagKeywords.map( tagKeyword => keywords.push( tagKeyword ) )
				}
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
		// Prefer linked data if available.
		const ld = getLDFromDomObject( domObject )
		if ( ld ) {
			const ldThumbnailUrl = ld?.thumbnailUrl
			if ( ldThumbnailUrl ) {
				return ldThumbnailUrl
			}

			const ldImages = ld?.image
			const ldImagesArray = ! Array.isArray( ldImages ) ? [ ldImages ] : ldImages

			const ldImageUrl = ldImages ? ldImagesArray[0].url : null
			if ( ldImageUrl ) {
				return ldImageUrl
			}
		}

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

	/**
	 * Swaps embedded content with raw WP URLs.
	 *
	 * @param {string} body Readable text, as determined by Readability.
	 * @param {array} embeds Array of swappable embeds, as detected by the server.
	 * @return {array}
	 */
	const processEmbeds = ( body, embeds ) => {
		const bodyDom = new DOMParser().parseFromString( body, 'text/html' )

		const iframes = bodyDom.querySelectorAll( 'iframe' )

		const getAppendNode = ( el ) => {
			const keepClimbing = [ 'FIGURE' ]

			if ( keepClimbing.includes( el.tagName ) ) {
				return getAppendNode( el.parentElement )
			}

			return el.parentElement
		}

		for ( const iframeNode of iframes ) {
			embeds.map(
				( embed ) => {
					if ( iframeNode.src === embed.embedSrc ) {
						const newEmbedNode = document.createElement( 'p' )

						newEmbedNode.innerHTML = "\n\n" + DOMPurify.sanitize( decodeURIComponent( embed.embedUrl ), { ALLOWED_TAGS: [] } ) + "\n\n"

						const appendBefore = getAppendNode( iframeNode )

						appendBefore.insertAdjacentElement( 'beforebegin', newEmbedNode )
					}
				}
			)
		}

		return bodyDom.body.innerHTML
	}

	/**
	 * Swaps embedded content with raw WP URLs.
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {object}
	 */
	const getLDFromDomObject = ( domObject ) => {
		const ldTag = domObject.querySelector( 'script[type="application/ld+json"]' )
		if ( ! ldTag ) {
			return;
		}

		return JSON.parse( ldTag.innerHTML )
	}

	/**
	 * Swaps embedded content with raw WP URLs.
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {string}
	 */
	const getAuthorFromLD = ( domObject ) => {
		const ld = getLDFromDomObject( domObject )
		if ( ! ld || ! ld.hasOwnProperty( 'author' ) ) {
			return ''
		}

		let authorStrings = []
		if ( Array.isArray( ld.author ) ) {
			authorStrings = ld.author.map( author => author.name )
		} else {
			authorStrings = [ ld.author.name ]
		}

		return authorStrings.join( ', ' )
	}
})()
