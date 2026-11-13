<?php
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

function storyline_overview_info()
{
    return array(
        "name"          => "Storyline Overview",
        "description"   => "Eigene Storyline-Seite mit ACP-Inhalten, Fakten, Statistik, Aktivität und frei pflegbaren CTA-Kacheln.",
        "website"       => "https://github.com/ladyfuchur-star/mybb-storyline-overview",
        "author"        => "Ladytime",
        "authorsite"    => "https://github.com/ladyfuchur-star",
        "version"       => "1.7.0",
        "compatibility" => "18*"
    );
}

function storyline_overview_is_installed()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='storyline_overview'");
    return ($db->num_rows($query) > 0);
}

function storyline_overview_install()
{
    storyline_overview_ensure_all();
}

function storyline_overview_uninstall()
{
    global $db;

    $query = $db->simple_select("settinggroups", "gid", "name='storyline_overview'");
    $gid = (int)$db->fetch_field($query, "gid");

    if($gid > 0)
    {
        $db->delete_query("settings", "gid='{$gid}'");
        $db->delete_query("settinggroups", "gid='{$gid}'");
    }

    $db->delete_query("templates", "title IN('storyline_overview_page','storyline_overview_fact_row','storyline_overview_recent_row','storyline_overview_recent_row_nolink','storyline_overview_cta_card')");
    rebuild_settings();
}

function storyline_overview_activate()
{
    storyline_overview_ensure_all();
}

function storyline_overview_deactivate()
{
}

function storyline_overview_ensure_all()
{
    global $db;

    $gid = storyline_overview_get_or_create_settinggroup();
    storyline_overview_ensure_settings($gid);
    storyline_overview_replace_templates();
    rebuild_settings();
}

function storyline_overview_get_or_create_settinggroup()
{
    global $db;

    $query = $db->simple_select("settinggroups", "gid", "name='storyline_overview'");
    $gid = (int)$db->fetch_field($query, "gid");

    if($gid > 0)
    {
        return $gid;
    }

    $setting_group = array(
        'name'        => 'storyline_overview',
        'title'       => 'Storyline Overview',
        'description' => 'Einstellungen für die Storyline-Seite.',
        'disporder'   => 50,
        'isdefault'   => 0
    );

    return (int)$db->insert_query("settinggroups", $setting_group);
}

