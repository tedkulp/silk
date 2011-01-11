<?php
/*

   PHP Rack v0.1.0

   Copyright (c) 2010 Jim Myhrberg.

   Permission is hereby granted, free of charge, to any person obtaining
   a copy of this software and associated documentation files (the
   'Software'), to deal in the Software without restriction, including
   without limitation the rights to use, copy, modify, merge, publish,
   distribute, sublicense, and/or sell copies of the Software, and to
   permit persons to whom the Software is furnished to do so, subject to
   the following conditions:

   The above copyright notice and this permission notice shall be
   included in all copies or substantial portions of the Software.

   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
   IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
   CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
   TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
   SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

namespace Rack;

class Request
{
	protected $env = null;

	public function __construct(&$env)
	{
		$this->env = $env;
	}

	public function body()
	{
		return file_get_contents($this->env['rack.input']);
	}

	public function script_name()
	{
		return $this->env['SCRIPT_NAME'];
	}

	public function path_info()
	{
		return $this->env['PATH_INFO'];
	}

	public function request_method()
	{
		return $this->env['REQUEST_METHOD'];
	}

	public function query_string()
	{
		return $this->env['QUERY_STRING'];
	}

	public function content_length()
	{
		return $this->env['CONTENT_LENGTH'];
	}

	public function session()
	{
		return $this->env['rack.session'];
	}

	public function logger()
	{
		return $this->env['rack.logger'];
	}

	public function scheme()
	{
		if ($this->env['HTTPS'] == 'on')
			return 'https';
		else if ($this->env['HTTP_X_FORWARDED_SSL'] == 'on')
			return 'https';
		else if (isset($this->env['HTTP_X_FORWARDED_PROTO']))
		{
			$ary = explode(',', $this->env['HTTP_X_FORWARDED_PROTO']);
			return $ary[0];
		}
		else
			return $this->env['rack.url_scheme'];
	}

	public function is_ssl()
	{
		return $this->scheme() == 'https';
	}

	public function host_with_port()
	{
		if (isset($this->env['HTTP_X_FORWARDED_HOST']))
		{
			return array_pop(preg_split('/,\s?/', $this->env['HTTP_X_FORWARDED_HOST']));
		}
		else
		{
			if (isset($this->env['HTTP_HOST']))
				return $this->env['HTTP_HOST'];
			else if ($this->env['SERVER_NAME'])
				return $this->env['SERVER_NAME'] . ':' . $this->env['SERVER_PORT'];
			else
				return $this->env['SERVER_ADDR'] . ':' . $this->env['SERVER_PORT'];
		}
	}

	public function port()
	{
		if (count(explode(':', $this->host_with_port())) > 1)
		{
			$ary = explode(':', $this->host_with_port());
			return $ary[1];
		}
		else if (isset($this->env['HTTP_X_FORWARDED_PORT']))
		{
			return (int)$this->env['HTTP_X_FORWARDED_PORT'];
		}
		else if ($this->is_ssl())
		{
			return 443;
		}
		else
		{
			return (int)$this->env['SERVER_PORT'];
		}
	}

	public function host()
	{
		return preg_replace('/:\d\z/', '', $this->host_with_port());
	}

	public function is_delete()
	{
		return $this->request_method() == 'DELETE';
	}

	public function is_get()
	{
		return $this->request_method() == 'GET';
	}

	public function is_head()
	{
		return $this->request_method() == 'HEAD';
	}

	public function is_options()
	{
		return $this->request_method() == 'OPTIONS';
	}

	public function is_post()
	{
		return $this->request_method() == 'POST';
	}

	public function is_put()
	{
		return $this->request_method() == 'PUT';
	}

	public function is_trace()
	{
		return $this->request_method() == 'TRACE';
	}

	public function referer()
	{
		return $this->env['HTTP_REFERER'];
	}

	public function referrer()
	{
		return $this->referer();
	}

	public function user_agent()
	{
		return $this->env['HTTP_USER_AGENT'];
	}

	public function is_xhr()
	{
		return isset($this->env['HTTP_X_REQUESTED_WITH']) && $this->env['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	public function base_url()
	{
		$url = $this->scheme() . '://';
		$url .= $this->host();

		if (($this->scheme() == 'https' && $this->port() != 443) || ($this->scheme() == 'http' && $this->port != 80))
		{
			$url .= ':' . $this->port();
		}

		return $url;
	}

	public function url()
	{
		return $this->base_url() . $this->fullpath();
	}

	public function path()
	{
		return $this->script_name() . $this->path_info();
	}

	public function fullpath()
	{
		return $this->query_string() ? $this->path() : $this->path() . '?' . $this->query_string();
	}

	public function accept_encoding()
	{
		//TODO: Fix me
	}

	public function ip()
	{
		if (isset($this->env['HTTP_X_FORWARDED_FOR']))
		{
			//TODO: Fix me -- need examples
			return $this->env['REMOTE_ADDR'];
		}
		else
		{
			return $this->env['REMOTE_ADDR'];
		}
	}
	
}
