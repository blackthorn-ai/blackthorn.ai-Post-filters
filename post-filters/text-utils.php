<?php

/*
Cut $string on words to make its size less than $length;
*/
function pf_cut_words($string, $length, $add_ellipsis=true) {
    $words = explode(" ", $string);
    $word_i = 0;

    for (
        $strlen = 0;
        $word_i < count($words);
    ) { 
        if ($strlen > $length) break;
        $strlen += strlen($words[$word_i]);
        $word_i++;
    }

    return implode(" ", array_slice($words, 0, $word_i)) . (
        $add_ellipsis ? "\u{2026}" : ""
    );
}

/*
Remove <a> tags from the text. Converts
    <a href="...">text</a> blabla
to
    text blabla
*/
function pf_remove_a_tag($text) {
    /*
    Regex explanation:
    <a             Open <a> tag
    (?:            Start non-capture group #1
        \s         Whitespace
        \w+        Sequence of a-z, A-Z, 0-9 and _ symbols of
                   length 1 to infty
        =          Equality sign
        ".+?"      Any symbols enclosed in quotes (non-greedy capture)
    )*             Repeat non-capture group #1 from 0 to infinite number
    >              Part of <a> tag
    (.+?)          Capture group #2, text inside </a> (non-greedy capture)
    <\/a>          Close </a> tag
    */
    return preg_replace(
        '/<a(?:\s\w+=".+?")*>(.+?)<\/a>/',
        '$1', // $1 equals to capture group #2
        $text
    );
}

/*
Decide is it Past or Future event based on date;
*/
function pf_is_upcoming_or_past_event($page_id) {
    $start_date = get_field('event_date', $page_id);
    $end_date = get_field('end_date', $page_id);
    
    $current_date_timestamp = strtotime(date('Y-m-d'));

    if ($end_date) {
        $event_date_timestamp = strtotime($end_date);
    } elseif ($start_date) {
        $event_date_timestamp = strtotime($start_date);
    } else {
        return ["Past"];
    }

    if ($current_date_timestamp <= $event_date_timestamp) {
        return ["Upcoming"];
    }
    else {
        return ["Past"];
    }
}
