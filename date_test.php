<!DOCTYPE html>
<html>
    <body>
 
        <h1>Datetime Test</h1>
        
        <?php

        $var_date_simple = '2022-05-28';

        $var_start = '2022-07-18T23:00:00Z';

        $var_end  = '2022-07-19T01:00:00Z';

        $format_simple = 'Y-m-d:H';

        $format_test = 'Y-m-d\TH:i:s\Z';

        $Jourformat = 'Y-m-d';

        $DateHeureFormat = 'Y-m-d\ H:i';

        $date_start = DateTime::createFromFormat($format_test, $var_start);

        $date_end = DateTime::createFromFormat($format_test, $var_end);

        $diff = $date_start->diff($date_end);

        $diffhours = $diff->h + 24*$diff->d;

        $i = (int) 0;

        while ($i <= $diffhours) {

            $newDate = clone $date_start;

            $newDate->modify('+'. $i .'hour');

            $currentDateStrg = $newDate->format($format_test);

            $newDate->modify('-4 hour');

            $currentDateJourFormat = $newDate->format($Jourformat);

            $currentDateHeureFormat = $newDate->format($DateHeureFormat);

            echo $currentDateStrg, ' -> ', $currentDateJourFormat,' -> ', $currentDateHeureFormat, '<br>';

            $i += 1;
        }

        echo $diffhours, ' hours';

        ?>
 
    </body>
</html>