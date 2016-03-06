<?php

$sDispensary = file_get_contents('edispensary.csv');
$aDispensary = explode("\r\n", $sDispensary);

$sUrl = 'http://www.nhs.uk/Services/pharmacies/Overview/DefaultView.aspx?id=';

for($i = 19; $i < 40; $i++)
{
  //echo $aDispensary[$i].PHP_EOL;
  $sNHSCode = substr($aDispensary[$i], 1, 5);
  $arrPharmacies[$sNHSCode] = getDataFromPage($sUrl . $sNHSCode);
}

echo print_r($arrPharmacies);


// A little function to extract text from betwen two strings in the document.
function getEnclosedText($sContent, $sOpen, $sClose, $lStart)
{
  if(!$lStart)
    $lStart = strpos($sContent, $sOpen, $lStart);
  $sOpenEnd = $lStart + strlen($sOpen);
  $lStartClose = strpos($sContent, $sClose, $sOpenEnd);
  $lContentLength = $lStartClose - $sOpenEnd;

  return substr($sContent, $sOpenEnd, $lContentLength);
}


// Main get data from page function.
function getDataFromPage($sUrl)
{
  $sPage = file_get_contents($sUrl);

  // If we hit the search page. This pharmacy does not exist.
  if(strpos($sPage, 'You can search all of our service directories from here. Try searching by service name, service type, condition or surgical procedure.'))
    return 'Unavailable';

  if(strpos($sPage, 'Profile Hidden'))
    return 'Hidden Profile';

  $sSearchString = 'itemprop="openingHours" content="';
  $lSearchStringLength = strlen($sSearchString);

  $arrTimes = array();
  $bExists = 0;

  do
  {
    $bExists = strpos($sPage, $sSearchString, $bExists);

    if($bExists)
    {
      $sString = getEnclosedText($sPage, $sSearchString, '"', $bExists); // Add the enclosed content to array
      $arrTimes['opening-times'][substr($sString, 0, 2)][] = substr($sString, 2);

      $bExists+= $lSearchStringLength;
    }
  } while($bExists);

  $sLastVerifiedOn = 'Last verified on ';
  $arrTimes['last-verified'] = getEnclosedText($sPage, $sLastVerifiedOn, '</p>', 0);

  $sRatingOpen = 'property ="v:rating">';
  if(strpos($sPage, $sRatingOpen))
    $arrTimes['reviews']['score'] = trim(getEnclosedText($sPage, $sRatingOpen, '</span>', 0));

  $sRatingCountOpen = "property='v:count'>";
  if(strpos($sPage, $sRatingCountOpen))
    $arrTimes['reviews']['count'] = trim(getEnclosedText($sPage, $sRatingCountOpen, '</strong>', 0));

  return $arrTimes;
}
?>
