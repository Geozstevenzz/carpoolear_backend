<?php

namespace STS\Services\Logic;

use STS\Repository\RoutesRepository as RoutesRep;
use DB;


class RoutesManager
{
    protected $routesRepo;

    public function __construct(RoutesRep $routesRepo){
        $this->routesRepo = $routesRepo;
    }
    
    public function autocomplete($name, $country, $multicountry) {
        return $this->routesRepo->autocomplete($name, $country, $multicountry);
    }

    public function createRoute ($route) {
        // $sourceNode, $destinyNode
        $sourceNode = $route->origin;
        $destinyNode = $route->destiny;
        // 1- Obenter todos los nodos geo dentro de la circunferencia
        $nodes = $this->routesRepo->getPotentialsNodes($sourceNode, $destinyNode);
        DB::disconnect('mysql');
        // var_dump($nodes);die;
        // 2- Calcular con OSM la ruta
        $url = "http://router.project-osrm.org/route/v1/driving/$sourceNode->lng,$sourceNode->lat;$destinyNode->lng,$destinyNode->lat?overview=false&alternatives=true&steps=true";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url); // CURLOPT_TIMEOUT	
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $result = curl_exec($ch);
        curl_close($ch);
        
        \Log::info($url);

        $result = json_decode($result, true);
        \Log::info('decode ok');
        // var_dump($result);die;
        foreach ($result['routes'][0]['legs'][0]['steps'] as $step) {
            foreach ($step['intersections'] as $i) {
                $points[] = $i['location'];
            }
        }
        // 3- Buscar los puntos obtenidos en 1 cercanos a los segumentos
        $nearPoints = [];
        \Log::info('count($points): ' . count($points));
        \Log::info('count($nodesArr): ' . count($nodes->toArray()));
        for ($i = 1; $i < count($points); $i++) { 
            $p1 = $points[$i - 1];
            $p2 = $points[$i];
            // [1] -> lat -> x
            // [0] -> lng -> y
            // m = (y2 - y1) / (x2 - x1)
            $m = 0;
            if (($p2[1] - $p1[1]) != 0) {
                $m = ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]);
            }
            // b = y - mx
            $b = $p2[0] - ($m * $p2[1]);
            // y = mx + b;
            $nodesArr = $nodes->toArray();
            for ($j = count($nodesArr) - 1; $j >= 0; $j--) {
                $node = (object)$nodesArr[$j];
                if (isset($nearPoints[$node->id])) {
                    break;
                }
                // | m * x1 - y1 + b | / sqr(pow(m, 2) + 1)
                $d = abs($m * $node->lat - $node->lng + $b) / sqrt(pow($m, 2) + 1);
                if ($d < 0.125) {
                    $d1 = sqrt(pow($p1[0] - $node->lng, 2) + pow ($p1[1] - $node->lat, 2));
                    $d2 = sqrt(pow($p2[0] - $node->lng, 02) + pow ($p2[1] - $node->lat, 2));
                    $dd = sqrt(pow($p2[0] - $p1[0], 02) + pow ($p2[1] - $p1[1], 2)) * 30;
                    $md = $d1 > $d2 ? $d2 : $d1;    
                    if (($md < 0.75 && $md < $dd)) {
                        if (!isset($nearPoints[$node->id])) {
                            $node->d = $d;
                            $nearPoints[$node->id] = $node;
                            break;
                        }
                    } else {
                        if ($md < 0.15 && $d < 0.0125) {
                            if (!isset($nearPoints[$node->id])) {
                                $node->d = $d;
                                $nearPoints[$node->id] = $node;
                                break;
                            }
                        }
                    }
                } else {
                    // 
                }

            }
        }
        DB::disconnect('mysql');
        // 4- Grabar
        \Log::info('Saving route');
        $this->routesRepo->saveRoute($route, $nearPoints);

    }
}
