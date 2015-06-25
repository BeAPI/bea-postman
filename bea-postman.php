<?php

/*
 Plugin Name: BEA Postman
 Version: 0.1
 Plugin URI: https://github.com/BeAPI/bea-postman
 Description: Postman class for templating and sending emails
 Author: Beapi
 Author URI: http://www.beapi.fr

 ----

 Copyright 2015 Beapi Technical team (technique@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class BEA_Postman {

	/**
	 * Message subject
	 * @var string
	 */
	private $subject = '';

	/**
	 * The emails to send the message, possibly array
	 *
	 * @var string|array
	 */
	private $emails;

	/**
	 * Filepath for the send
	 *
	 * @var string|array
	 */
	private $files;

	/**
	 * Template name to load for the replacements
	 *
	 * @var string
	 */
	private $template;

	/**
	 * Data for the replacements
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * @param $subject
	 * @param $email
	 * @param $template : the template to use
	 * @param string|array $files : the files to send with the email, full path
	 */
	function __construct( $subject, $emails, $template, $files = array() ) {
		$this->subject  = $subject;
		$this->emails   = $emails;
		$this->files    = $files;
		$this->template = $template;
	}

	/**
	 * Remove unwanted data from the array
	 *
	 * @param $value
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function cleanup_data( $value ) {
		return ! is_array( $value ) && ! is_object( $value );
	}

	/**
	 * Load the template given
	 *
	 * @param string $tpl : the template name to load
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function load_template( $tpl = '' ) {
		if ( empty( $tpl ) ) {
			return false;
		}

		$tpl_path = self::locate_template( $tpl );
		if ( false === $tpl_path ) {
			return false;
		}

		include( $tpl_path );

		return true;
	}

	/**
	 * Locate template in the theme or plugin if needed
	 *
	 * @param string $tpl : the tpl name
	 *
	 * @return bool|string
	 * @author Nicolas Juen
	 */
	public static function locate_template( $tpl = '' ) {
		if ( empty( $tpl ) ) {
			return false;
		}

		// Locate from the theme
		$located = locate_template( array( '/bea-postman/' . $tpl . '-html.tpl' ), false, false );
		if ( ! empty( $located ) ) {
			return $located;
		}

		return false;
	}

	/**
	 * Change the email content type to text/html
	 *
	 * @return string
	 * @author Nicolas Juen
	 */
	public static function email_content_type() {
		return 'text/html';
	}

	/**
	 * Set the mail for the send
	 *
	 * @param string|array $emails
	 */
	public function set_mail( $emails ) {
		$this->emails = $emails;
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function get_template() {
		return (string) $this->template;
	}

	/**
	 * Set the current template
	 *
	 * @param string $template
	 */
	public function set_template( $template ) {
		$this->template = $template;
	}

	/**
	 * Add one entry to the data array
	 *
	 * @param $data
	 * @param $name
	 */
	public function add_data( $data, $name ) {
		$this->data[ $name ] = $data;
	}

	/**
	 * Send a simple mail
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function send() {
		if ( empty( $this->email ) || empty( $this->subject ) || empty( $this->template ) ) {
			return false;
		}

		/**
		 * Get the data to the
		 */
		$data = $this->get_data();

		// Mix header, content and footer together
		$header  = self::generate_email_part( $data, 'header' );
		$message = self::generate_content( $data, $this->template );
		$footer  = self::generate_email_part( $data, 'footer' );

		/**
		 * Do not try to send empty messages
		 */
		if ( empty( $message ) ) {
			return false;
		}

		/**
		 * Before the email send
		 */
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'email_content_type' ) );
		do_action( 'bea_postman_before_send', $this );

		$mailed = false;
		if ( true === apply_filters( 'bea_postman_before_send', true, $this ) ) {
			/**
			 * Send email to the given email
			 */
			$headers = apply_filters( 'bea_postman_headers', '' );
			$mailed  = wp_mail( $this->get_emails(), $this->get_subject(), $header . $message . $footer, $headers, $this->get_files() );
		}

		/**
		 * After the email send
		 */
		do_action( 'bea_postman_after_send', $this, $mailed );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'email_content_type' ) );

		return apply_filters( 'bea_postman_mailed', $mailed, $this );
	}

	/**
	 * Get set data and filtered ones
	 *
	 * @return array
	 */
	public function get_data() {
		// Defaults data
		$defaults = array(
			'home_url'         => home_url( '/' ),
			'site_name'        => get_bloginfo( 'name' ),
			'site_description' => get_bloginfo( 'description' ),
		);

		$this->data = wp_parse_args( $this->data, $defaults );

		return apply_filters( 'bea_postman_mail_data', $this->data );
	}

	/**
	 * Override all at once the data
	 *
	 * @param $data
	 *
	 * @author Nicolas Juen
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}

	/**
	 * Return the content of the footer/header
	 * Default footer is loaded
	 *
	 * @param array $data : data to display
	 * @param string $type : header or footer
	 *
	 * @return string|false on failure
	 */
	public static function generate_email_part( $data = array(), $type = '' ) {
		switch ( $type ) {
			case 'header':
				return self::generate_content( $data, 'header' );
				break;
			case 'footer':
				return self::generate_content( $data, 'footer' );
				break;
			default:
				return false;
				break;

		}
	}

	/**
	 * Generate the email content with the data
	 * Array like $data['name'] => 'content' will be transformed to $data['NAME'] => 'content'
	 * Keys in the templates have to be %%NAME%% to be replaced
	 * @access public
	 *
	 * @param array $data . (default: array())
	 * @param string $template_name
	 *
	 * @return boolean|array
	 */
	private static function generate_content( $data = array(), $template_name ) {
		if ( ! isset( $data ) || empty( $data ) || ! isset( $template_name ) || empty( $template_name ) ) {
			return false;
		}

		// Locate the template
		$file    = self::locate_template( $template_name );
		$message = '';

		// If file not accessible, return empty string
		if ( empty( $file ) || ! is_readable( $file ) ) {
			return $message;
		}

		// Make the replacements
		return self::make_replacements( file_get_contents( $file ), $data );
	}

	/**
	 * Make the replacements
	 *
	 * @param $content
	 * @param $data
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public static function make_replacements( $content, $data ) {
		// Sanitize Data
		$data = self::sanitize_data( $data );

		// While there is content to replace, replace
		while ( self::is_replacements( $content, $data ) ) {
			$content = self::replace_data( $content, $data );
		}

		return $content;
	}

	/**
	 * Sanitize and create the data
	 *
	 * @param array $data
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	private static function sanitize_data( $data ) {
		// Clean data
		$data       = array_filter( $data, array( __CLASS__, 'cleanup_data' ) );
		$data_final = array();
		foreach ( $data as $tag => $value ) {
			$data_final[ '%%' . strtoupper( $tag ) . '%%' ] = $value;
		}

		return $data_final;
	}

	/**
	 * Check whenever there is replacements to do on message
	 *
	 * @param $content
	 * @param $data
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private static function is_replacements( $content, $data ) {
		foreach ( $data as $tag => $value ) {
			if ( false !== strpos( $content, $tag ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace data in the content
	 *
	 *
	 * @param string $content
	 * @param array $data
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	private static function replace_data( $content, $data ) {
		foreach ( $data as $tag => $replacement ) {
			$content = str_replace( $tag, $replacement, $content );
		}

		return $content;
	}

	/**
	 * @return string|array
	 */
	public function get_emails() {
		return $this->emails;
	}

	/**
	 * Get the subject of the mail
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;
	}

	/**
	 * @return string|array
	 */
	public function get_files() {
		return $this->files;
	}
}