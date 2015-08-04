<?php
require_once ("libs/vendor/autoload.php");

$conf = json_decode(file_get_contents('private/conf/conf.json'));

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim( array('debug' => true));

try {

    $conf = @json_decode(file_get_contents('private/conf/conf.json'));
    if ($conf == FALSE) throw new Exception("Manque le fichier de conf");
    
    $xml_reqs = @simplexml_load_file('private/conf/layers.xml');
    if ($xml_reqs == FALSE) throw new Exception('Erreur au chargement du fichier XML');
    
    $dbh = new PDO(
        // "pgsql:dbname=rcsi;host=localhost",
        sprintf("%s:dbname=%s;host=%s", $conf->db->driver, $conf->db->dbname, $conf->db->host), 
        $conf->db->login, 
        $conf->db->pwd
    );
    
    // Lever des exception par défaut
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ajouter la conf au registre SLIM
    $env = $app -> environment();
     
    $env['db_conn'] = $dbh;
    $env['reqs'] = $xml_reqs;

    $app -> get('/', function() use ($app) {
        $app -> response() -> header('Content-Type', 'application/json');
        echo json_encode(array('message' => 'Bienvenue dans l\'api georef'));
    });

    $app -> get('/test/accessApp', function() use ($app) {
        $app -> response() -> header('Content-Type', 'application/json');
        echo json_encode(array('message' => 'ok'));
    });
    
    $app -> get('/test/accessBdd', function() use ($app) {
        $env = $app->environment();    
        $dbh = $env['db_conn'];
        $query = $dbh->query(
            "SELECT table_name FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_name NOT IN 
                ('geography_columns', 'geometry_columns', 'spatial_ref_sys', 'raster_columns');", PDO::FETCH_OBJ);
        $oResult = $query->fetchAll(PDO::FETCH_OBJ);
        
        $app -> response() -> header('Content-Type', 'application/json');
        echo json_encode(array('message' => 'Base de données ok', 'tables' => $oResult));
    });
    
    // Couches dispos dans le fichier xml
    $app -> get('/layersAvailable', function() use ($app) {
        
        $env = $app->environment();
        $layersAvailable = array();
        foreach ($env['reqs'] as $req) {
            $layersAvailable[] = array((string) $req['name'], (string) $req['typeElement']);
        }
        
        $app -> response() -> header('Content-Type', 'application/json');
        echo json_encode($layersAvailable); 
    }); 
    
    // Créer les points de terminaison API   
    foreach ($xml_reqs as $req) {
        $endPoint = sprintf('/layers/%s',$req['name']);
        $app -> get($endPoint, function() use ($app, $req) {
            $httpReq = $app->request;
            
            $env = $app->environment();
            $dbh = $env['db_conn'];

            $lon = floatval($app->request->params("x"));
            $lat = floatval($app->request->params("y"));
    
            if(validWKT($lon) == false || validWKT($lat) == false) throw new Exception("Format des points incorrect", 1);

            $query = $dbh->query(bindParams((string) $req->sql, $lat, $lon), PDO::FETCH_OBJ);
            
            $oResult = $query->fetch(PDO::FETCH_OBJ);
            
            if($oResult == FALSE) throw new Exception(sprintf("Aucun résultat pour le point [x=%s y=%s]",$lon,$lat), 1);
            
            $app -> response() -> header('Content-Type', 'application/json');
            
            echo json_encode($oResult);
        }); 
    }

    /** @todo : tout layers d'un coup ? */

    $app -> run();

} catch(Exception $ex) {
    echo json_encode(array('result' => false, 'message' => $ex -> getMessage()));
}

/**
 * 
 */
function bindParams($sql, $lat, $lon) {
    $sql = str_replace('%lon%', $lon, $sql);
    $sql = str_replace('%lat%', $lat, $sql);

    return $sql;
}

function validWKT($wkt) {
    $pattern = '/-?\d+\.\d*/';
    
    preg_match($pattern, $wkt, $matches);
    if(count($matches) == 1) return true;
    else return false;
}
