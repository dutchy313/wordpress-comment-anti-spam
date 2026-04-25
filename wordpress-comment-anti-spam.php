<?php
/**
 * Plugin Name: Wordpress Anti-Spam (Honeypot + Time Trap)
 * Description: Lightweight, no-captcha anti-spam for comments. Adds honeypot, time trap, link-limit, keyword filter, per-IP rate limiting, removes URL field, and blocks pingbacks.
 * Version: 1.0.0
 * Author: Oludotun Babayemi
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) { exit; }

final class Wordpress_Anti_Spam {
    const HP_FIELD      = 'ypas_hp';
    const TS_FIELD      = 'ypas_ts';
    const JS_FIELD      = 'ypas_js';
    const NONCE_FIELD   = 'ypas_nonce';
    const NONCE_PREFIX  = 'ypas_comment_';

    // Tweak these to taste
    private int $min_seconds          = 5;                    // Must spend ≥5s on form
    private int $max_seconds          = 2 * HOUR_IN_SECONDS;  // Form expires after 2h
    private int $max_links            = 0;                    // Max links allowed in a comment
    private int $rate_limit_seconds   = 30;                   // Min gap between comments per IP

    private array $banned_keywords = [
        'viagra','cialis','porn','casino','loan','crypto','forex','sex','escort',
        'betting','win money','work from home','shemale','xxx','seo services'
    ];

    public function __construct() {
        // Render our hidden fields for guests and logged-in users
        add_action('comment_form_after_fields',      [$this, 'render_fields']);
        add_action('comment_form_logged_in_after',   [$this, 'render_fields']);

        // Remove the "Website/URL" field (huge spam magnet)
        add_filter('comment_form_default_fields', function(array $fields) {
            unset($fields['url']);
            return $fields;
        });

        // Block pingbacks/trackbacks at the XML-RPC level as well
        add_filter('xmlrpc_methods', function(array $methods) {
            unset($methods['pingback.ping']);
            return $methods;
        });

        // Validate before WP inserts the comment
        add_filter('preprocess_comment', [$this, 'check_comment'], 0);
    }

    public function render_fields(): void {
        // Only print once per page render
        static $printed = false;
        if ($printed) { return; }
        $printed = true;

        $ts = time();

        // Nonce ties to the timestamp to prevent replay
        wp_nonce_field(self::NONCE_PREFIX . $ts, self::NONCE_FIELD, false, true);
        ?>
        <!-- YouthPrep Anti-Spam fields (hidden from users & assistive tech) -->
        <div class="ypas-fields" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
            <label for="<?php echo esc_attr(self::HP_FIELD); ?>">Leave this field empty</label>
            <input type="text"
                   name="<?php echo esc_attr(self::HP_FIELD); ?>"
                   id="<?php echo esc_attr(self::HP_FIELD); ?>"
                   value=""
                   tabindex="-1"
                   autocomplete="off" />
            <input type="hidden" name="<?php echo esc_attr(self::TS_FIELD); ?>" value="<?php echo esc_attr($ts); ?>" />
            <input type="hidden" name="<?php echo esc_attr(self::JS_FIELD); ?>" id="<?php echo esc_attr(self::JS_FIELD); ?>" value="0" />
        </div>
        <script>
            // Mark that JS ran – many bots won’t execute this.
            (function () {
                var el = document.getElementById('<?php echo esc_js(self::JS_FIELD); ?>');
                if (el) { el.value = '1'; }
            })();
        </script>
        <?php
    }

    public function check_comment(array $data): array {
        // Don’t block moderators replying from wp-admin (admin reply form won’t have our fields)
        if (is_admin() && current_user_can('moderate_comments')) {
            return $data;
        }

        // Graceful handling if fields are missing (e.g., custom integrations)
        $hp    = isset($_POST[self::HP_FIELD])    ? (string) $_POST[self::HP_FIELD]    : '';
        $ts    = isset($_POST[self::TS_FIELD])    ? (int) $_POST[self::TS_FIELD]       : 0;
        $nonce = isset($_POST[self::NONCE_FIELD]) ? (string) $_POST[self::NONCE_FIELD] : '';

        // 1) Nonce + timestamp must validate
        if (!$nonce || !$ts || !wp_verify_nonce($nonce, self::NONCE_PREFIX . $ts)) {
            wp_die(__('Comment failed a security check. Please go back and try again.', 'ypas'), 403);
        }

        // 2) Honeypot must be empty
        if (!empty($hp)) {
            wp_die(__('Spam detected (honeypot).', 'ypas'), 403);
        }

        // 3) Time trap (too fast or too old)
        $age = time() - $ts;
        if ($age < $this->min_seconds) {
            wp_die(__('You posted too quickly. Please wait a few seconds and try again.', 'ypas'), 403);
        }
        if ($age > $this->max_seconds) {
            wp_die(__('This comment form has expired. Please reload the page and try again.', 'ypas'), 403);
        }

        // 4) Per-IP rate limiting
        $ip = $this->client_ip();
        if ($ip) {
            $key  = 'ypas_last_' . md5($ip);
            $last = (int) get_transient($key);
            if ($last && (time() - $last) < $this->rate_limit_seconds) {
                wp_die(__('You are commenting too fast. Please slow down.', 'ypas'), 403);
            }
            set_transient($key, time(), $this->rate_limit_seconds);
        }

        // 5) Block pingbacks/trackbacks via comment type
        if (!empty($data['comment_type'])) {
            wp_die(__('Pingbacks/trackbacks are disabled here.', 'ypas'), 403);
        }

        // 6) Link limit
        $linkCount = 0;
        if (!empty($data['comment_content'])) {
            preg_match_all('#https?://#i', (string) $data['comment_content'], $matches);
            $linkCount = isset($matches[0]) ? count($matches[0]) : 0;
        }
        if ($linkCount > $this->max_links) {
            wp_die(sprintf(__('Too many links in your comment (max %d).', 'ypas'), $this->max_links), 403);
        }

        // 7) Basic keyword filter (tune list above for your audience)
        $content = strtolower((string) ($data['comment_content'] ?? ''));
        foreach ($this->banned_keywords as $word) {
            if ($word !== '' && strpos($content, strtolower($word)) !== false) {
                wp_die(__('Your comment looks like spam.', 'ypas'), 403);
            }
        }

        return $data;
    }

    private function client_ip(): string {
        // Conservative IP extraction (no trust of X-Forwarded-For here)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = is_string($ip) ? $ip : '';
        // Keep only valid IP characters
        return preg_replace('/[^0-9a-fA-F:\.]/', '', $ip);
    }
}

new Wordpress_Anti_Spam();

/*
 * Optional: If you ONLY want to run this on the blog/news page, you can wrap render_fields() with:
 *
 * if (!is_home() && !is_singular('post')) { return; }
 *
 * However, spam generally hits all comment forms, so global protection is recommended.
 */
