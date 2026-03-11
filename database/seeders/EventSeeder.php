<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\EventSpeaker;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::create([
            'title'         => 'Workshop Interactiv TAVI',
            'subtitle'      => 'Abordare avansată și optimizarea rezultatelor, de la anatomie la implantare',
            'description'   => "Vă invităm să participați la Workshopul Interactiv TAVI Edwards™, organizat în cadrul Monza ARES în data de 31 octombrie 2025, un eveniment dedicat medicilor interesați de abordarea avansată a cazurilor TAVI și optimizarea rezultatelor clinice în anatomii provocatoare.\n\nAcest workshop este construit ca un format intensiv, orientat spre practică, care îmbină analiza decizională, transmiterea de cazuri live, discuții interactive și simulări hands-on cu focus pe precizie, timing și anatomii dificile – așa cum le întâlnim în practica reală.",
            'slug'          => 'workshop-interactiv-tavi',
            'date'          => '2025-10-31',
            'time_start'    => '08:30',
            'time_end'      => '18:00',
            'location'      => 'Centrul de Training',
            'venue'         => 'Spitalul Monza',
            'credits'       => 6,
            'credits_label' => 'CURS CREDITAT CU 6 PUNCTE EMC',
            'status'        => 'published',
            'max_participants' => 50,
            'created_by'    => 1,
        ]);

        // Speakers
        $speakers = [
            ['name' => 'Dr. Theodor Cebotaru', 'specialty' => 'Medic primar Chirurgie Cardiovasculară', 'image' => '/Dr_Theodor_Cebotaru.webp', 'slug' => 'dr-theodor-cebotaru', 'speaker_role' => 'director', 'order' => 0],
            ['name' => 'Dr. Stanislav Rurac',  'specialty' => 'Medic primar Chirurgie cardiovasculară', 'image' => '/dr-2.jpg', 'slug' => 'dr-stanislav-rurac', 'speaker_role' => 'director', 'order' => 1],
            ['name' => 'Dr. Călin Popa',       'specialty' => "Medic primar Chirurgie cardiovasculară\nChirurgie vasculară", 'image' => '/dr-3.jpg', 'slug' => 'dr-calin-popa', 'speaker_role' => 'speaker', 'order' => 2],
            ['name' => 'Dr. Andrei Eni',       'specialty' => 'Chirurgie vasculară', 'image' => '/dr-4.jpg', 'slug' => 'dr-andrei-eni', 'speaker_role' => 'speaker', 'order' => 3],
        ];

        foreach ($speakers as $s) {
            EventSpeaker::create(['event_id' => $event->id, ...$s]);
        }

        // Schedule / Program
        $sessions = [
            ['time_label' => '08:30-09:00', 'title' => 'REGISTRATION & COFFEE', 'order' => 0, 'items' => []],
            ['time_label' => '09:00-10:30', 'title' => 'SESIUNEA I', 'order' => 1, 'items' => [
                'PROTOCOLUL DE EVALUARE PRE TAVI',
                'PREZENTAREA ÎN DETALIU A VALVEI SAPIEN',
                'ABORD FEMURAL ȘI ALTERNATIV ÎN TAVI CU VALVA SAPIEN',
                'TEHNICA TAVI MINIMALISTĂ',
            ]],
            ['time_label' => '10:30-10:45', 'title' => 'PAUZĂ DE CAFEA', 'order' => 2, 'items' => []],
            ['time_label' => '10:45-12:45', 'title' => 'SESIUNEA II', 'order' => 3, 'items' => [
                'CAZ TAVI 1 LIVE',
                'ANALIZĂ COMENTATĂ A CAZULUI. TRANSMISIE LIVE. Q&A',
                'ATELIER HANDS-ON I – SIMULARE TAVI STEP-BY-STEP – CU FOCUS PE ROLE-PLAY DECIZIONAL',
                'TAVI ÎN BICUSPIDIA AORTICĂ',
                'TAVI ÎN SITUAȚII SPECIALE VIV, VIR, VIMAC',
            ]],
            ['time_label' => '12:45-13:30', 'title' => 'PAUZĂ DE MASĂ', 'order' => 4, 'items' => []],
            ['time_label' => '13:30-15:30', 'title' => 'SESIUNEA III', 'order' => 5, 'items' => [
                'CAZ TAVI 2 LIVE',
                'ANALIZĂ COMENTATĂ A CAZULUI. TRANSMISIE LIVE. Q&A',
                'ATELIER HANDS-ON II – SIMULARE TAVI STEP-BY-STEP – CU FOCUS PE ROLE-PLAY DECIZIONAL',
                'REVASCULARIZAREA CORONARIANĂ LA PACIENȚI CU TAVI',
                'STENOZA AORTICĂ MODERATĂ – CÂND DEVINE TAVI O OPȚIUNE TERAPEUTICĂ',
            ]],
            ['time_label' => '15:30-15:45', 'title' => 'PAUZĂ DE CAFEA', 'order' => 6, 'items' => []],
            ['time_label' => '15:45-17:45', 'title' => 'SESIUNEA IV', 'order' => 7, 'items' => [
                'CAZ TAVI 3 LIVE',
                'ANALIZĂ COMENTATĂ A CAZULUI. TRANSMISIE LIVE. Q&A',
                'ATELIER HANDS-ON II – SIMULARE TAVI STEP-BY-STEP – CU FOCUS PE ROLE-PLAY DECIZIONAL',
                'COMPLICAȚII ÎN TIMPUL ȘI DUPĂ TAVI',
                'DEBATE INTERACTIV: "TAVI PENTRU TOȚI" VS. "CHIRURGIE LA TINERI" - CUI DĂM DREPTATE?',
            ]],
            ['time_label' => '17:45-18:00', 'title' => 'CONCLUZII ȘI ÎNCHIDERE WORKSHOP', 'order' => 8, 'items' => []],
            ['time_label' => '18:30',        'title' => 'CINĂ', 'order' => 9, 'items' => []],
        ];

        foreach ($sessions as $s) {
            $session = EventSession::create([
                'event_id'   => $event->id,
                'time_label' => $s['time_label'],
                'title'      => $s['title'],
                'order'      => $s['order'],
            ]);
            foreach ($s['items'] as $i => $content) {
                $session->items()->create(['content' => $content, 'order' => $i]);
            }
        }
    }
}