function storyline_overview_ensure_settings($gid)
{
    global $db;


    $settings = array(
        'storyline_overview_enable' => array('title' => 'Plugin aktivieren','description' => 'Aktiviert die Storyline-Seite.','optionscode' => 'onoff','value' => '1','disporder' => 1),
        'storyline_overview_title' => array('title' => 'Seitentitel','description' => 'Titel der Storyline-Seite.','optionscode' => 'text','value' => 'Storyline','disporder' => 2),
        'storyline_overview_action' => array('title' => 'Seitenaktion','description' => 'Aufruf über misc.php?action=DEINWERT','optionscode' => 'text','value' => 'storyline','disporder' => 3),
        'storyline_overview_header_image' => array('title' => 'Bild oben rechts / Headerbild','description' => 'Optionale URL zu einem Bild für den oberen Bereich rechts.','optionscode' => 'text','value' => '','disporder' => 4),
        'storyline_overview_page_background' => array('title' => 'Seiten-Hintergrundbild','description' => 'URL zum Hintergrundbild der ganzen Seite. Leer = kein eigenes Bild.','optionscode' => 'text','value' => '','disporder' => 6),

        'storyline_overview_storyline_heading' => array('title' => 'Überschrift Storyline oben','description' => 'Titel des oberen Storyline-Blocks.','optionscode' => 'text','value' => 'Storyline','disporder' => 5),
        'storyline_overview_storyline_html' => array('title' => 'Storyline oben (HTML erlaubt)','description' => 'Inhalt des oberen scrollbaren Storyline-Blocks.','optionscode' => 'textarea','value' => '<p>Trage hier deine Storyline ein.</p>','disporder' => 6),
        'storyline_overview_overview_heading' => array('title' => 'Überschrift Überblick','description' => 'Titel des linken großen Textblocks.','optionscode' => 'text','value' => 'Überblick','disporder' => 7),
        'storyline_overview_overview_html' => array('title' => 'Überblick links (HTML erlaubt)','description' => 'Ausführlicher Überblickstext für den linken Block.','optionscode' => 'textarea','value' => '<p>Trage hier deinen ausführlichen Überblick ein.</p>','disporder' => 8),
        'storyline_overview_facts_heading' => array('title' => 'Überschrift Fakten','description' => 'Titel des Fakten-Blocks.','optionscode' => 'text','value' => 'Wichtige Fakten','disporder' => 9),
        'storyline_overview_stats_heading' => array('title' => 'Überschrift Statistik','description' => 'Titel des Statistik-Blocks.','optionscode' => 'text','value' => 'Rollenspiel-Statistik','disporder' => 10),
        'storyline_overview_recent_heading' => array('title' => 'Überschrift Letzte Szenen','description' => 'Titel des Aktivitäts-Blocks.','optionscode' => 'text','value' => 'Letzte Szenen / Aktivität','disporder' => 11),

        'storyline_overview_genre' => array('title' => 'Genre','description' => 'Genre des Forums oder der Storyline.','optionscode' => 'text','value' => 'Dein Genre','disporder' => 12),
        'storyline_overview_rating' => array('title' => 'System / Rating','description' => 'Zum Beispiel L3S3V3 oder 18+.','optionscode' => 'text','value' => 'Dein Rating','disporder' => 13),
        'storyline_overview_location' => array('title' => 'Ort','description' => 'Hauptspielort oder Setting-Ort.','optionscode' => 'text','value' => 'Dein Spielort','disporder' => 14),
        'storyline_overview_period' => array('title' => 'Zeitraum','description' => 'Zum Beispiel Gegenwart, Herbst 2026 oder 1890er.','optionscode' => 'text','value' => 'Gegenwart','disporder' => 15),

        'storyline_overview_forum_ids' => array('title' => 'Forum-IDs für Statistik','description' => 'Kommagetrennte Forum-IDs. Beispiel: 2,5,9','optionscode' => 'text','value' => '','disporder' => 16),
        'storyline_overview_days' => array('title' => 'Zeitraum in Tagen','description' => 'Für aktuelle Aktivität und Statistik.','optionscode' => 'numeric','value' => '30','disporder' => 17),
        'storyline_overview_recent_limit' => array('title' => 'Anzahl letzter Szenen','description' => 'Wie viele zuletzt aktive Szenen gelistet werden sollen.','optionscode' => 'numeric','value' => '10','disporder' => 18),

        'storyline_overview_cta_heading' => array('title' => 'Überschrift CTA-Bereich','description' => 'Optionaler Titel über den Kacheln unten.','optionscode' => 'text','value' => '','disporder' => 19),

        'storyline_overview_cta1_icon' => array('title' => 'Kachel 1 Icon-Klasse','description' => 'Optional. Font-Awesome-Klasse, falls das Theme Font Awesome lädt.','optionscode' => 'text','value' => '','disporder' => 20),
        'storyline_overview_cta1_title' => array('title' => 'Kachel 1 Titel','description' => 'Titel der ersten Kachel.','optionscode' => 'text','value' => 'Charakter erstellen','disporder' => 21),
        'storyline_overview_cta1_text' => array('title' => 'Kachel 1 Text','description' => 'Beschreibung der ersten Kachel.','optionscode' => 'textarea','value' => 'Werde Teil unserer Welt und erschaffe deinen eigenen Charakter.','disporder' => 22),
        'storyline_overview_cta1_button' => array('title' => 'Kachel 1 Buttontext','description' => 'Text des ersten Buttons.','optionscode' => 'text','value' => 'Jetzt erstellen','disporder' => 23),
        'storyline_overview_cta1_url' => array('title' => 'Kachel 1 Link','description' => 'URL der ersten Kachel.','optionscode' => 'text','value' => 'member.php?action=register','disporder' => 24),

        'storyline_overview_cta2_icon' => array('title' => 'Kachel 2 Icon-Klasse','description' => 'Optional. Font-Awesome-Klasse, falls das Theme Font Awesome lädt.','optionscode' => 'text','value' => '','disporder' => 25),
        'storyline_overview_cta2_title' => array('title' => 'Kachel 2 Titel','description' => 'Titel der zweiten Kachel.','optionscode' => 'text','value' => 'Regeln lesen','disporder' => 26),
        'storyline_overview_cta2_text' => array('title' => 'Kachel 2 Text','description' => 'Beschreibung der zweiten Kachel.','optionscode' => 'textarea','value' => 'Informiere dich über unsere Regeln und das Rating des Forums.','disporder' => 27),
        'storyline_overview_cta2_button' => array('title' => 'Kachel 2 Buttontext','description' => 'Text des zweiten Buttons.','optionscode' => 'text','value' => 'Zu den Regeln','disporder' => 28),
        'storyline_overview_cta2_url' => array('title' => 'Kachel 2 Link','description' => 'URL der zweiten Kachel.','optionscode' => 'text','value' => '#','disporder' => 29),

        'storyline_overview_cta3_icon' => array('title' => 'Kachel 3 Icon-Klasse','description' => 'Optional. Font-Awesome-Klasse, falls das Theme Font Awesome lädt.','optionscode' => 'text','value' => '','disporder' => 30),
        'storyline_overview_cta3_title' => array('title' => 'Kachel 3 Titel','description' => 'Titel der dritten Kachel.','optionscode' => 'text','value' => 'Welt entdecken','disporder' => 31),
        'storyline_overview_cta3_text' => array('title' => 'Kachel 3 Text','description' => 'Beschreibung der dritten Kachel.','optionscode' => 'textarea','value' => 'Tauche in die Welt ein und lerne Setting, Orte und Hintergründe kennen.','disporder' => 32),
        'storyline_overview_cta3_button' => array('title' => 'Kachel 3 Buttontext','description' => 'Text des dritten Buttons.','optionscode' => 'text','value' => 'Mehr erfahren','disporder' => 33),
        'storyline_overview_cta3_url' => array('title' => 'Kachel 3 Link','description' => 'URL der dritten Kachel.','optionscode' => 'text','value' => '#','disporder' => 34),

        'storyline_overview_cta4_icon' => array('title' => 'Kachel 4 Icon-Klasse','description' => 'Optional. Font-Awesome-Klasse, falls das Theme Font Awesome lädt.','optionscode' => 'text','value' => '','disporder' => 35),
        'storyline_overview_cta4_title' => array('title' => 'Kachel 4 Titel','description' => 'Titel der vierten Kachel.','optionscode' => 'text','value' => 'Mitglied werden','disporder' => 36),
        'storyline_overview_cta4_text' => array('title' => 'Kachel 4 Text','description' => 'Beschreibung der vierten Kachel.','optionscode' => 'textarea','value' => 'Tritt der Community bei und starte deinen Einstieg ins Inplay.','disporder' => 37),
        'storyline_overview_cta4_button' => array('title' => 'Kachel 4 Buttontext','description' => 'Text des vierten Buttons.','optionscode' => 'text','value' => 'Registrieren','disporder' => 38),
        'storyline_overview_cta4_url' => array('title' => 'Kachel 4 Link','description' => 'URL der vierten Kachel.','optionscode' => 'text','value' => 'member.php?action=register','disporder' => 39),
    );

    foreach($settings as $name => $setting)
    {
        $query = $db->simple_select("settings", "sid", "name='".$db->escape_string($name)."'");
        $sid = (int)$db->fetch_field($query, "sid");

        $data = array(
            'name'        => $name,
            'title'       => $setting['title'],
            'description' => $setting['description'],
            'optionscode' => $setting['optionscode'],
            'disporder'   => (int)$setting['disporder'],
            'gid'         => (int)$gid
        );

        if($sid > 0)
        {
            $db->update_query("settings", $data, "sid='{$sid}'");
        }
        else
        {
            $data['value'] = $setting['value'];
            $db->insert_query("settings", $data);
        }
    }
}

