<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client EWS Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .item { background: #f9f9f9; margin: 10px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba; }
        .email { border-left-color: #28a745; }
        .calendar { border-left-color: #dc3545; }
        .meeting { border-left-color: #ffc107; }
        .item h3 { margin: 0 0 10px 0; color: #333; }
        .meta { color: #666; font-size: 0.9em; margin: 5px 0; }
        .body { margin: 10px 0; max-height: 100px; overflow-y: auto; background: white; padding: 10px; border-radius: 3px; }
        .type-badge { display: inline-block; background: #007cba; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-bottom: 5px; }
        .calendar .type-badge { background: #dc3545; }
        .meeting .type-badge { background: #ffc107; color: #333; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require_once __DIR__ . '/vendor/autoload.php';

// Importer les classes nécessaires
use garethp\ews\API;
use EWSClient\SimpleEWSClient;

// Configuration
$server = 'ex.mail.ovh.ca/EWS/Exchange.asmx'; // ou votre serveur Exchange
$username = 'evoludata@outlook.evoludata.com';
$password = 'skbcJuyw9guQ^qPd%&$4';

try {
    echo "<h1>Client EWS Simple</h1>";
    echo "<div class='success'>Chargement de l'autoloader...</div>";

    echo "<div class='success'>Création du client EWS...</div>";
    $client = new SimpleEWSClient($server, $username, $password);

    echo "<div class='success'>Test de connexion...</div>";
    $test = $client->testConnection();

    if ($test['success']) {
        echo "<div class='success'>✓ Connexion réussie!<br>" . $test['message'] . "</div>";

        // Récupérer quelques emails (incluant les événements du calendrier)
        echo "<h2>📧 Récupération des emails et événements (Inbox)...</h2>";
        $emails = $client->getInboxEmails(10);

        echo "<p><strong>Items trouvés dans l'inbox: " . count($emails) . "</strong></p>";

        foreach ($emails as $index => $email) {
            $cssClass = 'item ';
            $icon = '📧';

            switch ($email['type']) {
                case 'CalendarItem':
                    $cssClass .= 'calendar';
                    $icon = '📅';
                    break;
                case 'MeetingRequest':
                case 'MeetingResponse':
                case 'MeetingCancellation':
                    $cssClass .= 'meeting';
                    $icon = '🤝';
                    break;
                default:
                    $cssClass .= 'email';
                    $icon = '📧';
            }

            echo "<div class='{$cssClass}'>";
            echo "<span class='type-badge'>{$email['type']}</span>";
            echo "<h3>{$icon} " . htmlspecialchars($email['subject']) . "</h3>";
            echo "<div class='meta'><strong>De:</strong> " . htmlspecialchars($email['from']) .
                 (!empty($email['from_name']) ? " (" . htmlspecialchars($email['from_name']) . ")" : "") . "</div>";
            echo "<div class='meta'><strong>Reçu:</strong> " . htmlspecialchars($email['received']) . "</div>";

            // Informations spécifiques aux événements
            if (isset($email['start']) && !empty($email['start'])) {
                echo "<div class='meta'><strong>📅 Début:</strong> " . htmlspecialchars($email['start']) . "</div>";
            }
            if (isset($email['end']) && !empty($email['end'])) {
                echo "<div class='meta'><strong>📅 Fin:</strong> " . htmlspecialchars($email['end']) . "</div>";
            }
            if (isset($email['location']) && !empty($email['location'])) {
                echo "<div class='meta'><strong>📍 Lieu:</strong> " . htmlspecialchars($email['location']) . "</div>";
            }
            if (isset($email['organizer']) && !empty($email['organizer'])) {
                echo "<div class='meta'><strong>👤 Organisateur:</strong> " . htmlspecialchars($email['organizer']) . "</div>";
            }

            echo "<div class='meta'><strong>Pièces jointes:</strong> " . ($email['has_attachments'] ? '✅ Oui' : '❌ Non') . "</div>";
            echo "<div class='meta'><strong>Importance:</strong> " . htmlspecialchars($email['importance']) . "</div>";

            if (!empty($email['body'])) {
                $body_preview = substr(strip_tags($email['body']), 0, 200);
                echo "<div class='body'><strong>Contenu:</strong><br>" . htmlspecialchars($body_preview) .
                     (strlen($email['body']) > 200 ? "..." : "") . "</div>";
            }
            echo "</div>";
        }

        // Récupérer spécifiquement les événements du calendrier
        echo "<h2>📅 Récupération des événements du calendrier...</h2>";
        $calendar_events = $client->getCalendarEvents(5);

        echo "<p><strong>Événements trouvés dans le calendrier: " . count($calendar_events) . "</strong></p>";

        foreach ($calendar_events as $index => $event) {
            echo "<div class='item calendar'>";
            echo "<span class='type-badge'>Événement Calendrier</span>";
            echo "<h3>📅 " . htmlspecialchars($event['subject']) . "</h3>";
            echo "<div class='meta'><strong>📅 Début:</strong> " . htmlspecialchars($event['start']) . "</div>";
            echo "<div class='meta'><strong>📅 Fin:</strong> " . htmlspecialchars($event['end']) . "</div>";
            if (!empty($event['location'])) {
                echo "<div class='meta'><strong>📍 Lieu:</strong> " . htmlspecialchars($event['location']) . "</div>";
            }
            if (!empty($event['organizer'])) {
                echo "<div class='meta'><strong>👤 Organisateur:</strong> " . htmlspecialchars($event['organizer']);
                if (!empty($event['organizer_name'])) {
                    echo " (" . htmlspecialchars($event['organizer_name']) . ")";
                }
                echo "</div>";
            }
            if (!empty($event['calendar_item_type'])) {
                echo "<div class='meta'><strong>Type:</strong> " . htmlspecialchars($event['calendar_item_type']) . "</div>";
            }
            echo "<div class='meta'><strong>Pièces jointes:</strong> " . ($event['has_attachments'] ? '✅ Oui' : '❌ Non') . "</div>";
            echo "<div class='meta'><strong>Importance:</strong> " . htmlspecialchars($event['importance']) . "</div>";

            if (!empty($event['body'])) {
                $body_preview = substr(strip_tags($event['body']), 0, 200);
                echo "<div class='body'><strong>Description:</strong><br>" . htmlspecialchars($body_preview) .
                     (strlen($event['body']) > 200 ? "..." : "") . "</div>";
            }
            echo "</div>";
        }

    } else {
        echo "<div class='error'>✗ Erreur de connexion: " . htmlspecialchars($test['message']) . "</div>";
    }

} catch (\Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Erreur fatale:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine();
    echo "</div>";

    // Debug supplémentaire
    echo "<div class='debug'>";
    echo "<strong>=== DEBUG ===</strong><br>";
    echo "<strong>Classes chargées:</strong><br>";
    if (class_exists('garethp\\ews\\API')) {
        echo "- garethp\\ews\\API: ✅ OK<br>";
    } else {
        echo "- garethp\\ews\\API: ❌ NON TROUVÉ<br>";
    }

    if (class_exists('EWSClient\\SimpleEWSClient')) {
        echo "- EWSClient\\SimpleEWSClient: ✅ OK<br>";
    } else {
        echo "- EWSClient\\SimpleEWSClient: ❌ NON TROUVÉ<br>";
    }
    echo "</div>";
}
?>
    </div>
</body>
</html>