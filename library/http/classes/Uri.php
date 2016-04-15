<?php
namespace pillr\library\http;

use \Psr\Http\Message\UriInterface as UriInterface;
/**
 * Value object representing a URI.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable{} all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @see http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements UriInterface
{
    public $scheme = '';
    public $authority = '';
    public $userInfo='';
    public $host='';
    public $port=NULL;
    public $path='';
    public $query='';
    public $fragment='';

    public function __construct($uriStr = '')
    {
        $tmp = $this; # tmp is necessary because php doesn't allow to reassign $this directly

        $scheme = strtolower(strval(parse_url($uriStr,PHP_URL_SCHEME))); #if scheme is NULL, strval(NULL) is an empty string
        $tmp = $tmp->withScheme($scheme);
        $user =  strval(parse_url($uriStr,PHP_URL_USER));
        $pass =  strval(parse_url($uriStr,PHP_URL_PASS));
        $tmp = $tmp->withUserInfo($user,$pass);
        $port =  parse_url($uriStr,PHP_URL_PORT);
        $tmp = $tmp->withPort($port);

        $query =  strval(parse_url($uriStr,PHP_URL_QUERY));
        $tmp=$tmp->withQuery($query);
        $fragment = strval(parse_url($uriStr,PHP_URL_FRAGMENT));
        $tmp=$tmp->withFragment($fragment);

        #Get around parse_url host bug when no scheme is present
        $uriStrTmp = $uriStr;
        #Adds a dummy scheme to dummy variable $uriStrTmp to retrieve the host correctly
        if(strpos($uriStrTmp,"://")===false && substr($uriStrTmp,0,1)!="/") $uriStrTmp = "http://".$uriStrTmp; #From http://stackoverflow.com/questions/10359347/php-parse-url-domain-retured-as-path-when-protocol-prefix-not-present
        $host = strval(parse_url($uriStrTmp,PHP_URL_HOST));
        $path =  strval(parse_url($uriStrTmp,PHP_URL_PATH));
        $tmp = $tmp->withHost($host);
        $tmp = $tmp->withPath($path);

        $this->scheme = $tmp->scheme;
        $this->userInfo = $tmp->userInfo;
        $this->host = $tmp->host;
        $this->port = $tmp->port;
        $this->path = $tmp->path;
        $this->query = $tmp->query;
        $this->fragment = $tmp->fragment;


        #Construction of authority
        if ($this->userInfo !== '')
        {
            $authorityUserInfo = $this->userInfo."@";
        }
        else
        {
            $authorityUserInfo = "";
        }

        if (is_null($this->port)) #If port is not present
        {
            $authorityPort = "";
        }
        else
        {
            $authorityPort = ":".strval($this->port);
        }

        $this->authority = $authorityUserInfo.$this->host.$authorityPort;

    }



    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value{}
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return urldecode($this->path);
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return urldecode($this->query);
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return urldecode($this->fragment);
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid schemes.
     * @throws \InvalidArgumentException for unsupported schemes.
     */
    public function withScheme($scheme)
    {
        #Need to compare to list of valid schemes
        $output = $this;
        $output->scheme = $scheme;
        return $output;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user{} an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
      $output = $this;
      $output->userInfo = $user;
      if ($password) {$output->userInfo.=':'.$password;} #Append ':password' id password is not null.
      return $output;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        #Need  to find regex to test validity
        $output = $this;
        $output->host = $host;
        return $output;


    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance{} a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
      $output = $this;

      if ($port == getservbyname($this->scheme, 'tcp') || $port == NULL) #If port is the default port
      {
        $output->port = NULL;
      }
      elseif(!(is_int($port) && $port>=0 && $port<=65535))
      {
        throw new \InvalidArgumentException('Port is not valid');
      }
      else
      {
        $output->port = $port;
      }

      return $output;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If an HTTP path is intended to be host-relative rather than path-relative
     * then it must begin with a slash ("/"). HTTP paths not starting with a slash
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
      $output = $this;

      if (!preg_match('/^[^*?"<>|:]*$/',$path)){throw new \InvalidArgumentException('Path is not valid.');}
      else
      {
        if($path)
        {
          if($path[0] = '/'){$path = '/'.trim($path,'/');} #if path is not rootless, trim all beginning and ending slashes then add first slash.
          else {$path = trim($path,'/');} #else just trim all beginning and ending slashes
          $output->path = urlencode(urldecode($path)); //We decode then encode again to be sure not to encode characters twice
        }
        else
        {
          $output->path = '';
        }

      }

      return $output;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
      #Robustness to be added
      $output = $this;

      if (!preg_match('^([\w-]+(=[\w-]*)?(&[\w-]+(=[\w-]*)?)*)?$^',$query)){throw new \InvalidArgumentException('Query is not valid.');}
      else
      {
        $output->query = urlencode(urldecode($query)); //We decode then encode again to be sure not to encode characters twice
      }

      return $output;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
      #Robustness to be added
      $output = $this;
      $output->fragment = urlencode(urldecode($fragment));
      return $output;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
      if($this->getScheme()==''){$scheme='';}
      else {$scheme = $this->getScheme().':';}

      if($this->getAuthority()==''){$authority='';}
      else{$authority = '//'.$this->getAuthority();}

      if($this->getPath()==''){$path='';}
      elseif(!($this->getAuthority()=='') && $this->getPath()[0]!=='/'){$path='/'.$this->getPath();}
      else{$path=$this->getPath();}

      if($this->getQuery()==''){$query='';}
      else{$query = '?'.$this->getQuery();}

      if($this->getFragment()==''){$fragment='';}
      else{$fragment = '#'.$this->getFragment();}

      return $scheme.$authority.$path.$query.$fragment;

    }

}
