<?php
/**
 * Content Filter — scoring-based analysis of form message content.
 * Detects: excessive links, HTML injection, gibberish, spam phrase patterns.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_ContentFilter {

    private array $opts;
    private int   $threshold;
    private int   $max_links;
    private bool  $block_html;

    public function __construct( array $opts ) {
        $this->opts       = $opts;
        $this->threshold  = max( 1, (int) ( $opts['spam_score_threshold']  ?? 5 ) );
        $this->max_links  = max( 0, (int) ( $opts['max_links_allowed']     ?? 2 ) );
        $this->block_html = ! empty( $opts['block_html_in_message'] );

        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'     ], 6, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms' ], 6, 2 );
        add_filter( 'wp_mail',                [ $this, 'filter_wp_mail' ] );
    }

    // ─── Scoring Engine ───────────────────────────────────────────────────────

    /**
     * Analyse a text body and return a spam score + reasons.
     * Score ≥ threshold → treat as spam.
     */
    public function analyse( string $text, string $subject = '', string $email = '' ): array {
        $score   = 0;
        $reasons = [];
        $full    = $subject . ' ' . $text;

        // 1. Excessive URLs
        $link_count = DFSAS_Helpers::count_urls( $full );
        if ( $link_count > $this->max_links ) {
            $score  += min( 10, ( $link_count - $this->max_links ) * 2 );
            $reasons[] = "excessive_links:{$link_count}";
        }

        // 2. HTML tags in message body
        if ( $this->block_html && $text !== strip_tags( $text ) ) {
            $score    += 4;
            $reasons[] = 'html_in_message';
        }

        // 3. Gibberish / all-caps subject
        if ( $subject && strtoupper( $subject ) === $subject && strlen( $subject ) > 5 ) {
            $score    += 2;
            $reasons[] = 'all_caps_subject';
        }

        // 4. Very short message (bots often send minimal text)
        $word_count = str_word_count( strip_tags( $text ) );
        if ( $word_count > 0 && $word_count < 3 ) {
            $score    += 2;
            $reasons[] = 'very_short_message';
        }

        // 5. Repeated characters (e.g. "buyyy nowwww")
        if ( preg_match( '/(.)\1{4,}/', $text ) ) {
            $score    += 2;
            $reasons[] = 'repeated_chars';
        }

        // 6. Non-ASCII character spam (excessive special chars)
        $non_ascii = preg_match_all( '/[^\x00-\x7F]/', $text );
        if ( $non_ascii > 20 && $non_ascii / max( 1, strlen( $text ) ) > 0.3 ) {
            $score    += 3;
            $reasons[] = 'excessive_non_ascii';
        }

        // 7. Suspicious email TLDs (PRO: more extensive check via DNSBL)
        if ( $email ) {
            $domain = DFSAS_Helpers::email_domain( $email );
            $bad_tlds = [ '.xyz', '.tk', '.cf', '.ga', '.gq', '.ml', '.click', '.download', '.work', '.online' ];
            foreach ( $bad_tlds as $tld ) {
                if ( str_ends_with( $domain, $tld ) ) {
                    $score    += 2;
                    $reasons[] = "suspicious_tld:{$tld}";
                    break;
                }
            }
        }

        // 8. Classic spam phrases (beyond the keyword blocklist)
        $spam_patterns = [
            '/\$\d+\s*(million|billion|thousand)/i'            => 'money_spam',
            '/\b(click here|act now|limited time|act fast)\b/i' => 'urgency_phrase',
            '/(earn|make)\s+\$?\d+\s*(a day|per day|daily)/i'   => 'income_claim',
            '/\b(free\s+money|cash\s+prize|you\s+have\s+won)\b/i' => 'prize_spam',
            '/\bseo\s+(service|boost|rank|link)\b/i'            => 'seo_spam',
            '/\b(buy\s+(now|cheap)|order\s+now)\b/i'            => 'sales_spam',
            '/http[^\s]{80,}/i'                                  => 'very_long_url',
        ];

        foreach ( $spam_patterns as $pattern => $label ) {
            if ( preg_match( $pattern, $full ) ) {
                $score    += 3;
                $reasons[] = $label;
            }
        }

        return [
            'score'   => $score,
            'spam'    => $score >= $this->threshold,
            'reasons' => $reasons,
        ];
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;

        $text    = sanitize_textarea_field( $_POST['your-message'] ?? '' );
        $subject = sanitize_text_field( $_POST['your-subject']  ?? '' );
        $email   = sanitize_email( $_POST['your-email'] ?? '' );

        $result = $this->analyse( $text, $subject, $email );
        if ( $result['spam'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'contact-form-7',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $email,
                'subject'   => $subject,
                'reason'    => 'content_filter',
                'score'     => $result['score'],
                'details'   => $result['reasons'],
            ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        // Gather text from all text/textarea fields
        $text  = '';
        $email = '';
        foreach ( $fields as $field ) {
            if ( in_array( $field['type'], [ 'text', 'textarea', 'paragraph-text' ], true ) ) {
                $text .= ' ' . ( $field['value'] ?? '' );
            }
            if ( $field['type'] === 'email' ) {
                $email = $field['value'] ?? '';
            }
        }

        $result = $this->analyse( trim( $text ), '', $email );
        if ( $result['spam'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wpforms',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $email,
                'reason'    => 'content_filter',
                'score'     => $result['score'],
                'details'   => $result['reasons'],
            ] );
            wpforms()->process->errors['header'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
    }

    public function filter_wp_mail( array $atts ): array {
        // Skip admin AJAX (booking plugins, scheduling tools etc.)
        if ( wp_doing_ajax() && is_admin() ) return $atts;

        // CRITICAL: Only filter emails from form submissions we injected into.
        // WooCommerce order emails, password resets, system notifications etc.
        // fire outside any form context and will never have our timestamp field.
        if ( empty( $_POST[ DFSAS_Helpers::timestamp_field_name() ] ) ) return $atts;

        $result = $this->analyse( $atts['message'] ?? '', $atts['subject'] ?? '' );
        if ( $result['spam'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-mail',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'subject'   => $atts['subject'] ?? '',
                'reason'    => 'content_filter',
                'score'     => $result['score'],
                'details'   => $result['reasons'],
            ] );
            $atts['to'] = 'blocked@localhost';
        }
        return $atts;
    }
}
