<?php

/**
 * ECM Render Context
 *
 * Stores all normalized data required to generate one certificate.
 *
 * The rendering engine receives this object instead of repeatedly
 * querying event, participant, session, template, and element tables.
 *
 * @package EventCertificateManager
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECM_Render_Context
{
    /**
     * Original normalized generation request.
     *
     * @var array
     */
    private $request = [];

    /**
     * Event database record.
     *
     * @var object
     */
    private $event;

    /**
     * Participant database record.
     *
     * @var object
     */
    private $participant;

    /**
     * Participant metadata indexed by meta key.
     *
     * @var array
     */
    private $participant_meta = [];

    /**
     * Optional session database record.
     *
     * Event-level certificates may not have a session.
     *
     * @var object|null
     */
    private $session;

    /**
     * Template database record.
     *
     * @var object
     */
    private $template;

    /**
     * Ordered template-element records.
     *
     * @var array
     */
    private $elements = [];

    /**
     * Create a rendering context.
     *
     * @param array       $request          Normalized generation request.
     * @param object      $event            Event record.
     * @param object      $participant      Participant record.
     * @param array       $participant_meta Participant metadata.
     * @param object|null $session          Optional session record.
     * @param object      $template         Template record.
     * @param array       $elements         Template elements.
     */
    public function __construct(
        $request,
        $event,
        $participant,
        $participant_meta,
        $session,
        $template,
        $elements
    ) {
        $this->request = $request;
        $this->event = $event;
        $this->participant = $participant;
        $this->participant_meta = $participant_meta;
        $this->session = $session;
        $this->template = $template;
        $this->elements = $elements;
    }

    /**
     * Return the normalized generation request.
     *
     * @return array
     */
    public function get_request()
    {
        return $this->request;
    }

    /**
     * Return the event record.
     *
     * @return object
     */
    public function get_event()
    {
        return $this->event;
    }

    /**
     * Return the participant record.
     *
     * @return object
     */
    public function get_participant()
    {
        return $this->participant;
    }

    /**
     * Return all participant metadata.
     *
     * @return array
     */
    public function get_participant_meta()
    {
        return $this->participant_meta;
    }

    /**
     * Return one participant metadata value.
     *
     * @param string $key     Metadata key.
     * @param mixed  $default Fallback value.
     *
     * @return mixed
     */
    public function get_participant_meta_value(
        $key,
        $default = ''
    ) {
        $key = sanitize_key($key);

        return array_key_exists($key, $this->participant_meta)
            ? $this->participant_meta[$key]
            : $default;
    }

    /**
     * Return the optional session record.
     *
     * @return object|null
     */
    public function get_session()
    {
        return $this->session;
    }

    /**
     * Determine whether this certificate belongs to a session.
     *
     * @return bool
     */
    public function has_session()
    {
        return $this->session !== null;
    }

    /**
     * Return the template record.
     *
     * @return object
     */
    public function get_template()
    {
        return $this->template;
    }

    /**
     * Return ordered template elements.
     *
     * @return array
     */
    public function get_elements()
    {
        return $this->elements;
    }

    /**
     * Return the event ID.
     *
     * @return int
     */
    public function get_event_id()
    {
        return (int) $this->event->id;
    }

    /**
     * Return the participant ID.
     *
     * @return int
     */
    public function get_participant_id()
    {
        return (int) $this->participant->id;
    }

    /**
     * Return the optional session ID.
     *
     * @return int|null
     */
    public function get_session_id()
    {
        return $this->session
            ? (int) $this->session->id
            : null;
    }

    /**
     * Return the template ID.
     *
     * @return int
     */
    public function get_template_id()
    {
        return (int) $this->template->id;
    }

    /**
     * Return the context as an array for debugging or logging.
     *
     * Do not expose this directly through public AJAX responses
     * because participant metadata may contain private information.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'request'          => $this->request,
            'event'            => $this->event,
            'participant'      => $this->participant,
            'participant_meta' => $this->participant_meta,
            'session'          => $this->session,
            'template'         => $this->template,
            'elements'         => $this->elements,
        ];
    }
}