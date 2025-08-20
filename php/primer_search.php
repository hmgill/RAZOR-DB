<?php


namespace razor;
use \PDO;


class VirusPrimerSearch {

  private $db;

  function __construct() {
    require_once __DIR__ . "/db.php";
    $con = new DB();
    $this->db = $con->connect();
  }

  // use stored protocols saved to schema to execute queries

  function get_virus_primers($virus_abbreviation) {

    // search for gene names that match input prefix
    $sql = "call get_all_virus_primers(:virus_abbreviation)";
    $stmt = $this->db->prepare($sql);

    $stmt->bindValue(":virus_abbreviation", $virus_abbreviation, PDO::PARAM_STR);

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res);
    $stmt = null;
  }





}





// search
$q = $_GET["q"];
$gls = new VirusPrimerSearch();
$gls->get_virus_primers($q);

?>


