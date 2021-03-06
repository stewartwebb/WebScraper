<?php

// Get the HSCIC pharmacy list.
$sDispensary = file_get_contents('edispensary.csv');
$aDispensary = explode("\r\n", $sDispensary);

$sUrl = 'http://www.nhs.uk/Services/pharmacies/Overview/DefaultView.aspx?id=';

// Loop through the first 100 pharmacies
for($i = 0; $i < 100; $i++)
{
  $sNHSCode = substr($aDispensary[$i], 1, 5); // ODS Code is always 5 characters long.
  $arrPharmacies[$sNHSCode] = getDataFromPage($sUrl . $sNHSCode);

  // Give some feedback to the user
  if(is_array($arrPharmacies[$sNHSCode]))
    echo $sNHSCode . ' Complete' . PHP_EOL;
  else
    echo $sNHSCode . ' ' . $arrPharmacies[$sNHSCode] . PHP_EOL;
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

  // Check when this information was last updated.
  $sLastVerifiedOn = 'Last verified on ';
  $arrTimes['last-verified'] = getEnclosedText($sPage, $sLastVerifiedOn, '</p>', 0);

  // If the text exists then get the review score
  $sRatingOpen = 'property ="v:rating">';
  if(strpos($sPage, $sRatingOpen))
    $arrTimes['reviews']['score'] = trim(getEnclosedText($sPage, $sRatingOpen, '</span>', 0));

  // If the text exists get the review 'count' (how many people reviewed the pharmacy)
  $sRatingCountOpen = "property='v:count'>";
  if(strpos($sPage, $sRatingCountOpen))
    $arrTimes['reviews']['count'] = trim(getEnclosedText($sPage, $sRatingCountOpen, '</strong>', 0));

  return $arrTimes;
}
?>
