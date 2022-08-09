<!DOCTYPE html>
<html>
    <body>
 
        <h1>Regex test</h1>
        
        <?php

        $dateFormat = 'Y-m-d\TH:i:s\Z';

        $href = 'TRANSMIT.FPCN71.07.18.0900Z.xml';

        $now = new DateTime();

        #preg_match('((?:[0-9]+,)*[0-9]+(?:\.[0-9]+)?)', $href, $matche);

        #preg_match("/(^([0-9])(Z\.)$)+/", $href, $matche1); ################### preg match et non preg_match_all lul

        preg_match_all('!\d+!', $href, $matches);

        $urlDateStr = $matches[0][1] . ':' . $matches[0][2] . ':' . $matches[0][3];

        $creationDate = DateTime::createFromFormat('m:d:Hi', $urlDateStr);

        $diff = $creationDate->diff($now);

        $diffhours = $diff->h + 24*$diff->d;

        print_r($matches);

        echo '<br>' . $urlDateStr;

        echo '<br>' . $diffhours

        ?>
 
    </body>
</html>