<?php

namespace Drupal\mtba_routes\FetchRoutes;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Datetime\DrupalDateTime;

class HandleRoutes
{

  /**
   * @var $client \GuzzleHttp\Client
   */
    protected $client;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
    private $keyValueFactory;

  /**
   * HandleRoutes  constructor.
   *
   * @param $http_client_factory \Drupal\Core\Http\ClientFactory
   * @param  $keyValueFactory KeyValueFactoryInterface
   */
    public function __construct(ClientFactory $http_client_factory, KeyValueFactoryInterface $keyValueFactory)
    {
        $this->client = $http_client_factory->fromOptions([
          'base_uri' => 'https://api-v3.mbta.com/',
        ]);
        $this->keyValueFactory = $keyValueFactory;
    }

    public function transformData($routeInfoData, $routeInfoColor, $routeID)
    {
        $style = 'background:#'.$routeInfoColor.';color: white';
        return ['data' => Markup::create("<a href=/routes/{$routeID}>{$routeInfoData}</a>"),
          'style' => $style, 'type' => 'link'];
    }

    public function transformScheduleData($scheduleStartTime, $scheduleEndTime)
    {
        if (!is_null($scheduleStartTime) && is_null($scheduleEndTime)) {
            return ['data' => $scheduleStartTime, 'style' => "background: green;"];
        }

        if (!is_null($scheduleEndTime) &&  is_null($scheduleStartTime)) {
            return ['data' =>  $scheduleEndTime, 'style' => "background: red;"];
        }
        if (!is_null($scheduleEndTime) &&  !is_null($scheduleStartTime)) {
            return ['data' => $scheduleEndTime, 'style' => "background: orange;"];
        }
    }

    public function fetchAllRoutes()
    {
        $response = $this->client->get('routes');
        $dataArray = Json::decode($response->getBody());
        $titleStyling = 'background: white; font-weight: 900; text-align: center !important;
        padding-bottom: 2%; font-size:26px;';

        $rapidTransit = [];
        $rapidTransitTitle = [
        "data" => ['Rapid Transit'],
        "style" => $titleStyling
        ];

        $localBus = [];
        $localBusTitle = [
        "data" => ['Local Bus'],
        "style" => $titleStyling
        ];

        $commuterRail = [];
        $commuterRailTitle = [
        "data" => ['Commuter Rail'],
        "style" => $titleStyling
        ];

        $ferry = [];
        $ferryTitle = [
        "data" => ['Ferry'],
        "style" => $titleStyling
        ];

        $innerExpress = [];
        $innerExpressTitle = [
        "data" => ['Inner Express'],
        "style" => $titleStyling
        ];

        $outerExpress = [];
        $outerExpressTitle = [
        "data" => ['Outer Express'],
        "style" => $titleStyling
        ];

        $datasIterate = $dataArray['data'];

        foreach ($datasIterate as $data) {
            if ($data["attributes"]["fare_class"] === "Rapid Transit") {
                $rapidTransit[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);
            }

            if ($data["attributes"]["fare_class"] === "Local Bus") {
                $localBus[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);
            }

            if ($data["attributes"]["fare_class"] === "Commuter Rail") {
                $commuterRail[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);
            }

            if ($data["attributes"]["fare_class"] === "Ferry") {
                $ferry[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);
            }

            if ($data["attributes"]["fare_class"] === "Inner Express") {
                $innerExpress[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);

            }

            if ($data["attributes"]["fare_class"] === "Outer Express") {
                $outerExpress[] = array_map(array($this, 'transformData'), (array)
                $data["attributes"]['long_name'], (array)$data["attributes"]["color"], (array)$data["id"]);
            }
        }

        return [
        '#type' => 'table',
        '#rows' => array_merge(
            [$rapidTransitTitle],
            $rapidTransit,
            [$localBusTitle],
            $localBus,
            [$commuterRailTitle],
            $commuterRail,
            [$ferryTitle],
            $ferry,
            [$innerExpressTitle],
            $innerExpress,
            [$outerExpressTitle],
            $outerExpress
        ),
        '#empty' => t('Route Data Unavailable, please try again later'),
          '#attached' => [
            'library' => [
              'mtba_routes/mtba-routes',
            ]
          ]
        ];
    }


    public function fetchSchedules($id)
    {
        $key = 'schedule_'.$id;
        $store = $this->keyValueFactory->get('schedule');

        if ($store->has($key)) {
            return $store->get($key);
        }

        $response = $this->client->get('schedules', [
        'query' => [
          'filter[route]' => $id
        ]
        ]);

        $data = Json::decode($response->getBody());

        $times = [];

        $header = ['Arrival(green) | Departure Times(red) | Arrival = Departure(orange)'];
        $schedules= $data['data'];

        foreach ($schedules as $schedule) {
            if (!is_null($schedule['attributes']['departure_time']) &&
                !is_null($schedule['attributes']['arrival_time'])) {
                  $date = new DrupalDateTime($schedule['attributes']['arrival_time'], 'UTC');
                  $formatted_date = \Drupal::service('date.formatter')->format(
                      $date->getTimestamp(),
                      'custom',
                      'F j, Y h:ia'
                  );
                $times[] = array_map([
                  $this,
                  'transformScheduleData'
                ], (array)
                $schedule['attributes']['arrival_time'], (array) $formatted_date);
            }

            if (!is_null($schedule['attributes']['arrival_time']) &&
              is_null($schedule['attributes']['departure_time'])) {
                $date = new DrupalDateTime($schedule['attributes']['arrival_time'], 'UTC');
                $formatted_date = \Drupal::service('date.formatter')->format(
                    $date->getTimestamp(),
                    'custom',
                    'F j, Y h:ia'
                );

                $times[] = array_map(array($this, 'transformScheduleData'), (array)
                $formatted_date, (array) $schedule['attributes']['departure_time']);
            }

            if (!is_null($schedule['attributes']['departure_time']) &&
              is_null($schedule['attributes']['arrival_time'])) {
                $date = new DrupalDateTime($schedule['attributes']['departure_time'], 'UTC');
                $formatted_date = \Drupal::service('date.formatter')->format(
                    $date->getTimestamp(),
                    'custom',
                    'F j, Y h:ia'
                );

                $times[] = array_map(array($this, 'transformScheduleData'), (array)
                $schedule['attributes']['arrival_time'], (array) $formatted_date);
            }
        }

        $result = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $times,
        '#empty' => t('Sorry for the inconvenience, there are no exact times available,
        our feature for adding approximate times will be available in the future'),
          '#attached' => [
            'library' => [
              'mtba_routes/mtba-routes',
            ]
          ]
        ];

        $store->set($key, $result);
        return $result;
    }
}