function storyline_overview_replace_templates()
{
    global $db;
    $db->delete_query("templates", "title IN('storyline_overview_page','storyline_overview_fact_row','storyline_overview_recent_row','storyline_overview_recent_row_nolink','storyline_overview_cta_card')");
    storyline_overview_add_templates();
}

function storyline_overview_add_templates()
{
    global $db;

    $templates = array();

    $templates[] = array(
        "title" => "storyline_overview_page",
        "template" => $db->escape_string('
<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$page_title}</title>
{$headerinclude}
{$storyline_extra_head}
</head>
<body class="storyline-page-bg">
{$header}
<div class="storyline-wrap">
    <div class="storyline-topbox tborder">
        <div class="thead storyline-panel-head"><strong>{$storyline_heading}</strong></div>
        <div class="trow1 storyline-topbox-body">
            <div class="storyline-scroll">{$storyline_html}</div>
        </div>
    </div>

    <div class="storyline-intro-grid">
        <section class="tborder storyline-panel">
                <div class="thead storyline-panel-head"><strong>{$overview_heading}</strong></div>
                <div class="trow1 storyline-panel-body storyline-content">{$overview_html}</div>
        </section>

        <aside class="tborder storyline-panel">
                <div class="thead storyline-panel-head"><strong>{$facts_heading}</strong></div>
                <div class="storyline-facts">{$facts_rows}</div>
        </aside>
    </div>

    <div class="storyline-pulse-grid">
            <section class="tborder storyline-panel">
                <div class="thead storyline-panel-head"><strong>{$stats_heading}</strong></div>
                <div class="storyline-stats-list">
                    <div class="trow1 storyline-stat-row"><span class="storyline-stat-label">Szenen insgesamt</span><span class="storyline-stat-value">{$stats_threads}</span></div>
                    <div class="trow2 storyline-stat-row"><span class="storyline-stat-label">Beiträge insgesamt</span><span class="storyline-stat-value">{$stats_posts}</span></div>
                    <div class="trow1 storyline-stat-row"><span class="storyline-stat-label">Szenen im Zeitraum</span><span class="storyline-stat-value">{$stats_recent_threads}</span></div>
                    <div class="trow2 storyline-stat-row"><span class="storyline-stat-label">Beiträge im Zeitraum</span><span class="storyline-stat-value">{$stats_recent_posts}</span></div>
                    <div class="trow1 storyline-stat-row"><span class="storyline-stat-label">Durchschnitt Beiträge pro Szene</span><span class="storyline-stat-value">{$stats_avg_posts}</span></div>
                    <div class="trow2 storyline-stat-row"><span class="storyline-stat-label">Durchschnitt neue Beiträge pro Tag</span><span class="storyline-stat-value">{$stats_avg_per_day}</span></div>
                </div>
            </section>

            <section class="tborder storyline-panel">
                <div class="thead storyline-panel-head"><strong>{$recent_heading}</strong></div>
                <div class="storyline-recent-list">{$recent_rows}</div>
            </section>
    </div>

    <div class="tborder storyline-panel storyline-cta-wrap">
        {$cta_heading_html}
        <div class="storyline-cta-grid">{$cta_cards}</div>
    </div>
</div>
{$footer}
</body>
</html>'),
        "sid" => "-1",
        "version" => "1800",
        "dateline" => TIME_NOW
    );

    $templates[] = array(
        "title" => "storyline_overview_fact_row",
        "template" => $db->escape_string('<div class="{$row_class} storyline-fact-row"><div class="storyline-fact-label">{$fact_label}</div><div class="storyline-fact-value">{$fact_value}</div></div>'),
        "sid" => "-1",
        "version" => "1800",
        "dateline" => TIME_NOW
    );

    $templates[] = array(
        "title" => "storyline_overview_recent_row",
        "template" => $db->escape_string('<div class="{$row_class} storyline-recent-row"><div class="storyline-recent-subject"><a href="{$thread_url}">{$thread_subject}</a></div><div class="smalltext storyline-recent-date">{$last_activity}</div></div>'),
        "sid" => "-1",
        "version" => "1800",
        "dateline" => TIME_NOW
    );

    $templates[] = array(
        "title" => "storyline_overview_recent_row_nolink",
        "template" => $db->escape_string('<div class="trow1 storyline-recent-row storyline-recent-row-empty"><div class="storyline-recent-subject"><span>{$thread_subject}</span></div><div class="smalltext storyline-recent-date">{$last_activity}</div></div>'),
        "sid" => "-1",
        "version" => "1800",
        "dateline" => TIME_NOW
    );

    $templates[] = array(
        "title" => "storyline_overview_cta_card",
        "template" => $db->escape_string('<article class="{$row_class} storyline-cta-card">{$cta_icon_html}<div class="storyline-cta-title">{$cta_title}</div><div class="storyline-cta-text">{$cta_text}</div><a class="button storyline-cta-button" href="{$cta_url}">{$cta_button}</a></article>'),
        "sid" => "-1",
        "version" => "1800",
        "dateline" => TIME_NOW
    );

    foreach($templates as $template)
    {
        $db->insert_query("templates", $template);
    }
}

