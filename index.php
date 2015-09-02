<?php
    // Variables
    $errorMsg = "";
    $alertMsg = "";
    $isBeingTracked = false;
    $trackingCookies = ["TM_user-id","x-acr","x-amobee-1","x-uidh","x-vf-acr","x-up-subno","x-msisdn","x-nokia-msisdn","x-piper-id"];
    $msgClass = "";
    $msgImg = "";
    $msgText = "";   
    $hostname = "";
    $isp = "";
    $city = "";
    $country = "";
    $ua = "";
    $ip = "";

    // =============
    // = Functions =
    // =============
    
    function is_an_ip($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return true;
        } else if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return true;
        }
        else {
            return false;
        }
    }
    
    function ip_details($ip) {
        
        if (is_an_ip($ip)){     
            $c = curl_init("http://ipinfo.io/$ip");
            curl_setopt($c,CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($c, CURLOPT_TIMEOUT, 30);
            $resp = curl_exec($c);
            $code = curl_getinfo($c,CURLINFO_HTTP_CODE);
            $cinfo = curl_getinfo($c);
            curl_close($c);

            if ($code == 200){      
                $details = json_decode($resp);  
                return $details;    
            }
            else {
                return null;
            }   
        }
        else {
            return null;
        }       
    }
    
    // ========
    // = MAIN = 
    // ========

    // Opbener IP del request
    $ip = $_SERVER['REMOTE_ADDR'];
    // Obtener User Agent
    $ua = $_SERVER['HTTP_USER_AGENT'];
    // Obtener array de cabeceras
    $headers = apache_request_headers();
    
    // Recolecta el operador de la ip recibida  
    $details = ip_details($ip);
    if ($details == null){
        $errorMsg = "Calling to http://ipinfo.io/$ip was not possible. Do not inject shit to the request :-(";
    }
    else {
        $hostname = $details->hostname;
        $city = $details->city;
        $country = $details->country;
        $isp = $details->org;
    }
?>
<html>
    <head>
        <title>Supercookies: Estoy siendo espiado por mi ISP?</title>
        <link rel="stylesheet" href="common.css">
        <script type="text/javascript">
        var googleSaysEnable = false;

        function emptyField(textfield){
            if (textfield.value == "Anonymous"){
                textfield.value = "";
            }
        }

        function fillField(textfield){
            if (textfield.value == ""){
                textfield.valude = "Anonymous";
            }
        }

        function captchaEnableSubmit(){
            googleSaysEnable = true;
            var checkbox = document.getElementById('acceptCheckbox');
            if (checkbox.checked == true) {
                 document.getElementById('submitbutton').disabled = false;  
            }
            else {
                document.getElementById('submitbutton').disabled = "disabled";
            }
        }

        function checkboxAccept(checkbox){
            // if (checkbox.checked == true && googleSaysEnable == true) {
            if (checkbox.checked == true) {
                document.getElementById('submitbutton').disabled = false;  
            }
            else {
                document.getElementById('submitbutton').disabled = "disabled";
            }
        }
        </script>
        <script src='https://www.google.com/recaptcha/api.js'></script>
    </head>
    <body>
        <h1>Comprueba si tu ISP espia tu línea móvil con Supercookies</h1>
        <br/>
        <a href="queesesto.html"/>¿De qué co*$&%es me estas hablando?</a>   
        <br/>
        <br/>
        <?php
        if ($errorMsg != null){
            print "<h3>".$errorMsg."</h3>";
        }
        else {
            // Imprimimos tabla con los resultados y boton de guardar resultados
?>
        <table id="ipinfoTable" name="ipinfoTable" class="Details">
            <tr>
                <th colspan=2>Connection Details</td>
            </tr>
            <tr>
                <td class="normalconn">IP</td>
                <td><?=$ip?></td>
            </tr>
            <tr>
                <td class="normalconn">Hostname</td>
                <td><?=$hostname?></td>
            </tr>
            <tr>
                <td class="normalconn">User Agent</td>
                <td><?=$ua?></td>
            </tr>
            <tr>
                <td class="normalconn">City</td>
                <td><?=$city?></td>
            </tr>
            <tr>
                <td class="normalconn">Country</td>
                <td><?=$country?></td>
            </tr>
            <tr>
                <td class="normalconn">ISP</td>
                <td><?=$isp?></td>
            </tr>

        </table>

        <br/>
        <br/> 
        <table id="headersTable" name="headersTable" class="Details">
            <tr>
                <th>Header</th>
                <th>Value</th>
            </tr>
            <?php
            foreach ($headers as $header => $value) {
                $tdclass = "normalheader";
                if (in_array($header,$trackingCookies)){
                    $tdclass = "alertheader";
                    $alertMsg = "Parece que sí estás siendo espiado por tu ISP ".$isp." a traves de la cabecera '".$header."'! :-(";
                    $isBeingTracked = true;
                }
            ?>
            <tr>
                <td class="<?=$tdclass?>"><?=$header?></td>
                <td><?=$value?></td>
            </tr>
            <?php
            }
            ?>
            
        </table>
        <?php
        }
?>
    <?php 
    if ($isBeingTracked == true){
        $msgClass = "alertMsg";
        $msgImg = "sad.png";
        $msgText = $alertMsg;
    }
    else {
        $msgClass = "noalertMsg";
        $msgImg = "happy.png";
        $msgText = "Enhorabuena, parece que '".$isp."' no te está espiando :-)";
    }
    ?>
    <div class="testResult">
        <table style="border: 0px;" class="resultTable">
        <tr>
        <td><img src=<?=$msgImg?> alt="result face"/></td>
        <td class=<?=$msgClass?>><?=$msgText?></td>
        </tr>
        </table>
    </div>
        <br/>
        <br/>
        <form id="saveTrackingData" name="saveTrackingData" action="storedata.php" method="POST">
            <table class="submitTable" id="submitTable" name="submitTable">
                <tr>
                <td>Alias [Opcional]</td>
                <td><input type="text" name="alias" id="alias" value="Anonymous" onclick="emptyField(this)" onfocusout="fillField(this)"/></td>
                </td>
                <tr>
                <td>Comentarios [Opcional]</td>
                <td><textarea cols=50 rows=5 name="comments"></textarea></td>
                </tr> 
                <?php
                if (isset($_GET['captchaNotSolved']) && intval($_GET['captchaNotSolved']) > 0){
                ?>
                <tr id="tr_nocaptcha">
                    <td>&nbsp;</td>
                    <td class="nocaptcha">Por favor, resuelve el captcha antes de enviar los resultados</td>
                </tr>
                <?php
                }
                ?>
                <tr>
                    <td>&nbsp;</td>
<!--                <td><div class="g-recaptcha" data-sitekey="6LfZHAwTAAAAALVrqEub_qzxol2MCZyoCeny4PPk" data-callback="captchaEnableSubmit"></div></td> -->
                    <td><div class="g-recaptcha" data-sitekey="6LfZHAwTAAAAALVrqEub_qzxol2MCZyoCeny4PPk" ></div></td>
                </tr>
                <input type="hidden" name="ip" value="<?=$ip?>"/>
                <input type="hidden" name="ua" value="<?=$ua?>"/>
                <input type="hidden" name="hostname" value="<?=$hostname?>"/>
                <input type="hidden" name="city" value="<?=$city?>"/>
                <input type="hidden" name="country" value="<?=$country?>"/>
                <input type="hidden" name="isp" value="<?=$isp?>"/>
                <input type="hidden" name="tracked" value="<?=intval($isBeingTracked)?>"/>
                <input type="hidden" name="headers" value='<?=json_encode($headers)?>'/>
                <tr>
                    <td colspan=2>&nbsp;</td>
                <tr>
                <tr>
                <td align=right><input type="checkbox" name="acceptCheckbox" id="acceptCheckbox" onchange="checkboxAccept(this)"/></td>
                <td>Acepto que la información presentada anteriormente se almacene en un fichero para ayudar a la lucha contra las "Supercookies" (<a href="https://www.accessnow.org/page/-/AIBT-Report.pdf">más información</a>)</td>
                </tr>
                <tr>
                <td align="center" colspan=2><input disabled="disabled" id="submitbutton" type="submit" value="Almacenar la informacion de seguimiento"/></td>
                </tr>
            </table>
       </form>
    </body>
</html>
