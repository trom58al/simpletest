<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	MockObjects
     *	@version	$Id$
     */
    
    /**#@+
     * include SimpleTest files
     */
    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR . 'options.php');
    /**#@-*/
    
    /**
     *    Stashes an error for later. Useful for constructors
     *    until PHP gets exceptions.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class StickyError {
        var $_error = 'Constructor not chained';
        
        /**
         *    Sets the error to empty.
         *    @access public
         */
        function StickyError() {
            $this->_clearError();
        }
        
        /**
         *    Test for an outstanding error.
         *    @return boolean           True if there is an error.
         *    @access public
         */
        function isError() {
            return ($this->_error != '');
        }
        
        /**
         *    Accessor for an outstanding error.
         *    @return string     Empty string if no error otherwise
         *                       the error message.
         *    @access public
         */
        function getError() {
            return $this->_error;
        }
        
        /**
         *    Sets the internal error.
         *    @param string       Error message to stash.
         *    @access protected
         */
        function _setError($error) {
            $this->_error = $error;
        }
        
        /**
         *    Resets the error state to no error.
         *    @access protected
         */
        function _clearError() {
            $this->_setError('');
        }
    }
    
    /**
     *    Wrapper for TCP/IP socket.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleSocket extends StickyError {
        var $_handle;
        var $_is_open;
        var $_sent;
        
        /**
         *    Opens a socket for reading and writing.
         *    @param string $host      Hostname to send request to.
         *    @param integer $port     Port on remote machine to open.
         *    @param integer $timeout  Connection timeout in seconds.
         *    @access public
         */
        function SimpleSocket($host, $port, $timeout) {
            $this->StickyError();
            $this->_is_open = false;
            $this->_sent = '';
            if (! ($this->_handle = $this->_openSocket($host, $port, $error_number, $error, $timeout))) {
                $this->_setError("Cannot open [$host:$port] with [$error] within [$timeout] seconds");
            } else {
                $this->_is_open = true;
                SimpleTestCompatibility::setTimeout($this->_handle, $timeout);
            }
        }
        
        /**
         *    Writes some data to the socket and saves alocal copy.
         *    @param string $message       String to send to socket.
         *    @return boolean              True if successful.
         *    @access public
         */
        function write($message) {
            if ($this->isError() || ! $this->isOpen()) {
                return false;
            }
            $count = fwrite($this->_handle, $message);
            if (! $count) {
                if ($count === false) {
                    $this->_setError("Cannot write to socket");
                }
                return false;
            }
            fflush($this->_handle);
            $this->_sent .= $message;
            return true;
        }
        
        /**
         *    Reads data from the socket.
         *    @param integer $block_size       Size of chunk to read.
         *    @return integer                  Incoming bytes. False
         *                                     on error.
         *    @access public
         */
        function read($block_size = 255) {
            if ($this->isError() || !$this->isOpen()) {
                return false;
            }
            return fread($this->_handle, $block_size);
        }
        
        /**
         *    Accessor for socket open state.
         *    @return boolean           True if open.
         *    @access public
         */
        function isOpen() {
            return $this->_is_open;
        }
        
        /**
         *    Closes the socket preventing further reads.
         *    Cannot be reopened once closed.
         *    @return boolean           True if successful.
         *    @access public
         */
        function close() {
            $this->_is_open = false;
            return fclose($this->_handle);
        }
        
        /**
         *    Accessor for content so far.
         *    @return string        Bytes sent only.
         *    @access public
         */
        function getSent() {
            return $this->_sent;
        }
        
        /**
         *    Actually opens the low level socket.
         *    @param string $host          Host to connect to.
         *    @param integer $port         Port on host.
         *    @param integer $error_number Recipient of error code.
         *    @param string $error         Recipoent of error message.
         *    @param integer $timeout      Maximum time to wait for connection.
         *    @access protected
         */
        function _openSocket($host, $port, &$error_number, &$error, $timeout) {
            return @fsockopen($host, $port, $error_number, $error, $timeout);
        }
    }
    
    /**
     *    Wrapper for TCP/IP socket over TLS.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleSecureSocket extends SimpleSocket {
        
        /**
         *    Opens a secure socket for reading and writing.
         *    @param string $host      Hostname to send request to.
         *    @param integer $port     Port on remote machine to open.
         *    @param integer $timeout  Connection timeout in seconds.
         *    @access public
         */
        function SimpleSecureSocket($host, $port, $timeout) {
            $this->SimpleSocket($host, $port, $timeout);
        }
        
        /**
         *    Actually opens the low level socket.
         *    @param string $host          Host to connect to.
         *    @param integer $port         Port on host.
         *    @param integer $error_number Recipient of error code.
         *    @param string $error         Recipient of error message.
         *    @param integer $timeout      Maximum time to wait for connection.
         *    @access protected
         */
        function _openSocket($host, $port, &$error_number, &$error, $timeout) {
            return parent::_openSocket("tls://$host", $port, $error_number, $error, $timeout);
        }
    }
?>