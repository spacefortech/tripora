<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GuideController
{
    public function index(): Response
    {
        $citiesJson = json_encode(
            array_values($this->getCities()),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $html = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyTravelGuide Germany</title>
    <meta name="description" content="Scopri attrazioni, ristoranti, locali e luoghi unici nelle principali città tedesche.">
    <link rel="preload" href="/assets/city-discovery-hero.png" as="image">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <div class="top-line" aria-hidden="true"></div>
    <header class="site-header">
        <div class="nav-inner" aria-label="Navigazione principale">
            <a class="brand" href="/" aria-label="MyTravelGuide Germany">
                <span class="brand-spark" aria-hidden="true">m</span>
                <span class="brand-text">MYTRAVEL<br>GUIDE</span>
            </a>
            <nav class="main-nav" aria-label="Sezioni">
                <a href="#places">Città cool</a>
                <a href="#cities">Destinazioni</a>
                <a href="#weekend">Weekend</a>
                <a href="/api/cities">API</a>
            </nav>
            <form class="mini-search" data-mini-search autocomplete="off">
                <label class="sr-only" for="mini-city-input">Cerca città</label>
                <input id="mini-city-input" type="search" placeholder="Cerca...">
                <button type="submit" aria-label="Cerca">
                    <span aria-hidden="true">&#9906;</span>
                </button>
            </form>
        </div>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-card">
                <div class="orange-route route-one" aria-hidden="true"></div>
                <div class="orange-route route-two" aria-hidden="true"></div>
                <div class="hero-shade" aria-hidden="true"></div>
                <div class="hero-content">
                    <h1 id="hero-title">Trova posti davvero cool in Germania.</h1>
                    <form class="search-panel" data-city-search autocomplete="off">
                        <div class="search-tabs" aria-label="Tipi di ricerca">
                            <button class="is-active" type="button"><span aria-hidden="true">&#9906;</span> Città</button>
                            <button type="button"><span aria-hidden="true">&#127860;</span> Food</button>
                            <button type="button"><span aria-hidden="true">&#9835;</span> Locali</button>
                        </div>
                        <div class="search-fields">
                            <label>
                                <span>Dove vuoi andare?</span>
                                <input id="city-input" name="city" list="city-options" type="search" placeholder="Es. Berlino" data-city-input>
                            </label>
                            <datalist id="city-options"></datalist>
                            <button type="submit">Scoprire</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="feed-shell">
            <div class="category-row" aria-label="Filtri">
                <button class="is-active" type="button" data-category-filter="all">&#9734; Nuovi luoghi</button>
                <button type="button" data-category-filter="Icona">Icone</button>
                <button type="button" data-category-filter="Cultura">Cultura</button>
                <button type="button" data-category-filter="Food">Food</button>
                <button type="button" data-category-filter="Locale">Locali</button>
                <button type="button" data-category-filter="Unico">Unici</button>
            </div>

            <section class="city-strip" id="cities" aria-label="Città principali" data-city-strip></section>

            <section class="results-shell" id="places" aria-live="polite">
                <div class="section-heading">
                    <h2 data-results-heading>Nuovi luoghi</h2>
                    <p data-results-copy>Scopri una selezione curata di posti da visitare, mangiare e vivere in città.</p>
                </div>
                <div data-city-result></div>
            </section>
        </section>
    </main>

    <footer class="site-footer" id="weekend">
        <span>MyTravelGuide Germany</span>
        <span>Primo prototipo con dati curati per le principali città tedesche.</span>
    </footer>

    <script>window.CITY_GUIDE_DATA = {$citiesJson};</script>
    <script src="/assets/app.js" defer></script>
</body>
</html>
HTML;

        return new Response($html);
    }

    public function apiCities(): JsonResponse
    {
        return new JsonResponse(array_values($this->getCities()));
    }

    public function apiCity(string $slug): JsonResponse
    {
        $city = $this->findCity($slug);

        if (!$city) {
            return new JsonResponse(
                array('error' => 'City not found'),
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($city);
    }

    private function findCity(string $slug): ?array
    {
        $normalizedSlug = $this->normalize($slug);

        foreach ($this->getCities() as $city) {
            if ($city['slug'] === $normalizedSlug) {
                return $city;
            }

            $aliases = array_merge(array($city['name']), $city['aliases']);

            foreach ($aliases as $alias) {
                if ($this->normalize($alias) === $normalizedSlug) {
                    return $city;
                }
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $map = array(
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'Ä' => 'ae',
            'Ö' => 'oe',
            'Ü' => 'ue',
            'à' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
        );
        $value = trim($value);
        $value = strtr($value, $map);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    private function getCities(): array
    {
        return array(
            array(
                'slug' => 'berlin',
                'name' => 'Berlin',
                'italianName' => 'Berlino',
                'aliases' => array('Berlino'),
                'region' => 'Capitale creativa',
                'headline' => 'Street art, musei audaci e notti lunghissime lungo la Sprea.',
                'summary' => 'Berlino funziona quando alterni icone culturali, cortili industriali, mercati e quartieri con una vita locale molto riconoscibile.',
                'bestFor' => 'Arte urbana, nightlife, cultura indipendente',
                'duration' => '2-4 giorni',
                'accent' => '#f9735b',
                'neighborhoods' => array('Mitte', 'Kreuzberg', 'Friedrichshain', 'Neukölln'),
                'spots' => array(
                    array('type' => 'Arte urbana', 'name' => 'East Side Gallery', 'area' => 'Friedrichshain', 'note' => 'Un tratto iconico del Muro trasformato in galleria aperta, perfetto per iniziare con energia.', 'time' => '60-90 min'),
                    array('type' => 'Cultura', 'name' => 'Sammlung Boros', 'area' => 'Mitte', 'note' => 'Arte contemporanea dentro un bunker riconvertito, con atmosfera ruvida e molto berlinese.', 'time' => 'Prenotazione'),
                    array('type' => 'Food', 'name' => 'Markthalle Neun', 'area' => 'Kreuzberg', 'note' => 'Mercato coperto con tavoli condivisi, street food e produttori locali.', 'time' => 'Pranzo'),
                    array('type' => 'Locale', 'name' => 'Klunkerkranich', 'area' => 'Neukölln', 'note' => 'Rooftop informale sopra la città, bello al tramonto e facile da inserire in una serata.', 'time' => 'Sera'),
                    array('type' => 'Unico', 'name' => 'Tempelhofer Feld', 'area' => 'Tempelhof', 'note' => 'L’ex aeroporto diventato parco urbano: bici, picnic e orizzonti larghi in piena città.', 'time' => '2 ore'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Mitte e arte contemporanea', 'detail' => 'Caffè, gallerie e una visita prenotata alla Sammlung Boros.'),
                    array('step' => 'Pomeriggio', 'title' => 'Kreuzberg lento', 'detail' => 'Pranzo alla Markthalle Neun e passeggiata tra canali, shop indipendenti e street art.'),
                    array('step' => 'Sera', 'title' => 'Rooftop e quartiere', 'detail' => 'Tramonto a Klunkerkranich, poi una cena semplice intorno a Neukölln.'),
                ),
            ),
            array(
                'slug' => 'hamburg',
                'name' => 'Hamburg',
                'italianName' => 'Amburgo',
                'aliases' => array('Amburgo'),
                'region' => 'Porto e design',
                'headline' => 'Canali, warehouse rossi, musica e tavoli sul porto.',
                'summary' => 'Amburgo combina acqua, architettura e quartieri creativi: il ritmo migliore è tra Speicherstadt, Sternschanze e il lungofiume.',
                'bestFor' => 'Architettura, concerti, passeggiate sull’acqua',
                'duration' => '2-3 giorni',
                'accent' => '#0f766e',
                'neighborhoods' => array('Speicherstadt', 'HafenCity', 'Sternschanze', 'St. Pauli'),
                'spots' => array(
                    array('type' => 'Icona', 'name' => 'Speicherstadt', 'area' => 'HafenCity', 'note' => 'Magazzini storici e canali: la base visiva perfetta per capire la città.', 'time' => '90 min'),
                    array('type' => 'Vista', 'name' => 'Elbphilharmonie Plaza', 'area' => 'HafenCity', 'note' => 'Una terrazza pubblica con vista sul porto e profilo architettonico molto riconoscibile.', 'time' => '45 min'),
                    array('type' => 'Quartiere', 'name' => 'Sternschanze', 'area' => 'Schanze', 'note' => 'Caffè, negozi indipendenti e locali facili per una serata senza programma rigido.', 'time' => 'Pomeriggio'),
                    array('type' => 'Food', 'name' => 'Fischmarkt', 'area' => 'Altona', 'note' => 'Classico locale, vivace e molto presto: ideale se vuoi vedere un lato diverso della città.', 'time' => 'Domenica'),
                    array('type' => 'Relax', 'name' => 'Strandperle', 'area' => 'Othmarschen', 'note' => 'Tavoli sulla sabbia lungo l’Elba, perfetti con luce morbida e tempo lento.', 'time' => 'Tramonto'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Speicherstadt a piedi', 'detail' => 'Canali, ponti e architettura in mattoni prima che la zona si riempia.'),
                    array('step' => 'Pomeriggio', 'title' => 'HafenCity e Schanze', 'detail' => 'Vista dalla Plaza, poi negozi e caffè intorno a Sternschanze.'),
                    array('step' => 'Sera', 'title' => 'Elba o St. Pauli', 'detail' => 'Cena informale sul fiume o musica live nel lato più notturno della città.'),
                ),
            ),
            array(
                'slug' => 'munich',
                'name' => 'Munich',
                'italianName' => 'Monaco',
                'aliases' => array('Monaco', 'Monaco di Baviera', 'München', 'Muenchen'),
                'region' => 'Classica e outdoor',
                'headline' => 'Musei, mercati, surf urbano e birrerie sotto gli alberi.',
                'summary' => 'Monaco è ordinata ma non rigida: alterna grandi musei, parchi, mercati storici e quartieri con ottimi indirizzi gastronomici.',
                'bestFor' => 'Musei, mercati, parchi, tradizione bavarese',
                'duration' => '2-3 giorni',
                'accent' => '#2563eb',
                'neighborhoods' => array('Altstadt', 'Maxvorstadt', 'Glockenbach', 'Schwabing'),
                'spots' => array(
                    array('type' => 'Musei', 'name' => 'Kunstareal', 'area' => 'Maxvorstadt', 'note' => 'Un distretto culturale denso, comodo per costruire una giornata tra arte e design.', 'time' => 'Mezza giornata'),
                    array('type' => 'Food', 'name' => 'Viktualienmarkt', 'area' => 'Altstadt', 'note' => 'Mercato storico per assaggi veloci, prodotti locali e una pausa all’aperto.', 'time' => 'Pranzo'),
                    array('type' => 'Outdoor', 'name' => 'Eisbachwelle', 'area' => 'Englischer Garten', 'note' => 'La famosa onda da surf urbana, breve ma sorprendente e molto fotogenica.', 'time' => '30 min'),
                    array('type' => 'Quartiere', 'name' => 'Glockenbachviertel', 'area' => 'Glockenbach', 'note' => 'Boutique, bar e ristoranti contemporanei in una zona compatta e vivace.', 'time' => 'Sera'),
                    array('type' => 'Parco', 'name' => 'Olympiapark', 'area' => 'Milbertshofen', 'note' => 'Architettura anni Settanta, colline e viste ampie sulla città.', 'time' => '2 ore'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Arte a Maxvorstadt', 'detail' => 'Scegli una Pinakothek e lascia spazio per una colazione lunga.'),
                    array('step' => 'Pomeriggio', 'title' => 'Mercato e parco', 'detail' => 'Viktualienmarkt, centro storico e deviazione all’Eisbach.'),
                    array('step' => 'Sera', 'title' => 'Glockenbach', 'detail' => 'Cena contemporanea e bar in un quartiere facile da esplorare a piedi.'),
                ),
            ),
            array(
                'slug' => 'cologne',
                'name' => 'Cologne',
                'italianName' => 'Colonia',
                'aliases' => array('Colonia', 'Köln', 'Koeln'),
                'region' => 'Reno e cultura pop',
                'headline' => 'Cattedrale, arte moderna, birrerie e quartieri creativi.',
                'summary' => 'Colonia è immediata e conviviale: il centro è compatto, ma i quartieri laterali danno il tono più interessante al viaggio.',
                'bestFor' => 'Arte moderna, birrerie, weekend facili',
                'duration' => '1-2 giorni',
                'accent' => '#db2777',
                'neighborhoods' => array('Altstadt', 'Belgisches Viertel', 'Ehrenfeld', 'Rheinauhafen'),
                'spots' => array(
                    array('type' => 'Icona', 'name' => 'Cattedrale di Colonia', 'area' => 'Altstadt', 'note' => 'Un ingresso scenografico alla città, soprattutto arrivando dalla stazione centrale.', 'time' => '45 min'),
                    array('type' => 'Cultura', 'name' => 'Museum Ludwig', 'area' => 'Altstadt', 'note' => 'Arte moderna e pop art in posizione perfetta tra Duomo e Reno.', 'time' => '2 ore'),
                    array('type' => 'Quartiere', 'name' => 'Belgisches Viertel', 'area' => 'Innenstadt', 'note' => 'Caffè, concept store e bar: il lato più facile per capire la Colonia contemporanea.', 'time' => 'Pomeriggio'),
                    array('type' => 'Food', 'name' => 'Brauhaus tour', 'area' => 'Altstadt', 'note' => 'Birrerie tradizionali e Kölsch servita veloce, da vivere con spirito leggero.', 'time' => 'Sera'),
                    array('type' => 'Unico', 'name' => 'Odonien', 'area' => 'Ehrenfeld', 'note' => 'Spazio artistico e industriale con eventi, installazioni e atmosfera alternativa.', 'time' => 'Evento'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Duomo e Museum Ludwig', 'detail' => 'Classico e contemporaneo in pochi passi, senza perdere tempo negli spostamenti.'),
                    array('step' => 'Pomeriggio', 'title' => 'Belgisches Viertel', 'detail' => 'Shopping indipendente, caffè e pausa lenta nelle piazze del quartiere.'),
                    array('step' => 'Sera', 'title' => 'Kölsch o Ehrenfeld', 'detail' => 'Birreria tradizionale in centro o serata più alternativa verso Odonien.'),
                ),
            ),
            array(
                'slug' => 'frankfurt',
                'name' => 'Frankfurt',
                'italianName' => 'Francoforte',
                'aliases' => array('Francoforte', 'Frankfurt am Main'),
                'region' => 'Skyline e musei',
                'headline' => 'Grattacieli, sidro, mercati coperti e musei sul Meno.',
                'summary' => 'Francoforte è più interessante quando la tratti come città di contrasti: finanza, rive verdi, food hall e quartieri molto diversi.',
                'bestFor' => 'Musei, skyline, food scene internazionale',
                'duration' => '1-2 giorni',
                'accent' => '#ea580c',
                'neighborhoods' => array('Innenstadt', 'Sachsenhausen', 'Bahnhofsviertel', 'Westend'),
                'spots' => array(
                    array('type' => 'Vista', 'name' => 'Main Tower', 'area' => 'Innenstadt', 'note' => 'La terrazza più semplice per leggere lo skyline e il fiume dall’alto.', 'time' => '45 min'),
                    array('type' => 'Food', 'name' => 'Kleinmarkthalle', 'area' => 'Innenstadt', 'note' => 'Mercato coperto con banchi storici, snack e prodotti da assaggiare al volo.', 'time' => 'Pranzo'),
                    array('type' => 'Musei', 'name' => 'Museumsufer', 'area' => 'Sachsenhausen', 'note' => 'Una sponda intera dedicata ai musei, perfetta se vuoi scegliere in base all’umore.', 'time' => 'Mezza giornata'),
                    array('type' => 'Quartiere', 'name' => 'Bahnhofsviertel', 'area' => 'Centro', 'note' => 'Cucine internazionali, bar e una scena urbana più ruvida e dinamica.', 'time' => 'Sera'),
                    array('type' => 'Locale', 'name' => 'Apfelweinwirtschaft', 'area' => 'Sachsenhausen', 'note' => 'Una cena tradizionale con sidro locale per chiudere in modo molto frankfurtese.', 'time' => 'Cena'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Centro e mercato', 'detail' => 'Passeggiata tra Römerberg e Kleinmarkthalle con assaggi veloci.'),
                    array('step' => 'Pomeriggio', 'title' => 'Museumsufer', 'detail' => 'Scegli un museo e cammina lungo il Meno al tramonto.'),
                    array('step' => 'Sera', 'title' => 'Sachsenhausen', 'detail' => 'Cena con Apfelwein o deviazione più urbana nel Bahnhofsviertel.'),
                ),
            ),
            array(
                'slug' => 'dresden',
                'name' => 'Dresden',
                'italianName' => 'Dresda',
                'aliases' => array('Dresda'),
                'region' => 'Barocco e Neustadt',
                'headline' => 'Palazzi scenografici, cortili artistici e serate alternative.',
                'summary' => 'Dresda unisce monumentalità e quartieri creativi: il bello è passare dal centro barocco alla Neustadt senza trattarle come due viaggi separati.',
                'bestFor' => 'Architettura, gallerie, weekend romantici ma non ovvi',
                'duration' => '2 giorni',
                'accent' => '#7c3aed',
                'neighborhoods' => array('Altstadt', 'Innere Neustadt', 'Äußere Neustadt', 'Elbufer'),
                'spots' => array(
                    array('type' => 'Icona', 'name' => 'Zwinger', 'area' => 'Altstadt', 'note' => 'Architettura barocca teatrale, perfetta per aprire la giornata nel centro storico.', 'time' => '90 min'),
                    array('type' => 'Cultura', 'name' => 'Albertinum', 'area' => 'Altstadt', 'note' => 'Arte moderna e contemporanea in un edificio elegante vicino all’Elba.', 'time' => '2 ore'),
                    array('type' => 'Unico', 'name' => 'Kunsthofpassage', 'area' => 'Äußere Neustadt', 'note' => 'Cortili colorati, installazioni e piccoli indirizzi indipendenti.', 'time' => '60 min'),
                    array('type' => 'Food', 'name' => 'Pfunds Molkerei', 'area' => 'Neustadt', 'note' => 'Una latteria storica con interni decorati e atmosfera molto particolare.', 'time' => '30 min'),
                    array('type' => 'Relax', 'name' => 'Elbufer', 'area' => 'Fiume Elba', 'note' => 'Rive ampie per una pausa lenta con vista sul profilo della città.', 'time' => 'Tramonto'),
                ),
                'route' => array(
                    array('step' => 'Mattina', 'title' => 'Altstadt scenografica', 'detail' => 'Zwinger, Frauenkirche e passeggiata breve tra piazze monumentali.'),
                    array('step' => 'Pomeriggio', 'title' => 'Neustadt creativa', 'detail' => 'Kunsthofpassage, caffè e piccole gallerie senza percorso rigido.'),
                    array('step' => 'Sera', 'title' => 'Elba al tramonto', 'detail' => 'Rientro lungo il fiume e cena informale nella Neustadt.'),
                ),
            ),
        );
    }
}
