<?php

namespace Drupal\mtba_routes\Controller;

use Drupal\mtba_routes\FetchRoutes\HandleRoutes;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RouteController.
 */
class RouteController extends ControllerBase
{

  /**
   * @var \Drupal\mtba_routes\FetchRoutes\HandleRoutes
   */
    protected $handleRoutesClient;

    public function __construct(HandleRoutes $handleRoutesClient)
    {
        $this->handleRoutesClient = $handleRoutesClient;
    }

    public function generateRoutes(): array
    {
        $routes = $this->handleRoutesClient->fetchAllRoutes();
        return $routes;
    }


    public function generateRouteSchedules($routeIdentifier)
    {
        $pullSchedule = $this->handleRoutesClient->fetchSchedules($routeIdentifier);
        return $pullSchedule;
    }

    public static function create(ContainerInterface $container)
    {
        return new static($container->get('handle_routes_client'));
    }
}
