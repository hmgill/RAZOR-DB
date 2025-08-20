<?php


namespace razor;
use \PDO;


class PairSearch {

  private $db;

  function __construct() {
    require_once __DIR__ . "/db.php";
    $con = new DB();
    $this->db = $con->connect();
  }

  // use stored protocols saved to schema to execute queries

  function get_pairs($query_accession) {

    // search for gene names that match input prefix
    $sql = "call GetPairIDs(:query_accession)";
    $stmt = $this->db->prepare($sql);

    $stmt->bindValue(":query_accession", $query_accession, PDO::PARAM_STR);

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res);
    $stmt = null;
  }





}





// search
$q = $_GET["q"];
$gls = new PairSearch();
$gls->get_pairs($q);

?>

