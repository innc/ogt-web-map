<?php


namespace App\Http\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Query Wikidata.
 *
 * @package App\Http\Clients
 */
class WikidataClient
{
    /**
     * Groups of Wikidata Q-Ids of place instances.
     */
    const PLACE_GROUPS_IDS = [
        'events'                  => [],
        'extPolicePrisons'        => [
            'Q108047650',   // https://www.wikidata.org/wiki/Q108047650     Extended police prison
            'Q108048094',   // https://www.wikidata.org/wiki/Q108048094     Police Detention Camp
        ],
        'fieldOffices'            => [
            'Q108047541',   // https://www.wikidata.org/wiki/Q108047541     Gestapo Field Office
            'Q108047989',   // https://www.wikidata.org/wiki/Q108047989     Outpost (State Police)
            'Q108047676',   // https://www.wikidata.org/wiki/Q108047676     Border Police Commissariat
            'Q108047833',   // https://www.wikidata.org/wiki/Q108047833     Border police station
            'Q108047775',   // https://www.wikidata.org/wiki/Q108047775     Branch office (border police)
        ],
        'laborEducationCamps'     => [
            'Q277565',      // https://www.wikidata.org/wiki/Q277565        labor education camp
        ],
        'memorials'               => [],
        'prisons'                 => [
            'Q40357',       // https://www.wikidata.org/wiki/Q40357         prison
        ],
        'statePoliceHeadquarters' => [
            'Q108047581',   // https://www.wikidata.org/wiki/Q108047581     State Police Headquarter
        ],
        'statePoliceOffices'      => [
            'Q108048310',   // https://www.wikidata.org/wiki/Q108048310     Branch office (state police)
            'Q2101520',     // https://www.wikidata.org/wiki/Q2101520       Political police (Germany)
            'Q108047567',   // https://www.wikidata.org/wiki/Q108047567     State Police Office
        ],
    ];

    /**
     * Properties of a queried Wikidata place.
     */
    const PLACE_PROPERTIES = [
        'item',
        'itemLabel',
        'itemDescription',
        'instanceUrls',
        'instanceLabels',
        'coordinates',
        'imageUrl',
        'source',
        'sourceAuthorLabels',
        'sourceLabel',
        'sourcePublisherCityLabel',
        'sourcePublisherLabel',
        'sourcePublicationYear',
        'sourcePages',
        'sourceDnbLink',
    ];

    /**
     * Wikidata properties of a queried locations and associated property labels.
     */
    const PROPERTY_LABEL_OF_ID = [
        'P18'   => 'images',                // https://www.wikidata.org/wiki/Property:P18
        'P31'   => 'instances',             // https://www.wikidata.org/wiki/Property:P31
        'P355'  => 'subsidiaries',          // https://www.wikidata.org/wiki/Property:P355
        'P571'  => 'inceptionDates',        // https://www.wikidata.org/wiki/Property:P571
        'P576'  => 'dissolvedDates',        // https://www.wikidata.org/wiki/Property:P576
        'P625'  => 'coordinates',           // https://www.wikidata.org/wiki/Property:P625
        'P749'  => 'parentOrganizations',   // https://www.wikidata.org/wiki/Property:P749
        'P793'  => 'significantEvents',     // https://www.wikidata.org/wiki/Property:P793
        'P1037' => 'directors',             // https://www.wikidata.org/wiki/Property:P1037
        'P1128' => 'employeeCounts',        // https://www.wikidata.org/wiki/Property:P1128
        'P1343' => 'describedBySources',    // https://www.wikidata.org/wiki/Property:P1343
        'P1365' => 'replaces',              // https://www.wikidata.org/wiki/Property:P1365
        'P1366' => 'replacedBys',           // https://www.wikidata.org/wiki/Property:P1366
        'P5630' => 'prisonerCounts',        // https://www.wikidata.org/wiki/Property:P5630
        'P6375' => 'streetAddresses',       // https://www.wikidata.org/wiki/Property:P6375
    ];