$plugins->add_hook("misc_start", "storyline_overview_misc");

function storyline_overview_misc()
{
    global $mybb;

    storyline_overview_soft_upgrade();

    if((int)storyline_overview_setting('storyline_overview_enable', 1) !== 1)
    {
        return;
    }

    $action = trim((string)storyline_overview_setting('storyline_overview_action', 'storyline'));
    if($action === '')
    {
        $action = 'storyline';
    }

    if($mybb->get_input('action') !== $action)
    {
        return;
    }

    storyline_overview_render_page();
    exit;
}

function storyline_overview_render_page()
{
    global $db, $mybb, $templates, $headerinclude, $header, $footer;

    $action_name = (string)storyline_overview_setting('storyline_overview_action', 'storyline');
    add_breadcrumb("Storyline", "misc.php?action=".urlencode($action_name));

    $page_title        = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_title', 'Storyline'));
    $storyline_heading = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_storyline_heading', 'Storyline'));
    $overview_heading  = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_overview_heading', 'Überblick'));
    $facts_heading     = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_facts_heading', 'Wichtige Fakten'));
    $stats_heading     = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_stats_heading', 'Rollenspiel-Statistik'));
    $recent_heading    = htmlspecialchars_uni((string)storyline_overview_setting('storyline_overview_recent_heading', 'Letzte Szenen / Aktivität'));

    $storyline_html = (string)storyline_overview_setting('storyline_overview_storyline_html', '<p>Keine Storyline hinterlegt.</p>');
    $overview_html  = (string)storyline_overview_setting('storyline_overview_overview_html', '<p>Kein Überblick hinterlegt.</p>');
    $days           = max(1, (int)storyline_overview_setting('storyline_overview_days', 30));
    $limit          = max(1, (int)storyline_overview_setting('storyline_overview_recent_limit', 10));

        $page_background = trim((string)storyline_overview_setting('storyline_overview_page_background', ''));
    $header_image    = trim((string)storyline_overview_setting('storyline_overview_header_image', ''));
    $page_bg_css     = '';
    $header_bg_css   = '';
    if($page_background !== '')
    {
        $page_bg_css = ".storyline-page-bg{background-image:url('".htmlspecialchars_uni($page_background)."');background-size:cover;background-position:center top;background-attachment:fixed;}";
    }
    if($header_image !== '')
    {
        $header_bg_css = ".storyline-topbox::after{background-image:url('".htmlspecialchars_uni($header_image)."');}";
    }

    $stylesheet_url = rtrim((string)$mybb->settings['bburl'], '/') . '/css/storyline_overview.css?v=170';
    $storyline_extra_head = "\n<link rel=\"stylesheet\" href=\"".htmlspecialchars_uni($stylesheet_url)."\">\n";

    if($page_bg_css !== '' || $header_bg_css !== '')
    {
        $storyline_extra_head .= "<style>".$page_bg_css."\n".$header_bg_css."</style>\n";
    }

    $forum_ids = storyline_overview_accessible_forum_ids(
        storyline_overview_parse_forum_ids((string)storyline_overview_setting('storyline_overview_forum_ids', ''))
    );
    $fid_sql   = storyline_overview_build_in_sql($forum_ids);

    $facts_rows = '';
    $fact_items = array(
        'Genre'    => (string)storyline_overview_setting('storyline_overview_genre', '-'),
        'System'   => (string)storyline_overview_setting('storyline_overview_rating', '-'),
        'Ort'      => (string)storyline_overview_setting('storyline_overview_location', '-'),
        'Zeitraum' => (string)storyline_overview_setting('storyline_overview_period', '-')
    );

    $fact_index = 0;
    foreach($fact_items as $label => $value)
    {
        $row_class = ($fact_index % 2 === 0) ? 'trow1' : 'trow2';
        $fact_label = htmlspecialchars_uni($label);
        $fact_value = htmlspecialchars_uni(trim((string)$value));
        if($fact_value === '')
        {
            $fact_value = '-';
        }
        eval("\$facts_rows .= \"".$templates->get("storyline_overview_fact_row")."\";");
        $fact_index++;
    }

    $cutoff = TIME_NOW - ($days * 86400);

    $stats_threads = 0;
    $stats_posts = 0;
    $stats_recent_threads = 0;
    $stats_recent_posts = 0;
    $stats_avg_posts = '0.00';
    $stats_avg_per_day = '0.00';

    if($fid_sql !== '')
    {
        $query = $db->query("SELECT COUNT(t.tid) AS thread_count, COALESCE(SUM(t.replies + 1), 0) AS post_count FROM ".TABLE_PREFIX."threads t WHERE t.fid IN ({$fid_sql}) AND t.visible='1'");
        $row = $db->fetch_array($query);
        $stats_threads = (int)$row['thread_count'];
        $stats_posts   = (int)$row['post_count'];

        $query = $db->query("SELECT COUNT(t.tid) AS recent_thread_count FROM ".TABLE_PREFIX."threads t WHERE t.fid IN ({$fid_sql}) AND t.visible='1' AND t.dateline >= '{$cutoff}'");
        $row = $db->fetch_array($query);
        $stats_recent_threads = (int)$row['recent_thread_count'];

        $query = $db->query("SELECT COUNT(p.pid) AS recent_post_count FROM ".TABLE_PREFIX."posts p INNER JOIN ".TABLE_PREFIX."threads t ON (t.tid = p.tid) WHERE t.fid IN ({$fid_sql}) AND t.visible='1' AND p.visible='1' AND p.dateline >= '{$cutoff}'");
        $row = $db->fetch_array($query);
        $stats_recent_posts = (int)$row['recent_post_count'];

        if($stats_threads > 0)
        {
            $stats_avg_posts = my_number_format($stats_posts / $stats_threads, 2);
        }

        $stats_avg_per_day = my_number_format($stats_recent_posts / $days, 2);
    }

    $recent_rows = '';
    if($fid_sql !== '')
    {
        require_once MYBB_ROOT . "inc/functions_post.php";

        $query = $db->query("SELECT t.tid, t.subject, t.lastpost FROM ".TABLE_PREFIX."threads t WHERE t.fid IN ({$fid_sql}) AND t.visible='1' AND t.lastpost >= '{$cutoff}' ORDER BY t.lastpost DESC LIMIT {$limit}");
        $recent_index = 0;
        while($thread = $db->fetch_array($query))
        {
            $row_class = ($recent_index % 2 === 0) ? 'trow1' : 'trow2';
            $thread_subject = htmlspecialchars_uni((string)$thread['subject']);
            $thread_url = get_thread_link((int)$thread['tid']);
            $last_activity = my_date($mybb->settings['dateformat'], (int)$thread['lastpost']) . ", " . my_date($mybb->settings['timeformat'], (int)$thread['lastpost']);
            eval("\$recent_rows .= \"".$templates->get("storyline_overview_recent_row")."\";");
            $recent_index++;
        }
    }

    if($recent_rows === '')
    {
        $thread_subject = 'Keine Aktivität im gewählten Zeitraum.';
        $last_activity = '-';
        eval("\$recent_rows .= \"".$templates->get("storyline_overview_recent_row_nolink")."\";");
    }

    $cta_cards = '';
    for($i = 1; $i <= 4; $i++)
    {
        $cta_icon   = htmlspecialchars_uni(trim((string)storyline_overview_setting('storyline_overview_cta'.$i.'_icon', 'fa-regular fa-circle')));
        $cta_icon_html = ($cta_icon !== '') ? '<div class="storyline-cta-icon"><i class="'.$cta_icon.'"></i></div>' : '';
        $row_class  = ($i % 2 === 1) ? 'trow1' : 'trow2';
        $cta_title  = htmlspecialchars_uni(trim((string)storyline_overview_setting('storyline_overview_cta'.$i.'_title', '')));
        $cta_text   = nl2br(htmlspecialchars_uni(trim((string)storyline_overview_setting('storyline_overview_cta'.$i.'_text', ''))));
        $cta_button = htmlspecialchars_uni(trim((string)storyline_overview_setting('storyline_overview_cta'.$i.'_button', 'Mehr')));
        $cta_url    = htmlspecialchars_uni(trim((string)storyline_overview_setting('storyline_overview_cta'.$i.'_url', '#')));

        if($cta_title === '' && $cta_text === '')
        {
            continue;
        }

        eval("\$cta_cards .= \"".$templates->get("storyline_overview_cta_card")."\";");
    }

    if($cta_cards === '')
    {
        $cta_icon_html = '';
        $row_class  = 'trow1';
        $cta_title  = 'Noch keine Kacheln gepflegt';
        $cta_text   = 'Trage die Inhalte der unteren Kacheln einfach im Admin-CP ein.';
        $cta_button = 'Zum ACP';
        $cta_url    = 'admin/index.php?module=config-settings';
        eval("\$cta_cards .= \"".$templates->get("storyline_overview_cta_card")."\";");
    }

    $cta_heading_html = '';
    $cta_heading = trim((string)storyline_overview_setting('storyline_overview_cta_heading', ''));
    if($cta_heading !== '')
    {
        $cta_heading_html = '<div class="thead storyline-panel-head"><strong>'.htmlspecialchars_uni($cta_heading).'</strong></div>';
    }

    eval("\$page = \"".$templates->get("storyline_overview_page")."\";");
    output_page($page);
}

