<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
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
	public function get_config($key, $default)
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
	public function from(string $email, $name = false)
	{
		$this->config['from']['name'] = (string) $name;
		$this->config['from']['email'] = (string) $email;
		
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
	 * 
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
		// File not found? Give 'm hell!
		if( ! is_array($file))
		{
			$file = array($file, pathinfo($file, PATHINFO_BASENAME));
		}
		
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
				if( ! filter_var($address, FILTER_VALIDATE_EMAIL))
				{
					$failed = $recipient;
				}
			}
		}
		
		if(count($faild) === 0)
		{
			return true;
		}
		
		return $failed;
	}
		
	/**
	 * Initiates the sending process.
	 */
	public function send($validate = null)
	{
		if((empty($this->to) and empty($this->cc) and empty($this->bcc)) or empty($this->config['from']['name']))
		{
			throw new \Fuel_Exception('Cannot send email without recipients.');
		}
		
		if($from = $this->get_config('from.email', false) or empty($from))
		{
			throw new \Fuel_Excetion('Cannot send without from address.');
		}
		
		is_bool($validate) or $validate = $this->get_config('validate', true);
		
		if($validate and ! $this->validate_email)
		{
			return \Email::FAILED_VALIDATION;
		}
		
		if( ! $this->_send())
		{
			return \Email::FAILED_SEND;
		}
		
		return \Email::SEND;
	}
	
	/**
	 * Initiates the sending process.
	 *
	 * @return	bool	success boolean
	 */
	abstract protected function _send();
	
}