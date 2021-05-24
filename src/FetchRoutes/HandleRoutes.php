<?php

namespace Drupal\mtba_routes\FetchRoutes;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Render\Markup;

class HandleRoutes
{

  /**
   * @var $client \GuzzleHttp\Client
   */
    protected $client;

  /**
   * HandleRoutes  constructor.
   *
   * @param $http_client_factory \Drupal\Core\Http\ClientFactory
   */
    public function __construct(ClientFactory $http_client_factory)
    {
        $this->client = $http_client_factory->fromOptions([
          'base_uri' => 'https://api-v3.mbta.com/',
        ]);
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

        $response = $this->client->get('schedules', [
        'query' => [
          'filter[route]' => $id
        ]
        ]);

        $data = Json::decode($response->getBody());

        $scheduleTimesArrival = [];

        $scheduleTimesDeparture = [];

        $header = ['Arrival(green) | Departure Times(red) | Arrival = Departure(orange)'];
        $schedules= $data['data'];

        foreach ($schedules as $schedule) {
            if (!is_null($schedule['attributes']['departure_time']) &&
                !is_null($schedule['attributes']['arrival_time'])) {
                $date = date_create($schedule['attributes']['departure_time']);
                $date = date_format($date, "Y/m/d H:i A");
                $scheduleTimesDeparture[] = array_map(array($this, 'transformScheduleData'), (array)
                $schedule['attributes']['arrival_time'], (array) $date);
            }

            if (!is_null($schedule['attributes']['arrival_time']) &&
              is_null($schedule['attributes']['departure_time'])) {
                $date = date_create($schedule['attributes']['arrival_time']);
                $date = date_format($date, "Y/m/d H:i A");
                $scheduleTimesArrival[] = array_map(array($this, 'transformScheduleData'), (array) $date, (array)
                $schedule['attributes']['departure_time']);
            }

            if (!is_null($schedule['attributes']['departure_time']) &&
              is_null($schedule['attributes']['arrival_time'])) {
                $date = date_create($schedule['attributes']['departure_time']);
                $date = date_format($date, "Y/m/d H:i A");

                $scheduleTimesDeparture[] = array_map(array($this, 'transformScheduleData'), (array)
                $schedule['attributes']['arrival_time'], (array) $date);
            }
        }

        return [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => array_merge($scheduleTimesArrival, $scheduleTimesDeparture),
        '#empty' => t('Sorry for the inconvenience, there are no exact times available,
        our feature for adding approximate times will be available in the future'),
          '#attached' => [
            'library' => [
              'mtba_routes/mtba-routes',
            ]
          ]
        ];
    }
}