    /**
     * Wikidata qualifiers of a queried locations and associated qualifier labels.
     */
    const QUALIFIER_LABEL_OF_ID = [
        'P304'  => 'pages',                 // https://www.wikidata.org/wiki/Property:P304
        'P580'  => 'startTime',             // https://www.wikidata.org/wiki/Property:P580
        'P582'  => 'endTime',               // https://www.wikidata.org/wiki/Property:P582
        'P585'  => 'pointInTime',           // https://www.wikidata.org/wiki/Property:P585
        'P625'  => 'coordinates',           // https://www.wikidata.org/wiki/Property:P625
        'P1319' => 'earliestDate',          // https://www.wikidata.org/wiki/Property:P1319
        'P1326' => 'latestDate',            // https://www.wikidata.org/wiki/Property:P1326
        'P1480' => 'sourcingCircumstances', // https://www.wikidata.org/wiki/Property:P1480
        'P2096' => 'mediaLegend',           // https://www.wikidata.org/wiki/Property:P2096
        'P6375' => 'streetAddress',         // https://www.wikidata.org/wiki/Property:P6375
        'P8554' => 'earliestEndDate',       // https://www.wikidata.org/wiki/Property:P8554
        'P8555' => 'latestStartDate',       // https://www.wikidata.org/wiki/Property:P8555
    ];

    /**
     * Get places of Gestapo terror from Wikidata.
     *
     * @return array
     */
    public function queryPlaces() : array
    {
        $query = '
            SELECT 
                ?item 
                ?itemLabel 
                ?itemDescription 
                ?property 
                ?statement 
                ?propertyValue 
                ?propertyValueLabel 
                ?propertyTimePrecision 
                ?qualifier 
                ?qualifierValue 
                ?qualifierValueLabel 
                ?qualifierTimePrecision 
            WHERE {
                ?item wdt:P31 wd:Q106996250.
                FILTER(EXISTS { ?item wdt:P625 ?coordinateLocation. })
                ?property wikibase:claim ?claim.
                ?item ?claim ?statement.
                {
                    ?property wikibase:propertyType ?propertyType.
                    FILTER(?property IN(
                        wd:P18, wd:P31, wd:P355, wd:P625, wd:P749, wd:P793, wd:P1037, wd:P1128, wd:P1343, wd:P1365, 
                        wd:P1366, wd:P5630, wd:P6375
                    ))
                    FILTER(?propertyType != wikibase:Time)
                    ?property wikibase:statementProperty ?ps.
                    ?statement ?ps ?propertyValue.
                }
                UNION
                {
                    ?property wikibase:statementValue ?psv.
                    FILTER(?property IN(wd:P571, wd:P576))
                    ?statement ?psv ?propertyValueNode.
                    ?propertyValueNode wikibase:timeValue ?propertyValue;
                        wikibase:timePrecision ?propertyTimePrecision.
                }
                OPTIONAL {
                    {
                        ?qualifier wikibase:propertyType ?qualifierType.
                        FILTER(?qualifier IN(wd:P304, wd:P625, wd:P1480, wd:P2096, wd:P6375))
                        FILTER(?qualifierType != wikibase:Time)
                        ?qualifier wikibase:qualifier ?pq.      
                        ?statement ?pq ?qualifierValue.
                    }
                    UNION
                    {
                        ?qualifier wikibase:qualifierValue ?pqv.
                        FILTER(?qualifier IN(wd:P580, wd:P582, wd:P585, wd:P1319, wd:P1326, wd:P8554, wd:P8555))
                        ?statement ?pqv ?qualifierValueNode.
                        ?qualifierValueNode wikibase:timeValue ?qualifierValue;
                            wikibase:timePrecision ?qualifierTimePrecision.
                    }
                }
                SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
            }
            ORDER BY (?item) (?property) (?statement)';

        return $this->requestWikidata($query);
    }

