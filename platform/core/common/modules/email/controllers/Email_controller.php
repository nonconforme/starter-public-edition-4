<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2014
 * @license The MIT License, http://opensource.org/licenses/MIT
 */

class Email_controller extends Core_Controller {
    
    public function __construct() {

        parent::__construct();

        $this->load->library('settings');
        $this->load->library('email');
        $this->load->library('registry');
    }

    public function _remap() {

        show_404();
    }

    /**
     * Sends an email message.
     * @param       array       $data       The email message data. It is an associative array
     *                                      with the following elements (not all of them are mandatory):
     *                                      'from', 'from_name', 'return_path', 'to' (string or array),
     *                                      'reply_to', 'reply_to_name', 'cc' (string or array),
     *                                      'subject' (mandatory), 'body' (mandatory), 'alt_body',
     *                                      'attach' (array of file names).
     * @return      boolean                 TRUE on success, FALSE on failure
     */
    public function index($data = array()) {

        $this->registry->delete('email_debugger');

        if (!$this->settings->get('mailer_enabled')) {

            $debug_message = 'Send email: E-mail service has not been activated.';

            $this->registry->set('email_debugger', $debug_message);
            log_message('error', $debug_message);

            return false;
        }

        if (isset($data['from']) && $data['from'] != '') {

            $from = $data['from'];

            if (isset($data['from_name']) && $data['from_name'] != '') {
                $from_name = $data['from_name'];
            } else {
                $from_name = null;
            }

        } else {

            $from = $this->settings->get('site_email');
            $from_name = $this->settings->get('site_name');
        }

        if (isset($data['reply_to']) && $data['reply_to'] != '') {

            $reply_to = $data['reply_to'];

            if (isset($data['reply_to_name']) && $data['reply_to_name'] != '') {
                $reply_to_name = $data['reply_to_name'];
            } else {
                $reply_to_name = null;
            }

        } else {

            $reply_to = $this->settings->get('notification_email');
            $reply_to_name = $this->settings->get('site_name');
        }

        if (isset($data['return_path']) && $data['return_path'] != '') {
            $return_path = $data['reply_to'];
        } else {
            $return_path = $this->settings->get('notification_email');
        }

        if (isset($data['to'])) {

            if (is_array($data['to']) && !empty($data['to'])) {
                $to = $data['to'];
            } elseif ($data['to'] != '') {
                $to = $data['to'];
            } else {
                $to = $this->settings->get('notification_email');
            }

        } else {

            $to = $this->settings->get('notification_email');
        }

        $cc_email = $this->settings->get('cc_email');

        if (!is_array($cc_email)) {

            if ($cc_email != '') {
                $cc_email = array($cc_email);
            } else {
                $cc_email = array();
            }
        }

        if (isset($data['cc'])) {

            if (!is_array($data['cc'])) {

                if ($data['cc'] != '') {
                    $cc = array($data['cc']);
                } else {
                    $cc = array();
                }

            } else {

                $cc = $data['cc'];
            }

        } else {

            $cc = array();
        }

        $subject = isset($data['subject']) ? trim($data['subject']) : '';
        $body = isset($data['body']) ? $data['body'] : '';
        $alt_body = isset($data['alt_body']) ? $data['alt_body'] : '';

        if ($subject != '' && trim($body) != '') {

            if (!empty($cc)) {
                $this->email->cc($cc);
            }

            if (isset($data['attach'])) {

                if (!is_array($data['attach'])) {
                    $data['attach'] = array((string) $data['attach']);
                }

                foreach ($data['attach'] as $attachment) {

                    if (!is_array($attachment)) {
                        $attachment = array('file' => $attachment);
                    }

                    $attachment['file'] = isset($attachment['file']) ? (string) $attachment['file'] : '';
                    $attachment['disposition'] = isset($attachment['disposition']) ? (string) $attachment['disposition'] : '';
                    $attachment['newname'] = isset($attachment['newname']) ? (string) $attachment['newname'] : null;
                    $attachment['mime'] = isset($attachment['mime']) ? (string) $attachment['mime'] : '';
                    $attachment['embedded_image'] = !empty($attachment['embedded_image']);
                    $attachment['key'] = isset($attachment['key']) ? (string) $attachment['key'] : null;

                    if ($attachment['file'] == '') {
                        continue;
                    }

                    if ($attachment['mime'] != '' && $attachment['newname'] == '') {
                        continue;
                    }

                    $this->email->attach($attachment['file'], $attachment['disposition'], $attachment['newname'], $attachment['mime'], $attachment['embedded_image']);

                    if ($attachment['key'] != '') {

                        if ($attachment['mime'] != '') {
                            $attachment['file'] = $attachment['newname'];
                        }

                        $attachment['cid'] = (string) $this->email->get_attachment_cid($attachment['file']);

                        if ($attachment['cid'] != '') {
                            $body = str_replace("cid:{$attachment['key']}", "cid:{$attachment['cid']}", $body);
                        }
                    }
                }
            }

            $this->email
                ->from($from, $from_name, $return_path)
                ->to($to)
                ->reply_to($reply_to, $reply_to_name)
                ->subject($subject)
                ->message($body);

            if (trim($alt_body) != '') {
                $this->email->set_alt_message($alt_body);
            }

            $result = (bool) $this->email->send();

            $debug_message = trim(strip_tags($this->email->print_debugger()));

            $this->registry->set('email_debugger', $debug_message);

            if (!$result && $debug_message != '') {
                log_message($result ? 'debug' : 'error', $debug_message);
            }

            return $result;
        }

        $debug_message = 'Send email: No subject or body text.';

        $this->registry->set('email_debugger', $debug_message);
        log_message('error', $debug_message);

        return false;
    }

}
