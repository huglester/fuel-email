<?php

return array(

	/**
	 * Default settings
	 */
	'defaults' => array(
	
		/**
		 * Mail useragent string
		 */
		'user_agent'	=> 'Fuel, PHP 5.3 Framework',
		/**
		 * Mail driver (mail, smpt, sendmail)
		 */
		'driver'		=> 'mail',
		
		/**
		 * Whether to send as html, set to null for autodetection.
		 */
		'is_html'		=> null,
		
		/**
		 * Email charset
		 */
		'charset'		=> 'utf-8',
		
		/**
		 * Email priority
		 */
		'priority'		=> \Email::P_NORMAL,
		
		/**
		 * Default sender details
		 */
		'from'		=> array(
			'email'		=> false,
			'name'		=> false,
		),
		
		/**
		 * Whether to validate email addresses
		 */
		'validate'	=> true,
		
		/**
		 * Wordwrap size, set to null, 0 or false to disable wordwrapping
		 */
		'wordwrap'	=> 76,
		
		/**
		 * Path to sendmail
		 */
		'sendmail_path' => '/usr/sbin/sendmail',
		
		/**
		 * Sendmail settings
		 */
		'sendmail'	=> array(
			'host'		=> '',
			'port'		=> 25,
			'username'	=> '',
			'password'	=> '',
			'timeout'	=> 5,
		),
		
		/**
		 * Newline 
		 */
		'newline'	=> "\n",
		'crfl'		=> "\n",
	),
	
	/**
	 * Default setup group
	 */
	'default_setup' => 'default',
	
	/**
	 * Setup groups
	 */
	'setups' => array(
		'default' => array(),
	),
	
);