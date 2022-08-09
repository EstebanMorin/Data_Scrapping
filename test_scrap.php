<!DOCTYPE html>
<html>
    <body>
 
        <h1>Data Scrapping</h1>
        
        <?php
        
        #commit test

        #Create an array with the url for each XML site


        $urlGlobal = 'http://hpfx.collab.science.gc.ca/20220718/WXO-DD/meteocode/que/cmml/';

        $html = file_get_contents($urlGlobal);

        $start = stripos($html, '<hr>');

        $end = stripos($html, '</hr>', $offset = $start);

        $length = $end - $start;

        $htmlSection = substr($html, $start, $length);

        preg_match_all('@<a href=(.+)</a>@', $htmlSection, $matches);

        $hrefList = array();

        $dirEnglish = ['north', 'northeast', 'northwest', 'south', 'southeast', 'southwest', 'easterly', 'west', 'nil'];

        $dirFrench = ['nord', 'nord-est', 'nord-ouest', 'sud', 'sud-est', 'sud-ouest', 'est', 'ouest', 'nul'];

        $dateFormat = 'Y-m-d\TH:i:s\Z';

        $Jourformat = 'Y-m-d';

        $DateHeureFormat = 'Y-m-d\ H:i';

        foreach (array_slice($matches[1], 1) as $match) {

            preg_match_all('@"(.*)"@', $match, $hrefMatch); #Take string between quotes for all href
            
            array_push($hrefList, $hrefMatch[1][0]);
            
            // echo $hrefMatch[1][0] . "<br>";
            
            // print_r($hrefMatch);
        }

        $urlFileList = array();

        $csvArray = [];

        $stationList = [];


        #Chose only the files that were updated in the last hour

        $now = new DateTime();

        //$now = DateTime::createFromFormat('m:d:H', '07:18:20'); ///////////////////////////////////// test

        foreach ($hrefList as $href) {

            preg_match_all('!\d+!', $href, $matches);

            $urlDateStr = $matches[0][1] . ':' . $matches[0][2] . ':' . $matches[0][3];

            $creationDate = DateTime::createFromFormat('m:d:Hi', $urlDateStr);
    
            $diff = $creationDate->diff($now);
    
            $diffhours = $diff->h + 24*$diff->d;

            if ($diff->i !== 0) {

                $diffhours += 1;

            }

            if ($diffhours <= 1) {
                       
                $urlFile = $urlGlobal . $href;

                echo $urlFile . '<br>';
                
                echo $diff->h . '<br>' . $diff->i . '<br>';

                array_push($urlFileList, $urlFile);

            }

        }

        if (count($urlFileList) == 0) {
        
            echo 'No updates needed';

            exit();

        }

        // print_r($urlFileList);


        #Open and search of information for each XML site

        foreach ($urlFileList as $url) {

            #sleep(1);

            $xmlStr = file_get_contents($url);#itération avec foreach à faire

            $cmml = new SimpleXMLElement($xmlStr);

            $csvHeader = array (
                ['DateHeureEC', 'Pfd', 'Neb', 'TypeEvent', 'Frequency', 'Intensity', 'Pop', 'Occurrence', 'TypPrecip', 'AccMax', 'TaMin', 'TdMin', 'Dir_eng', 'Vit', 'VitMax', 'Jour', 'Dir', 'DateHeure', 'U']
            );

            $emptyArray = [];

            foreach ($cmml->data->forecast->{'meteocode-forecast'} as $meteocodeForecast) { 
            # Search in balise an object = balise['start'], Go tot next child = balsie->child, For value go to child with nothing
            #{'meteocode-forecast'} for illegal caracter such as '-'

                #station id


                $mcsZoneCode = (string) $meteocodeForecast->location->{'msc-zone-code'}; #(String) to pass from xml element to int, string ...

                // echo $mcsZoneCode, '<br>';
                
                if (!array_key_exists($mcsZoneCode, $stationList)) {

                    $stationList[] = $mcsZoneCode;

                    $csvArray[$mcsZoneCode] = $csvHeader;

                }

                #Cloud cover  and ceiling code data

                $cloudCoverList = $meteocodeForecast->parameters->{'cloud-list'};
                
                foreach ($cloudCoverList->{'cloud-cover'} as $cloudCover) {

                    $cloudStart = (string) $cloudCover['start']; 

                    $cloudEnd = (string) $cloudCover['end'];
                    
                    foreach ($csvArray[$mcsZoneCode] as $index => $line) { #ne prend pas en compte l'ordre des heures

                        if (in_array($cloudStart, $line)) {

                            $csvArray[$mcsZoneCode][$index][2] = (string) $cloudCover;

                            if (!isset($cloudCover['ceiling-code'])) { #issset -> If is null gives False

                                $csvArray[$mcsZoneCode][$index][1] = (string) $cloudCover['ceiling-code'];

                            } 
                            
                            elseif ($index >= 0) {

                                $csvArray[$mcsZoneCode][$index][1] = 'N/A';

                            }

                            break;

                        } 
                        
                        elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                            array_push($csvArray[$mcsZoneCode], $emptyArray);

                            $csvArray[$mcsZoneCode][$index+1][0] = $cloudStart; 

                            $cloudDateStart = DateTime::createFromFormat($dateFormat, $cloudStart);

#******************************************************************************************************************
                            $cloudDateStart->modify('-4 hour'); # À modifier pour -4h ou -5h
#******************************************************************************************************************

                            $cloudDateJourFormat = $cloudDateStart->format($Jourformat);
                
                            $cloudDateHeureFormat = $cloudDateStart->format($DateHeureFormat);

                            $csvArray[$mcsZoneCode][$index+1][15] = $cloudDateJourFormat;

                            $csvArray[$mcsZoneCode][$index+1][17] = $cloudDateHeureFormat;

                            $csvArray[$mcsZoneCode][$index+1][2] = (string) $cloudCover;

                            if (!is_null($cloudCover['ceiling-code'])) {

                                $csvArray[$mcsZoneCode][$index+1][1] = (string) $cloudCover['ceiling-code'];

                            } 
                            
                            elseif ($index >= 0) {

                                $csvArray[$mcsZoneCode][$index+1][1] = 'N/A';

                            }

                            break;

                        }

                    }

                    //echo 'Start : (', $cloudCover['start'], '), End : (', $cloudCover['end'], ') -> ';#Data to append (date cloud)
                    //echo 'Value = ', $cloudCover, '<br>';#Data to append (cloud)
                    //echo 'Value = ', $cloudCover['ceiling-code'], var_dump($cloudCover['ceiling-code']), '<br>';
                }
                
                #Precipitation type data

                $precipitationList = $meteocodeForecast->parameters->{'precipitation-list'};

                foreach ($precipitationList->{'precipitation-event'} as $preEvent) {

                    $preStart = (string) $preEvent['start'];

                    $preEnd = (string) $preEvent['end'];

                    $intensity = (string) $preEvent['intensity'];

                    $occurrence = (string) $preEvent['occurrence'];

                    $frequency = (string) $preEvent['frequency'];
                    
                    $preType = (string) $preEvent['type'];

                    $dateTimeStart = DateTime::createFromFormat($dateFormat, $preStart);

                    $dateTimeEnd = DateTime::createFromFormat($dateFormat, $preEnd);

                    $timediff = $dateTimeStart->diff($dateTimeEnd); #Calcul of the difference between two dates

                    $diffhours = $timediff->h + 24*$timediff->d;

                    $i = (int) 0;

                    while ($i <= $diffhours) {

                    $newDate = clone $dateTimeStart;

                    $newDate->modify('+'. $i .'hour');

                    $currentDateStrg = $newDate->format($dateFormat);

                    foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                        if (in_array($currentDateStrg, $line)) {

                            $csvArray[$mcsZoneCode][$index][3] = $preType;

                            $csvArray[$mcsZoneCode][$index][4] = $frequency;

                            $csvArray[$mcsZoneCode][$index][5] = $intensity;

                            $csvArray[$mcsZoneCode][$index][6] = $occurrence;

                            break;

                        }
                        
                        elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                            array_push($csvArray[$mcsZoneCode], $emptyArray);

                            $csvArray[$mcsZoneCode][$index+1][0] = $currentDateStrg;

                            $newDate->modify('-4 hour');

                            $prepDateJourFormat = $newDate->format($Jourformat);
                
                            $prepDateHeureFormat = $newDate->format($DateHeureFormat);

                            $csvArray[$mcsZoneCode][$index+1][15] = $prepDateJourFormat;

                            $csvArray[$mcsZoneCode][$index+1][17] = $prepDateHeureFormat;

                            $csvArray[$mcsZoneCode][$index+1][3] = $preType;

                            $csvArray[$mcsZoneCode][$index+1][4] = $frequency;

                            $csvArray[$mcsZoneCode][$index+1][5] = $intensity;

                            $csvArray[$mcsZoneCode][$index+1][6] = $occurrence;

                            break;

                        }       

                    }

                    $i += 1;

                    }

                }

                
                #Probability of precipitation

                $precipitationProbabilityList = $meteocodeForecast->parameters->{'probability-of-precipitation-list'};

                foreach ($precipitationProbabilityList->{'probability-of-precipitation'} as $precipitationProbability) {

                    $POPStart = (string) $precipitationProbability['start'];

                    $POPEnd = (string) $precipitationProbability['end'];

                    $POP = (string) $precipitationProbability;

                    $dateTimeStart = DateTime::createFromFormat($dateFormat, $POPStart);

                    $dateTimeEnd = DateTime::createFromFormat($dateFormat, $POPEnd);

                    $timediff = $dateTimeStart->diff($dateTimeEnd);

                    $diffhours = $timediff->h + 24*$timediff->d;

                    $i = (int) 0;

                    while ($i <= $diffhours) {

                        $newDate = clone $dateTimeStart;

                        $newDate->modify('+'. $i .'hour');

                        $currentDateStrg = $newDate->format($dateFormat);

                        foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                            if (in_array($currentDateStrg, $line)) {

                                $csvArray[$mcsZoneCode][$index][7] = $POP;

                                break;

                            }
                            
                            elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                                array_push($csvArray[$mcsZoneCode], $emptyArray);

                                $csvArray[$mcsZoneCode][$index+1][0] = $currentDateStrg;

                                $newDate->modify('-4 hour');

                                $POPDateJourFormat = $newDate->format($Jourformat);
                
                                $POPDateHeureFormat = $newDate->format($DateHeureFormat);

                                $csvArray[$mcsZoneCode][$index+1][15] = $POPDateJourFormat;

                                $csvArray[$mcsZoneCode][$index+1][17] = $POPDateHeureFormat;

                                $csvArray[$mcsZoneCode][$index+1][7] = $POP;

                                break;

                            }       

                        }

                        $i += 1;

                    }

                }


                #Accumulation (time is one hour only when there is an accumulation, when N/A the time frame is multiple hours)

                $AccumList = $meteocodeForecast->parameters->{'accum-list'};

                foreach ($AccumList->{'accum-amount'} as $AccumAmount) {

                    $AccumStart = (string) $AccumAmount['start'];

                    $AccumEnd = (string) $AccumAmount['end'];

                    $AccumType = (string) $AccumAmount['type'];

                    $acuumMax = (string) $AccumAmount->{'upper-limit'};

                    if ($AccumType == 'N/A') {

                        #dateTime pour faire plusieurs heures avec la même donnée

                    } else {

                        foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                            if (in_array($AccumStart, $line)) {

                                $csvArray[$mcsZoneCode][$index][8] = $AccumType;

                                $csvArray[$mcsZoneCode][$index][9] = $acuumMax;

                                break;

                            }
                            
                            elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                                array_push($csvArray[$mcsZoneCode], $emptyArray);

                                $csvArray[$mcsZoneCode][$index+1][0] = $AccumStart;

                                $accumDateStart = DateTime::createFromFormat($dateFormat, $AccumStart);

                                $accumDateStart->modify('-4 hour');

                                $accumDateJourFormat = $accumDateStart->format($Jourformat);
                
                                $accumDateHeureFormat = $accumDateStart->format($DateHeureFormat);

                                $csvArray[$mcsZoneCode][$index+1][15] = $accumDateJourFormat;

                                $csvArray[$mcsZoneCode][$index+1][17] = $accumDateHeureFormat;

                                $csvArray[$mcsZoneCode][$index+1][8] = $AccumType;

                                $csvArray[$mcsZoneCode][$index+1][9] = $acuumMax;

                                break;

                            }       

                        }

                    }

                }

                #Temperature

                $tempList = $meteocodeForecast->parameters->{'temperature-list'};

                foreach ($tempList as $tempType) {

                    if ($tempType['type'] == 'air') {

                        foreach ($tempType->{'temperature-value'} as $temp) {

                            $tempStart = (string) $temp['start'];

                            $tempEnd = (string) $temp['end'];
            
                            $tempValue = (string) $temp->{'upper-limit'};

                            foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                                if (in_array($tempStart, $line)) {

                                    $csvArray[$mcsZoneCode][$index][10] = $tempValue;

                                    break;

                                }
                                
                                elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                                    array_push($csvArray[$mcsZoneCode], $emptyArray);

                                    $csvArray[$mcsZoneCode][$index+1][0] = $tempStart;

                                    $tempDateStart = DateTime::createFromFormat($dateFormat, $tempStart);

                                    $tempDateStart->modify('-4 hour');
            
                                    $tempDateJourFormat = $tempDateStart->format($Jourformat);
                        
                                    $tempDateHeureFormat = $tempDateStart->format($DateHeureFormat);
            
                                    $csvArray[$mcsZoneCode][$index+1][15] = $tempDateJourFormat;
            
                                    $csvArray[$mcsZoneCode][$index+1][17] = $tempDateHeureFormat;        

                                    $csvArray[$mcsZoneCode][$index+1][10] = $tempValue;

                                    break;
            
                                }

                            }

                        }

                    }

                    if ($tempType['type'] == 'dew-point') {

                        foreach ($tempType->{'temperature-value'} as $temp) {

                            $tempStart = (string) $temp['start'];

                            $tempEnd = (string) $temp['end'];
            
                            $tempValue = (string) $temp->{'upper-limit'};

                            foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                                if (in_array($tempStart, $line)) {

                                    $csvArray[$mcsZoneCode][$index][11] = $tempValue;

                                    break;

                                }
                                
                                elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                                    array_push($csvArray[$mcsZoneCode], $emptyArray);

                                    $csvArray[$mcsZoneCode][$index+1][0] = $tempStart;

                                    $tempDateStart = DateTime::createFromFormat($dateFormat, $tempStart);

                                    $tempDateStart->modify('-4 hour');
            
                                    $tempDateJourFormat = $tempDateStart->format($Jourformat);
                        
                                    $tempDateHeureFormat = $tempDateStart->format($DateHeureFormat);
            
                                    $csvArray[$mcsZoneCode][$index+1][15] = $tempDateJourFormat;
            
                                    $csvArray[$mcsZoneCode][$index+1][17] = $tempDateHeureFormat;

                                    $csvArray[$mcsZoneCode][$index+1][11] = $tempValue;

                                    break;
            
                                }

                            }

                        }

                    }

                }

                #Wind

                $windList = $meteocodeForecast->parameters->{'wind-list'};

                foreach ($windList->wind as $wind) {

                    $windStart = (string) $wind['start'];

                    $windEnd = (string) $wind['end'];

                    $windDir = (string) $wind['direction'];
                        
                    $k = array_search ($windDir, $dirEnglish); #translate from english to french

                    $windDirFrench = $dirFrench[$k];
                    
                    $windSpeed =  $wind->{'wind-speed'}->{'upper-limit'};

                    $gustSpeed =  $wind->{'gust-speed'}->{'upper-limit'};

                    $dateTimeStart = DateTime::createFromFormat($dateFormat, $windStart); # Add the same value to multiple hours

                    $dateTimeEnd = DateTime::createFromFormat($dateFormat, $windEnd);

                    $timediff = $dateTimeStart->diff($dateTimeEnd);

                    $diffhours = $timediff->h + 24*$timediff->d;

                    $i = (int) 0;

                    while ($i <= $diffhours) {

                        $newDate = clone $dateTimeStart;

                        $newDate->modify('+'. $i .'hour');

                        $currentDateStrg = $newDate->format($dateFormat);

                    
                        foreach ($csvArray[$mcsZoneCode] as $index => $line) {

                            if (in_array($currentDateStrg, $line)) {

                                $csvArray[$mcsZoneCode][$index][12] = $windDir;

                                $csvArray[$mcsZoneCode][$index][13] = $windSpeed;

                                $csvArray[$mcsZoneCode][$index][14] = $gustSpeed;

                                $csvArray[$mcsZoneCode][$index][16] = $windDirFrench;

                                break;

                            }
                            
                            elseif ($index == count($csvArray[$mcsZoneCode])-1) {

                                array_push($csvArray[$mcsZoneCode], $emptyArray);

                                $csvArray[$mcsZoneCode][$index+1][0] = $currentDateStrg;

                                $windDateStart = DateTime::createFromFormat($dateFormat, $currentDateStrg);

                                $windDateStart->modify('-4 hour');

                                $windDateJourFormat = $windDateStart->format($Jourformat);
                    
                                $windDateHeureFormat = $windDateStart->format($DateHeureFormat);

                                $csvArray[$mcsZoneCode][$index+1][15] = $windDateJourFormat;

                                $csvArray[$mcsZoneCode][$index+1][17] = $windDateHeureFormat;

                                $csvArray[$mcsZoneCode][$index+1][12] = $windDir;

                                $csvArray[$mcsZoneCode][$index+1][13] = $windSpeed;

                                $csvArray[$mcsZoneCode][$index][14] = $gustSpeed;
                                
                                $csvArray[$mcsZoneCode][$index+1][16] = $windDirFrench;

                                break;

                            }

                        }

                        $i += 1;

                    }

                }

            }

        }


        #Fill Null with N/A in array and rearange datetimes

        foreach ($stationList as $station) {

            foreach ($csvArray[$station] as $index => $line) {

                //usort($csvArray[$station], "cmp");

                $i = (int) 0;

                while ($i <= 18)  {

                    if (!array_key_exists($i, $line)) {

                        $csvArray[$station][$index][$i] = 'N/A';

                    } elseif (is_null($csvArray[$station][$index][$i])) {

                        $csvArray[$station][$index][$i] = 'N/A';

                    }

                    $i += 1;

                }

            }

        }

        #Array to csv

        foreach ($stationList as $station) {

            $fp = fopen($station . '.csv', 'w');

            foreach ($csvArray[$station] as $fields) {
                
                ksort($fields);

                fputcsv($fp, $fields);

            }

        }
        
        

        print_r($urlFileList);
        echo '<br>','<br>';
        //print_r($stationList);
        //echo '<br>','<br>';
        print_r($csvArray); 
        //echo '<br>', '<br>';
        //echo $csvArray['r71.1'][0][0];
        //echo '<br>', '<br>';
        //print_r($csvArray['r71.1'][0]);
        //echo '<br>', '<br>';
        //print_r($csvArray['r71.1'][1]);

        ?>

    </body>
</html>