function storyline_overview_soft_upgrade()
{
    static $done = false;
    if($done === true)
    {
        return;
    }
    $done = true;

    if(!storyline_overview_is_installed())
    {
        return;
    }

    global $mybb;
    if(!isset($mybb->settings['storyline_overview_storyline_html']) || !isset($mybb->settings['storyline_overview_cta1_icon']) || !isset($mybb->settings['storyline_overview_page_background']))
    {
        storyline_overview_ensure_all();
        rebuild_settings();
    }
}

function storyline_overview_setting($key, $default = '')
{
    global $mybb;
    return isset($mybb->settings[$key]) ? $mybb->settings[$key] : $default;
}

function storyline_overview_parse_forum_ids($csv)
{
    $ids = array();
    $parts = explode(",", (string)$csv);
    foreach($parts as $part)
    {
        $fid = (int)trim($part);
        if($fid > 0)
        {
            $ids[$fid] = $fid;
        }
    }
    return array_values($ids);
}

function storyline_overview_build_in_sql(array $ids)
{
    if(empty($ids))
    {
        return '';
    }

    $clean = array();
    foreach($ids as $id)
    {
        $id = (int)$id;
        if($id > 0)
        {
            $clean[] = $id;
        }
    }
    return empty($clean) ? '' : implode(",", $clean);
}

function storyline_overview_accessible_forum_ids(array $ids)
{
    if(empty($ids))
    {
        return array();
    }

    $blocked = array();
    $unviewable = storyline_overview_parse_forum_ids(get_unviewable_forums(true));
    $inactive = storyline_overview_parse_forum_ids(get_inactive_forums());

    foreach(array_merge($unviewable, $inactive) as $fid)
    {
        $blocked[(int)$fid] = true;
    }

    $accessible = array();
    foreach($ids as $fid)
    {
        $fid = (int)$fid;
        if($fid > 0 && !isset($blocked[$fid]))
        {
            $accessible[$fid] = $fid;
        }
    }

    return array_values($accessible);
}

?>
