Welcome to URLResolver.php
====================================================

URLResolver.php is a PHP class that attempts to resolve URLs to a final,
canonical link. On the web today, link shorteners, tracking codes and more can
result in many different links that ultimately point to the same resource.
By following HTTP redirects and parsing web pages for open graph and canonical
URLs, URLResolver.php attempts to solve this issue.

## Patterns Recognized

- Follows 301, 302, and 303 redirects found in HTTP headers
- Follows [Open Graph] URL &lt;meta&gt; tags found in web page &lt;head&gt;
- Follows [Canonical] URL &lt;link&gt; tags found in web page &lt;head&gt;
- Aborts download quickly if content type is not an HTML page

I am open to additional suggestions for improvement.

## Usage

Resolving a URL can be as easy as:

``` php
<?php require_once('URLResolver.php');

$resolver = new URLResolver();
print $resolver->resolveURL('http://goo.gl/0GMP1')->getURL();
```

However, in most cases you will want to perform a little extra setup. The
following code sets a user agent to identify your crawler (otherwise the
default will be used) and also designates a temporary file that can be used
for storing cookies during the session.  Some web sites will test the browser
for cookie support, so this will enhance your results.

``` php
<?php require_once('URLResolver.php');
$resolver = new URLResolver();

# Identify your crawler (otherwise the default will be used)
$resolver->setUserAgent('Mozilla/5.0 (compatible; YourAppName/1.0; +http://www.example.com)');

# Designate a temporary file that will store cookies during the session.
# Some web sites test the browser for cookie support, so this enhances results.
$resolver->setCookieJar('/tmp/url_resolver.cookies');

# resolveURL() returns an object that allows for additional information.
$url = 'http://goo.gl/0GMP1';
$url_result = $resolver->resolveURL($url);

# Test to see if any error occurred while resolving the URL:
if ($url_result->didErrorOccur()) {
	print "there was an error resolving $url:\n  ";
	print $url_result->getErrorMessageString();
}

# Otherwise, print out the resolved URL.  The [HTTP status code] will tell you
# additional information about the success/failure. For instance, if the
# link resulted in a 404 Not Found error, it would print '404: http://...'
# The successful status code is 200.
else {
	print $url_result->getHTTPStatusCode();
	print ': ';
	print $url_result->getURL();
}
```

## Download and Requirements  
  

#### License
URLResolver.php is licensed under the [MIT License], viewable in the source code.

#### Download
URLResolver.php as a [.tar.gz](https://github.com/mattwright/URLResolver.php/tarball/master) or [.zip](https://github.com/mattwright/URLResolver.php/zipball/master) file.

#### Requirements
- The [curl](http://php.net/manual/en/book.curl.php) extension must be installed as part of PHP
- [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/) is required and included with the download.

## API

### URLResolver()

`$resolver = new URLResolver();`  
Create the URL resolver object that you call additional methods on.

`$resolver->resolveURL($url);`  
$url is the link you want to resolve.  
Returns a [URLResult] object that contains the final, resolved URL.

`$resolver->setUserAgent($user_agent);`  
Pass in a string that is sent to each web server to identify your crawler.

`$resolver->setCookieJar($cookie_file);  # Defaults to disable cookies`  
*** This file will be removed at the end of each resolveURL() call. ***  
Pass in the path to a file used to store cookies during each resolveURL() call.  
If no cookie file is set, cookies will be disabled and results may suffer.  
This file must not already exist.
If it does, pass _true_ as second argument to enable overwrite.

`$resolver->setMaxRedirects($max_redirects);  # Defaults to 10`  
Set the maximum number of URL requests to attempt during each resolveURL() call.

`$resolver->setMaxResponseDataSize($max_bytes);  # Defaults to 120000`  
Pass in an integer specifying the maximum data to download per request.  
Multiple URL requests may occur during each resolveURL() call.  
Setting this too low may limit the usefulness of results (default 120000).

`$resolver->setRequestTimeout($num_seconds);  # Defaults to 30`  
Set the maximum amount of time, in seconds, any URL request can take.  
Multiple URL requests may occur during each resolveURL() call.

`$resolver->isDebugMode($value);  # Defaults to false`  
Set $value to _true_ to enable debug mode and _false_ to disable (the default).  
This will print out each link visited, along with status codes and link types.

### URLResolverResult()

`$url_result = $resolver->resolveURL($url);`  
Retrieve the URLResolverResult() object representing the resolution of $url.

`$url_result->getURL();`  
This is the best resolved URL we could obtain after following redirects.

`$url_result->getHTTPStatusCode();`  
Returns the integer [HTTP status code] for the resolved URL.  
Examples: 200 - OK (success), 404 - Not Found, 301 - Moved Permanently, ...

`$url_result->hasSuccessHTTPStatus();`  
Returns _true_ if the [HTTP status code] for the resolved URL is 200.

`$url_result->hasRedirectHTTPStatus();`  
Returns _true_ if the [HTTP status code] for the resolved URL is 301, 302, or 303.

`$url_result->getContentType();`  
Returns the value of the Content-Type [HTTP header] for the resolved URL.  
If header not provided, _null_ is returned. Examples: text/html, image/jpeg, ...

`$url_result->getContentLength();`  
Returns the size of the fetched URL in bytes for the resolved URL.  
Determined only by the Content-Length [HTTP header]. _null_ returned otherwise.

`$url_result->isOpenGraphURL();`  
Returns _true_ if resolved URL was marked as the Open Graph URL (og:url)

`$url_result->isCanonicalURL();`  
Returns _true_ if resolved URL was marked as the Canonical URL (rel=canonical)

`$url_result->isStartingURL();`  
Returns _true_ if resolved URL was also the URL you passed to resolveURL().

`$url_result->didErrorOccur();`  
Returns _true_ if an error occurred while resolving the URL.  
If this returns _false_, $url_result is guaranteed to have a status code.

`$url_result->getErrorMessageString();`  
Returns an explanation of what went wrong if didErrorOccur() returns _true_.

`$url_result->didConnectionFail();`  
Returns _true_ if there was a connection error (no header or no body returned).  
May indicate a situation where you are more likely to try at least once more.  
If this returns _true_, didErrorOccur() will true as well.

## Changelog
- v1.1 - June 3, 2014
	- Support http redirect code 303

- v1.0 - December 3, 2011
	- Initial release supports http header redirects, og:url and rel=canonical

[curl]: http://php.net/manual/en/book.curl.php
[PHP Simple HTML DOM Parser]: http://simplehtmldom.sourceforge.net/
[Open Graph]: https://developers.facebook.com/docs/opengraph/
[Canonical]: http://www.google.com/support/webmasters/bin/answer.py?answer=139394
[HTTP status code]: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
[HTTP header]: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
[MIT License]: http://en.wikipedia.org/wiki/MIT_License
