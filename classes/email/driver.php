<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author 		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 * @link 			http://fuelphp.com
 */

namespace Email;

abstract class Email_Driver {
	
	/**
	 * Driver config
	 */
	protected $config = array();
	
	/**
	 * To recipients list
	 */
	protected $to = array();
	
	/**
	 * Cc recipients list
	 */
	protected $cc = array();
	
	/**
	 * Bcc recipients list
	 */
	protected $bcc = array();
	
	/**
	 *	Reply to list
	 */
	protected $reply_to = array();
	
	/**
	 * Attachments array
	 */
	protected $attachments = array();
	
	/**
	 * Message body
	 */
	protected $body = '';
	
	/**
	 * Message alt body
	 */
	protected $alt_body = '';
	
	/**
	 * Message subject
	 */
	protected $subject = '';
	
	/**
	 * Invalid addresses
	 */
	protected $invalid_addresses = array();
	
	/**
	 * Message boundaries
	 */
	protected $boundaries = array();
	
	/**
	 * Message headers
	 */
	protected $headers = array();
	
	/**
	 * Driver constructor
	 *
	 * @param	array	$config		driver config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	}
	
	/**
	 * Get a driver config setting.
	 *
	 * @param	string		$key		the config key
	 * @return	mixed					the config setting value
	 */
	public function get_config($key, $default = null)
	{
		return \Arr::get($this->config, $key, $default);
	}
	
	/**
	 * Set a driver config setting.
	 *
	 * @param	string		$key		the config key
	 * @param	mixed		$value		the new config value
	 * @return	object					$this
	 */
	public function set_config($key, $value)
	{
		\Arr::set($this->config, $key, $value);
		
		return $this;
	}
	
	/**
	 * Sets the from address and name
	 *
	 */
	public function from($email, $name = false)
	{
		$this->config['from']['email'] = (string) $email;
		$this->config['from']['name'] = (is_string($name)) ? $name : false;
		
		return $this;
	}
	
	/**
	 * Add to the to recipients list.
	 *
	 * @param	string|array	$email	email address or list of email addresses, array(email => name, email)
	 * @param	string|bool		$name	recipient name, false, null or empty for no name
	 * @return	object			$this
	 */
	public function to($email, $name = false)
	{
		static::add_to_list('to', $email, $name);
		
		return $this;
	}
	
	/**
	 * Add to the cc recipients list.
	 *
	 * @param	string|array	$email	email address or list of email addresses, array(email => name, email)
	 * @param	string|bool		$name	recipient name, false, null or empty for no name
	 * @return	object			$this
	 */
	public function cc($email, $name = false)
	{
		static::add_to_list('cc', $email, $name);
		
		return $this;
	}
	
	
	/**
	 * Add to the bcc recipients list.
	 *
	 * @param	string|array	$email	email address or list of email addresses, array(email => name, email)
	 * @param	string|bool		$name	recipient name, false, null or empty for no name
	 * @return	object			$this
	 */
	public function bcc($email, $name = false)
	{
		static::add_to_list('bcc', $email, $name);
		
		return $this;
	}
	
	/**
	 * Add to the 'reply to' list.
	 *
	 * @param	string|array	$email	email address or list of email addresses, array(email => name, email)
	 * @param	string|bool		$name	the name, false, null or empty for no name
	 * @return	object			$this
	 */
	public function reply_to($email, $name = false)
	{
		static::add_to_list('reply_to', $email, $name);
		
		return $this;
	}
	
	/**
	 * Add to a recipients list.
	 *
	 * @param	string			$list	list to add to (to, cc, bcc)
	 * @param	string|array	$email	email address or list of email addresses, array(email => name, email)
	 * @param	string|bool		$name	recipient name, false, null or empty for no name
	 * @return	void
	 */
	protected function add_to_list($list, $email, $name = false)
	{
		if( ! is_array($email))
		{
			$email = (is_string($name)) ? array($email => $name) : array($email);
		}
		
		foreach($email as $_email => $name)
		{
			if(is_numeric($_email))
			{
				$_email = $name;
				$name = false;
			}
			
			$this->{$list}[$_email] = array(
				'name' => $name,
				'email' => $_email,
			);
		}
	}
	
