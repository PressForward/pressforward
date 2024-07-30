/* global ajaxurl, addEventListener, DOMParser, postboxes, tinymce */
import { Readability } from '@mozilla/readability'
import DOMPurify from 'dompurify'

import 'url-search-params-polyfill'
import 'whatwg-fetch'

import './nominate-this.scss'

import { assignTags } from '../util/tags'

import { __, sprintf } from '@wordpress/i18n'

(function(){
	const params = new URLSearchParams( document.location.search )
	const hasSelection = !! params.get( 's' )

	let sanitizedLoadingUrl

	let requestIsPending = false

	addEventListener(
		'DOMContentLoaded',
		() => {
			const url = params.get( 'u' )

			if ( url ) {
				fetchUrlData( url )

				sanitizedLoadingUrl = DOMPurify.sanitize( url, { ALLOWED_TAGS: [] } )

				const loadingUrl = document.getElementById( 'loading-url' )
				if ( loadingUrl ) {
					document.getElementById( 'loading-url' ).innerHTML = sanitizedLoadingUrl
				}
			}

			// Needed for the postbox toggles.
			document.body.classList.add( 'js' )

			// WP has a hardcoded exception for 'press-this' when saving postbox toggles.
			setTimeout(
				() => {
					if ( 'undefined' !== typeof postboxes ) {
						postboxes.post = 'press-this'
					}
				},
			500 )

			// Set a reasonable default size for the window.
			const { availWidth, availHeight } = window.screen

			const maxWindowWidth = 700
			const maxWindowHeight = 900

			const newWindowWidth = maxWindowWidth > ( availWidth - 40 ) ? availWidth - 40 : maxWindowWidth
			const newWindowHeight = maxWindowHeight > ( availHeight - 40 ) ? availHeight - 40 : maxWindowHeight

			window.resizeTo( newWindowHeight, newWindowWidth );

			wp.i18n.setLocaleData( {
				'Publish': [
					'Nominate',
					'pressforward'
				],
				'Are you ready to publish?': [
					'Are you ready to nominate?',
					'pressforward'
				],
				'Double-check your settings before publishing.': [
					'Double-check your settings before nominating.',
					'pressforward'
				],
			} );
		}
	)

	/**
	 * Fetches data about a URL from the server.
	 *
	 * @param {string} url URL.
	 */
	const fetchUrlData = ( url ) => {
		const fetchUrl = ajaxurl + '?action=pf_fetch_url_content&url=' + encodeURIComponent( url )

		// Determine whether this is a classic or block editor.
		const isBlockEditor = document.getElementById( 'editor' ) ? true : false

		// Only show the loading indicator if there's a delay of more than 2 seconds.
		const requestStartTime = Date.now()
		requestIsPending = true
		setTimeout(
			() => {
				if ( ! requestIsPending ) {
					return
				}

				if ( isBlockEditor ) {
					setLoadingIndicator( true )
				} else {
					setIsLoading( true )
				}
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
					if ( isBlockEditor ) {
						setLoadingIndicator( false )
					} else {
						setIsLoading( false )
					}
				} else {
					setTimeout(
						() => {
							if ( isBlockEditor ) {
								setLoadingIndicator( false )
							} else {
								setIsLoading( false )
							}
						},
						5000 - timeElapsed
					)
				}

				if ( responseJSON.success ) {
					// DOM object is necessary for Readability as well as other parsing.
					const domObject = new DOMParser().parseFromString( responseJSON.data.body, 'text/html' )

					const documentTitle = domObject.title

					// Readability object will provide post content and author.
					const readabilityObj = new Readability( domObject.cloneNode( true ) ).parse()

					// Detect embeds in the readable content and replace with raw URLs.
					const processedReadableContent = processEmbeds( readabilityObj.content, responseJSON.data.embeds )

					// Post content. Overwrite only if no selection is passed.
					if ( ! hasSelection ) {
						const cleanContent = DOMPurify.sanitize( processedReadableContent )

						if ( isBlockEditor ) {
							const blockContent = wp.blocks.pasteHandler( { HTML: cleanContent } )
							wp.data.dispatch( 'core/block-editor' ).insertBlocks( blockContent )
							wp.data.dispatch( 'core/editor' ).editPost( { title: documentTitle } )
						} else {
							const contentEditor = tinymce.get( 'content' )
							if ( contentEditor ) {
								// "Visual" tab.
								contentEditor.setContent( cleanContent )

								// "Text" tab.
								const contentTextarea = document.getElementById( 'content' )
								if ( contentTextarea ) {
									contentTextarea.innerHTML = cleanContent
								}
							}
						}
					}

					// Post author.
					const authorFromLD = getAuthorFromLD( domObject )
					const authorValue = authorFromLD ?? readabilityObj.byline
					const authorValueSanitized = DOMPurify.sanitize( authorValue, { ALLOWED_TAGS: [] } )
					if ( authorValueSanitized ) {
						if ( isBlockEditor ) {
							wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_author': authorValueSanitized } } )
						} else {
							const authorField = document.getElementById( 'item_author' )
							if ( authorField ) {
								authorField.value = authorValueSanitized
							}
						}
					}

					// A number of items are only set in the block editor.
					if ( isBlockEditor ) {
						const itemLinkValue = DOMPurify.sanitize( url, { ALLOWED_TAGS: [] } )
						wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_link': itemLinkValue } } )

						const sourcePublisher = getLDFromDomObject( domObject )?.publisher
						if ( sourcePublisher ) {
							const sourcePublisherName = sourcePublisher?.name
							if ( sourcePublisherName ) {
								wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'source_publication_name': sourcePublisherName } } )
							}

							const sourcePublisherUrl = sourcePublisher?.url
							if ( sourcePublisherUrl ) {
								wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'source_publication_url': sourcePublisherUrl } } )
							}
						}

						const itemDate = getTimestampFromLD( domObject )
						if ( itemDate ) {
							wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_date': itemDate } } )
							wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_wp_date': itemDate } } )
						}

						// Format the current time as a MySQL timestamp.
						const now = new Date()
						const nowFormatted = formatISODateAsMysql( now.toISOString() )
						wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'date_nominated': nowFormatted } } )

						// Get the current date formated as a UNIX timestamp.
						const nowUnix = Math.floor( now.getTime() / 1000 )
						wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'sortable_item_date': nowUnix.toString() } } )
					}

					const keywords = getKeywords( domObject )
					if ( isBlockEditor ) {
						// @todo Tags must be created before they can be assigned.
						// But this is likely disruptive, as it will clutter up
						// the tags list. Perhaps we need a toggle for the auto-import
						// of remote tags. In the meantime, we do need the
						// 'via bookmarklet' tag.
						assignTags( [ __( 'via bookmarklet', 'pressforward' ) ] )

						// A comma-separated list is stored in 'item_tags' postmeta.
						const tagsFieldValue = DOMPurify.sanitize( keywords.join( ',' ), { ALLOWED_TAGS: [] } )
						wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_tags': tagsFieldValue } } )

					} else {

						// Add 'via bookmarklet' tag to the list.
						keywords.push( __( 'via bookmarklet', 'pressforward' ) )

						const tagsFieldValue = DOMPurify.sanitize( keywords.join( ', ' ), { ALLOWED_TAGS: [] } )
						const tagsField = document.getElementById( 'post_tags' )
						if ( tagsField ) {
							tagsField.value = tagsFieldValue
						}
					}

					// Featured images.
					const sourceUrl = new URL( url )
					const imageUrl = ensureAbsoluteUrl( getImageUrl( domObject ), sourceUrl.protocol + '//' + sourceUrl.hostname )
					const itemFeatImgValue = DOMPurify.sanitize( imageUrl, { ALLOWED_TAGS: [] } )

					if ( itemFeatImgValue ) {
						if ( isBlockEditor ) {
							wp.data.dispatch( 'core/editor' ).editPost( { meta: { 'item_feat_img': itemFeatImgValue } } )
						} else {
							const itemFeatImgField = document.getElementById( 'item_feat_img' )
							if ( itemFeatImgField ) {
								itemFeatImgField.value = itemFeatImgValue
							}
						}
					}

					// Switch to the Post tab so the user can see the Tags, Post Author, etc fields.
					if ( isBlockEditor ) {
						setTimeout( () => {
						   wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/document' );
						}, 2000 )
					}
				} else if ( isBlockEditor ) {
					wp.data.dispatch( 'core/notices' ).createErrorNotice(
						__( 'Could not fetch remote URL', 'pressforward' ),
						{ id: 'fetch-url-failed', isDismissible: false }
					);
				} else {
					document.body.classList.add( 'is-failed-request' )
				}
			} )
	}

	/**
	 * Sets or unsets the 'is-loading' body class.
	 *
	 * @param {boolean} isLoading Whether the page is loading.
	 */
	const setIsLoading = ( isLoading ) => {
		if ( isLoading ) {
			document.body.classList.add( 'is-loading' )
		} else {
			document.body.classList.remove( 'is-loading' )
		}
	}

	/**
	 * Configures the loading indicator notice.
	 *
	 * @param {boolean} isLoading Whether to turn on or off.
	 * @return {void}
	 */
	const setLoadingIndicator = ( isLoading ) => {
		if ( isLoading ) {
			wp.data.dispatch( 'core/notices' ).createWarningNotice(
				// translators: URL being loaded
				sprintf( __( 'Loading content from %s.', 'pressforward' ), sanitizedLoadingUrl ),
				{ id: 'loading-content', isDismissible: false }
			);
		} else {
			wp.data.dispatch( 'core/notices' ).removeNotice( 'loading-content' )
		}
	}

	/**
	 * Gets keywords out of a DOM object.
	 *
	 * As in the PFOpenGraph library,
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {Array} Array of keywords.
	 */
	const getKeywords = ( domObject ) => {
		const keywords = []

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

			return keywordArray
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
	 * @return {Array} Array of swappable embeds, as detected by the server.
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
	 * @return {string} URL.
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
	 * @param {string} body   Readable text, as determined by Readability.
	 * @param {Array}  embeds Array of swappable embeds, as detected by the server.
	 * @return {Array} Array of swappable embeds, as detected by the server.
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

					return embed;
				}
			)
		}

		return bodyDom.body.innerHTML
	}

	/**
	 * Swaps embedded content with raw WP URLs.
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {Object|void} Linked data object.
	 */
	const getLDFromDomObject = ( domObject ) => {
		const ldTag = domObject.querySelector( 'script[type="application/ld+json"]' )
		if ( ! ldTag ) {
			return;
		}

		return JSON.parse( ldTag.innerHTML )
	}

	/**
	 * Gets the author from an object's linked data, if available.
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {string} Author name.
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

	/**
	 * Gets an item's timestamp from its linked data, if available.
	 *
	 * @param {HTMLDocument} domObject DOM object representing the source page.
	 * @return {string} Timestamp.
	 */
	const getTimestampFromLD = ( domObject ) => {
		const ld = getLDFromDomObject( domObject )
		if ( ! ld || ! ld.hasOwnProperty( 'datePublished' ) ) {
			return ''
		}

		return formatISODateAsMysql( ld.datePublished )
	}

	const formatISODateAsMysql = (isoDate) => {
		// Parse the ISO 8601 timestamp
		const date = new Date( isoDate );

		// Pad the date/month/hour/minute/second with leading zero if necessary
		const pad = (num) => (num < 10 ? '0' + num : num);

		// Format the date
		const year = date.getFullYear();
		const month = pad(date.getMonth() + 1); // getMonth() returns 0-11
		const day = pad(date.getDate());
		const hour = pad(date.getHours());
		const minute = pad(date.getMinutes());
		const second = pad(date.getSeconds());

		// Combine into final string
		return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
	}
})()
