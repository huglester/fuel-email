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


class Email_Driver_Mail extends \Email_Driver {

	protected function _send()
	{
		$message = $this->build_message();
		
		print_r($message);
		
		return @mail(static::format_addresses($this->to), $this->subject, $message['body'], $message['header'], '-oi -f '.$this->config['from']['email']);
	}
	
}