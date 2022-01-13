<?php

/**
 * @file
 * Contains Site Audit render helper
 */

namespace SiteAudit\Util;

class RenderHelper {

    /**
     * Output a Drupal render array, object or string as plain text.
     *
     * @param string $data
     *   Data to render.
     *
     * @return string
     *   The plain-text representation of the input.
     */
    public static function render($data)
    {
        if (is_array($data)) {
            $data = \Drupal::service('renderer')->renderRoot($data);
        }

        $data = \Drupal\Core\Mail\MailFormatHelper::htmlToText($data);
        return $data;
    }

}
