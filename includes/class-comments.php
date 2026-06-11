<?php
/**
 * Comment Spam Protection — FREE feature.
 *
 * Protects WordPress comment forms from bots and spammers using the
 * same checks as the rest of the plugin: honeypot, time check, rate
 * limiting, IP/email/keyword blocklist, content scoring, and disposable
 * email detection.
 *
 * Skips: trackbacks, pingbacks, and logged-in moderators/admins.
 *
 * Uses two WordPress hooks:
 *  - preprocess_comment  → hard block (die) for clear bot signals
 *  - pre_comment_approved → return 'spam' for scored content (goes to spam queue)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Comments {

    private array $opts;

    public function __construct( array $opts ) {
        $this->opts = $opts;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        // Inject honeypot + timestamp into comment form
        add_action( 'comment_form_after_fields',    [ $this, 'inject_fields' ] );
        add_action( 'comment_form_logged_in_after', [ $this, 'inject_fields' ] );

        // Hard block: honeypot, time check, rate limit, blocklist
        add_filter( 'preprocess_comment', [ $this, 'run_hard_checks' ] );

        // Soft block: content scoring → marks as spam (not wp_die)
        add_filter( 'pre_comment_approved', [ $this, 'score_comment' ], 10, 2 );
    }

    // ─── Field Injection ──────────────────────────────────────────────────────

    public function inject_fields(): void {
        $hp  = esc_attr( DFSAS_Helpers::honeypot_field_name() );
        $ts  = esc_attr( DFSAS_Helpers::timestamp_field_name() );
        $enc = esc_attr( DFSAS_Helpers::encrypt_timestamp( time() ) );
        echo '<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">';
        echo '<input type="text" name="' . $hp . '" value="" autocomplete="off" tabindex="-1" />';
        echo '</div>';
        echo '<input type="hidden" name="' . $ts . '" value="' . $enc . '" />';
    }

    // ─── Hard Checks (wp_die on failure) ─────────────────────────────────────

    public function run_hard_checks( array $commentdata ): array {
        // Skip trackbacks, pingbacks, and logged-in moderators
        if ( in_array( $commentdata['comment_type'] ?? '', [ 'trackback', 'pingback' ], true ) ) {
            return $commentdata;
        }
        if ( is_user_logged_in() && current_user_can( 'moderate_comments' ) ) {
            return $commentdata;
        }

        $ip      = DFSAS_Helpers::get_client_ip();
        $email   = sanitize_email( $commentdata['comment_author_email'] ?? '' );
        $content = $commentdata['comment_content'] ?? '';

        // ── 1. Honeypot ───────────────────────────────────────────────────────
        $hp = DFSAS_Helpers::honeypot_field_name();
        if ( isset( $_POST[ $hp ] ) && '' !== $_POST[ $hp ] ) {
            $this->block( 'honeypot_filled', $ip, $email );
        }

        // ── 2. Time Check ─────────────────────────────────────────────────────
        $ts_name = DFSAS_Helpers::timestamp_field_name();
        if ( ! empty( $_POST[ $ts_name ] ) ) {
            $submitted = DFSAS_Helpers::decrypt_timestamp( sanitize_text_field( $_POST[ $ts_name ] ) );
            $min       = max( 1, (int) ( $this->opts['time_check_min_seconds'] ?? 3 ) );
            if ( ! $submitted || ( time() - $submitted ) < $min ) {
                $this->block( 'submitted_too_fast', $ip, $email );
            }
        }

        // ── 3. Rate Limiter ───────────────────────────────────────────────────
        if ( ! empty( $this->opts['enable_rate_limiter'] ) ) {
            $max     = max( 1,  (int) ( $this->opts['rate_limit_max']     ?? 5     ) );
            $window  = max( 60, (int) ( $this->opts['rate_limit_window']  ?? 3600  ) );
            $lockout = max( 60, (int) ( $this->opts['rate_limit_lockout'] ?? 86400 ) );
            $lk      = 'dfsas_lo_' . md5( $ip );
            $rk      = 'dfsas_rl_' . md5( $ip );

            if ( get_transient( $lk ) !== false ) {
                $this->block( 'rate_limited', $ip, $email );
            }
            $count = (int) get_transient( $rk );
            $count === 0
                ? set_transient( $rk, 1, $window )
                : set_transient( $rk, $count + 1, $window );
            if ( ( $count + 1 ) > $max ) {
                set_transient( $lk, 1, $lockout );
                $this->block( 'rate_limited', $ip, $email );
            }
        }

        // ── 4. IP Blocklist ───────────────────────────────────────────────────
        if ( ! empty( $this->opts['enable_blocklist'] ) ) {
            foreach ( DFSAS_Helpers::textarea_to_array( $this->opts['blocked_ips'] ?? '' ) as $pattern ) {
                if ( DFSAS_Helpers::ip_matches( $ip, $pattern ) ) {
                    $this->block( 'blocked_ip', $ip, $email );
                }
            }

            if ( $email ) {
                $domain          = DFSAS_Helpers::email_domain( $email );
                $blocked_emails  = array_map( 'strtolower', DFSAS_Helpers::textarea_to_array( $this->opts['blocked_emails']  ?? '' ) );
                $blocked_domains = array_map( 'strtolower', DFSAS_Helpers::textarea_to_array( $this->opts['blocked_domains'] ?? '' ) );

                if ( in_array( strtolower( $email ), $blocked_emails, true ) ) {
                    $this->block( 'blocked_email', $ip, $email );
                }
                if ( $domain && in_array( strtolower( $domain ), $blocked_domains, true ) ) {
                    $this->block( 'blocked_email', $ip, $email );
                }
            }

            foreach ( DFSAS_Helpers::textarea_to_array( $this->opts['blocked_keywords'] ?? '' ) as $kw ) {
                if ( $kw && stripos( $content, trim( $kw ) ) !== false ) {
                    $this->block( 'blocked_keyword', $ip, $email );
                }
            }
        }

        return $commentdata;
    }

    // ─── Spam Scoring (returns 'spam' to go to spam queue) ───────────────────

    /**
     * @param int|string $approved
     */
    public function score_comment( $approved, array $commentdata ) {
        if ( $approved === 'spam' || $approved === 1 ) return $approved;
        if ( in_array( $commentdata['comment_type'] ?? '', [ 'trackback', 'pingback' ], true ) ) return $approved;
        if ( is_user_logged_in() && current_user_can( 'moderate_comments' ) ) return $approved;

        $ip      = DFSAS_Helpers::get_client_ip();
        $email   = sanitize_email( $commentdata['comment_author_email'] ?? '' );
        $content = $commentdata['comment_content'] ?? '';
        $url     = $commentdata['comment_author_url'] ?? '';

        $score   = 0;
        $reasons = [];

        // Excessive links
        $link_count = DFSAS_Helpers::count_urls( $content . ' ' . $url );
        $max_links  = max( 0, (int) ( $this->opts['max_links_allowed'] ?? 2 ) );
        if ( $link_count > $max_links ) {
            $score    += min( 10, ( $link_count - $max_links ) * 2 );
            $reasons[] = "excessive_links:{$link_count}";
        }

        // HTML in comment
        if ( ! empty( $this->opts['block_html_in_message'] ) && $content !== strip_tags( $content ) ) {
            $score    += 3;
            $reasons[] = 'html_in_comment';
        }

        // Very short or empty comment
        if ( str_word_count( strip_tags( $content ) ) < 2 ) {
            $score    += 2;
            $reasons[] = 'very_short_comment';
        }

        // Repeated characters (aaaaa, !!!!!)
        if ( preg_match( '/(.)\1{4,}/', $content ) ) {
            $score    += 2;
            $reasons[] = 'repeated_chars';
        }

        // Spam keyword phrases
        foreach ( DFSAS_Helpers::textarea_to_array( $this->opts['blocked_keywords'] ?? '' ) as $kw ) {
            if ( $kw && stripos( $content, trim( $kw ) ) !== false ) {
                $score    += 4;
                $reasons[] = 'keyword_match';
                break;
            }
        }

        // Disposable email
        if ( ! empty( $this->opts['block_disposable_emails'] ) && $email ) {
            $domain = DFSAS_Helpers::email_domain( $email );
            $list   = DFSAS_Helpers::is_pro()
                ? (array) get_option( 'dfsas_disposable_domains', [] )
                : DFSAS_EmailValidator::get_free_list();
            if ( $domain && in_array( strtolower( $domain ), $list, true ) ) {
                $score    += 8;
                $reasons[] = 'disposable_email';
            }
        }

        // No website URL submitted at all (very common for real humans)
        // If URL is present, check for suspicious TLD
        if ( $url ) {
            $bad_tlds = [ '.xyz', '.tk', '.cf', '.ga', '.gq', '.ml', '.click', '.online', '.work' ];
            foreach ( $bad_tlds as $tld ) {
                if ( str_ends_with( strtolower( $url ), $tld ) ) {
                    $score    += 3;
                    $reasons[] = "suspicious_url_tld:{$tld}";
                    break;
                }
            }
        }

        $threshold = max( 1, (int) ( $this->opts['spam_score_threshold'] ?? 5 ) );

        if ( $score >= $threshold ) {
            DFSAS_Logger::log( [
                'form_type' => 'comment',
                'ip'        => $ip,
                'email'     => $email,
                'reason'    => 'content_filter',
                'score'     => $score,
                'details'   => $reasons,
            ] );
            return 'spam';
        }

        return $approved;
    }

    // ─── Block + Log ──────────────────────────────────────────────────────────

    private function block( string $reason, string $ip, string $email ): void {
        DFSAS_Logger::log( [
            'form_type' => 'comment',
            'ip'        => $ip,
            'email'     => $email,
            'reason'    => $reason,
            'score'     => 10,
        ] );
        wp_die(
            esc_html__( 'Your comment could not be submitted. Please go back and try again.', 'dadsfam-antispam' ),
            esc_html__( 'Comment Blocked', 'dadsfam-antispam' ),
            [ 'response' => 403, 'back_link' => true ]
        );
    }
}