    /**
     * Send request to Wikidata.
     *
     * @param string $query
     * @return array
     */
    public function requestWikidata(string $query) : array
    {
        $query = preg_replace('/\s\s+/', ' ', trim($query));

        $response = Http::accept('application/sparql-results+json')
            ->get(config('wikidata.url'), ['query' => $query,]);

        if ($response->ok()) {
            return $response->json();
        }
        else {
            $exceptionLog = [];

            if (! is_null($response->toException())) {
                $exceptionLog = [
                    'message' => $response->toException()->getMessage(),
                    'file'    => $response->toException()->getFile(),
                    'line'    => $response->toException()->getLine(),
                    'trace'   => $response->toException()->getTraceAsString(),
                ];
            }

            Log::error(
                'Wikidata request failed.',
                [
                    'status' => $response->status(),
                    'query'  => $query,
                ] + $exceptionLog
            );

            return [];
        }
    }

    /**
     * Filter Wikidata place data and group places by
     * - Events
     * - Extended police prisons / Labor education camps
     * - Field Offices
     * - Prisons
     * - State Police Headquarters
     * - State Police Offices
     *
     * @param array $places
     * @return array
     */
    public function groupFilteredPlacesByType(array $places) : array
    {
        $groupedPlaces = array_fill_keys(array_keys(self::PLACE_GROUPS_IDS), []);

        foreach ($places as $place) {
            $updatedPlace = $this->convertPlaceData($this->filterPlaceData($place));

            $instanceUrls = $updatedPlace['instanceUrls']['value'];
            $instanceQIds = str_replace('http://www.wikidata.org/entity/', '', $instanceUrls);
            $instanceQIdsArray = explode('|', $instanceQIds);

            $foundGroupForPlace = false;

            foreach ($groupedPlaces as $groupedPlaceName => $groupedPlace) {
                if (count(array_intersect($instanceQIdsArray, self::PLACE_GROUPS_IDS[$groupedPlaceName])) > 0) {
                    $groupedPlaces[$groupedPlaceName][] = $updatedPlace;
                    $foundGroupForPlace = true;
                }
            }

            if (! $foundGroupForPlace) {
                Log::warning(
                    'The location cannot be assigned to a map marker category based on its Wikidata instances.',
                    [
                        'instanceQIds' => $instanceUrls,
                        'placeQId'     => $updatedPlace['item']['value'],
                    ]
                );
            }
        }

        return $groupedPlaces;
    }

    /**
     * Filtering of the required place data.
     *
     * @param array $place
     * @return array
     */
    private function filterPlaceData(array $place) : array
    {
        foreach ($place as $key => $placeData) {
            $place[$key] = Arr::only($placeData, 'value');
        }

        return $place;
    }

    /**
     * Conversion to the appropriate format for further processing.
     *
     * @param array $place
     * @return array
     */
    private function convertPlaceData(array $place) : array
    {
        /* Example for location data with multiple coordinates
           ... from Wikidata ...
           [
                'type' => 'literal',
                'value' => '52.3667941,9.7448449240635|52.3642957,9.7473133',
           ]
           ... for Leaflet convert to ...
           [
                [lat => 52.3667941, lng => 9.7448449240635], [lat => 52.3642957, lng => 9.7473133],
           ]
        */
        $coordinatesArray = explode('|', $place['coordinates']['value']);

        foreach ($coordinatesArray as &$coordinate) {
            $latLng = explode(',', $coordinate);

            $coordinate = [
                'lat' => $latLng[0],
                'lng' => $latLng[1],
            ];
        }

        $place['coordinates'] = $coordinatesArray;

        return $place;
    }

    /**
     * Merge item data from Wikidata query response.
     *
     * @param array $queryData
     * @return array
     */
    public function mergeItemsData(array $queryData) : array
    {
        return [];
    }

    /**
     * Group locations by type.
     *
     * @param array $locations
     * @return array
     */
    public function groupLocationsByType(array $locations) : array
    {
        return [];
    }
}
