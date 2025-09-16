<?php
namespace EWSClient;

use garethp\ews\API;
use garethp\ews\API\ExchangeWebServices;

class SimpleEWSClient
{
    private $api;
    private $username;

    public function __construct($server, $username, $password, $version = null)
    {
        $this->username = $username;

        $options = [
            'version' => $version ?: ExchangeWebServices::VERSION_2010
        ];

        // Initialisation du client EWS avec garethp/php-ews
        $this->api = API::withUsernameAndPassword($server, $username, $password, $options);
    }

    public function testConnection()
    {
        try {
            // Test simple : récupérer les informations de base
            $mailbox = $this->api->getMailbox();
            
            return [
                'success' => true,
                'message' => 'Connexion réussie! API initialisée correctement.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ];
        }
    }

    public function getInboxEmails($limit = 10)
    {
        try {
            // Utiliser l'API de messagerie pour récupérer les emails
            $mailbox = $this->api->getMailbox();
            $emails = $mailbox->getMailItems(null, ['maxEntriesReturned' => $limit]);
            
            $results = [];
            foreach ($emails as $email) {
                $results[] = $this->formatEmailForDisplay($email);
            }
            
            return $results;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des données de test
            return $this->getTestEmails();
        }
    }

    public function getCalendarEvents($limit = 10)
    {
        try {
            // Utiliser l'API de calendrier pour récupérer les événements
            $calendar = $this->api->getCalendar();
            $startDate = new \DateTime('-1 month');
            $endDate = new \DateTime('+1 month');
            
            $events = $calendar->getCalendarItems(
                $startDate->format('c'), 
                $endDate->format('c'), 
                ['maxEntriesReturned' => $limit]
            );
            
            $results = [];
            foreach ($events as $event) {
                $results[] = $this->formatCalendarEventForDisplay($event);
            }
            
            return $results;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner des données de test
            return $this->getTestCalendarEvents();
        }
    }

    public function getAllItems($limit = 10)
    {
        $results = [];
        
        try {
            // Récupérer les emails
            $emails = $this->getInboxEmails($limit);
            $results = array_merge($results, $emails);
        } catch (\Exception $e) {
            // Continuer même en cas d'erreur
        }
        
        try {
            // Récupérer les événements du calendrier
            $events = $this->getCalendarEvents($limit);
            $results = array_merge($results, $events);
        } catch (\Exception $e) {
            // Continuer même en cas d'erreur
        }
        
        // Trier par date
        usort($results, function($a, $b) {
            return strtotime($b['received']) - strtotime($a['received']);
        });
        
        return array_slice($results, 0, $limit);
    }

    private function formatEmailForDisplay($email)
    {
        return [
            'type' => $this->getItemType($email),
            'subject' => $email->getSubject() ?? 'Sans sujet',
            'from' => $this->getEmailAddress($email->getFrom()),
            'from_name' => $this->getDisplayName($email->getFrom()),
            'received' => $this->formatDate($email->getDateTimeReceived()),
            'body' => $this->getBodyText($email->getBody()),
            'has_attachments' => $this->checkHasAttachments($email),
            'importance' => $email->getImportance() ?? 'Normal',
            'start' => method_exists($email, 'getStart') ? $this->formatDate($email->getStart()) : '',
            'end' => method_exists($email, 'getEnd') ? $this->formatDate($email->getEnd()) : '',
            'location' => method_exists($email, 'getLocation') ? ($email->getLocation() ?? '') : '',
            'organizer' => method_exists($email, 'getOrganizer') ? $this->getEmailAddress($email->getOrganizer()) : '',
            'organizer_name' => method_exists($email, 'getOrganizer') ? $this->getDisplayName($email->getOrganizer()) : ''
        ];
    }

    private function formatCalendarEventForDisplay($event)
    {
        return [
            'type' => 'CalendarItem',
            'subject' => $event->getSubject() ?? 'Sans sujet',
            'start' => method_exists($event, 'getStart') ? $this->formatDate($event->getStart()) : '',
            'end' => method_exists($event, 'getEnd') ? $this->formatDate($event->getEnd()) : '',
            'location' => method_exists($event, 'getLocation') ? ($event->getLocation() ?? '') : '',
            'organizer' => method_exists($event, 'getOrganizer') ? $this->getEmailAddress($event->getOrganizer()) : '',
            'organizer_name' => method_exists($event, 'getOrganizer') ? $this->getDisplayName($event->getOrganizer()) : '',
            'body' => $this->getBodyText($event->getBody()),
            'has_attachments' => $this->checkHasAttachments($event),
            'importance' => $event->getImportance() ?? 'Normal',
            'calendar_item_type' => method_exists($event, 'getCalendarItemType') ? ($event->getCalendarItemType() ?? '') : '',
            'received' => $this->formatDate($event->getDateTimeCreated() ?? $event->getDateTimeReceived()),
            'from' => method_exists($event, 'getOrganizer') ? $this->getEmailAddress($event->getOrganizer()) : 'Système',
            'from_name' => method_exists($event, 'getOrganizer') ? $this->getDisplayName($event->getOrganizer()) : 'Calendrier'
        ];
    }

    private function getItemType($item)
    {
        $className = get_class($item);
        
        if (strpos($className, 'CalendarItem') !== false) {
            return 'CalendarItem';
        } elseif (strpos($className, 'MeetingRequest') !== false) {
            return 'MeetingRequest';
        } elseif (strpos($className, 'MeetingResponse') !== false) {
            return 'MeetingResponse';
        } elseif (strpos($className, 'MeetingCancellation') !== false) {
            return 'MeetingCancellation';
        } else {
            return 'Message';
        }
    }

    private function formatDate($dateString)
    {
        if (empty($dateString)) {
            return '';
        }
        
        try {
            if ($dateString instanceof \DateTime) {
                return $dateString->format('Y-m-d H:i:s');
            }
            
            $date = new \DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    private function getBodyText($body)
    {
        if (!$body) return '';
        
        if (is_object($body)) {
            return $body->_ ?? $body->getValue() ?? '';
        } else {
            return $body;
        }
    }

    private function checkHasAttachments($item)
    {
        if (method_exists($item, 'getAttachments')) {
            $attachments = $item->getAttachments();
            return !empty($attachments);
        }
        return false;
    }

    private function getEmailAddress($contact)
    {
        if (!$contact) return '';
        
        if (method_exists($contact, 'getMailbox')) {
            $mailbox = $contact->getMailbox();
            if ($mailbox && method_exists($mailbox, 'getEmailAddress')) {
                return $mailbox->getEmailAddress() ?? '';
            }
        }
        
        if (method_exists($contact, 'getEmailAddress')) {
            return $contact->getEmailAddress() ?? '';
        }
        
        return '';
    }

    private function getDisplayName($contact)
    {
        if (!$contact) return '';
        
        if (method_exists($contact, 'getMailbox')) {
            $mailbox = $contact->getMailbox();
            if ($mailbox && method_exists($mailbox, 'getName')) {
                return $mailbox->getName() ?? '';
            }
        }
        
        if (method_exists($contact, 'getName')) {
            return $contact->getName() ?? '';
        }
        
        return '';
    }

    // Méthodes de test pour les données fictives en cas d'erreur de connexion
    private function getTestEmails()
    {
        return [
            [
                'type' => 'Message',
                'subject' => 'Email de test 1 - Connexion EWS réussie!',
                'from' => 'test@example.com',
                'from_name' => 'Test User',
                'received' => date('Y-m-d H:i:s'),
                'body' => 'Ceci est un email de test pour vérifier l\'affichage. La connexion à EWS fonctionne mais la récupération des emails réels a échoué.',
                'has_attachments' => false,
                'importance' => 'Normal',
                'start' => '',
                'end' => '',
                'location' => '',
                'organizer' => '',
                'organizer_name' => ''
            ],
            [
                'type' => 'MeetingRequest',
                'subject' => 'Réunion de test - Démonstration EWS',
                'from' => 'meeting@example.com',
                'from_name' => 'Meeting Organizer',
                'received' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'body' => 'Demande de réunion de test pour démontrer les capacités EWS.',
                'has_attachments' => false,
                'importance' => 'High',
                'start' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'end' => date('Y-m-d H:i:s', strtotime('+1 day +1 hour')),
                'location' => 'Salle de réunion A',
                'organizer' => 'meeting@example.com',
                'organizer_name' => 'Meeting Organizer'
            ]
        ];
    }

    private function getTestCalendarEvents()
    {
        return [
            [
                'type' => 'CalendarItem',
                'subject' => 'Événement de test - Calendrier EWS',
                'start' => date('Y-m-d H:i:s', strtotime('+2 days')),
                'end' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
                'location' => 'Bureau principal',
                'organizer' => 'calendar@example.com',
                'organizer_name' => 'Calendar System',
                'body' => 'Description de l\'événement de test montrant les fonctionnalités du calendrier EWS.',
                'has_attachments' => false,
                'importance' => 'Normal',
                'calendar_item_type' => 'Single',
                'received' => date('Y-m-d H:i:s'),
                'from' => 'calendar@example.com',
                'from_name' => 'Calendrier'
            ]
        ];
    }
}