	/**
	 * Clear the a recipient list.
	 *
	 * @param	string|array	$list	list or array of lists
	 * @return	void
	 */
	protected function clear_list($list)
	{
		is_array($list) or $list = array($list);
		
		foreach($list as $_list)
		{
			$this->{$_list} = array();
		}
	}
	
	/**
	 * Clear all recipient lists.
	 *
	 * @return	object	$this
	 */
	public function clear_recipients()
	{
		static::clear_list(array('to', 'cc', 'bcc'));
		
		return $this;
	}
	
	/**
	 * Clear all address lists.
	 *
	 * @return	object	$this
	 */
	public function clear_addresses()
	{
		static::clear_list(array('to', 'cc', 'bcc', 'reply_to'));
		
		$this->set_config('from', array(
			'name' => false,
			'email' => false,
		));
		
		return $this;
	}
	
	/**
	 * Clear the 'to' recipient list.
	 *
	 * @return	object	$this
	 */
	protected function clear_to()
	{
		static::clear_list('to');
		
		return $this;
	}
	
	/**
	 * Clear the 'cc' recipient list.
	 *
	 * @return	object	$this
	 */
	protected function clear_cc()
	{
		static::clear_list('cc');
		
		return $this;
	}
	
	/**
	 * Clear the 'bcc' recipient list.
	 *
	 * @return	object	$this
	 */
	protected function clear_bcc()
	{
		static::clear_list('bcc');
		
		return $this;
	}
	
	/**
	 * Clear the 'reply to' recipient list.
	 *
	 * @return	object	$this
	 */
	protected function clear_reply_to()
	{
		static::clear_list('reply_to');
		
		return $this;
	}
	
	/**
	 * Attaches a file to the email.
	 *
	 * @param	string	$file		the file to attach
	 * @param	bool	$inline		whether to include the file inline
	 */
	public function attach($file, $inline = false, $mime = null)
	{
		if( ! is_array($file))
		{
			$file = array($file, pathinfo($file, PATHINFO_BASENAME));
		}
		
		// File not found? Give 'm hell!
		if( ! is_file($file[0]))
		{
			throw new \AttachmentNotFoundException('Email attachment not found: '.$file);
		}
		
		$disp = ($inline) ? 'inline' : 'attachment';
		
		// Fetch the file mime type.
		$mime or $mime = static::attachment_mime($file[0]);
		
		$this->attachments[] = array(
			'file' => $file,
			'mime' => $mime,
			'disp' => $disp,
		);
		
		return $this;
	}
	
	/**
	 * Clear the attachments list.
	 *
	 * @return	object	$this
	 */
	public function clear_attachments()
	{
		$this->attachments = array();
		
		return $this;
	}
	
	/**
	 * Get the mimetype for an attachment
	 *
	 * @param	string	$file	the path to the attachment
	 * @return	string			the attachment's mimetype
	 */
	protected static function attachment_mime($file)
	{
		static $mimes = false;

		if( ! $mimes)
		{
			$mimes = \Config::load('mimes');
		}
		
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		$mime = \Arr::get($mimes, $ext, 'application/octet-stream');
		is_array($mime) and $mime = reset($mime);
		
		return $mime;
	}
	
	/**
	 * Validates all the email addresses.
	 *
	 * @return	bool|array	true if all are valid or an array of recipients which failed validation.
	 */
	protected function validate_addresses()
	{
		$failed = array();
		foreach(array('to', 'cc', 'bcc') as $list)
		{
			foreach($this->{$list} as $recipient)
			{
				if( ! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL))
				{
					$failed[][$list] = $recipient;
				}
			}
		}
		
		if(count($failed) === 0)
		{
			return true;
		}
		
