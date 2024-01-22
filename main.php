<?php
// Include WordPress for functionality
require_once('wp-load.php');

function fetch_external_links() {
    // Array zum Speichern der externen Links
    $external_links = [];

    // Hole alle Posts
    $posts = get_posts(array('numberposts' => -1));

    // Durchsuche jeden Post nach externen Links
    foreach ($posts as $post) {
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($post->post_content, 'HTML-ENTITIES', 'UTF-8'));
        $links = $doc->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            // Prüfe, ob es sich um einen externen Link handelt
            if (strpos($href, get_site_url()) === false && filter_var($href, FILTER_VALIDATE_URL)) {
                $external_links[] = [
                    'url' => $href,
                    'post_id' => $post->ID, // Speichere Post-ID
                    'post_name' => $post->post_title,
                    'post_url' => get_permalink($post->ID)
                ];
            }
        }
    }

    return $external_links;
}

function display_external_links() {
    $links = fetch_external_links();
    echo '<form action="" method="post">';
    foreach ($links as $link) {
        echo '<input type="checkbox" name="urls_to_delete[]" value="' . htmlspecialchars($link['url']) . '|' . $link['post_id'] . '"> ';
        echo 'Externer Link: <a href="' . $link['url'] . '" target="_blank">' . $link['url'] . '</a> - Post: <a href="' . $link['post_url'] . '" target="_blank">' . $link['post_name'] . '</a><br>';
    }
    echo '<input type="submit" value="Ausgewählte Links löschen">';
    echo '</form>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['urls_to_delete'])) {
    foreach ($_POST['urls_to_delete'] as $url_with_post_id) {
        list($url_to_delete, $post_id) = explode('|', $url_with_post_id);
        $post = get_post($post_id);
        $content = $post->post_content;

        // Verwende Regex zum Entfernen des Links, behalte aber den Linktext
        $content = preg_replace_callback('/<a [^>]*?href=["\']' . preg_quote($url_to_delete, '/') . '["\'][^>]*>(.*?)<\/a>/', function($matches) {
            return $matches[1]; // Behalte nur den Linktext
        }, $content);

        wp_update_post([
            'ID' => $post_id,
            'post_content' => $content
        ]);
    }
}

// Anzeigen der Links
display_external_links();
?>
