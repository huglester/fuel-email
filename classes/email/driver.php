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
	protected $attachments = array(
		'inline' => array(),
		'attachment' => array(),
	);
	
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
	 * Mail type
	 */
	protected $type = 'plain';
	
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
	 * Sets the body
	 *
	 * @param	string		$body			the message body
	 * @return	object		$this
	 */
	public function body($body)
	{
		$this->body = (string) $body;
		
		return $this;
	}
	
	/**
	 * Sets the alt body
	 *
	 * @param	string		$alt_body			the message alt body
	 * @return	object		$this
	 */
	public function alt_body($alt_body)
	{
		$this->alt_body = (string) $alt_body;
		
		return $this;
	}
	
	/**
	 * Sets the mail priority
	 *
	 * @param	string		$priority			the message priority
	 * @return	object		$this
	 */
	public function proirity($priority)
	{
		$this->config['proirity'] = $priority;
		
		return $this;
	}
	
	/**
	 * Sets the html body and optionally a generated alt body.
	 *
	 * @param	string	$html 			the body html
	 * @param	bool	$generate_alt	whether to generate the alt body, will set is html to true
	 * @param	bool	$auto_attach	whether to auto attach inline files
	 * @return	object	$this
	 */
	public function html_body($html, $generate_alt = null, $auto_attach = null)
	{
		$this->config['is_html'] = true;
		
		// Check settings
		$generate_alt = is_bool($generate_alt) ? $generate_alt : $this->config['generate_alt'];
		$auto_attach = is_bool($auto_attach) ? $auto_attach : $this->config['auto_attach'];
		
		// Remove html comments
		$html = preg_replace('/<!--(.*)-->/', '', (string) $html);
		
		// Remove css comments
		$html = preg_replace('/\/\*(.*)\*\//', '', $html);
				
		if($auto_attach)
		{
			// Auto attach all images
			preg_match_all("/(src|background)=\"(.*)\"/Ui", $html, $images);
			if( ! empty($images[2]))
			{
				foreach($images[2] as $i => $image_url)
				{
					// Don't attach absolute urls
					if(($beginning = substr($image_url, 0, 7)) !== 'http://' and $beginning !== 'htts://' and substr($image_url, 0, 4) !== 'cid:')
					{
						$cid = 'cid:'.md5(pathinfo($image_url, PATHINFO_BASENAME));
						if( ! isset($this->attachments['inline'][$cid]))
						{
							$this->attach($image_url, true, $cid);
						}
						$html = preg_replace("/".$images[1][$i]."=\"".preg_quote($image_url, '/')."\"/Ui", $images[1][$i]."=\"".$cid."\"", $html);
					}
				}
			}
		}
		
		$this->body = $html;
		
		$generate_alt and $this->alt_body = static::generate_alt($html, $this->config['wordwrap'], $this->config['newline']);
		
		return $this;
	}
	
	/**
	 * Sets the message subject
	 *
	 * @param	string		$subject	the message subject
	 * @return	object		$this
	 */
	 public function subject($subject)
	 {
	 	$this->subject = $subject;
	 	
	 	return $this;
	 }
	
	/**
	 * Sets the from address and name
	 *
	 * @param	string		$email	the from email address
	 * @param	string		$name	the optional from name
	 * @return	object		$this
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
	 * @param	string	$mime		the file's mime-type
	 * @param	string	$mime		the file's mime-type
	 */
	public function attach($file, $inline = false, $cid = null, $mime = null)
	{
		if( ! is_array($file))
		{
			$file = array($file, pathinfo($file, PATHINFO_BASENAME));
		}
		
		// Encode the file contents
		$contents = static::encode_file($file[0], $this->config['newline'], $this->config['wordwrap']);
		
		$disp = ($inline) ? 'inline' : 'attachment';
		
		$cid = empty($cid) ? 'cid:'.md5($file[1]) : 'cid:'.ltrim($cid, 'cid:');
		
		// Fetch the file mime type.
		$mime or $mime = static::attachment_mime($file[0]);
		
		$this->attachments[$disp][$cid] = array(
			'file' => $file,
			'contents' => $contents,
			'mime' => $mime,
			'disp' => $disp,
			'cid' => $cid,
		);
		
		return $this;
	}
	
	/**
	 * Attach a file using string input
	 *
	 * @param	string	$contents	file contents
	 * @param	string	$filename	the files name
	 * @param	bool	$inline		whether it's an inline attachment
	 * @param	string	$mime		the file's mime-type
	 * @return	object	$this
	 */
	public function string_attach($contents, $filename, $cid = null, $inline = false, $mime = null)
	{
		$disp = ($inline) ? 'inline' : 'attachment';
		
		$cid = empty($cid) ? 'cid:'.md5($filename) : 'cid:'.ltrim($cid, 'cid:');
		
		$mime or $mime = static::attachment_mime($filename);
		
		$this->attachments[$disp][$cid] = array(
			'file' => array(1=>$file),
			'contents' => $contents,
			'mime' => $mime,
			'disp' => $disp,
			'cid' => $cid,
		);
		
		return $this;
	}
	
	/**
	 * Encodes a file
	 *
	 * @param	string	$filename	path to the file
	 * @param	string	$encoding	the encoding
	 * @retun	string	the encoded file data
	 */
	 public static function encode_file($file, $newline, $length = 76)
	 {
	 	// File not found? Give 'm hell!
		if( ! is_file($file))
		{
			throw new \AttachmentNotFoundException('Email attachment not found: '.$file);
		}
		
		if(($contents = file_get_contents($file)) === false or empty($contents))
		{
			throw new \InvalidAttachmentsException('Could not read attachment or attachment is empty: '.$file);
		}
		
		return chunk_split(base64_encode($contents), $length, "\r\n");
	 }
	
	/**
	 * Clear the attachments list.
	 *
	 * @return	object	$this
	 */
	public function clear_attachments()
	{
		$this->attachments = array(
			'inline' => array(),
			'attachment' => array(),
		);
		
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
		
		// Message part boundary, (separates message and attachments).
		$this->boundaries[0] = 'B1_'.$uniq_id;
		
		// Message body boundary (separates message, alt message)
		$this->boundaries[1] = 'B2_'.$uniq_id;
		
		$this->boundaries[2] = 'B3_'.$uniq_id;
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

		if(($from = $this->config['from']['email']) === false or empty($from))
		{
			throw new \Fuel_Exception('Cannot send without from address.');
		}
		
		// Check which validation bool to use
		is_bool($validate) or $validate = $this->config['validate'];
		
		// Validate the email addresses if specified
		if($validate and ($failed = $this->validate_addresses()) !== true)
		{
			$this->invalid_addresses = $failed;
			return \Email::FAILED_VALIDATION;
		}
		
		// Reset the headers
		$this->headers = array();
		
		// Set the email boundaries
		$this->set_boundaries();
		
		// Set RFC 822 formatted date
		$this->set_header('Date', date('r'));
		
		// Set return path
		$this->set_header('Return-Path', $this->config['from']['email']);
		
		if(($this instanceof \Email_Driver_Mail) !== true)
		{
			if( ! empty($this->to))
			{
				// Set from
				$this->set_header('To', static::format_addresses($this->to));
			}
			
			// Set subject
			$this->set_header('Subject', $this->subject);
		}
		
		$this->set_header('From', static::format_addresses(array($this->config['from'])));
		
		foreach(array('cc' => 'Cc', 'bcc' => 'Bcc', 'reply_to' => 'Reply-To') as $list => $header)
		{
			if(count($this->cc) > 0)
			{
				$this->set_header('Cc', static::format_addresses($this->cc));
			}
		}
		
		// Set message id
		$this->set_header('Message-ID', $this->get_message_id());
		
		// Set mime version
		$this->set_header('MIME-Version', '1.0');
		
		// Set priority
		$this->set_header('X-Priority', $this->config['priority']);
		
		// Set mailer useragent
		$this->set_header('X-Mailer', $this->config['useragent']);
		
		$newline =$this->config['newline'];
		
		$this->type = $this->get_mail_type();
		
		$encoding = $this->config['encoding'];
		$charset = $this->config['charset'];
		
		if($this->type !== 'plain' and $this->type !== 'html')
		{
			$this->set_header('Content-Type', $this->get_content_type($this->type, $newline."\tboundary=\"".$this->boundaries[0].'"'));
		}
		else
		{
			$this->set_header('Content-Transfer-Encoding', $encoding);
			$this->set_header('Content-Type', 'text/'.$this->type.'; charset="'.$this->config['charset'].'"');
		}
		
		// Set wordwrapping
		$wrapping = $this->config['wordwrap'];
		$qp_mode = $encoding === 'quoted-printable';
		$wrapping and $this->body = static::wrap_text(static::encode_string($this->body, $encoding, $newline), $wrapping, $charset, $newline, $qp_mode);
		$wrapping and $this->alt_body = static::wrap_text(static::encode_string($this->alt_body, $encoding, $newline), $wrapping, $charset, $newline, $qp_mode);
		
		if( ! $this->_send())
		{
			return \Email::FAILED_SEND;
		}
		
		return \Email::SEND;
	}
	
	/**
	 * Get the invalid addresses
	 *
	 * @return	array	an array of invalid email addresses
	 */
	public function get_invalid_addresses()
	{
		return $this->invalid_addresses;
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
	protected function get_header($header = null, $formatted = true)
	{
		if($header === null)
		{
			return $this->headers;
		}
		
		if(array_key_exists($header, $this->headers))
		{
			$prefix = ($formatted) ? $header.': ' : '';
			$suffix = ($formatted) ? $this->config['newline'] : '';
			return $prefix.$this->headers[$header].$suffix;
		}
		
		return '';
	}
	
	/**
	 * Get the attachment headers
	 *
	 */
	protected function get_attachment_headers($type, $boundary)
	{
		$return = '';
		
		$newline = $this->config['newline'];
		
		foreach($this->attachments[$type] as $attachment)
		{
			$return .= '--'.$boundary.$newline;
			$return .= 'Content-Type: '.$attachment['mime'].'; name="'.$attachment['file'][1].'"'.$newline;
			$return .= 'Content-Transfer-Encoding: base64'.$newline;
			$type === 'inline' and $return .= 'Content-ID: <'.substr($attachment['cid'], 4).'>'.$newline;
			$return .= 'Content-Disposition: '.$type.'; filename="'.$attachment['file'][1].'"'.$newline.$newline;
			$return .= $attachment['contents'].$newline.$newline;
		}
		
		return $return;
	}
	
	/**
	 * Get a unique message id
	 *
	 * @return	string	the message id
	 */
	protected function get_message_id()
	{
		$from = $this->config['from']['email'];
		return "<".uniqid('').strstr($from, '@').">";
	}
	
	/**
	 * Returns the mail's type
	 *
	 * @return	string		mail type
	 */
	protected function get_mail_type()
	{
		$return = $this->config['is_html'] ? 'html' : 'plain' ;
		$alt = trim($this->alt_body);
		$return .= ($this->config['is_html'] and ! empty($alt)) ? '_alt' : '';
		$return .= ($this->config['is_html'] and count($this->attachments['inline'])) ? '_inline' : '';
		$return .= (count($this->attachments['attachment'])) ? '_attach' : '';
		return $return;
	}
	
	/**
	 * Returns the content type
	 *
	 * @param	string		mail type
	 * @return	string		mail content type
	 */
	protected function get_content_type($mail_type, $boundary)
	{	
		switch($mail_type)
		{
			case 'plain':
				return 'text/plain';
			case 'plain_attach':
			case 'html_attach':
				return 'multipart/related; '.$boundary;
			case 'html':
				return 'text/html';
			case 'html_alt_attach':
			case 'html_alt_inline':
			case 'html_alt_inline_attach':
				return 'multipart/mixed; '.$boundary;
			case 'html_alt':
			case 'html_inline':
				return 'multipart/alternative; '.$boundary;
			default:
				throw new \Fuel_Exception('Invalid content-type'.$mail_type);
		}
	}
	
	/**
	 * Builds the headers and body
	 *
	 * @return	array	an array containing the headers and the body
	 */
	protected function build_message()
	{
		$newline = $this->config['newline'];
		$charset = $this->config['charset'];
		$encoding = $this->config['encoding'];
	
		$headers = '';
			
		foreach(array('Date', 'Return-Path', 'From', 'To', 'Cc', 'Bcc', 'Reply-to', 'Message-ID', 'X-Priority', 'X-Mailer', 'MIME-Version', 'Content-Type') as $part)
		{
			$headers .= $this->get_header($part);
		}
		
		$headers .= $newline;
		$body = '';

		if($this->type === 'plain' or $this->type === 'html')
		{
			$body = $this->body;
		}
		else
		{
			switch($this->type)
			{
				case 'html_alt':
					$body .= '--'.$this->boundaries[0].$newline;				
					$body .= 'Content-Type: text/plain; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->alt_body.$newline.$newline;
					$body .= '--'.$this->boundaries[0].$newline;	
					$body .= 'Content-Type: text/html; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->body.$newline.$newline;
					$body .= '--'.$this->boundaries[0].'--';
					break;
				case 'plain_attach':
				case 'html_attach':
				case 'html_inline':
					$body .= '--'.$this->boundaries[0].$newline;
					$text_type = (stripos($this->type, 'html') !== false) ? 'html' : 'plain';				
					$body .= 'Content-Type: text/'.$text_type.'; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->body.$newline.$newline;
					$attach_type = (stripos($this->type, 'attach') !== false) ? 'attachment' : 'inline';
					$body .= $this->get_attachment_headers($attach_type, $this->boundaries[0]);
					$body .= '--'.$this->boundaries[0].'--';
					break;
				case 'html_alt_attach':
				case 'html_inline_attach':
					$body .= '--'.$this->boundaries[0].$newline;
					$body .= 'Content-Type: multipart/alternative;'.$newline."\t boundary=\"{$this->boundaries[1]}\"".$newline.$newline;
					if(stripos($this->type, 'alt') !== false)
					{
						$body .= '--'.$this->boundaries[1].$newline;
						$body .= 'Content-Type: text/plain; charset="'.$charset.'"'.$newline;
						$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
						$body .= $this->alt_body.$newline.$newline;
					}
					$body .= '--'.$this->boundaries[1].$newline;
					$body .= 'Content-Type: text/html; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->body.$newline.$newline;
					if(stripos($this->type, 'inline') !== false)
					{
						$body .= $this->get_attachment_headers('inline', $this->boundaries[1]);
						$body .= $this->alt_body.$newline.$newline;
					}
					$body .= '--'.$this->boundaries[1].'--'.$newline.$newline;
					$body .= $this->get_attachment_headers('attachment', $this->boundaries[0]);
					$body .= '--'.$this->boundaries[0].'--';
					break;
				case 'html_alt_inline_attach':
					$body .= '--'.$this->boundaries[0].$newline;
					$body .= 'Content-Type: multipart/alternative;'.$newline."\t boundary=\"{$this->boundaries[1]}\"".$newline.$newline;
					$body .= '--'.$this->boundaries[1].$newline;
					$body .= 'Content-Type: text/plain; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->alt_body.$newline.$newline;
					$body .= '--'.$this->boundaries[1].$newline;
					$body .= 'Content-Type: multipart/related;'.$newline."\t boundary=\"{$this->boundaries[2]}\"".$newline.$newline;
					$body .= '--'.$this->boundaries[2].$newline;
					$body .= 'Content-Type: text/html; charset="'.$charset.'"'.$newline;
					$body .= 'Content-Transfer-Encoding: '.$this->config['encoding'].$newline.$newline;
					$body .= $this->body.$newline.$newline;
					$body .= $this->get_attachment_headers('inline', $this->boundaries[2]);
					$body .= $this->alt_body.$newline.$newline;
					$body .= '--'.$this->boundaries[2].'--'.$newline.$newline;
					$body .= '--'.$this->boundaries[1].'--'.$newline.$newline;
					$body .= $this->get_attachment_headers('attachment', $this->boundaries[0]);
					$body .= '--'.$this->boundaries[0].'--';
					break;
				
			}
		
		}
		
		return array(
			'header' => $headers,
			'body' => $body,
		);
	}
	
	/**
	 * Returns the uft8 character boundary
	 *
	 * @param	string	$encoded_text	utf-8 encoded text
	 * @param	int		$max_length		the max boundary position
	 * @return	int		the fount char boundary
	 */
	public static function utf8_char_boundary($encoded_text, $max_length) {
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
	
	/**
	 * Wraps the body or alt text
	 *
	 * @param	string	$message	the text to wrap
	 * @param	int		$length		the max line length
	 * @param	string	$charset	the text charset
	 * @param	string	$newline	the newline delimiter
	 * @param	bool	$qp_mode	whether the text is quoted printable encoded
	 */
	protected static function wrap_text($message, $length, $charset, $newline, $qp_mode = false)
	{
		$length = ($length > 76) ? 76 : $length;
	
		$soft_break = ($qp_mode) ? " =".$newline : $newline;
		$is_utf8 = (strtolower($charset) == "utf-8");

		$message = static::prep_newlines($message, $newline);
		$message = rtrim($message, $newline);

		$line = explode($newline, $message);
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
								$len = static::utf8_char_boundary($word, $len);
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
							$buf .= ' '.$part;
							$message .= $buf.'='.$newline;
						}
						else
						{
							$message .= $buf.$soft_break;
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
							$message .= $part.'=%'.$newline;
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
					$buf .= ($e == 0) ? $word : (' '.$word);

					if(strlen($buf) > $length and $buf_o != '')
					{
						$message .= $buf_o.$soft_break;
						$buf = $word;
					}
				}
			}
			$message .= $buf.$newline;
		}

		return $message;
	}
	
	/**
	 * Standardize newlines.
	 *
	 * @param	string	$string		string to prep
	 * @param	string	$newline	the newline delimiter
	 * @return	string	string with standardized newlines
	 */
	protected static function prep_newlines($string, $newline = null)
	{
		$newline or $newline = \Config::get('email.defaults.newline');
		$replace = array(
			"\r\n"	=> "\n",
			"\r"	=> "\n",
			"\n"	=> $newline,
		);
		return str_replace(array_keys($replace), array_values($replace), $string);
	}
	
	/**
	 * Encodes a string in the given encoding.
	 *
	 * @param	string	$string		string to encode
	 * @param	string	$encoding	the charset
	 * @return	string	encoded string
	 */
	public static function encode_string($string, $encoding, $newline = null)
	{
		$newline or $newline = \Config::get('email.defaults.newline', "\r\n");
	
		switch($encoding)
		{
			case 'quoted-printable':
				return quoted_printable_encode($string);
			case '7bit':
			case '8bit':
				return static::prep_newlines(rtrim($string, $newline), $newline);
			case 'base64':
				return chunk_split(base64_encode($str), 76, $newline);
			default:
				throw new \InvalidEmailStringEncoding($encoding.' is not a supported encoding method.');
		}
	}
	
	/**
	 * Returns a formatted string of email addresses.
	 *
	 * @param	array	$addresses	array of adresses array(array(name=>name, email=>email));
	 * @return	string	correctly formatted email addresses
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
	 * Generates an alt body
	 *	
	 * @param	string	$html		html body to al body generate from
	 * @param	int		$wordwrap	wordwrap length
	 * @param	string	$newline	line separator to use
	 * @return	string	the generated alt body
	 */
	protected static function generate_alt($html, $wordwrap, $newline)
	{
		$html = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $html)));
		$lines = explode($newline, $html);
		$result = array();
		$first_newline = true;
		foreach($lines as $line)
		{
			$line = trim($line);
			if(($empty = empty($line)) === false or $first_newline === true)
			{
				$first_newline = ! $empty;
				$result[] = $line;
			}
		};
		
		$html = join($newline, $result);
		return wordwrap($html, $wordwrap, $newline, true);
	}
	
	/**
	 * Initiates the sending process.
	 *
	 * @return	bool	success boolean
	 */
	abstract protected function _send();
	
}