		return $failed;
	}
	
	/**
	 * Sets unique message boundaries
	 */
	protected function set_boundaries()
	{
		$uniq_id = md5(uniqid(microtime(true)));
		$this->boundary[1] = 'B1_'.$uniq_id;
		$this->boundary[2] = 'B2_'.$uniq_id;
		$this->boundary[3] = 'b3_'.$uniq_id;
	}
		
	/**
	 * Initiates the sending process.
	 *
	 * @param	mixed	whether to validate the addresses, falls back to config setting 
	 * @return	bool	success boolean
	 */
	public function send($validate = null)
	{
		if(empty($this->to) and empty($this->cc) and empty($this->bcc))
		{
			throw new \Fuel_Exception('Cannot send email without recipients.');
		}

		if(($from = $this->get_config('from.email', false)) === false or empty($from))
		{
			throw new \Fuel_Exception('Cannot send without from address.');
		}
		
		// Check which validation bool to use
		is_bool($validate) or $validate = $this->get_config('validate', true);
		
		// Validate the email addresses if specified
		if($validate and ($failed = $this->validate_addresses()) !== true)
		{
			return \Email::FAILED_VALIDATION;
		}
		
		// Reset the headers
		$this->headers = array();
		
		// Set the email boundries
		$this->set_boundaries();
		
		// Set RFC 822 formatted date
		$this->set_header('Date', date('r'));
		
		// Set return path
		$this->set_header('Return-Path', $this->get_config('from.email'));
		
		// Set from
		$this->set_header('From', static::format_addresses(array($this->get_config('from'))));
		
		// Set message id
		$this->set_header('Message-ID', $this->get_message_id());
		
		// Set mime version
		$this->set_header('Mime-Version', '1.0');
		
		// Set priority
		$this->set_header('X-Priority', $this->get_config('priority'));
		
		// Set mailer useragent
		$this->set_header('X-Mailer', $this->get_config('useragent'));
		
		if( ! $this->_send())
		{
			return \Email::FAILED_SEND;
		}
		
		return \Email::SEND;
	}
	
	/**
	 * Sets the message headers
	 *
	 * @param	string	$header	the header type
	 * @param	string	$value	the header value
	 */
	protected function set_header($header, $value)
	{
		empty($value) or $this->headers[$header] = $value;
	}
	
	/**
	 * Gets the header
	 *
	 * @param	string	$header		the header time
	 * @return	string|array		mail header or array of headers
	 */
	private function get_header($header = null, $formatted = true)
	{
		if($header === null)
		{
			return $this->headers;
		}
		
		if(array_key_exists($header, $this->headers))
		{
			$prefix = ($formatted) ? $header.': ' : '';
			$suffix = ($formatted) ? $this->get_config('newline', "\r\n") : '';
			return $prefix.$this->headers[$header].$suffix;
		}
		
		return '';
	}
	
	/**
	 * Get a unique message id
	 *
	 * @return	string	the message id
	 */
	protected function get_message_id()
	{
		$from = $this->get_config('from.email');
		return "<".uniqid('').strstr($from, '@').">";
	}
	
	/**
	 * Returns the mail's content type
	 *
	 * @return	string		mail content type
	 */
	protected function get_content_type()
	{
		$type = ($this->get_config('is_html', false)) ? 'html' : 'plain' ;
		
		$attach = (count($this->attachments)) ? '-attach' : '';
		
		return $type.$attach;
	}
	
	/**
	 * Returns the uft8 character boundary
	 *
	 *
	 *
	 */
	public function utf8_char_boundary($encoded_text, $max_length) {
		$found_split_pos = false;
		$look_back = 3;
		while ( ! $found_split_pos)
		{
			$last_chunk = substr($encoded_text, $max_length - $look_back, $look_back);
			$encoded_char_pos = strpos($last_chunk, '=');
			if($encoded_char_pos !== false)
			{
				// Found start of encoded character byte within $lookBack block.
				// Check the encoded byte value (the 2 chars after the '=')
				$hex = substr($encoded_text, $max_length - $look_back + $encoded_char_pos + 1, 2);
				$dec = hexdec($hex);
				if($dec < 128)
				{ 
					// Single byte character.
					// If the encoded char was found at pos 0, it will fit
					// otherwise reduce maxLength to start of the encoded char
					$max_length = ($encoded_char_pos == 0) ? $max_length :
					$max_length - ($look_back - $encoded_char_pos);
					$found_split_pos = true;
				}
				elseif($dec >= 192)
				{
					// First byte of a multi byte character
					// Reduce maxLength to split at start of character
					$max_length = $max_length - ($look_back - $encoded_char_pos);
					$foundSplitPos = true;
				}
				elseif($dec < 192)
				{
					// Middle byte of a multi byte character, look further back
					$lookBack += 3;
				}
			}
			else
			{
				// No encoded character found
				$found_split_pos = true;
			}
		}
		return $maxLength;
	}
	
	public function wrap_text($message, $length, $qp_mode = false)
	{
		$soft_break = ($qp_mode) ? sprintf(" =%s", $this->LE) : $this->LE;
		// If utf-8 encoding is used, we will need to make sure we don't
		// split multibyte characters when we wrap
		$is_utf8 = (strtolower($this->get_config('charset')) == "utf-8");

		$message = $this->prep_newlines($message);
		$message = rtrim($message, $this->get_config('newline'));

		$line = explode($this->get_config('newline'), $message);
		$message = '';
		for($i = 0 ;$i < count($line); $i++)
		{
			$line_part = explode(' ', $line[$i]);
			$buf = '';
			for($e = 0; $e < count($line_part); $e++)
			{
				$word = $line_part[$e];
				if ($qp_mode and (strlen($word) > $length))
				{
					$space_left = $length - strlen($buf) - 1;
					if($e != 0)
					{
						if($space_left > 20)
						{
							$len = $space_left;
							
							if($is_utf8)
							{
								$len = $this->utf8_char_boundary($word, $len);
							}
							elseif (substr($word, $len - 1, 1) == "=")
							{
								$len--;
							}
							elseif(substr($word, $len - 2, 1) == "=")
							{
								$len -= 2;
							}
							
							$part = substr($word, 0, $len);
							$word = substr($word, $len);
							$buf .= ' ' . $part;
							$message .= $buf . sprintf("=%s", $this->get_config('newline'));
						}
						else
						{
							$message .= $buf . $soft_break;
						}	
						$buf = '';
					}
					
					while(strlen($word) > 0)
					{
						$len = $length;
						
						if($is_utf8)
						{
							$len = $this->utf8_char_boundary($word, $len);
						}
						elseif(substr($word, $len - 1, 1) === '=')
						{
							$len--;
						}
						elseif(substr($word, $len - 2, 1) === '=')
						{
							$len -= 2;
						}
						
						$part = substr($word, 0, $len);
						$word = substr($word, $len);

						if(strlen($word) > 0)
						{
							$message .= $part . sprintf("=%s", $this->get_config('newline'));
						}
						else
						{
							$buf = $part;
						}
					}
				}
				else
				{
					$buf_o = $buf;
					$buf .= ($e == 0) ? $word : (' ' . $word);

					if(strlen($buf) > $length and $buf_o != '')
					{
						$message .= $buf_o . $soft_break;
						$buf = $word;
					}
				}
			}
			$message .= $buf . $this->get_config('newline');
		}

		return $message;
	}
	
	/**
	 * Standardize newlines.
	 *
	 * @param	string	$string		string to prep
	 */
	protected static function prep_newlines($string)
	{
		return str_replace(array(
			"\r\n"	=> "\n",
			"\r"	=> "\n",
			"\n"	=> $this->get_config('newline'),
		));
	}
	
	/**
	 * Encodes a string in the given encoding.
	 *
	 * @param	string	$string		string to encode
	 * @param	string	$encoding	the encoding
	 * @return	string	encoded string
	 */
	public static function encode_string($string, $charset)
	{	
		$end = "?=";
		$start = '=?' . $charset . '?B?';
		$spacer = $end . "\r\n " . $start;
		$length = 75 - strlen($start) - strlen($end);
		$length = $length - ($length % 4);
		$string = base64_encode($string);
		$string = chunk_split($string, $length, $spacer);
		$spacer = preg_quote($spacer);
		$string = preg_replace("/" . $spacer . "$/", '', $string);
		$string = $start . $string . $end;
		return $string;
	}
	
	/**
	 * Wordwraps a string
	 *
	 * @param	string	$string	string to wrap
	 * @param	int		$length	the max line length
	 * @return	string	wrapped text
	 */
	protected static function wrap_string($string, $length = 76)
	{
		$length = ($length < 0) ? $length : 76;
	}
	
	/**
	 * Returns a formatted string of email addresses.
	 *
	 * @param	array	$addresses	array of adresses array(array(name=>name, email=>email));
	 */
	protected static function format_addresses($addresses)
	{
		$return = array();
		
		foreach($addresses as $recipient)
		{
			$recipient['name'] and $recipient['email'] = $recipient['name'].' <'.$recipient['email'].'>';
			$return[] = $recipient['email'];
		}
		
		return join(', ', $return);
	}
	
	/**
	 * Initiates the sending process.
	 *
	 * @return	bool	success boolean
	 */
	abstract protected function _send();
	
}