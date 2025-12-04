<?php
/**
 * Plugin Name: ACF Öffnungszeiten & Feiertage
 * Description: Verwaltet Öffnungszeiten pro Wochentag mit Feiertags- und Brückentags-Logik und stellt einen Shortcode bereit, um anzuzeigen, ob aktuell geöffnet ist.
 * Version: 1.0.0
 * Author: OpenAI
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Opening_Hours_Plugin
{
    private const OPTION_STATE = 'acf_opening_hours_state';
    private const OPTION_GROUP_KEY = 'acf_opening_hours_group';

    public function __construct()
    {
        add_action('init', [$this, 'register_fields']);
        add_shortcode('opening_status', [$this, 'render_shortcode']);
    }

    public function register_fields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => __('Öffnungszeiten & Feiertage', 'acf-opening-hours'),
                'menu_title' => __('Öffnungszeiten', 'acf-opening-hours'),
                'menu_slug'  => 'acf-opening-hours',
                'capability' => 'manage_options',
                'redirect'   => false,
            ]);
        }

        acf_add_local_field_group([
            'key' => self::OPTION_GROUP_KEY,
            'title' => __('Öffnungszeiten & Feiertage', 'acf-opening-hours'),
            'fields' => array_merge(
                $this->build_state_field(),
                $this->build_bridge_fields(),
                $this->build_closed_dates_field(),
                $this->build_weekday_fields()
            ),
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-opening-hours',
                    ],
                ],
            ],
        ]);
    }

    private function build_state_field(): array
    {
        $states = [
            'BW' => __('Baden-Württemberg', 'acf-opening-hours'),
            'BY' => __('Bayern', 'acf-opening-hours'),
            'BE' => __('Berlin', 'acf-opening-hours'),
            'BB' => __('Brandenburg', 'acf-opening-hours'),
            'HB' => __('Bremen', 'acf-opening-hours'),
            'HH' => __('Hamburg', 'acf-opening-hours'),
            'HE' => __('Hessen', 'acf-opening-hours'),
            'MV' => __('Mecklenburg-Vorpommern', 'acf-opening-hours'),
            'NI' => __('Niedersachsen', 'acf-opening-hours'),
            'NW' => __('Nordrhein-Westfalen', 'acf-opening-hours'),
            'RP' => __('Rheinland-Pfalz', 'acf-opening-hours'),
            'SL' => __('Saarland', 'acf-opening-hours'),
            'SN' => __('Sachsen', 'acf-opening-hours'),
            'ST' => __('Sachsen-Anhalt', 'acf-opening-hours'),
            'SH' => __('Schleswig-Holstein', 'acf-opening-hours'),
            'TH' => __('Thüringen', 'acf-opening-hours'),
        ];

        return [[
            'key' => 'field_state',
            'label' => __('Bundesland', 'acf-opening-hours'),
            'name' => self::OPTION_STATE,
            'type' => 'select',
            'instructions' => __('Feiertage werden auf Basis dieses Bundeslandes berechnet.', 'acf-opening-hours'),
            'choices' => $states,
            'default_value' => 'BY',
            'ui' => true,
        ]];
    }

    private function build_bridge_fields(): array
    {
        return [[
            'key' => 'field_bridge_days',
            'label' => __('Brückentage', 'acf-opening-hours'),
            'name' => 'bridge_days',
            'type' => 'repeater',
            'layout' => 'table',
            'instructions' => __('Zusätzliche Schließtage (Brückentage).', 'acf-opening-hours'),
            'sub_fields' => [
                [
                    'key' => 'field_bridge_day',
                    'label' => __('Datum', 'acf-opening-hours'),
                    'name' => 'bridge_date',
                    'type' => 'date_picker',
                    'display_format' => 'd.m.Y',
                    'return_format' => 'Y-m-d',
                ],
                [
                    'key' => 'field_bridge_reason',
                    'label' => __('Grund', 'acf-opening-hours'),
                    'name' => 'bridge_reason',
                    'type' => 'text',
                ],
            ],
        ]];
    }

    private function build_closed_dates_field(): array
    {
        return [[
            'key' => 'field_additional_closed',
            'label' => __('Individuelle Schließtage', 'acf-opening-hours'),
            'name' => 'additional_closed_days',
            'type' => 'repeater',
            'layout' => 'table',
            'instructions' => __('Manuell gepflegte Feiertage oder Sondertage.', 'acf-opening-hours'),
            'sub_fields' => [
                [
                    'key' => 'field_closed_date',
                    'label' => __('Datum', 'acf-opening-hours'),
                    'name' => 'closed_date',
                    'type' => 'date_picker',
                    'display_format' => 'd.m.Y',
                    'return_format' => 'Y-m-d',
                ],
                [
                    'key' => 'field_closed_reason',
                    'label' => __('Grund', 'acf-opening-hours'),
                    'name' => 'closed_reason',
                    'type' => 'text',
                ],
            ],
        ]];
    }

    private function build_weekday_fields(): array
    {
        $days = [
            'monday' => __('Montag', 'acf-opening-hours'),
            'tuesday' => __('Dienstag', 'acf-opening-hours'),
            'wednesday' => __('Mittwoch', 'acf-opening-hours'),
            'thursday' => __('Donnerstag', 'acf-opening-hours'),
            'friday' => __('Freitag', 'acf-opening-hours'),
            'saturday' => __('Samstag', 'acf-opening-hours'),
            'sunday' => __('Sonntag', 'acf-opening-hours'),
        ];

        $fields = [];

        foreach ($days as $key => $label) {
            $fields[] = [
                'key' => 'field_hours_' . $key,
                'label' => $label,
                'name' => $key . '_hours',
                'type' => 'repeater',
                'layout' => 'table',
                'instructions' => __('Mehrere Zeitfenster sind möglich.', 'acf-opening-hours'),
                'sub_fields' => [
                    [
                        'key' => 'field_' . $key . '_start',
                        'label' => __('Start', 'acf-opening-hours'),
                        'name' => 'start_time',
                        'type' => 'time_picker',
                        'display_format' => 'H:i',
                        'return_format' => 'H:i',
                    ],
                    [
                        'key' => 'field_' . $key . '_end',
                        'label' => __('Ende', 'acf-opening-hours'),
                        'name' => 'end_time',
                        'type' => 'time_picker',
                        'display_format' => 'H:i',
                        'return_format' => 'H:i',
                    ],
                ],
            ];
        }

        return $fields;
    }

    public function render_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'state' => get_field(self::OPTION_STATE, 'option') ?: 'BY',
            'show_schedule' => false,
        ], $atts, 'opening_status');

        $state = is_string($atts['state']) ? strtoupper($atts['state']) : 'BY';
        $now = $this->current_datetime();
        $schedule = $this->get_week_schedule();
        $holidays = $this->get_holidays($state, (int) $now->format('Y'));

        $status = $this->determine_status($now, $schedule, $holidays);

        $message = $status['open']
            ? sprintf(__('Wir haben geöffnet bis %s.', 'acf-opening-hours'), $status['until']->format('H:i'))
            : $this->build_closed_message($status);

        $classes = ['opening-status', $status['open'] ? 'is-open' : 'is-closed'];

        if (!empty($status['reason'])) {
            $message .= ' ' . esc_html($status['reason']);
        }

        $html = sprintf('<div class="%s">%s</div>', esc_attr(implode(' ', $classes)), esc_html($message));

        if (!empty($atts['show_schedule'])) {
            $html .= $this->render_schedule_table($schedule);
        }

        return $html;
    }

    private function build_closed_message(array $status): string
    {
        if (!empty($status['next_open'])) {
            return sprintf(
                __('Aktuell geschlossen. Nächste Öffnung: %s um %s.', 'acf-opening-hours'),
                $status['next_open']->format('d.m.Y'),
                $status['next_open']->format('H:i')
            );
        }

        return __('Aktuell geschlossen. Keine künftigen Öffnungszeiten gefunden.', 'acf-opening-hours');
    }

    private function render_schedule_table(array $schedule): string
    {
        $labels = [
            'monday' => __('Montag', 'acf-opening-hours'),
            'tuesday' => __('Dienstag', 'acf-opening-hours'),
            'wednesday' => __('Mittwoch', 'acf-opening-hours'),
            'thursday' => __('Donnerstag', 'acf-opening-hours'),
            'friday' => __('Freitag', 'acf-opening-hours'),
            'saturday' => __('Samstag', 'acf-opening-hours'),
            'sunday' => __('Sonntag', 'acf-opening-hours'),
        ];

        $rows = '';
        foreach ($labels as $key => $label) {
            $entries = $schedule[$key] ?? [];
            $times = array_map(
                fn($row) => sprintf('%s – %s', $row['start_time'], $row['end_time']),
                $entries
            );

            $rows .= sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                esc_html($label),
                esc_html(!empty($times) ? implode(', ', $times) : __('Geschlossen', 'acf-opening-hours'))
            );
        }

        return '<table class="opening-schedule">' . $rows . '</table>';
    }

    private function determine_status(\DateTimeImmutable $now, array $schedule, array $holidays): array
    {
        $todayKey = strtolower($now->format('l')); // monday, tuesday...
        $formattedToday = $now->format('Y-m-d');

        $isHoliday = array_key_exists($formattedToday, $holidays);
        $reason = $isHoliday ? $holidays[$formattedToday] : '';

        if ($isHoliday) {
            $open = false;
        } else {
            $open = $this->is_time_in_schedule($now, $schedule[$todayKey] ?? []);
        }

        $until = null;
        if ($open) {
            $until = $this->current_interval_end($now, $schedule[$todayKey]);
        }

        $next = $open ? null : $this->find_next_opening($now, $schedule, $holidays);

        if (!$open && !$reason && !empty($holidays[$formattedToday])) {
            $reason = $holidays[$formattedToday];
        }

        return [
            'open' => $open,
            'until' => $until,
            'reason' => $reason,
            'next_open' => $next,
        ];
    }

    private function is_time_in_schedule(\DateTimeImmutable $now, array $intervals): bool
    {
        foreach ($intervals as $interval) {
            if (empty($interval['start_time']) || empty($interval['end_time'])) {
                continue;
            }

            $start = $this->date_with_time($now, $interval['start_time']);
            $end = $this->date_with_time($now, $interval['end_time']);

            if ($now >= $start && $now <= $end) {
                return true;
            }
        }

        return false;
    }

    private function current_interval_end(\DateTimeImmutable $now, array $intervals): ?\DateTimeImmutable
    {
        foreach ($intervals as $interval) {
            if (empty($interval['start_time']) || empty($interval['end_time'])) {
                continue;
            }

            $start = $this->date_with_time($now, $interval['start_time']);
            $end = $this->date_with_time($now, $interval['end_time']);

            if ($now >= $start && $now <= $end) {
                return $end;
            }
        }

        return null;
    }

    private function find_next_opening(\DateTimeImmutable $now, array $schedule, array $holidays): ?\DateTimeImmutable
    {
        for ($i = 0; $i < 14; $i++) {
            $candidateDay = $now->modify("+{$i} days");
            $key = strtolower($candidateDay->format('l'));
            $dateString = $candidateDay->format('Y-m-d');

            if (!empty($holidays[$dateString])) {
                continue;
            }

            $intervals = $schedule[$key] ?? [];
            if (empty($intervals)) {
                continue;
            }

            foreach ($intervals as $interval) {
                if (empty($interval['start_time']) || empty($interval['end_time'])) {
                    continue;
                }

                $start = $this->date_with_time($candidateDay, $interval['start_time']);

                if ($start > $now) {
                    return $start;
                }
            }
        }

        return null;
    }

    private function date_with_time(\DateTimeImmutable $day, string $time): \DateTimeImmutable
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        return $day->setTime((int) $hour, (int) $minute, 0);
    }

    private function get_week_schedule(): array
    {
        $keys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedule = [];

        foreach ($keys as $key) {
            $schedule[$key] = get_field($key . '_hours', 'option') ?: [];
        }

        return $schedule;
    }

    private function current_datetime(): \DateTimeImmutable
    {
        $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('Europe/Berlin');
        return new \DateTimeImmutable('now', $timezone);
    }

    private function get_holidays(string $state, int $year): array
    {
        $holidays = German_Holidays::for_state($state, $year);

        $custom = get_field('additional_closed_days', 'option') ?: [];
        foreach ($custom as $entry) {
            if (!empty($entry['closed_date'])) {
                $holidays[$entry['closed_date']] = $entry['closed_reason'] ?: __('Individueller Schließtag', 'acf-opening-hours');
            }
        }

        $bridges = get_field('bridge_days', 'option') ?: [];
        foreach ($bridges as $entry) {
            if (!empty($entry['bridge_date'])) {
                $holidays[$entry['bridge_date']] = $entry['bridge_reason'] ?: __('Brückentag', 'acf-opening-hours');
            }
        }

        return $holidays;
    }
}

class German_Holidays
{
    public static function for_state(string $state, int $year): array
    {
        $state = strtoupper($state);
        $easter = self::easter_datetime($year);

        $holidays = [
            "$year-01-01" => __('Neujahr', 'acf-opening-hours'),
            "$year-05-01" => __('Tag der Arbeit', 'acf-opening-hours'),
            "$year-10-03" => __('Tag der Deutschen Einheit', 'acf-opening-hours'),
            "$year-12-25" => __('1. Weihnachtstag', 'acf-opening-hours'),
            "$year-12-26" => __('2. Weihnachtstag', 'acf-opening-hours'),
        ];

        $holidays[$easter->modify('-2 days')->format('Y-m-d')] = __('Karfreitag', 'acf-opening-hours');
        $holidays[$easter->modify('+1 day')->format('Y-m-d')] = __('Ostermontag', 'acf-opening-hours');
        $holidays[$easter->modify('+39 days')->format('Y-m-d')] = __('Christi Himmelfahrt', 'acf-opening-hours');
        $holidays[$easter->modify('+50 days')->format('Y-m-d')] = __('Pfingstmontag', 'acf-opening-hours');

        $stateSpecific = self::state_specific($state, $year, $easter);

        return array_merge($holidays, $stateSpecific);
    }

    private static function state_specific(string $state, int $year, \DateTimeImmutable $easter): array
    {
        $corpusChristiStates = ['BW', 'BY', 'HE', 'NW', 'RP', 'SL'];
        $epiphanyStates = ['BW', 'BY', 'ST'];
        $reformationStates = ['BB', 'HB', 'HH', 'MV', 'NI', 'SH', 'SN', 'ST', 'TH'];
        $assumptionStates = ['BY', 'SL'];
        $worldChildrensDayStates = ['TH'];
        $internationalWomensDayStates = ['BE', 'MV'];

        $holidays = [];

        if (in_array($state, $corpusChristiStates, true)) {
            $holidays[$easter->modify('+60 days')->format('Y-m-d')] = __('Fronleichnam', 'acf-opening-hours');
        }

        if (in_array($state, $epiphanyStates, true)) {
            $holidays["$year-01-06"] = __('Heilige Drei Könige', 'acf-opening-hours');
        }

        if (in_array($state, $reformationStates, true)) {
            $holidays["$year-10-31"] = __('Reformationstag', 'acf-opening-hours');
        }

        if (in_array($state, $assumptionStates, true)) {
            $holidays["$year-08-15"] = __('Mariä Himmelfahrt', 'acf-opening-hours');
        }

        if (in_array($state, $worldChildrensDayStates, true)) {
            $holidays["$year-09-20"] = __('Weltkindertag', 'acf-opening-hours');
        }

        if (in_array($state, $internationalWomensDayStates, true)) {
            $holidays["$year-03-08"] = __('Internationaler Frauentag', 'acf-opening-hours');
        }

        // Buss- und Bettag: Mittwoch vor dem 23. November (SN)
        if ($state === 'SN') {
            $date = new \DateTimeImmutable("$year-11-23", new \DateTimeZone('Europe/Berlin'));
            $holiday = $date->modify('last wednesday');
            $holidays[$holiday->format('Y-m-d')] = __('Buß- und Bettag', 'acf-opening-hours');
        }

        return $holidays;
    }

    private static function easter_datetime(int $year): \DateTimeImmutable
    {
        $base = easter_date($year);
        $timezone = new \DateTimeZone('Europe/Berlin');
        return (new \DateTimeImmutable('@' . $base))->setTimezone($timezone);
    }
}

new ACF_Opening_Hours_Plugin();
