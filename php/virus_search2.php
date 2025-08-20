<?php


namespace razor;
use \PDO;


class VirusNameSearch {

  private $db;

  function __construct() {
    require_once __DIR__ . "/db.php";
    $con = new DB();
    $this->db = $con->connect();
  }

  // use stored protocols saved to schema to execute queries

  function get_virus_names() {

    // search for gene names that match input prefix
    $sql = "call RetrieveVirusInfo()";
    $stmt = $this->db->prepare($sql);

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res);
    $stmt = null;
  }


}





// search
$gls = new VirusNameSearch();
$gls->get_virus_names();

?>
