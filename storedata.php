<?php
    $error = false;
    $trackingCookies = Array("TM_user-id","x-acr","x-amobee-1","x-uidh","x-vf-acr","x-up-subno","x-msisdn","x-nokia-msisdn","x-piper-id");

    function verifyReCaptcha(){
        $valid = false;
        if (isset($_POST['g-recaptcha-response'])){         
            $grr=$_POST['g-recaptcha-response'];
            $ip=$_SERVER['REMOTE_ADDR'];

            $fields = array (
                'secret' => '<blahblahblahblah>',
                'response' => $grr,
                'remoteip' => $ip
            );
            $fields_string = ""; 
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            $resp = curl_exec($ch);
            $cinfo = curl_getinfo($ch);
            curl_close($ch);

            $jresp = json_decode($resp);
            if ($jresp->success == "true"){
                $valid = true;
            }
        }
        return $valid;    
    }

    function openDatabase(){
        $servername = "blahblahbla";
        $username = "blahblahblah";
        $password = "blahblahblah";
        $dbname = "blahblahbla";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if (mysqli_connect_errno()) {
            die("Fallo en la conexion con DB");
            return null;
        } 
        else {
            return $conn;
        }
    }

    function insertTrackingData($connection){

        global $trackingCookies;
        $ip = $_POST['ip'];
        $ua = $_POST['ua'];
       $alias = $_POST['alias'];
       $comments = $_POST['comments'];
       $hostname = $_POST['hostname'];
       $city = $_POST['city'];
       $country = $_POST['country'];
       $isp = $_POST['isp'];
       $tracked = $_POST['tracked'];
       $headers = json_decode($_POST['headers']);
       $editedheaders = Array();

       foreach ($headers as $name => $value){
        if (in_array($name,$trackingCookies) == true){
            // Delete this identificable info
            $editedheaders[$name] = "<REMOVED>";
        }
        else {
            $editedheaders[$name] = $value;
        }
       }
       $jheaders = json_encode($editedheaders);
       if (strlen($jheaders) > 4000) {
        $jheaders = substr($jheaders,0,4000);
       }

       $insertres = false;
       //  create table  ispTrakingData(id integer primary key auto_increment,timestamp integer, ip varchar(46),ua varchar(100),alias varchar(25),comments varchar(180),hostname varchar(100),city varchar(50),country varchar(50),isp varchar(120),tracked int,headers text);

       if (strlen($comments)>180) {
        $comments = substr($comments,0,180);
       }
       if (strlen($isp)>120){
        $isp = substr($isp,0,120);
       }
       if (strlen($ua)>200){
        $ua = substr($ua,0,200);
       }
       if (strlen($hostname)>100) {
        $hostname = substr($hostname,0,100);
       }
       if (strlen($city) > 50){
        $city = substr($city,0,50);
       }
       if (strlen($country) > 50){
        $country = substr($country,0,50);
       }
       $nsec = time();
       $storesql = "INSERT INTO ispTrakingData(ip,ua,timestamp,alias,comments,hostname,city,country,isp,tracked,headers) VALUES (?,?,?,?,?,?,?,?,?,?,?)";

       if ($stmt = $connection->prepare($storesql)){
            $stmt->bind_param("ssissssssis",$ip,$ua,$nsec,$alias,$comments,$hostname,$city,$country,$isp,$tracked,$jheaders);
            if ($stmt->execute()){
                $insertres = true;
                $stmt->close();
            }
       }
       return $insertres;

    }

    function closeDatabase($connection){
        $connection->close();
    }

    // MAIN 
    $resultMessage = "";
    $verified = verifyReCaptcha();
    if ($verified) {
        // store in database
        $conn = openDatabase(); 
        if ($conn != null){
            $res = insertTrackingData($conn);
            closeDatabase($conn);
            if ($res){
                $resultMessage = "Gracias!";
                $resultClass = "noalertMsg";
            }
            else {
                $resultMessage = "Information was not stored";
                $resultClass = "noalertMsg";
            }
        }
    }
    else {
        $error = true;
        $errorMsg = "¿Ya estás tocando donde no debes?";
        header("Location: http://felmoltor.info/supercookies/?captchaNotSolved=1#tr_nocaptcha");
        die();
    }
?>
<html>
    <head><title>Store supercookies information</title></head>
    <link rel="stylesheet" href="common.css"/>
    <body>
        <?php
        if (!$error){
        ?>
        <div id="resultsDiv" class="noalertMsg">
            <?=$resultMessage?>
        </div>
        <div class="agradecimiento" class="agradecimiento">
        La información que has donado servirá para facilitar el estudio de qué operadoras y empresas proveedoras de internet estan inyectando en tu navegación cabeceras de seguimiento al usuario movil.<br/><br/>
        En caso de que en tu navegación se hayan encontrado cabeceras de seguimiento por parte de de tu ISP, esta información se ha eliminado antes de almacenarla en la base de datos, siendo sustituida por un texto genérico '&lt;REMOVED&gt;'.
        </div>
        <?php
        }
        else {
        ?>
        </div> <div id="errordiv" class="alertMsg">
            <?=$errorMsg?> 
        </div>
        <?php
        }
        ?>
        
    </body>
</html>
